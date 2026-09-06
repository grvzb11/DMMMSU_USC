<?php
/**
 * Developed by: George Rexy Vincent Z. Bacani
 * College: College of Information Technology
 * Role: System Developer / Front-End Developer
 * Development Year: 2026–2027
 * Institution: Don Mariano Marcos Memorial State University
 * Version: v1.0
 * Email: rexygeorge11@gmail.com
 * Copyright: © 2026–2027. All rights reserved.
 */
require_once '../config/database.php';
require_once '_layout.php';
if (!can_manage_media()) deny_access('You do not have permission to manage Media Library assets.');
$scope = admin_scope_code();
$portals = media_portal_codes();
$uploadLimit = 100;
$videoUploadLimit = 250;
// Media Library only. Other modules keep their own configured limits.
function media_php_ini_size_bytes(string $value): int {
    $value = trim($value);
    if ($value === '' || $value === '-1') return 0;
    $unit = strtolower(substr($value, -1));
    $number = (float)$value;
    return match($unit) {
        'g' => (int)round($number*1024*1024*1024),
        'm' => (int)round($number*1024*1024),
        'k' => (int)round($number*1024),
        default => (int)round($number),
    };
}
function media_php_effective_upload_limit_mb(): int {
    $limits = [];
    foreach (['upload_max_filesize', 'post_max_size'] as $key) {
        $bytes = media_php_ini_size_bytes((string)ini_get($key));
        if ($bytes>0) $limits[] = $bytes;
    }
    if (!$limits) return 0;
    return max(1, (int)floor(min($limits)/(1024*1024)));
}
$serverUploadLimit = media_php_effective_upload_limit_mb();
$diagnosticsReady = db_column_exists($pdo, 'media_library', 'sha256');
$error = '';

$requestedPortal = strtoupper(trim((string)($_GET['portal'] ?? $_POST['portal'] ?? '')));
$activePortal = $scope ?: (isset($portals[$requestedPortal])?$requestedPortal:'USC');

