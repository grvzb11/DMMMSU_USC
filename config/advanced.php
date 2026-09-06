<?php
declare(strict_types=1);

/* Advanced governance, resilience, security, storage and accessibility helpers. */

function governance_portals(): array {
    return ['USC' => 'University Student Council'] + admin_campuses();
}

function academic_year_rows(PDO $pdo, bool $includeArchived = true): array {
    if (!db_table_exists($pdo, 'academic_years')) return [];
    $sql = 'SELECT * FROM academic_years'.($includeArchived?'':' WHERE is_archived=0').' ORDER BY start_date DESC,id DESC';
    return $pdo->query($sql)->fetchAll();
}
function active_academic_year(PDO $pdo): ?array {
    if (!db_table_exists($pdo, 'academic_years')) return null;
    $id = (int)(system_setting($pdo, 'active_academic_year_id', '0') ?? 0);
    if ($id>0) {
        $st = $pdo->prepare('SELECT * FROM academic_years WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        if ($row) return $row;
    }
    $row = $pdo->query('SELECT * FROM academic_years WHERE is_active=1 AND is_archived=0 ORDER BY start_date DESC,id DESC LIMIT 1')->fetch();
    return $row?:null;
}
function active_academic_year_id(PDO $pdo): ?int {
    $row = active_academic_year($pdo);
    return $row?(int)$row['id']:null;
}
function academic_year_label(?array $row): string {
    return $row?trim((string)$row['label']):'Unassigned';
}

function permission_override_lookup(PDO $pdo, string $role, string $permission, ?int $adminId = null): ?bool {
    try {
        if ($adminId && db_table_exists($pdo, 'admin_permission_overrides')) {
            $st = $pdo->prepare('SELECT allowed FROM admin_permission_overrides WHERE admin_id=? AND permission=? LIMIT 1');
            $st->execute([$adminId, $permission]);
            $v = $st->fetchColumn();
            if ($v !== false) return (bool)$v;
        }
        if (db_table_exists($pdo, 'role_permission_overrides')) {
            $st = $pdo->prepare('SELECT allowed FROM role_permission_overrides WHERE role=? AND permission=? LIMIT 1');
            $st->execute([$role, $permission]);
            $v = $st->fetchColumn();
            if ($v !== false) return (bool)$v;
        }
    } catch (Throwable $ignored) {
    }
    return null;
}
function permission_save_role_override(PDO $pdo, string $role, string $permission, ?bool $allowed): void {
    if (!db_table_exists($pdo, 'role_permission_overrides')) throw new RuntimeException('Run pending migrations before editing permissions.');
    if ($allowed === null) {
        $pdo->prepare('DELETE FROM role_permission_overrides WHERE role=? AND permission=?')->execute([$role, $permission]);
        return;
    }
    $st = $pdo->prepare('INSERT INTO role_permission_overrides(role,permission,allowed,updated_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed),updated_by=VALUES(updated_by),updated_at=NOW()');
    $st->execute([$role, $permission, $allowed?1:0, $_SESSION['admin_id'] ?? null]);
}
function permission_save_admin_override(PDO $pdo, int $adminId, string $permission, ?bool $allowed): void {
    if (!db_table_exists($pdo, 'admin_permission_overrides')) throw new RuntimeException('Run pending migrations before editing permissions.');
    if ($allowed === null) {
        $pdo->prepare('DELETE FROM admin_permission_overrides WHERE admin_id=? AND permission=?')->execute([$adminId, $permission]);
        return;
    }
    $st = $pdo->prepare('INSERT INTO admin_permission_overrides(admin_id,permission,allowed,updated_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed),updated_by=VALUES(updated_by),updated_at=NOW()');
    $st->execute([$adminId, $permission, $allowed?1:0, $_SESSION['admin_id'] ?? null]);
}
function role_permission_effective_matrix(PDO $pdo): array {
    $matrix = admin_role_permission_matrix();
    if (!db_table_exists($pdo, 'role_permission_overrides')) return $matrix;
    foreach ($pdo->query('SELECT role,permission,allowed FROM role_permission_overrides')->fetchAll() as $row) {
        $role = (string)$row['role'];
        $permission = (string)$row['permission'];
        $allowed = (bool)$row['allowed'];
        if (!isset($matrix[$role])) $matrix[$role] = [];
        $matrix[$role] = array_values(array_diff($matrix[$role], [$permission]));
        if ($allowed) $matrix[$role][] = $permission;
    }
    foreach ($matrix as &$items) $items = array_values(array_unique($items));
    unset($items);
    // System Administrator is intentionally immutable full access.
    $matrix['admin'] = array_keys(admin_permission_definitions());
    return $matrix;
}

function dashboard_widget_catalog(): array {
    return [
    'overview' => 'Overview cards', 'attention' => 'Needs attention', 'cases' => 'Recent E-Sumbong cases', 'content' => 'Latest publications',
    'health' => 'Operations snapshot', 'activity' => 'Recent activity', 'announcements' => 'Announcements & advisories', 'governance' => 'Governance snapshot'
    ];
}
function dashboard_widget_preferences(PDO $pdo, int $adminId): array {
    $catalog = dashboard_widget_catalog();
    $visible = array_fill_keys(array_keys($catalog), true);
    // Sensible role defaults are used until an administrator saves a personal layout.
    $role = (string)($_SESSION['admin_role'] ?? 'admin');
    if (in_array($role, ['campus_sas_head', 'sbo_adviser', 'campus_sbo'], true)) {
        $visible['health'] = false;
        $visible['activity'] = false;
    }
    elseif ($role === 'usc') {
        $visible['health'] = false;
    }
    if (!db_table_exists($pdo, 'admin_dashboard_widgets')) return $visible;
    $st = $pdo->prepare('SELECT widget_key,is_visible FROM admin_dashboard_widgets WHERE admin_id=?');
    $st->execute([$adminId]);
    $rows = $st->fetchAll();
    foreach ($rows as $r) if (array_key_exists($r['widget_key'], $visible)) $visible[$r['widget_key']] = (bool)$r['is_visible'];
    return $visible;
}
function save_dashboard_widget_preferences(PDO $pdo, int $adminId, array $visibleKeys): void {
    if (!db_table_exists($pdo, 'admin_dashboard_widgets')) throw new RuntimeException('Run pending migrations before customizing the dashboard.');
    $allowed = array_keys(dashboard_widget_catalog());
    $visibleKeys = array_values(array_intersect($allowed, $visibleKeys));
    $st = $pdo->prepare('INSERT INTO admin_dashboard_widgets(admin_id,widget_key,is_visible,sort_order) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE is_visible=VALUES(is_visible),sort_order=VALUES(sort_order),updated_at=NOW()');
    foreach ($allowed as $i => $key) $st->execute([$adminId, $key, in_array($key, $visibleKeys, true)?1:0, $i]);
}

function platform_rate_limit(PDO $pdo, string $namespace, string $identity, int $limit, int $windowSeconds, int $blockSeconds = 0): array {
    if (!db_table_exists($pdo, 'submission_rate_limits')) return ['allowed' => true, 'retry_after' => 0];
    $limit = max(1, $limit);
    $windowSeconds = max(1, $windowSeconds);
    $blockSeconds = max(1, $blockSeconds?:$windowSeconds);
    $key = hash('sha256', $namespace.'|'.$identity);
    $now = time();
    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $hasAdminLink = db_column_exists($pdo, 'submission_rate_limits', 'admin_id');
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT * FROM submission_rate_limits WHERE bucket_key=? FOR UPDATE');
        $st->execute([$key]);
        $row = $st->fetch();
        if (!$row) {
            if ($hasAdminLink) $pdo->prepare('INSERT INTO submission_rate_limits(bucket_key,admin_id,attempts,window_started_at,last_attempt_at) VALUES(?,?,1,NOW(),NOW())')->execute([$key, $adminId?:null]);
            else $pdo->prepare('INSERT INTO submission_rate_limits(bucket_key,attempts,window_started_at,last_attempt_at) VALUES(?,1,NOW(),NOW())')->execute([$key]);
            $pdo->commit();
            return ['allowed' => true, 'retry_after' => 0];
        }
        if ($hasAdminLink && $adminId && empty($row['admin_id'])) $pdo->prepare('UPDATE submission_rate_limits SET admin_id=? WHERE bucket_key=?')->execute([$adminId, $key]);
        $blocked = !empty($row['blocked_until'])?strtotime((string)$row['blocked_until']):0;
        if ($blocked>$now) {
            $pdo->commit();
            return ['allowed' => false, 'retry_after' => $blocked-$now];
        }
        $started = strtotime((string)$row['window_started_at']);
        if ($now-$started >= $windowSeconds) {
            $pdo->prepare('UPDATE submission_rate_limits SET attempts=1,window_started_at=NOW(),last_attempt_at=NOW(),blocked_until=NULL WHERE bucket_key=?')->execute([$key]);
            $pdo->commit();
            return ['allowed' => true, 'retry_after' => 0];
        }
        $attempts = (int)$row['attempts']+1;
        if ($attempts>$limit) {
            $pdo->prepare('UPDATE submission_rate_limits SET attempts=?,last_attempt_at=NOW(),blocked_until=DATE_ADD(NOW(),INTERVAL ? SECOND) WHERE bucket_key=?')->execute([$attempts, $blockSeconds, $key]);
            $pdo->commit();
            return ['allowed' => false, 'retry_after' => $blockSeconds];
        }
        $pdo->prepare('UPDATE submission_rate_limits SET attempts=?,last_attempt_at=NOW() WHERE bucket_key=?')->execute([$attempts, $key]);
        $pdo->commit();
        return ['allowed' => true, 'retry_after' => 0];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        app_log_error('Rate limiter error', ['namespace' => $namespace, 'error' => $e->getMessage()]);
        return ['allowed' => true, 'retry_after' => 0];
    }
}
function platform_rate_limit_or_429(PDO $pdo, string $namespace, string $identity, int $limit, int $windowSeconds, int $blockSeconds = 0): void {
    $result = platform_rate_limit($pdo, $namespace, $identity, $limit, $windowSeconds, $blockSeconds);
    if ($result['allowed']) return;
    if (!headers_sent()) header('Retry-After: '.max(1, (int)$result['retry_after']));
    app_render_error_page(429, 'Too many requests', 'Too many requests were received. Please wait a short time and try again.');
}

