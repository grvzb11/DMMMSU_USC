<?php
require_once '../config/database.php';
require_once '_layout.php';
if (!admin_can_permission('storage.view') && !admin_can_permission('system.health')) deny_access('You do not have permission to view storage health.');

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!can_manage_media()) deny_access('Media Library permission is required to optimize images.');
    try {
        platform_rate_limit_or_429($pdo, 'admin-media-optimize', (string)($_SESSION['admin_id'] ?? 0), 20, 3600, 900);
        $action = (string)($_POST['action'] ?? '');
        $ids = [];
        if ($action === 'optimize_asset') $ids = [(int)($_POST['id'] ?? 0)];
        elseif ($action === 'optimize_batch') {
            $st = $pdo->query("SELECT id FROM media_library WHERE deleted_at IS NULL AND mime_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 50");
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        }
        $changed = 0;
        $thumbs = 0;
        foreach ($ids as $id) {
            $asset = media_asset($pdo, $id);
            if (!$asset || !media_asset_allowed_for_admin($asset)) continue;
            $result = media_optimize_file((string)$asset['file_path']);
            if ($result['optimized']) $changed++;
            if ($result['thumbnail']) $thumbs++;
            if (db_column_exists($pdo, 'media_library', 'thumbnail_path')) {
                $full = app_root((string)$asset['file_path']);
                $sha = is_file($full)?(hash_file('sha256', $full)?:null):null;
                $pdo->prepare('UPDATE media_library SET file_size=?,width=?,height=?,sha256=?,last_verified_at=NOW(),optimized_at=NOW(),thumbnail_path=COALESCE(?,thumbnail_path) WHERE id=?')->execute([$result['bytes'], $result['width'], $result['height'], $sha, $result['thumbnail'], $id]);
            }
        }
        admin_log($pdo, 'media', 'Optimized media assets', $changed.' image(s) reduced; '.$thumbs.' thumbnail(s) generated');
        $notice = 'Optimization pass completed. Existing files were replaced only when the optimized version was smaller.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
        app_log_error('Storage optimization failed', ['error' => $e->getMessage()]);
    }
}

function storage_page_dir_size(string $path): int {
    if (!is_dir($path)) return is_file($path)?(int)@filesize($path):0;
    $bytes = 0;
    try {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            try {
                if ($f->isFile()) $bytes+=(int)$f->getSize();
            } catch (Throwable $ignored) {
            }
        }
    } catch (Throwable $ignored) {
    }
    return $bytes;
}
function storage_page_setting(PDO $pdo, string $key, string $default): string {
    try {
        return (string)(system_setting($pdo, $key, $default) ?? $default);
    } catch (Throwable $ignored) {
        return $default;
    }
}

$usage = [];
$categoryPaths = [
'News images' => 'uploads/posts',
'Media Library' => 'uploads/library',
'Hero banners' => 'uploads/hero',
'Profiles' => 'uploads/admin-profiles',
'E-Sumbong evidence' => 'uploads/concerns',
'Backups' => 'backups',
'Logs & cache' => 'storage',
];
foreach ($categoryPaths as $label => $relative) {
    try {
        $usage[$label] = ['path' => $relative, 'bytes' => storage_page_dir_size(app_root($relative))];
    }
    catch (Throwable $ignored) {
        $usage[$label] = ['path' => $relative, 'bytes' => 0];
    }
}