function media_physical_diagnostics(array $asset): array {
    $full = dirname(__DIR__).'/'.ltrim((string)($asset['file_path'] ?? ''), '/');
    $exists = is_file($full);
    $sha = null;
    $width = null;
    $height = null;
    if ($exists) {
        $sha = hash_file('sha256', $full)?:null;
        if (str_starts_with((string)($asset['mime_type'] ?? ''), 'image/')) {
            $info = @getimagesize($full);
            if ($info) {
                $width = (int)$info[0];
                $height = (int)$info[1];
            }
        }
    }
    return ['exists' => $exists, 'sha256' => $sha, 'width' => $width, 'height' => $height, 'full' => $full];
}
function media_update_diagnostics(PDO $pdo, int $id, array $diag): void {
    if (!db_column_exists($pdo, 'media_library', 'sha256')) return;
    $st = $pdo->prepare('UPDATE media_library SET sha256=?,width=?,height=?,last_verified_at=NOW() WHERE id=?');
    $st->execute([$diag['sha256'], $diag['width'], $diag['height'], $id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (request_exceeds_php_post_limit()) {
        $limit = trim((string)ini_get('post_max_size'));
        app_render_error_page(
            413,
            'Upload too large',
            'The selected Media Library upload is larger than the server can receive in one request'.($limit !== ''?' (current POST limit: '.$limit.')':'.').' Reduce the file size or restart the USC LAN server using scripts/windows/START_USC_LAN_SERVER.bat.'
        );
    }
    verify_csrf();
    $action = $_POST['action'] ?? 'upload';
    if ($action === 'delete') {
        if (!can_delete_admin_records()) deny_access('Your role cannot move Media Library assets to Trash.');
        $id = (int)($_POST['id'] ?? 0);
        $m = media_asset($pdo, $id);
        if ($m && admin_can_access_code($m['campus']?:'USC')) {
            $pdo->prepare('UPDATE media_library SET deleted_at=NOW(),deleted_by=? WHERE id=?')->execute([$_SESSION['admin_id'] ?? null, $id]);
            admin_log($pdo, 'media', 'Moved media to trash', $m['original_name']?:$m['file_path'], 'media', $id);
        }
        header('Location: media.php?portal='.urlencode($activePortal).'&deleted=1');
        exit;
    }
    if ($action === 'verify') {
        $id = (int)($_POST['id'] ?? 0);
        $m = media_asset($pdo, $id);
        if (!$m || !admin_can_access_code($m['campus']?:'USC')) deny_access('This asset is outside your portal scope.');
        $diag = media_physical_diagnostics($m);
        media_update_diagnostics($pdo, $id, $diag);
        admin_log($pdo, 'media', 'Verified media asset', ($m['original_name']?:$m['file_path']).($diag['exists']?' · file present':' · file missing'), 'media', $id);
        header('Location: media.php?portal='.urlencode($activePortal).'&verified='.($diag['exists']?'1':'missing'));
        exit;
    }

    if (!empty($_FILES['media']['name'])) {
        try {
            $rateLimit = max(5, (int)(system_setting($pdo, 'security_rate_limit_uploads_per_hour', '40') ?? 40));
            platform_rate_limit_or_429($pdo, 'admin-upload', ((string)($_SESSION['admin_id'] ?? 0)).'|'.admin_current_ip(), $rateLimit, 3600, 900);
            $file = $_FILES['media'];
            $safeOriginal = basename((string)$file['name']);
            $ext = strtolower((string)pathinfo($safeOriginal, PATHINFO_EXTENSION));
            $videoExtensions = ['mp4', 'webm', 'mov'];
            $isVideoByName = in_array($ext, $videoExtensions, true);
            $appLimit = $isVideoByName?$videoUploadLimit:$uploadLimit;
            $size = (int)$file['size'];
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $uploadError = (int)($file['error'] ?? UPLOAD_ERR_OK);
                if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $serverNote = $serverUploadLimit>0?' Current PHP limit: '.$serverUploadLimit.' MB.':'';
                    throw new RuntimeException('This file may be up to '.$appLimit.' MB, but PHP blocked the request before the application could process it.'.$serverNote.' Increase PHP upload_max_filesize and post_max_size if needed.');
                }
                throw new RuntimeException('The upload did not complete successfully.');
            }
            if ($size <= 0 || $size>$appLimit*1024*1024) throw new RuntimeException(($isVideoByName?'Video':'Media Library file').' must be '.$appLimit.' MB or smaller.');
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt'];
            if (!in_array($ext, $allowedExt, true)) throw new RuntimeException('Supported formats: JPG, PNG, WEBP, MP4, WEBM, MOV, PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, CSV, and TXT.');
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$finfo->file($file['tmp_name']);
            $strict = [
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
            'mp4' => ['video/mp4', 'application/mp4'], 'webm' => ['video/webm'], 'mov' => ['video/quicktime'], 'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/octet-stream'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'xls' => ['application/vnd.ms-excel', 'application/octet-stream'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
            'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'], 'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
            'csv' => ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/octet-stream'], 'txt' => ['text/plain', 'application/octet-stream'],
            ];
            if (isset($strict[$ext]) && !in_array($mime, $strict[$ext], true)) throw new RuntimeException('The selected file does not match its file type.');
            if ($ext === 'mp4' && $mime === 'application/mp4') $mime = 'video/mp4';
            $incomingHash = hash_file('sha256', $file['tmp_name'])?:null;
            $portal = $scope?:strtoupper(trim((string)($_POST['portal'] ?? 'USC')));
            if (!isset($portals[$portal])) $portal = 'USC';
            $storeExt = $ext === 'jpeg'?'jpg':$ext;
            $dir = dirname(__DIR__).'/uploads/library';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Unable to create the media folder.');
            $name = 'media_'.bin2hex(random_bytes(8)).'.'.$storeExt;
            if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) throw new RuntimeException('Unable to save file.');
            $path = 'uploads/library/'.$name;
            $width = $height = null;
            $thumbnailPath = null;
            $optimizedAt = null;
            if (str_starts_with($mime, 'image/')) {
                if (system_setting($pdo, 'media_auto_optimize', '1') === '1') {
                    $opt = media_optimize_file($path);
                    $width = $opt['width'];
                    $height = $opt['height'];
                    $size = (int)$opt['bytes'];
                    $incomingHash = hash_file('sha256', $dir.'/'.$name)?:$incomingHash;
                    $thumbnailPath = system_setting($pdo, 'media_thumbnail_enabled', '1') === '1'?($opt['thumbnail'] ?? null):null;
                    $optimizedAt = !empty($opt['optimized'])?date('Y-m-d H:i:s'):null;
                } else {
                    $info = @getimagesize($dir.'/'.$name);
                    if ($info) {
                        $width = (int)$info[0];
                        $height = (int)$info[1];
                    }
                    if (system_setting($pdo, 'media_thumbnail_enabled', '1') === '1') $thumbnailPath = media_create_thumbnail($path);
                }
            }
            if ($diagnosticsReady) {
                $st = $pdo->prepare('INSERT INTO media_library(file_path,original_name,mime_type,file_size,width,height,sha256,last_verified_at,alt_text,uploaded_by,campus) VALUES(?,?,?,?,?,?,?,NOW(),?,?,?)');
                $st->execute([$path, $safeOriginal, $mime, $size, $width, $height, $incomingHash, trim($_POST['alt_text'] ?? ''), $_SESSION['admin_id'] ?? null, $portal]);
            } else {
                $st = $pdo->prepare('INSERT INTO media_library(file_path,original_name,mime_type,file_size,alt_text,uploaded_by,campus) VALUES(?,?,?,?,?,?,?)');
                $st->execute([$path, $safeOriginal, $mime, $size, trim($_POST['alt_text'] ?? ''), $_SESSION['admin_id'] ?? null, $portal]);
            }
            $id = (int)$pdo->lastInsertId();
            if (db_column_exists($pdo, 'media_library', 'thumbnail_path')) {
                $pdo->prepare('UPDATE media_library SET thumbnail_path=?,optimized_at=? WHERE id=?')->execute([$thumbnailPath, $optimizedAt, $id]);
            }
            admin_log($pdo, 'media', 'Uploaded media', $safeOriginal.' · SHA-256 '.substr((string)$incomingHash, 0, 12), 'media', $id);
            $qs = 'portal='.urlencode($portal).'&uploaded=1';
            header('Location: media.php?'.$qs);
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$type = (string)($_GET['type'] ?? 'all');
$usageFilter = (string)($_GET['usage'] ?? 'all');
$where = ['m.deleted_at IS NULL'];
$params = [];
if ($scope) {
    if ($scope === 'USC') $where[] = "(UPPER(m.campus)=? OR m.campus IS NULL OR m.campus='')";
    else $where[] = 'UPPER(m.campus)=?';
    $params[] = $scope;
}
else {
    if ($activePortal === 'USC') $where[] = "(UPPER(m.campus)=? OR m.campus IS NULL OR m.campus='')";
    else $where[] = 'UPPER(m.campus)=?';
    $params[] = $activePortal;
}
if ($q !== '') {
    $where[] = '(m.original_name LIKE ? OR m.alt_text LIKE ?)';
    $like = '%'.$q.'%';
    array_push($params, $like, $like);
}
if ($type === 'images') {
    $where[] = "m.mime_type LIKE 'image/%'";
} elseif ($type === 'videos') {
    $where[] = "m.mime_type LIKE 'video/%'";
} elseif ($type === 'pdf') {
    $where[] = "(m.mime_type='application/pdf' OR LOWER(m.original_name) LIKE '%.pdf')";
} elseif ($type === 'pptx') {
    $where[] = "(m.mime_type='application/vnd.openxmlformats-officedocument.presentationml.presentation' OR LOWER(m.original_name) LIKE '%.pptx')";
} elseif ($type === 'csv') {
    $where[] = "(m.mime_type IN ('text/csv','application/vnd.ms-excel') OR LOWER(m.original_name) LIKE '%.csv')";
} elseif ($type === 'documents') {
    $where[] = "m.mime_type NOT LIKE 'image/%' AND m.mime_type NOT LIKE 'video/%'";
}
$postVideoUsageExpr = db_table_exists($pdo, 'post_videos')?"+(SELECT COUNT(*) FROM post_videos pv WHERE pv.media_id=m.id)":'';
$usageExpr = "((SELECT COUNT(*) FROM post_images pi WHERE pi.media_id=m.id)".$postVideoUsageExpr."+(SELECT COUNT(*) FROM hero_slides hs WHERE hs.panel_media_id=m.id OR hs.background_media_id=m.id))";
if ($usageFilter === 'used') $where[] = "$usageExpr>0";
elseif ($usageFilter === 'unused') $where[] = "$usageExpr=0";
$baseSql = ' FROM media_library m WHERE '.implode(' AND ', $where);
$page = 1;
$perPage = 24;
$total = 0;
$media = [];
if ($usageFilter === 'missing') {
    $st = $pdo->prepare('SELECT m.*'.$baseSql.' ORDER BY m.created_at DESC,m.id DESC');
    $st->execute($params);
    $all = $st->fetchAll();
    $all = array_values(array_filter($all, fn($m) => !media_physical_diagnostics($m)['exists']));
    $total = count($all);
    $pg = pagination_state($total, $perPage);
    $page = $pg['page'];
    $media = array_slice($all, $pg['offset'], $perPage);
} else {
    $st = $pdo->prepare('SELECT COUNT(*)'.$baseSql);
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $pg = pagination_state($total, $perPage);
    $page = $pg['page'];
    $st = $pdo->prepare('SELECT m.*'.$baseSql.' ORDER BY m.created_at DESC,m.id DESC LIMIT '.$perPage.' OFFSET '.$pg['offset']);
    $st->execute($params);
    $media = $st->fetchAll();
}
foreach ($media as &$m) {
    $m['usage'] = media_usage_counts($pdo, (int)$m['id']);
    $m['diag'] = media_physical_diagnostics($m);
}
unset($m);
$hasFilters = $q !== '' || $type !== 'all' || $usageFilter !== 'all';

admin_header('Media Library', 'Content assets');
?>
<?php if ($error) : ?>
    <div class="notice notice--error">
        <?=e($error)?>
    </div>
<?php endif; ?>
<?php if (!$error && isset($_GET['uploaded'])) : ?>
    <div class="notice media-upload-success" id="mediaUploadSuccess" role="status">
        Asset uploaded and is ready to reuse.
    </div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])) : ?>
    <div class="notice">
        Asset moved to Trash. Existing content and the physical file remain recoverable until an authorized Trash action is taken.
    </div>