function admin_login_event(PDO $pdo, ?int $adminId, string $username, string $event, string $result, string $reason = ''): void {
    if (!db_table_exists($pdo, 'admin_login_events')) return;
    $st = $pdo->prepare('INSERT INTO admin_login_events(admin_id,username,event_type,result,reason,ip_address,user_agent,device_label,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())');
    $st->execute([$adminId, substr($username, 0, 150), substr($event, 0, 50), substr($result, 0, 20), substr($reason, 0, 255), admin_current_ip(), admin_user_agent(), admin_device_label(admin_user_agent())]);
}
function admin_login_history(PDO $pdo, int $adminId, int $limit = 100): array {
    if (!db_table_exists($pdo, 'admin_login_events')) return [];
    $limit = max(1, min(500, $limit));
    $st = $pdo->prepare('SELECT * FROM admin_login_events WHERE admin_id=? ORDER BY created_at DESC,id DESC LIMIT '.$limit);
    $st->execute([$adminId]);
    return $st->fetchAll();
}

function audit_hash_payload(array $row, string $prevHash): string {
    $parts = [$prevHash, (string)($row['admin_id'] ?? ''), (string)($row['admin_name'] ?? ''), (string)($row['role'] ?? ''), (string)($row['campus'] ?? ''), (string)($row['module'] ?? ''), (string)($row['action'] ?? ''), (string)($row['description'] ?? ''), (string)($row['entity_type'] ?? ''), (string)($row['entity_id'] ?? ''), (string)($row['old_values'] ?? ''), (string)($row['new_values'] ?? ''), (string)($row['ip_address'] ?? ''), (string)($row['user_agent'] ?? ''), (string)($row['created_at'] ?? '')];
    return hash('sha256', implode("\x1f", $parts));
}
function audit_latest_hash(PDO $pdo): string {
    if (!db_column_exists($pdo, 'admin_activity_logs', 'record_hash')) return str_repeat('0', 64);
    $v = $pdo->query("SELECT record_hash FROM admin_activity_logs WHERE record_hash IS NOT NULL AND record_hash<>'' ORDER BY id DESC LIMIT 1")->fetchColumn();
    return is_string($v) && strlen($v) === 64?$v:str_repeat('0', 64);
}
function audit_verify_chain(PDO $pdo, int $limit = 0): array {
    if (!db_column_exists($pdo, 'admin_activity_logs', 'record_hash')) return ['ok' => false, 'checked' => 0, 'message' => 'Audit hashing migration is pending.'];
    $sql = 'SELECT * FROM admin_activity_logs ORDER BY id ASC';
    if ($limit>0) $sql.=' LIMIT '.max(1, $limit);
    $rows = $pdo->query($sql)->fetchAll();
    $prev = str_repeat('0', 64);
    $checked = 0;
    foreach ($rows as $row) {
        $storedPrev = (string)($row['prev_hash'] ?? '');
        $stored = (string)($row['record_hash'] ?? '');
        if ($storedPrev !== $prev) return ['ok' => false, 'checked' => $checked, 'message' => 'Broken previous-hash link at audit record #'.$row['id'].'.'];
        $calc = audit_hash_payload($row, $prev);
        if (!hash_equals($calc, $stored)) return ['ok' => false, 'checked' => $checked, 'message' => 'Audit record #'.$row['id'].' failed integrity verification.'];
        $prev = $stored;
        $checked++;
    }
    return ['ok' => true, 'checked' => $checked, 'message' => $checked.' audit record'.($checked === 1?'':'s').' verified.'];
}