$diskFree = @disk_free_space(app_root());
$diskTotal = @disk_total_space(app_root());
$diskUsedPct = ($diskFree !== false && $diskTotal !== false && (float)$diskTotal>0)?(int)round((1-((float)$diskFree/(float)$diskTotal))*100):null;
$warning = max(50, (int)storage_page_setting($pdo, 'storage_warning_percent', '85'));
$critical = max($warning+1, (int)storage_page_setting($pdo, 'storage_critical_percent', '95'));
$mediaTotal = $mediaImages = $missing = $unoptimized = 0;
if (db_table_exists($pdo, 'media_library')) {
    try {
        $hasDeleted = db_column_exists($pdo, 'media_library', 'deleted_at');
        $where = $hasDeleted?' WHERE deleted_at IS NULL':'';
        $mediaTotal = (int)$pdo->query('SELECT COUNT(*) FROM media_library'.$where)->fetchColumn();
        $mimeFilter = db_column_exists($pdo, 'media_library', 'mime_type')?($hasDeleted?" WHERE deleted_at IS NULL AND mime_type LIKE 'image/%'":" WHERE mime_type LIKE 'image/%'"):$where;
        $mediaImages = db_column_exists($pdo, 'media_library', 'mime_type')?(int)$pdo->query("SELECT COUNT(*) FROM media_library".$mimeFilter)->fetchColumn():0;
        if (db_column_exists($pdo, 'media_library', 'optimized_at') && db_column_exists($pdo, 'media_library', 'mime_type')) {
            $optWhere = $hasDeleted?" WHERE deleted_at IS NULL AND mime_type LIKE 'image/%' AND optimized_at IS NULL":" WHERE mime_type LIKE 'image/%' AND optimized_at IS NULL";
            $unoptimized = (int)$pdo->query("SELECT COUNT(*) FROM media_library".$optWhere)->fetchColumn();
        } else {
            $unoptimized = $mediaImages;
        }
        if (db_column_exists($pdo, 'media_library', 'file_path')) {
            $st = $pdo->query('SELECT file_path FROM media_library'.$where);
            foreach ($st->fetchAll() as $r) {
                $rel = (string)($r['file_path'] ?? '');
                if ($rel !== '' && !is_file(app_root($rel))) $missing++;
            }
        }
    } catch (Throwable $e) {
        $error = $error?:'Media integrity statistics could not be loaded.';
        app_log_error('Media integrity scan failed', ['error' => $e->getMessage()]);
    }
}

$large = [];
$root = app_root('uploads');
try {
    if (is_dir($root)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            try {
                if ($f->isFile() && (int)$f->getSize()>5*1024*1024) $large[] = ['path' => str_replace('\\', '/', substr($f->getPathname(), strlen(app_root())+1)), 'size' => (int)$f->getSize()];
            } catch (Throwable $ignored) {
            }
        }
        usort($large, static fn($a, $b) => (int)$b['size'] <=> (int)$a['size']);
        $large = array_slice($large, 0, 20);
    }
} catch (Throwable $e) {
    app_log_error('Large upload scan failed', ['error' => $e->getMessage()]);
}