<?php endif; ?>
<?php if (($_GET['verified'] ?? '') === '1') : ?>
    <div class="notice">
        Asset verified. File fingerprint and image dimensions were refreshed.
    </div>
<?php elseif (($_GET['verified'] ?? '') === 'missing') : ?>
    <div class="notice notice--error">
        The library record exists, but its physical file is missing. No record was deleted.
    </div>
<?php endif; ?>
<?php if (!$diagnosticsReady) : ?>
    <div class="notice notice--error">
        Media diagnostics need the latest database migration. Run it from <a href="maintenance.php">Maintenance</a>.
    </div>
<?php endif; ?>
<div class="media-workspace">
    <section class="panel media-upload-card">
        <div class="media-upload-head">
            <div>
                <span class="media-upload-kicker">UPLOAD ASSET</span>
                <h2>Add to library</h2>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="media-upload-form">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="portal" value="<?=e($activePortal)?>">
            <div class="media-upload-field">
                <div class="media-upload-field__label">
                    <strong>File</strong><span>Required</span>
                </div>
                <label class="media-library-drop" for="libraryFile">
                    <span class="media-library-drop__copy"><strong>Select a reusable file</strong><small>Images/docs · <?=$uploadLimit?> MB max · Video · <?=$videoUploadLimit?> MB max</small></span><span class="media-library-drop__action">Choose file</span>
                    <input id="libraryFile" type="file" name="media" required accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt" data-app-limit-mb="<?=$uploadLimit?>" data-video-limit-mb="<?=$videoUploadLimit?>" data-server-limit-mb="<?=e($serverUploadLimit)?>">
                </label>
                <div class="media-selected-file" id="libraryFileName" aria-live="polite">
                    No file selected
                </div>
                <div class="media-file-limit-note">
                    Images/documents: <strong><?=$uploadLimit?> MB</strong> · Videos: <strong><?=$videoUploadLimit?> MB</strong>
                </div>
            </div>
            <label class="media-upload-field media-upload-field--text">
                <span class="media-upload-field__label"><strong>Alt text / description</strong><span>Recommended for images</span></span>
                <input name="alt_text" maxlength="255" placeholder="Describe this asset for accessibility and search">
            </label>
            <div class="media-upload-actions media-upload-actions--solo">
                <button class="btn">Upload</button>
            </div>
        </form>
    </section>
    <section class="panel media-library-panel">
        <div class="media-library-toolbar">
            <div>
                <strong><?=$total?> asset<?=$total===1?'':'s'?></strong><span>Library items matching the current view</span>
            </div>
            <form method="get" class="media-library-filters">
                <?php if (!$scope) : ?>
                    <select name="portal" class="media-library-portal" onchange="this.form.submit()" aria-label="Portal">
                        <?php foreach ($portals as $code => $name) : ?>
                            <option value="<?=e($code)?>" <?=$activePortal===$code?'selected':''?>><?=e($name)?></option>
                        <?php endforeach ?>
                    </select>
                <?php endif ?>
                <input type="search" name="q" value="<?=e($q)?>" placeholder="Search assets">
                <select name="type" onchange="this.form.submit()">
                    <option value="all" <?=$type==='all'?'selected':''?>>All files</option>
                    <option value="images" <?=$type==='images'?'selected':''?>>Images</option>
                    <option value="videos" <?=$type==='videos'?'selected':''?>>Videos</option>
                    <option value="documents" <?=$type==='documents'?'selected':''?>>Documents</option>
                    <option value="pdf" <?=$type==='pdf'?'selected':''?>>PDF</option>
                    <option value="pptx" <?=$type==='pptx'?'selected':''?>>PPTX</option>
                    <option value="csv" <?=$type==='csv'?'selected':''?>>CSV</option>
                </select>
                <select name="usage" onchange="this.form.submit()">
                    <option value="all" <?=$usageFilter==='all'?'selected':''?>>All health states</option>
                    <option value="used" <?=$usageFilter==='used'?'selected':''?>>Used</option>
                    <option value="unused" <?=$usageFilter==='unused'?'selected':''?>>Unused</option>
                    <option value="missing" <?=$usageFilter==='missing'?'selected':''?>>Missing files</option>
                </select>
                <?php if ($hasFilters) : ?>
                    <a class="media-reset" href="media.php<?=!$scope?'?portal='.urlencode($activePortal):''?>">Reset</a>
                <?php endif ?>
            </form>
        </div>
        <div class="panel__body">
            <?php if ($media) : ?>
                <div class="media-grid media-grid--integrated">
                    <?php foreach($media as $m):$isImage=media_asset_is_image($m);$isVideo=media_asset_is_video($m);$usage=$m['usage'];$diag=$m['diag'];?>
                        <article class="media-card media-card--integrated <?=$diag['exists']?'':'media-card--missing'?>">
                            <?php if ($diag['exists']) : ?>
                                <a class="media-card__preview" href="../<?=e($m['file_path'])?>" target="_blank" rel="noopener">
                                <?php if ($isImage) : ?>
                                    <img loading="lazy" src="../<?=e($m['file_path'])?>" alt="<?=e($m['alt_text']?:$m['original_name'])?>">
                                <?php elseif ($isVideo) : ?>
                                    <video preload="metadata" muted src="../<?=e($m['file_path'])?>"></video>
                                <?php else: ?>
                                    <span class="media-file-icon"><?=e(file_type_label($m['mime_type'],$m['original_name']))?></span>
                                <?php endif ?>
                                </a>
                            <?php else: ?>
                                <div class="media-card__preview media-card__preview--missing">
                                    <span class="media-file-icon">!</span><small>Physical file missing</small>
                                </div>
                            <?php endif; ?>
                            <div class="media-card__body">
                                <strong title="<?=e($m['original_name'])?>"><?=e($m['original_name']?:'Media file')?></strong>
                                <small><?=e(format_file_size((int)$m['file_size']))?>
                                <?php if (!empty($m['width']) && !empty($m['height'])) : ?>
                                    · <?=e($m['width'].'×'.$m['height'])?>
                                <?php endif; ?>
                                · <?=e(date('M j, Y',strtotime($m['created_at'])))?></small>
                                <div class="media-health-badges">
                                    <span class="media-usage-badge"><?=$usage['total']?> use<?=$usage['total']===1?'':'s'?></span>
                                    <?php if (!$diag['exists']) : ?>
                                        <span class="media-usage-badge media-usage-badge--danger">Missing</span>
                                    <?php endif; ?>
                                </div>
                                <div class="media-card__actions">
                                    <?php if ($diag['exists']) : ?>
                                        <a class="media-action media-action--view" href="../<?=e($m['file_path'])?>" target="_blank" rel="noopener">View</a>
                                    <?php endif; ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="id" value="<?=$m['id']?>">
                                        <input type="hidden" name="portal" value="<?=e($activePortal)?>">
                                        <button class="media-action media-action--verify" type="submit">Verify</button>
                                    </form>
                                    <?php if (can_delete_admin_records()) : ?>
                                        <form method="post" data-confirm="Move this asset to Trash? No physical file or existing content data will be removed by this action.">
                                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?=$m['id']?>">
                                            <input type="hidden" name="portal" value="<?=e($activePortal)?>">
                                            <button class="media-action media-action--danger" type="submit">Trash</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state media-empty-state">
                    <strong>No assets found</strong><span><?= $hasFilters ? 'No assets match the selected filters. Try changing or clearing the current filters.' : 'Upload your first reusable file using the library form on the left.' ?></span>
                </div>
            <?php endif; ?>
            <?=render_pagination($pg)?>
        </div>
    </section>