function advanced_dir_size(string $path): int {
    if (!is_dir($path)) return is_file($path)?(int)@filesize($path):0;
    $sum = 0;
    try {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS|FilesystemIterator::CURRENT_AS_FILEINFO));
        foreach ($it as $f) {
            try {
                if ($f->isFile()) $sum+=(int)$f->getSize();
            } catch (Throwable $ignored) {
            }
        }
    } catch (Throwable $ignored) {
    }
    return $sum;
}
function storage_category_usage(): array {
    $root = app_root();
    $categories = ['News images' => 'uploads/posts', 'Media Library' => 'uploads/library', 'Hero banners' => 'uploads/hero', 'Profiles' => 'uploads/admin-profiles', 'E-Sumbong evidence' => 'uploads/concerns', 'Backups' => 'backups', 'Logs & cache' => 'storage'];
    $out = [];
    foreach ($categories as $label => $rel) $out[$label] = ['path' => $rel, 'bytes' => advanced_dir_size($root.'/'.$rel)];
    return $out;
}
function media_create_thumbnail(string $relativePath, int $maxWidth = 480, int $maxHeight = 320): ?string {
    $full = app_root($relativePath);
    if (!is_file($full) || !extension_loaded('gd')) return null;
    $info = @getimagesize($full);
    if (!$info) return null;
    [$w, $h] = $info;
    $mime = $info['mime'] ?? '';
    if ($w<1 || $h<1) return null;
    $src = match($mime) {
        'image/jpeg' => @imagecreatefromjpeg($full), 'image/png' => @imagecreatefrompng($full), 'image/webp' => function_exists('imagecreatefromwebp')?@imagecreatefromwebp($full):false, default => false
    };
    if (!$src) return null;
    $scale = min(1, $maxWidth/$w, $maxHeight/$h);
    $nw = max(1, (int)round($w*$scale));
    $nh = max(1, (int)round($h*$scale));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($mime === 'image/png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $dir = app_root('uploads/thumbnails');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($src);
        imagedestroy($dst);
        return null;
    }
    $name = 'thumb_'.substr(hash('sha256', $relativePath.'|'.@filemtime($full)), 0, 20).'.webp';
    $out = $dir.'/'.$name;
    $ok = function_exists('imagewebp')?@imagewebp($dst, $out, 82):false;
    imagedestroy($src);
    imagedestroy($dst);
    return $ok?'uploads/thumbnails/'.$name:null;
}
function media_optimize_file(string $relativePath, int $maxDimension = 2560): array {
    $full = app_root($relativePath);
    $result = ['optimized' => false, 'thumbnail' => null, 'width' => null, 'height' => null, 'bytes' => is_file($full)?(int)filesize($full):0];
    if (!is_file($full)) return $result;
    $info = @getimagesize($full);
    if (!$info) return $result;
    [$w, $h] = $info;
    $result['width'] = $w;
    $result['height'] = $h;
    $result['thumbnail'] = media_create_thumbnail($relativePath);
    if (!extension_loaded('gd') || max($w, $h) <= $maxDimension) return $result;
    $mime = $info['mime'] ?? '';
    $src = match($mime) {
        'image/jpeg' => @imagecreatefromjpeg($full), 'image/png' => @imagecreatefrompng($full), 'image/webp' => function_exists('imagecreatefromwebp')?@imagecreatefromwebp($full):false, default => false
    };
    if (!$src) return $result;
    $scale = $maxDimension/max($w, $h);
    $nw = (int)round($w*$scale);
    $nh = (int)round($h*$scale);
    $dst = imagecreatetruecolor($nw, $nh);
    if ($mime === 'image/png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $tmp = $full.'.opt';
    $ok = match($mime) {
        'image/jpeg' => @imagejpeg($dst, $tmp, 86), 'image/png' => @imagepng($dst, $tmp, 6), 'image/webp' => function_exists('imagewebp')?@imagewebp($dst, $tmp, 84):false, default => false
    };
    imagedestroy($src);
    imagedestroy($dst);
    if ($ok && is_file($tmp) && filesize($tmp)>0 && filesize($tmp)<filesize($full)) {
        @rename($tmp, $full);
        $result['optimized'] = true;
    } else {
        @unlink($tmp);
    }
    clearstatcache(true, $full);
    $result['bytes'] = (int)filesize($full);
    $result['width'] = $nw;
    $result['height'] = $nh;
    return $result;
}

function active_announcements(PDO $pdo, string $portal = 'USC', string $audience = 'public', int $limit = 5): array {
    if (!db_table_exists($pdo, 'announcements')) return [];
    $portal = strtoupper($portal);
    $limit = max(1, min(20, $limit));
    $st = $pdo->prepare("SELECT * FROM announcements WHERE status='active' AND (audience=? OR audience='both') AND (portal_code=? OR portal_code='ALL') AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) ORDER BY FIELD(priority,'emergency','warning','info'),starts_at DESC,id DESC LIMIT $limit");
    $st->execute([$audience, $portal]);
    return $st->fetchAll();
}
function public_alerts_html(PDO $pdo, string $portal = 'USC'): string {
    $rows = active_announcements($pdo, $portal, 'public', 3);
    if (!$rows) return '';
    $topPriority = in_array($rows[0]['priority'], ['emergency', 'warning', 'info'], true)?$rows[0]['priority']:'info';
    $items = '';
    foreach ($rows as $row) {
        $title = trim((string)($row['title'] ?? ''));
        $message = trim((string)($row['message'] ?? ''));
        $content = '<strong>'.e($title).'</strong>'.($message !== ''?'<span>'.e($message).'</span>':'');
        if (!empty($row['link_url'])) $content = '<a href="'.e($row['link_url']).'"'.(preg_match('~^https?://~i', (string)$row['link_url'])?' target="_blank" rel="noopener"':'').'>'.$content.'</a>';
        $items.='<span class="public-alert-ticker__item">'.$content.'</span><span class="public-alert-ticker__sep" aria-hidden="true">•</span>';
    }
    $html = '<section class="public-alert-ticker public-alert-ticker--'.e($topPriority).'" aria-label="Current advisories"><div class="public-alert-ticker__inner"><span class="public-alert-ticker__label"><i aria-hidden="true"></i>Announcement</span><div class="public-alert-ticker__viewport"><div class="public-alert-ticker__track"><div class="public-alert-ticker__group">'.$items.'</div><div class="public-alert-ticker__group" aria-hidden="true">'.$items.'</div></div></div></div></section>';
    return $html;
}
function current_officers(PDO $pdo, string $portal = 'USC', ?int $academicYearId = null): array {
    if (!db_table_exists($pdo, 'officers')) return [];
    $academicYearId = $academicYearId?:active_academic_year_id($pdo);
    $where = ['is_archived=0', 'portal_code=?'];
    $params = [strtoupper($portal)];
    if ($academicYearId) {
        $where[] = 'academic_year_id=?';
        $params[] = $academicYearId;
    }
    $st = $pdo->prepare('SELECT * FROM officers WHERE '.implode(' AND ', $where).' ORDER BY sort_order,position_title,full_name');
    $st->execute($params);
    return $st->fetchAll();
}
function public_officers_html(PDO $pdo, string $portal = 'USC'): string {
    $rows = current_officers($pdo, $portal);
    if (!$rows) return '';
    $ay = active_academic_year($pdo);
    $html = '<section class="public-officers"><div class="container"><div class="section-head"><div><span class="section-kicker">STUDENT LEADERSHIP</span><h2>Current officers</h2><p>'.e(academic_year_label($ay)).'</p></div></div><div class="public-officers-grid">';
    foreach ($rows as $o) $html.='<article><strong>'.e($o['full_name']).'</strong><span>'.e($o['position_title']).'</span>'.(!empty($o['office_name'])?'<small>'.e($o['office_name']).'</small>':'').'</article>';
    $html.='</div></div></section>';
    return $html;
}

function offsite_backup_copy(PDO $pdo, string $filename): array {
    if (system_setting($pdo, 'offsite_backup_enabled', '0') !== '1') return ['ok' => false, 'message' => 'Off-site copy is disabled.'];
    $dest = trim((string)(system_setting($pdo, 'offsite_backup_path', '') ?? ''));
    if ($dest === '') return ['ok' => false, 'message' => 'No off-site destination is configured.'];
    $src = app_root('backups/'.basename($filename));
    if (!is_file($src)) return ['ok' => false, 'message' => 'Backup file is missing.'];
    if (!is_dir($dest) && !@mkdir($dest, 0750, true) && !is_dir($dest)) return ['ok' => false, 'message' => 'Off-site destination is not writable.'];
    $target = rtrim($dest, '/\\').DIRECTORY_SEPARATOR.basename($filename);
    $ok = @copy($src, $target);
    if ($ok && db_column_exists($pdo, 'backup_history', 'offsite_copied_at')) $pdo->prepare('UPDATE backup_history SET offsite_copied_at=NOW(),offsite_path=? WHERE filename=?')->execute([$target, basename($filename)]);
    return ['ok' => $ok, 'message' => $ok?'Backup copied to the configured secondary destination.':'Unable to copy backup to the secondary destination.', 'path' => $ok?$target:null];
}

function notification_cleanup_expired(PDO $pdo): int {
    if (!db_column_exists($pdo, 'admin_notifications', 'expires_at')) return 0;
    $days = max(30, (int)(system_setting($pdo, 'notification_retention_days', '180') ?? 180));
    $st = $pdo->prepare('UPDATE admin_notifications SET dismissed_at=COALESCE(dismissed_at,NOW()) WHERE dismissed_at IS NULL AND ((expires_at IS NOT NULL AND expires_at<NOW()) OR created_at<DATE_SUB(NOW(),INTERVAL ? DAY))');
    $st->execute([$days]);
    return $st->rowCount();
}