$diskState = $diskUsedPct === null?'neutral':($diskUsedPct >= $critical?'bad':($diskUsedPct >= $warning?'warn':'ok'));
$mediaState = $missing>0?'warn':'ok';
$optimizationState = $unoptimized>0?'neutral':'ok';
$maxUsage = 1;
foreach ($usage as $row) {
    $maxUsage = max($maxUsage, (int)($row['bytes'] ?? 0));
}
admin_header('Storage & Media Health', 'System administration');
admin_system_management_tabs('storage.php');
?>
<div class="sysadmin-page sysadmin-storage-page">
    <section class="sysadmin-intro">
        <div>
            <span class="page-kicker">CAPACITY &amp; MEDIA</span>
            <h2>Storage overview</h2>
            <p>Monitor disk capacity and media integrity without mixing storage checks with backup or recovery controls.</p>
        </div>
        <a class="btn btn--soft" href="media.php">Media Library</a>
    </section>
    <?php if ($notice) : ?>
        <div class="notice">
            <?=e($notice)?>
        </div>
    <?php endif; ?>
    <?php if ($error) : ?>
        <div class="notice notice--error">
            <?=e($error)?>
        </div>
    <?php endif; ?>
    <section class="sysadmin-stat-grid sysadmin-stat-grid--3" aria-label="Storage summary">
        <article class="sysadmin-stat-card is-<?=e($diskState)?>">
            <div class="sysadmin-stat-card__top">
                <span>Disk usage</span><i></i>
            </div>
            <strong><?=$diskUsedPct===null?'Unavailable':$diskUsedPct.'% used'?></strong>
            <p><?=$diskFree===false?'Free space could not be determined.':e(format_file_size((int)$diskFree).' free')?></p>
            <?php if ($diskUsedPct !== null) : ?>
                <div class="sysadmin-meter">
                    <span style="width:<?=max(0,min(100,$diskUsedPct))?>%"></span>
                </div>
            <?php endif; ?>
        </article>
        <article class="sysadmin-stat-card is-<?=e($mediaState)?>">
            <div class="sysadmin-stat-card__top">
                <span>Media integrity</span><i></i>
            </div>
            <strong><?=$mediaTotal?> assets</strong>
            <p><?=$mediaImages?> images · <?=$missing?> missing file<?= $missing===1?'':'s' ?></p>
            <small><?=$missing?'Review missing paths in Media Library':'No missing media detected'?></small>
        </article>
        <article class="sysadmin-stat-card is-<?=e($optimizationState)?>">
            <div class="sysadmin-stat-card__top">
                <span>Optimization queue</span><i></i>
            </div>
            <strong><?=$unoptimized?></strong>
            <p>Image record<?=$unoptimized===1?'':'s'?> awaiting an optimization pass</p>
            <small>Optimization is non-destructive to database records</small>
        </article>
    </section>
    <div class="sysadmin-two-column sysadmin-storage-main">
        <section class="panel sysadmin-panel">
            <div class="panel__head sysadmin-panel__head">
                <div>
                    <span class="page-kicker">CAPACITY</span>
                    <h2>Storage breakdown</h2>
                    <p>Relative usage by protected and public storage areas.</p>
                </div>
            </div>
            <div class="sysadmin-breakdown">
                <?php if ($usage) :foreach ($usage as $label => $row) :$pct = (int)round(((int)($row['bytes'] ?? 0)/$maxUsage)*100); ?>
                    <article>
                        <div class="sysadmin-breakdown__row">
                            <div>
                                <strong><?=e($label)?></strong><small><?=e((string)($row['path']??''))?></small>
                            </div>
                            <b><?=e(format_file_size((int)($row['bytes']??0)))?></b>
                        </div>
                        <div class="sysadmin-meter">
                            <span style="width:<?=$pct?>%"></span>
                        </div>
                    </article>
                <?php endforeach;else:?>
                <div class="empty-state">
                    Storage categories could not be scanned. Core disk statistics above are still available.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="panel sysadmin-panel">
        <div class="panel__head sysadmin-panel__head">
            <div>
                <span class="page-kicker">MEDIA MAINTENANCE</span>
                <h2>Media integrity</h2>
                <p>Review only the items that need attention.</p>
            </div>
        </div>
        <div class="sysadmin-checklist sysadmin-checklist--compact">
            <div class="<?=$missing?'is-warn':'is-ok'?>">
                <i><?=$missing?'!':'✓'?></i>
                <div>
                    <strong>Physical files</strong><small><?=$missing?$missing.' missing file'.($missing===1?'':'s').' need review':'All Media Library paths resolve to a file'?></small>
                </div>
            </div>
            <div class="<?=$unoptimized?'is-neutral':'is-ok'?>">
                <i><?=$unoptimized?'•':'✓'?></i>
                <div>
                    <strong>Optimization queue</strong><small><?=$unoptimized?$unoptimized.' image'.($unoptimized===1?'':'s').' have not been optimized':'All image records have an optimization timestamp'?></small>
                </div>
            </div>
            <div class="is-ok">
                <i>✓</i>
                <div>
                    <strong>Original records</strong><small>Optimization never deletes Media Library records automatically.</small>
                </div>
            </div>
        </div>
        <?php if (can_manage_media()) : ?>
            <form method="post" class="sysadmin-panel-action" data-confirm="Run a non-destructive optimization pass on up to 50 recent Media Library images?">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="optimize_batch">
                <button class="btn" <?=$unoptimized?'':'disabled'?>><?=$unoptimized?'Optimize recent images':'Nothing to optimize'?></button>
            </form>
        <?php endif; ?>
    </section>
</div>
<?php if ($large) : ?>
    <details class="panel sysadmin-details">
        <summary>
            <div>
                <span class="page-kicker">LARGE UPLOADS</span><strong>Files over 5 MB</strong><small><?=count($large)?> large upload<?=count($large)===1?'':'s'?> found. Review only; nothing is deleted automatically.</small>
            </div>
            <span class="sysadmin-muted-pill"><?=count($large)?> files</span></summary>
        <div class="table-wrap sysadmin-simple-table">
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($large as $r) : ?>
                        <tr>
                            <td><code><?=e($r['path'])?></code></td>
                            <td><?=e(format_file_size((int)$r['size']))?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
<?php endif; ?>
</div>
<?php
admin_footer();
?>