</div>
<script>
(() => {
    const input = document.getElementById('libraryFile'), out = document.getElementById('libraryFileName');
    if (input && out) {
        const standardLimit = Number(input.dataset.appLimitMb || 100), videoLimit = Number(input.dataset.videoLimitMb || 250), serverLimit = Number(input.dataset.serverLimitMb || 0);
        const reset = () => { out.classList.remove('has-file', 'is-warning', 'is-error'); out.textContent = 'No file selected'; };
        input.addEventListener('change', () => { const file = input.files && input.files[0]; if (!file) { reset(); return; } const sizeMb = file.size / 1024 / 1024; const appLimit = file.type.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(file.name) ? videoLimit : standardLimit; out.classList.remove('is-warning', 'is-error'); out.classList.add('has-file'); if (sizeMb > appLimit) { out.classList.add('is-error'); out.textContent = file.name + ' · ' + sizeMb.toFixed(1) + ' MB · exceeds the ' + appLimit + ' MB limit'; return; } if (serverLimit > 0 && serverLimit < appLimit && sizeMb > serverLimit) { out.classList.add('is-warning'); out.textContent = file.name + ' · ' + sizeMb.toFixed(1) + ' MB · server currently accepts up to ' + serverLimit + ' MB per request'; return; } out.textContent = file.name + ' · ' + sizeMb.toFixed(1) + ' MB'; });
    }
})();
</script>
<?php
admin_footer();
?>
