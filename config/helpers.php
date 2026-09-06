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
require_once __DIR__.'/app.php';
require_once __DIR__.'/security.php';
function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/**
* Normalize a database-stored public file path and make sure it resolves
* inside this application. This prevents stale upload records from rendering
* as broken images while keeping URLs correct when the project is installed
* in a subdirectory such as /DMMMSU_USC.
*/
function public_file_relative_path(?string $path): ?string {
    $raw = trim(str_replace('\\', '/', (string)$path));
    if ($raw === '') return null;

    // Accept legacy same-site absolute URLs as well as relative paths. Older
    // installations may have stored /DMMMSU_USC/uploads/... or a complete
    // http(s) URL instead of uploads/... . Normalize all of them back to an
    // application-relative upload path without trusting traversal segments.
    if (preg_match('~^(?:https?:)?//~i', $raw)) {
        $url = str_starts_with($raw, '//') ? 'http:'.$raw : $raw;
        $parsedPath = parse_url($url, PHP_URL_PATH);
        if (!is_string($parsedPath) || $parsedPath === '') return null;
        $raw = $parsedPath;
    } else {
        $raw = preg_replace('~[?#].*$~', '', $raw) ?? $raw;
    }

    $raw = rawurldecode($raw);
    $raw = preg_replace('~/+~', '/', $raw) ?? $raw;
    $raw = ltrim($raw, '/');

    // If a project-folder prefix was stored (for example
    // DMMMSU_USC/uploads/posts/photo.jpg), keep only the public upload path.
    $lower = strtolower($raw);
    $uploadPos = strpos($lower, 'uploads/');
    if ($uploadPos !== false) $raw = substr($raw, $uploadPos);

    $segments = array_values(array_filter(explode('/', $raw), static fn(string $segment): bool => $segment !== '' && $segment !== '.'));
    if (!$segments || in_array('..', $segments, true)) return null;
    $relative = implode('/', $segments);

    // This helper is intentionally limited to files under the public uploads
    // tree. Static assets use their normal application paths.
    if (!str_starts_with(strtolower($relative), 'uploads/')) return null;
    return $relative;
}
function public_file_exists(?string $path): bool {
    $relative = public_file_relative_path($path);
    return $relative !== null && is_file(app_root($relative));
}
function public_file_url(?string $path): ?string {
    $relative = public_file_relative_path($path);
    if ($relative === null) return null;

    // Do not reject a valid browser URL solely because PHP's filesystem view
    // differs from the web server's document-root mapping. Public <img>
    // elements handle a genuinely missing file with an onerror fallback.
    return app_absolute_url($relative);
}
function public_file_record(array $record): ?array {
    $url = public_file_url((string)($record['file_path'] ?? ''));
    if ($url === null) return null;
    $record['_public_url'] = $url;
    return $record;
}
function public_file_records(array $records): array {
    $available = [];
    foreach ($records as $record) {
        if (!is_array($record)) continue;
        $resolved = public_file_record($record);
        if ($resolved !== null) $available[] = $resolved;
    }
    return $available;
}
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        app_log_error('CSRF verification failed', ['path' => $_SERVER['REQUEST_URI'] ?? '', 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        app_render_error_page(419, 'Request expired', 'The security token for this request is invalid or expired. Return to the previous page and try again.');
    }
}

/**
* Public forms use an independent double-submit CSRF cookie instead of the
* administrator PHP session. This prevents an admin login/logout or session
* regeneration in another tab from invalidating a public E-Sumbong form.
*/
function public_csrf_cookie_name(): string {
    return 'DMMMSU_USC_PUBLIC_CSRF';
}
function public_csrf_cookie_path(): string {
    $base = app_base_url();
    if ($base === '') return '/';
    $path = (string)(parse_url($base, PHP_URL_PATH) ?? '');
    $path = '/'.trim($path, '/');
    return $path === '/' ? '/' : $path.'/';
}
function public_csrf_token(): string {
    $name = public_csrf_cookie_name();
    $token = strtolower(trim((string)($_COOKIE[$name] ?? '')));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $token = bin2hex(random_bytes(32));
        if (!headers_sent()) {
            setcookie($name, $token, [
            'expires' => 0,
            'path' => public_csrf_cookie_path(),
            'domain' => '',
            'secure' => app_is_https(),
            'httponly' => true,
            'samesite' => 'Strict',
            ]);
        }
        // Keep repeated calls in the same request consistent even before the
        // browser receives the Set-Cookie header.
        $_COOKIE[$name] = $token;
    }
    return $token;
}
function verify_public_csrf(): void {
    $posted = strtolower(trim((string)($_POST['csrf'] ?? '')));
    $cookie = strtolower(trim((string)($_COOKIE[public_csrf_cookie_name()] ?? '')));
    $validShape = static fn(string $token): bool => (bool)preg_match('/^[a-f0-9]{64}$/', $token);

    $valid = $validShape($posted) && $validShape($cookie) && hash_equals($cookie, $posted);

    // Transitional compatibility for a form that was opened immediately
    // before this update. New public forms never depend on the admin session.
    if (!$valid && $validShape($posted)) {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $legacy = strtolower(trim((string)($_SESSION['csrf'] ?? '')));
        $valid = $validShape($legacy) && hash_equals($legacy, $posted);
    }

    // Modern browsers include Origin on cross-site POSTs. Reject a supplied
    // cross-origin host while remaining compatible with LAN/reverse-proxy use.
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($valid && $origin !== '') {
        $originHost = strtolower((string)(parse_url($origin, PHP_URL_HOST) ?? ''));
        $hostHeader = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
        $requestHost = strtolower((string)(parse_url((app_is_https()?'https':'http').'://'.$hostHeader, PHP_URL_HOST) ?? ''));
        if ($originHost !== '' && $requestHost !== '' && $originHost !== $requestHost) $valid = false;
    }

    if (!$valid) {
        app_log_error('Public CSRF verification failed', [
        'path' => $_SERVER['REQUEST_URI'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'posted_token' => $posted !== '',
        'cookie_token' => $cookie !== '',
        ]);
        app_render_error_page(419, 'Request expired', 'The security token for this form is no longer valid. Reload the form and try again.');
    }
}

function app_ini_size_bytes(string $value): int {
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
function request_exceeds_php_post_limit(): bool {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') return false;
    $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $limit = app_ini_size_bytes((string)ini_get('post_max_size'));
    return $length > 0 && $limit > 0 && $length > $limit;
}
function require_admin(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }

    // Keep long-lived sessions synchronized with account status, role, scope, and forced password resets.
    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            if (!admin_session_is_valid($pdo, (int)$_SESSION['admin_id'])) {
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                header('Location: login.php?session_expired=1');
                exit;
            }
            $st = $pdo->prepare('SELECT full_name,email,profile_image,role,campus,status,force_password_change,two_factor_enabled FROM admins WHERE id=? LIMIT 1');
            $st->execute([(int)$_SESSION['admin_id']]);
            $account = $st->fetch();
            if (!$account || ($account['status'] ?? 'active') !== 'active') {
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                header('Location: login.php?inactive=1');
                exit;
            }
            $_SESSION['admin_name'] = $account['full_name'];
            $_SESSION['admin_email'] = $account['email'] ?? null;
            $_SESSION['admin_profile_image'] = $account['profile_image'] ?? null;
            $_SESSION['admin_role'] = $account['role'] ?? 'admin';
            $_SESSION['admin_campus'] = $account['campus'] ?? null;
            $_SESSION['force_password_change'] = !empty($account['force_password_change'])?1:0;
            if ((int)($_SESSION['admin_activity_touch'] ?? 0)<time()-120) {
                try {
                    $pdo->prepare('UPDATE admins SET last_activity_at=NOW() WHERE id=?')->execute([(int)$_SESSION['admin_id']]);
                } catch (Throwable $ignored) {
                }
                $_SESSION['admin_activity_touch'] = time();
            }
            $current = basename((string)($_SERVER['PHP_SELF'] ?? ''));
            if (!empty($_SESSION['force_password_change']) && !in_array($current, ['profile.php', 'logout.php'], true)) {
                header('Location: profile.php?force_password=1');
                exit;
            }
            $requireAdmin2fa = false;
            try {
                $roles = admin_required_2fa_roles($pdo);
                if (system_setting($pdo, 'security_require_2fa_for_admin', '0') === '1' && !in_array('admin', $roles, true)) $roles[] = 'admin';
                $requireAdmin2fa = in_array((string)($account['role'] ?? ''), $roles, true);
            } catch (Throwable $ignored) {
            }
            if ($requireAdmin2fa && empty($account['two_factor_enabled']) && !in_array($current, ['profile.php', 'logout.php'], true)) {
                header('Location: profile.php?require_2fa=1');
                exit;
            }
        } catch (Throwable $ignored) {
        }
    }
}
function ref_code(): string {
    return 'ES-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function app_schema_mutation_allowed(): bool {
    return defined('APP_MIGRATION_MODE') && APP_MIGRATION_MODE === true;
}
function deny_access(string $message = 'Your account does not have permission to perform this action.'): never {
    app_render_error_page(403, 'Access denied', $message);
}
function not_found(string $message = 'The requested record could not be found.'): never {
    app_render_error_page(404, 'Not found', $message);
}

function ensure_post_images_table(PDO $pdo): void {
    if (!app_schema_mutation_allowed()) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    media_id INT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post_images_post_id (post_id),
    INDEX idx_post_images_media_id (media_id),
    CONSTRAINT fk_post_images_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    try {
        $cols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM post_images")->fetchAll() as $c) $cols[$c['Field']] = true;
        if (!isset($cols['media_id'])) $pdo->exec("ALTER TABLE post_images ADD COLUMN media_id INT NULL AFTER original_name");
        $idx = [];
        foreach ($pdo->query("SHOW INDEX FROM post_images")->fetchAll() as $i) $idx[$i['Key_name']] = true;
        if (!isset($idx['idx_post_images_media_id'])) $pdo->exec("ALTER TABLE post_images ADD INDEX idx_post_images_media_id(media_id)");
    } catch (PDOException $e) {
    }
}

function post_images(PDO $pdo, int $postId): array {
    ensure_post_images_table($pdo);
    $st = $pdo->prepare('SELECT * FROM post_images WHERE post_id=? ORDER BY sort_order ASC,id ASC');
    $st->execute([$postId]);
    return $st->fetchAll();
}

/** Return the next shared sort position across publication photos and videos. */
function post_media_next_sort_order(PDO $pdo, int $postId): int {
    $max = -1;
    if (db_table_exists($pdo, 'post_images')) {
        $st = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1) FROM post_images WHERE post_id=?');
        $st->execute([$postId]);
        $max = max($max, (int)$st->fetchColumn());
    }
    if (db_table_exists($pdo, 'post_videos')) {
        $st = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1) FROM post_videos WHERE post_id=?');
        $st->execute([$postId]);
        $max = max($max, (int)$st->fetchColumn());
    }
    return $max + 1;
}

/**
* Build one public-facing mixed sequence for publication photos and videos.
*
* Newer posts use one global sort_order across both tables. Older posts may
* have duplicate photo/video orders; for those legacy rows we preserve the
* historical photos-first ordering instead of guessing a mixed sequence.
*/
function post_media_sequence_records(array $images, array $videos): array {
    $media = [];
    foreach ($images as $row) {
        if (!is_array($row)) continue;
        $row['_media_type'] = 'image';
        $media[] = $row;
    }
    foreach ($videos as $row) {
        if (!is_array($row)) continue;
        $row['_media_type'] = 'video';
        $media[] = $row;
    }
    if (count($media) < 2) return $media;

    $orders = array_map(static fn($row) => (int)($row['sort_order'] ?? 0), $media);
    $hasGlobalOrder = count(array_unique($orders, SORT_REGULAR)) === count($orders);
    if (!$hasGlobalOrder) return $media;

    usort($media, static function ($a, $b): int {
        $cmp = ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0));
        if ($cmp !== 0) return $cmp;
        $typeCmp = strcmp((string)($a['_media_type'] ?? ''), (string)($b['_media_type'] ?? ''));
        return $typeCmp !== 0 ? $typeCmp : ((int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0));
    });
    return $media;
}

/** The first item in the mixed media order is the publication's featured media. */
function post_featured_media_record(array $images, array $videos): ?array {
    $sequence = post_media_sequence_records($images, $videos);
    return $sequence[0] ?? null;
}

/**
* Choose one consistent gallery aspect ratio from the saved cover photo.
*
* The first post image is the cover (sort_order 0). Its natural dimensions
* are snapped to the nearest supported photo ratio so every gallery tile in
* the article uses the same shape. Portrait orientation is preserved (for
* example a portrait 4:3 photo becomes 3:4).
*/
function post_gallery_display_ratio(array $images): string {
    if (!$images) return '1 / 1';

    $relative = public_file_relative_path((string)($images[0]['file_path'] ?? ''));
    if ($relative === null) return '1 / 1';
    $full = app_root($relative);
    if (!is_file($full)) return '1 / 1';

    $size = @getimagesize($full);
    $width = (int)($size[0] ?? 0);
    $height = (int)($size[1] ?? 0);
    if ($width <= 0 || $height <= 0) return '1 / 1';

    $actual = $width / $height;
    $supported = [
    '1 / 1' => 1.0,
    '4 / 3' => 4 / 3,
    '3 / 4' => 3 / 4,
    '16 / 9' => 16 / 9,
    '9 / 16' => 9 / 16,
    ];

    $best = '1 / 1';
    $bestDistance = INF;
    foreach ($supported as $cssRatio => $ratio) {
        // Log distance treats reciprocal/portrait ratios fairly.
        $distance = abs(log($actual / $ratio));
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $best = $cssRatio;
        }
    }
    return $best;
}

function upload_post_images(PDO $pdo, int $postId, array $files, int $maxFiles = 100): array {
    ensure_post_images_table($pdo);
    if (empty($files['name']) || !is_array($files['name'])) return [];
    $existing = post_images($pdo, $postId);
    $remaining = max(0, $maxFiles-count($existing));
    if ($remaining === 0) return ['Maximum of '.$maxFiles.' images reached.'];
    $uploadDir = dirname(__DIR__).'/uploads/posts';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) return ['Unable to create image upload folder.'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $errors = [];
    $saved = 0;
    $sort = post_media_next_sort_order($pdo, $postId);
    foreach ($files['name'] as $i => $original) {
        if ($saved >= $remaining) break;
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = 'Could not upload '.basename((string)$original).'.';
            continue;
        }
        $tmp = $files['tmp_name'][$i] ?? '';
        $size = (int)($files['size'][$i] ?? 0);
        if ($size <= 0 || $size>8*1024*1024) {
            $errors[] = basename((string)$original).' must be 8 MB or smaller.';
            continue;
        }
        $mime = $finfo->file($tmp);
        if (!isset($allowed[$mime])) {
            $errors[] = basename((string)$original).' is not a supported JPG, PNG, or WebP image.';
            continue;
        }
        $filename = 'post_'.$postId.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
        if (!move_uploaded_file($tmp, $uploadDir.'/'.$filename)) {
            $errors[] = 'Could not save '.basename((string)$original).'.';
            continue;
        }
        $relative = 'uploads/posts/'.$filename;
        if (function_exists('media_optimize_file') && system_setting($pdo, 'media_auto_optimize', '1') === '1') media_optimize_file($relative);
        $st = $pdo->prepare('INSERT INTO post_images(post_id,file_path,original_name,sort_order) VALUES(?,?,?,?)');
        $st->execute([$postId, $relative, basename((string)$original), $sort++]);
        $saved++;
    }
    return $errors;
}

function delete_post_image(PDO $pdo, int $imageId, ?int $postId = null): void {
    ensure_post_images_table($pdo);
    $sql = 'SELECT * FROM post_images WHERE id=?'.($postId !== null?' AND post_id=?':'');
    $st = $pdo->prepare($sql);
    $params = [$imageId];
    if ($postId !== null) $params[] = $postId;
    $st->execute($params);
    $img = $st->fetch();
    if (!$img) return;
    if (empty($img['media_id'])) {
        $full = dirname(__DIR__).'/'.ltrim((string)$img['file_path'], '/');
        if (is_file($full)) @unlink($full);
    }
    $pdo->prepare('DELETE FROM post_images WHERE id=?')->execute([$imageId]);
}


function ensure_post_videos_table(PDO $pdo): void {
    if (!app_schema_mutation_allowed()) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    media_id INT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post_videos_post_id (post_id),
    INDEX idx_post_videos_media_id (media_id),
    CONSTRAINT fk_post_videos_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function post_videos(PDO $pdo, int $postId): array {
    if (!db_table_exists($pdo, 'post_videos')) return [];
    $st = $pdo->prepare('SELECT * FROM post_videos WHERE post_id=? ORDER BY sort_order ASC,id ASC');
    $st->execute([$postId]);
    return $st->fetchAll();
}

function upload_post_videos(PDO $pdo, int $postId, array $files, int $maxMb = 250): array {
    if (empty($files['name']) || !is_array($files['name'])) return [];
    if (!db_table_exists($pdo, 'post_videos')) return ['Video support needs the latest database migration. Run it from Maintenance, then try again.'];
    $uploadDir = dirname(__DIR__).'/uploads/posts';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) return ['Unable to create video upload folder.'];
    $allowed = ['video/mp4' => 'mp4', 'application/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $errors = [];
    $existing = post_videos($pdo, $postId);
    $sort = post_media_next_sort_order($pdo, $postId);
    foreach ($files['name'] as $i => $original) {
        $error = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $errors[] = basename((string)$original).' was blocked by the PHP server upload limit. Videos may be up to '.$maxMb.' MB; increase upload_max_filesize and post_max_size if needed.';
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = 'Could not upload '.basename((string)$original).'.';
            continue;
        }
        $tmp = (string)($files['tmp_name'][$i] ?? '');
        $size = (int)($files['size'][$i] ?? 0);
        if ($size <= 0 || $size > $maxMb*1024*1024) {
            $errors[] = basename((string)$original).' must be '.$maxMb.' MB or smaller.';
            continue;
        }
        $mime = (string)$finfo->file($tmp);
        if (!isset($allowed[$mime])) {
            $errors[] = basename((string)$original).' is not a supported MP4, WebM, or MOV video.';
            continue;
        }
        $filename = 'video_'.$postId.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
        if (!move_uploaded_file($tmp, $uploadDir.'/'.$filename)) {
            $errors[] = 'Could not save '.basename((string)$original).'.';
            continue;
        }
        $relative = 'uploads/posts/'.$filename;
        if ($mime === 'application/mp4') $mime = 'video/mp4';
        $st = $pdo->prepare('INSERT INTO post_videos(post_id,file_path,original_name,mime_type,file_size,sort_order) VALUES(?,?,?,?,?,?)');
        $st->execute([$postId, $relative, basename((string)$original), $mime, $size, $sort++]);
    }
    return $errors;
}

function delete_post_video(PDO $pdo, int $videoId, ?int $postId = null): void {
    if (!db_table_exists($pdo, 'post_videos')) return;
    $sql = 'SELECT * FROM post_videos WHERE id=?'.($postId !== null?' AND post_id=?':'');
    $st = $pdo->prepare($sql);
    $params = [$videoId];
    if ($postId !== null) $params[] = $postId;
    $st->execute($params);
    $video = $st->fetch();
    if (!$video) return;
    if (empty($video['media_id'])) {
        $full = dirname(__DIR__).'/'.ltrim((string)$video['file_path'], '/');
        if (is_file($full)) @unlink($full);
    }
    $pdo->prepare('DELETE FROM post_videos WHERE id=?')->execute([$videoId]);
}

/* Administration role and campus authorization */
function admin_roles(): array {
    // Keep the internal role keys stable for database compatibility; only the public labels change.
    return [
    'admin' => 'System Administrator',
    'usc' => 'University Student Council',
    'sas_director' => 'Student Affairs and Services',
    'sbo_adviser' => 'Adviser',
    'campus_sbo' => 'Student Body Organization',
    ];
}
function admin_role_descriptions(): array {
    return [
    'admin' => 'Overall control of the entire platform, including accounts, permissions, settings, recovery, backups, and every portal.',
    'usc' => 'University-wide council operations with access across USC and campus portals. Similar operational control to Student Affairs and Services at the university level, without infrastructure or account-security administration.',
    'sas_director' => 'Student Affairs and Services with university-wide operational oversight across all portals, plus system-health, storage, recovery, and security visibility.',
    'sbo_adviser' => 'Full operational control of the assigned campus website, shared with Student Affairs and Services and the Student Body Organization.',
    'campus_sbo' => 'Full operational control of the assigned campus website, shared with Student Affairs and Services and the Adviser.',
    ];
}
function admin_role_scope_label(?string $role = null): string {
    $role = $role ?? admin_role();
    if (in_array($role, ['admin', 'usc', 'sas_director'], true)) return 'University-wide';
    return 'Assigned campus only';
}
function admin_campuses(): array {
    return [
    'NLUC' => 'North La Union Campus',
    'MLUC' => 'Mid La Union Campus',
    'SLUC' => 'South La Union Campus',
    'OUS' => 'Open University System'
    ];
}
function portal_display_name(?string $code): string {
    $key = strtoupper(trim((string)$code));
    if ($key === 'USC') return 'University Student Council';
    return admin_campuses()[$key] ?? trim((string)$code);
}
function student_campus_display(?string $value): string {
    $raw = trim((string)$value);
    if ($raw === '') return '';
    $upper = strtoupper($raw);
    foreach (admin_campuses() as $code => $name) {
        if ($upper === $code || str_starts_with($upper, $code.' ') || str_starts_with($upper, $code.' —')) return $name;
        if (strcasecmp($raw, $name) === 0) return $name;
    }
    return $raw;
}

function admin_profile_image(?string $path): ?string {
    $path = trim((string)$path);
    if ($path === '' || !str_starts_with($path, 'uploads/admin-profiles/')) return null;
    $full = dirname(__DIR__).'/'.$path;
    return is_file($full) ? $path : null;
}
function admin_avatar_html(string $name, ?string $profileImage = null, string $class = 'admin-user__avatar'): string {
    $image = admin_profile_image($profileImage);
    if ($image) {
        return '<span class="'.e($class).' admin-user__avatar--photo"><img src="../'.e($image).'" alt=""></span>';
    }
    $initial = strtoupper(substr(trim($name) !== ''?trim($name):'A', 0, 1));
    return '<span class="'.e($class).'">'.e($initial).'</span>';
}
function admin_delete_profile_image(?string $path): void {
    $path = trim((string)$path);
    if ($path === '' || !str_starts_with($path, 'uploads/admin-profiles/')) return;
    $full = dirname(__DIR__).'/'.$path;
    if (is_file($full)) @unlink($full);
}
function admin_store_profile_image(array $file, ?string $oldPath = null): string {
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) throw new RuntimeException('Choose a valid profile image.');
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size>2*1024*1024) throw new RuntimeException('Profile picture must be 2 MB or smaller.');
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) throw new RuntimeException('Unable to read the uploaded profile picture.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Profile pictures must be JPG, PNG, or WebP.');
    $dir = dirname(__DIR__).'/uploads/admin-profiles';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Unable to create the profile image folder.');
    $name = 'profile_'.bin2hex(random_bytes(10)).'.'.$allowed[$mime];
    if (!move_uploaded_file($tmp, $dir.'/'.$name)) throw new RuntimeException('Unable to save the profile picture.');
    $relative = 'uploads/admin-profiles/'.$name;
    if (function_exists('media_optimize_file')) media_optimize_file($relative, 1200);
    return $relative;
}

function officer_delete_photo(?string $path): void {
    $path = trim((string)$path);
    if ($path === '' || !str_starts_with($path, 'uploads/officers/')) return;
    $full = dirname(__DIR__).'/'.$path;
    if (is_file($full)) @unlink($full);
}
function officer_store_photo(array $file): string {
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) throw new RuntimeException('Choose a valid officer photo.');
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size>4*1024*1024) throw new RuntimeException('Officer photos must be 4 MB or smaller.');
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) throw new RuntimeException('Unable to read the officer photo.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Officer photos must be JPG, PNG, or WebP.');
    $dir = dirname(__DIR__).'/uploads/officers';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Unable to create the officer photo folder.');
    $name = 'officer_'.bin2hex(random_bytes(10)).'.'.$allowed[$mime];
    if (!move_uploaded_file($tmp, $dir.'/'.$name)) throw new RuntimeException('Unable to save the officer photo.');
    $relative = 'uploads/officers/'.$name;
    if (function_exists('media_optimize_file')) media_optimize_file($relative, 1600);
    return $relative;
}

function admin_role(): string {
    return $_SESSION['admin_role'] ?? 'admin';
}
function admin_campus(): ?string {
    $campus = strtoupper(trim((string)($_SESSION['admin_campus'] ?? '')));
    return isset(admin_campuses()[$campus]) ? $campus : null;
}
function admin_role_label(?string $role = null): string {
    $role = $role ?? admin_role();
    if ($role === 'campus_sas_head') return 'Student Affairs and Services';
    // legacy compatibility
    return admin_roles()[$role] ?? 'Administrator';
}
function admin_role_option_label(string $role): string {
    $label = admin_role_label($role);
    return $label;
}
function is_system_admin(): bool {
    return admin_role() === 'admin';
}
function is_sas_director(): bool {
    return admin_role() === 'sas_director';
}
function is_usc_role(): bool {
    return admin_role() === 'usc';
}
function admin_is_global(): bool {
    return in_array(admin_role(), ['admin', 'usc', 'sas_director'], true);
}

/* Centralized permission model. Keep role names separate from portal scope. */
function admin_permission_definitions(): array {
    return [
    'accounts.manage' => ['Accounts', 'Manage administrator accounts'],
    'accounts.reset_password' => ['Accounts', 'Reset other administrator passwords'],
    'settings.manage' => ['System', 'Change system settings'],
    'permissions.view' => ['System', 'View role permission matrix'],
    'permissions.manage' => ['System', 'Customize role and account permissions'],
    'system.health' => ['System', 'View system health and security status'],
    'storage.view' => ['System', 'View storage usage and media health'],
    'disaster_recovery.view' => ['System', 'View disaster-recovery readiness'],
    'disaster_recovery.manage' => ['System', 'Manage recovery destinations and verification'],
    'academic.manage' => ['Governance', 'Manage academic years and terms'],
    'officers.manage' => ['Governance', 'Manage officer term history'],
    'announcements.manage' => ['Content', 'Manage announcements and emergency advisories'],
    'security.login_history' => ['Security', 'View administrator login history'],
    'content.manage' => ['Content', 'Create and edit News & Updates'],
    'content.publish' => ['Content', 'Publish News & Updates'],
    'homepage.manage' => ['Homepage', 'Manage portal Hero Slider'],
    'homepage.promotions.approve' => ['Homepage', 'Approve campus promotion requests'],
    'media.manage' => ['Media', 'Manage Media Library'],
    'concerns.view' => ['E-Sumbong', 'View concerns in accessible scope'],
    'concerns.update' => ['E-Sumbong', 'Update concern status and notes'],
    'concerns.templates' => ['E-Sumbong', 'Access reusable response templates'],
    'concerns.assign' => ['E-Sumbong', 'Assign concerns to personnel'],
    'concerns.route' => ['E-Sumbong', 'Route concerns between portals'],
    'concerns.close' => ['E-Sumbong', 'Resolve and close concerns'],
    'reports.view' => ['Reports', 'View analytics'],
    'reports.export' => ['Reports', 'Export report data'],
    'activity.view' => ['Audit', 'View Activity Log'],
    'trash.manage' => ['Trash', 'Move and restore records'],
    'trash.purge' => ['Trash', 'Permanently delete trashed records'],
    'backups.manage' => ['Maintenance', 'Create and restore protected backups'],
    'migrations.manage' => ['Maintenance', 'Run database schema migrations'],
    'privacy.export' => ['Privacy', 'Export sensitive E-Sumbong identity data'],
    ];
}
function admin_role_permission_matrix(): array {
    $all = array_keys(admin_permission_definitions());

    // University-wide operational permissions shared by USC and Student Affairs and Services.
    $universityOperations = [
    'academic.manage', 'officers.manage', 'announcements.manage',
    'content.manage', 'content.publish', 'homepage.manage', 'homepage.promotions.approve', 'media.manage',
    'concerns.view', 'concerns.update', 'concerns.templates', 'concerns.assign', 'concerns.route', 'concerns.close',
    'reports.view', 'reports.export', 'activity.view', 'trash.manage',
    ];

    // Student Affairs and Services, Adviser, and Student Body Organization intentionally share one campus baseline.
    // Their records remain restricted by admin_scope_code() to the assigned campus.
    $campusPortalControl = [
    'officers.manage', 'announcements.manage',
    'content.manage', 'content.publish', 'homepage.manage', 'media.manage',
    'concerns.view', 'concerns.update', 'concerns.templates', 'concerns.assign', 'concerns.route', 'concerns.close',
    'reports.view', 'reports.export', 'trash.manage',
    ];

    return [
    'admin' => $all,
    'usc' => $universityOperations,
    'sas_director' => array_values(array_unique(array_merge($universityOperations, [
    'permissions.view', 'system.health', 'storage.view', 'disaster_recovery.view',
    'security.login_history', 'privacy.export',
    ]))),
    'campus_sas_head' => $campusPortalControl,
    'sbo_adviser' => $campusPortalControl,
    'campus_sbo' => $campusPortalControl,
    ];
}
function admin_can_permission(string $permission, ?string $role = null): bool {
    $role = $role ?? admin_role();
    $matrix = admin_role_permission_matrix();
    $default = in_array($permission, $matrix[$role] ?? [], true);
    // The System Administrator is a fixed full-access role and cannot be reduced by overrides.
    if ($role === 'admin') return true;
    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO && function_exists('permission_override_lookup')) {
        $adminId = $role === admin_role()?(int)($_SESSION['admin_id'] ?? 0):null;
        $override = permission_override_lookup($pdo, $role, $permission, $adminId?:null);
        if ($override !== null) return $override;
    }
    return $default;
}
function can_manage_accounts(): bool {
    return admin_can_permission('accounts.manage');
}
function can_reset_account_passwords(): bool {
    return admin_can_permission('accounts.reset_password');
}
function can_delete_admin_records(): bool {
    return admin_can_permission('trash.manage');
}
function can_permanently_delete_admin_records(): bool {
    return admin_can_permission('trash.purge');
}
function can_publish_content(): bool {
    return admin_can_permission('content.publish');
}
function can_export_reports(): bool {
    return admin_can_permission('reports.export');
}
function can_approve_homepage_promotions(): bool {
    return admin_can_permission('homepage.promotions.approve');
}

function require_system_admin(): void {
    require_admin();
    if (!can_manage_accounts()) {
        deny_access('System Administrator permission is required.');
    }
}
function admin_selected_portal(): ?string {
    if (!admin_is_global()) return null;
    $portal = strtoupper(trim((string)($_SESSION['admin_selected_portal'] ?? '')));
    return in_array($portal, ['USC', 'NLUC', 'MLUC', 'SLUC', 'OUS'], true)?$portal:null;
}
function admin_scope_code(): ?string {
    if (admin_is_global()) return admin_selected_portal();
    if (is_usc_role()) return 'USC';
    return admin_campus();
}
function admin_can_access_code(?string $code): bool {
    if (admin_is_global()) return true;
    $scope = admin_scope_code();
    return $scope !== null && strtoupper(trim((string)$code)) === $scope;
}

function concern_portal_code(array $concern): string {
    $source = strtoupper(trim((string)($concern['source_portal'] ?? '')));
    if (in_array($source, ['USC', 'NLUC', 'MLUC', 'SLUC', 'OUS'], true)) return $source;
    return 'USC';
}
function concern_assigned_scope(array $concern): string {
    $assigned = strtoupper(trim((string)($concern['assigned_scope'] ?? '')));
    if (in_array($assigned, ['USC', 'NLUC', 'MLUC', 'SLUC', 'OUS'], true)) return $assigned;
    return concern_portal_code($concern);
}
function admin_can_access_concern(array $concern): bool {
    if (admin_is_global()) return true;
    $scope = admin_scope_code();
    if (!$scope) return false;
    return concern_portal_code($concern) === $scope || concern_assigned_scope($concern) === $scope;
}
function admin_can_access_concern_campus(?string $campus): bool {
    // Legacy compatibility for older callers. New concern authorization uses source_portal/assigned_scope.
    if (admin_is_global()) return true;
    $scope = admin_scope_code();
    $value = strtoupper(trim((string)$campus));
    return $scope !== null && ($value === $scope || str_starts_with($value, $scope.' ') || str_starts_with($value, $scope.' —'));
}
function require_admin_code(?string $code): void {
    if (!admin_can_access_code($code)) {
        deny_access('Your account cannot access this portal.');
    }
}
function require_admin_concern(array $concern): void {
    if (!admin_can_access_concern($concern)) {
        deny_access('Your account cannot access this concern.');
    }
}
function require_admin_concern_campus(?string $campus): void {
    if (!admin_can_access_concern_campus($campus)) {
        deny_access('Your account cannot access this concern.');
    }
}

function hero_portal_codes(): array {
    return ['USC' => 'University Student Council'] + admin_campuses();
}
function hero_portal_home_path(string $portal): string {
    $portal = strtoupper(trim($portal));
    return match($portal) {
        'NLUC' => 'campus/nluc.php',
        'MLUC' => 'campus/mluc.php',
        'SLUC' => 'campus/sluc.php',
        'OUS' => 'campus/ous.php',
        default => 'index.php',
    };
}
function normalize_hero_slide_order(PDO $pdo, string $portal = 'USC'): void {
    $portal = strtoupper(trim($portal));
    if (!isset(hero_portal_codes()[$portal])) $portal = 'USC';
    $st = $pdo->prepare("SELECT id, sort_order FROM hero_slides WHERE UPPER(portal_code)=? ORDER BY sort_order, id");
    $st->execute([$portal]);
    $rows = $st->fetchAll();
    if (!$rows) return;

    $updates = [];
    foreach ($rows as $index => $row) {
        $expected = $index+1;
        if ((int)$row['sort_order'] !== $expected) {
            $updates[] = [(int)$row['id'], $expected];
        }
    }
    if (!$updates) return;

    $started = !$pdo->inTransaction();
    if ($started) $pdo->beginTransaction();
    try {
        $up = $pdo->prepare("UPDATE hero_slides SET sort_order=? WHERE id=? AND UPPER(portal_code)=?");
        foreach ($updates as [$id, $position]) $up->execute([$position, $id, $portal]);
        if ($started) $pdo->commit();
    } catch (Throwable $e) {
        if ($started && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
function seed_hero_portal(PDO $pdo, string $portal): void {
    $portal = strtoupper(trim($portal));
    $portals = hero_portal_codes();
    if (!isset($portals[$portal])) return;
    $st = $pdo->prepare('SELECT COUNT(*) FROM hero_slides WHERE UPPER(portal_code)=?');
    $st->execute([$portal]);
    if ((int)$st->fetchColumn()>0) return;

    $name = $portals[$portal];
    if ($portal === 'USC') {
        $rows = [
        ['green', 'USC DIGITAL STUDENT SERVICES', 'Your voice deserves a clear channel.', 'Submit student concerns through E-Sumbong and follow their progress through one simple USC service portal.', 'Open E-Sumbong', 'esumbong/esumbong.php', 'Track Concern', 'esumbong/track.php', 'card', 'E-SUMBONG', 'ONLINE', 'Speak. Submit. Be heard.', 'A direct, organized channel for concerns, suggestions, complaints, and feedback.', 1],
        ['green', 'USC STUDENT RESOURCES', 'Services and information, easier to access.', 'Use the USC portal to reach student services, official announcements, concern tracking, and campus information.', 'Student Services', 'esumbong/esumbong.php', 'View Updates', 'news/updates.php', 'card', 'USC RESOURCES', 'ONLINE', 'Student Resources', 'Essential student services and official information in one accessible portal.', 2],
        ['blue', 'USC NEWS & UPDATES', 'Stay informed without the clutter.', 'See important USC announcements and university-wide updates in one focused news feed.', 'Latest Updates', '#latest', 'All News', 'news/updates.php', 'card', 'USC NEWS', 'LIVE', 'Stay connected.', 'Important student information in one place.', 3]
        ];
    } else {
        $q = '?campus='.rawurlencode($portal);
        $rows = [
        ['green', strtoupper($name).' • STUDENT SERVICES', 'Your campus voice deserves a clear channel.', 'Access student services, submit concerns, and follow updates for '.$name.' through its dedicated portal.', 'Open E-Sumbong', 'esumbong/esumbong.php'.$q, 'Track Concern', 'esumbong/track.php'.$q, 'card', strtoupper($name), 'ONLINE', $name, 'A dedicated student-services homepage for this campus.', 1],
        ['green', strtoupper($name).' • STUDENT RESOURCES', 'Campus services, easier to access.', 'Reach student services, official announcements, concern tracking, and campus information for '.$name.'.', 'Student Services', 'esumbong/esumbong.php'.$q, 'View Updates', 'news/updates.php'.$q, 'card', 'CAMPUS RESOURCES', 'ONLINE', $name.' Resources', 'Essential campus services and official information in one accessible portal.', 2],
        ['blue', strtoupper($name).' NEWS & UPDATES', 'Stay connected to your campus.', 'See announcements, service notices, campus stories, and council updates from '.$name.'.', 'Latest Updates', '#latest', 'All News', 'news/updates.php'.$q, 'card', 'CAMPUS NEWS', 'LIVE', 'Stay connected.', 'Important campus information in one place.', 3]
        ];
    }
    $ins = $pdo->prepare("INSERT INTO hero_slides(portal_code,theme,eyebrow,title,description,primary_label,primary_url,secondary_label,secondary_url,panel_type,panel_kicker,panel_status,panel_title,panel_description,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)");
    foreach ($rows as $r) $ins->execute(array_merge([$portal], $r));
    normalize_hero_slide_order($pdo, $portal);
}

function ensure_hero_slides_table(PDO $pdo): void {
    if (!app_schema_mutation_allowed()) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS hero_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portal_code VARCHAR(10) NOT NULL DEFAULT 'USC',
    theme VARCHAR(30) NOT NULL DEFAULT 'green',
    eyebrow VARCHAR(160) NOT NULL,
    title VARCHAR(220) NOT NULL,
    description TEXT NOT NULL,
    primary_label VARCHAR(80) NULL,
    primary_url VARCHAR(255) NULL,
    secondary_label VARCHAR(80) NULL,
    secondary_url VARCHAR(255) NULL,
    button_mode VARCHAR(20) NOT NULL DEFAULT 'both',
    panel_type VARCHAR(30) NOT NULL DEFAULT 'card',
    panel_kicker VARCHAR(80) NULL,
    panel_status VARCHAR(40) NULL,
    panel_title VARCHAR(180) NULL,
    panel_description TEXT NULL,
    panel_image VARCHAR(255) NULL,
    panel_media_id INT NULL,
    background_image VARCHAR(255) NULL,
    background_media_id INT NULL,
    background_position_x TINYINT UNSIGNED NOT NULL DEFAULT 50,
    background_position_y TINYINT UNSIGNED NOT NULL DEFAULT 50,
    display_mode VARCHAR(30) NOT NULL DEFAULT 'standard',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hero_portal_order(portal_code,sort_order,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $heroColumns = [];
        foreach ($pdo->query("SHOW COLUMNS FROM hero_slides")->fetchAll() as $column) {
            $heroColumns[$column['Field']] = true;
        }
        if (!isset($heroColumns['portal_code'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN portal_code VARCHAR(10) NOT NULL DEFAULT 'USC' AFTER id");
        if (!isset($heroColumns['panel_image'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN panel_image VARCHAR(255) NULL AFTER panel_description");
        if (!isset($heroColumns['panel_media_id'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN panel_media_id INT NULL AFTER panel_image");
        if (!isset($heroColumns['background_image'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN background_image VARCHAR(255) NULL AFTER panel_media_id");
        if (!isset($heroColumns['background_media_id'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN background_media_id INT NULL AFTER background_image");
        if (!isset($heroColumns['background_position_x'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN background_position_x TINYINT UNSIGNED NOT NULL DEFAULT 50 AFTER background_image");
        if (!isset($heroColumns['background_position_y'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN background_position_y TINYINT UNSIGNED NOT NULL DEFAULT 50 AFTER background_position_x");
        if (!isset($heroColumns['display_mode'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN display_mode VARCHAR(30) NOT NULL DEFAULT 'standard' AFTER background_position_y");
        if (!isset($heroColumns['button_mode'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN button_mode VARCHAR(20) NOT NULL DEFAULT 'both' AFTER secondary_url");
        if (!isset($heroColumns['updated_at'])) $pdo->exec("ALTER TABLE hero_slides ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $pdo->exec("UPDATE hero_slides SET portal_code='USC' WHERE portal_code IS NULL OR TRIM(portal_code)=''");
        $pdo->exec("UPDATE hero_slides SET panel_type='card' WHERE panel_type IN ('esumbong','metrics')");
        try {
            $pdo->exec("ALTER TABLE hero_slides ADD INDEX idx_hero_portal_order(portal_code,sort_order,id)");
        } catch (PDOException $ignored) {
        }
    } catch (PDOException $e) {
        // The manager still works on fresh installs even when ALTER permissions are restricted.
    }

    foreach (array_keys(hero_portal_codes()) as $portal) seed_hero_portal($pdo, $portal);
}
function can_manage_homepage(): bool {
    return admin_can_permission('homepage.manage');
}

function ensure_hero_promotion_requests_table(PDO $pdo): void {
    if (!app_schema_mutation_allowed()) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS hero_promotion_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    source_slide_id INT NOT NULL,
    source_portal VARCHAR(10) NOT NULL,
    target_portal VARCHAR(10) NOT NULL DEFAULT 'USC',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    request_note TEXT NULL,
    review_note TEXT NULL,
    requested_by INT NULL,
    requested_by_name VARCHAR(150) NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_by_name VARCHAR(150) NULL,
    reviewed_at DATETIME NULL,
    display_from DATETIME NULL,
    display_until DATETIME NULL,
    display_order INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hero_promo_source(source_slide_id,source_portal,status),
    INDEX idx_hero_promo_target(target_portal,status,display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    try {
        $pdo->exec("UPDATE hero_promotion_requests SET status='expired' WHERE status='approved' AND display_until IS NOT NULL AND display_until < NOW()");
    } catch (PDOException $ignored) {
    }
}

function hero_promotion_status_label(string $status): string {
    return match(strtolower(trim($status))) {
        'approved' => 'Approved',
        'changes_requested' => 'Changes requested',
        'needs_reapproval' => 'Needs reapproval',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
        'revoked' => 'Revoked',
        'withdrawn' => 'Withdrawn',
        default => 'Pending',
    };
}

function hero_promotion_url(?string $url, string $sourcePortal): string {
    $url = trim((string)$url);
    $sourcePortal = strtoupper(trim($sourcePortal));
    if ($url === '') return $url;
    if (preg_match('~^(?:https?:)?//|^(?:mailto:|tel:)~i', $url)) return $url;
    if (str_starts_with($url, '#')) return hero_portal_home_path($sourcePortal).$url;

    // Keep hero buttons created before the folder cleanup working without
    // leaving duplicate public PHP files in the project root.
    $legacyRoutes = [
    'updates.php' => 'news/updates.php',
    'article.php' => 'news/article.php',
    'esumbong.php' => 'esumbong/esumbong.php',
    'track.php' => 'esumbong/track.php',
    ];
    foreach ($legacyRoutes as $legacy => $current) {
        if (preg_match('~^(?:\./)?'.preg_quote($legacy, '~').'(?=\?|#|$)~i', $url)) {
            $url = preg_replace('~^(?:\./)?'.preg_quote($legacy, '~').'~i', $current, $url, 1);
            break;
        }
    }
    if ($sourcePortal === 'USC') return $url;
    if (preg_match('~(?:^|[?&])campus=~i', $url)) return $url;

    $fragment = '';
    if (str_contains($url, '#')) {
        [$url, $fragment] = explode('#', $url, 2);
        $fragment = '#'.$fragment;
    }
    $path = parse_url($url, PHP_URL_PATH) ?: '';
    $base = basename($path);
    if (in_array($base, ['index.php'], true)) return hero_portal_home_path($sourcePortal).$fragment;
    if (in_array($base, ['esumbong.php', 'track.php', 'updates.php', 'article.php'], true)) {
        $url.=str_contains($url, '?')?'&':'?';
        $url.='campus='.rawurlencode($sourcePortal);
    }
    return $url.$fragment;
}

function hero_public_slides(PDO $pdo, string $portal): array {
    ensure_hero_slides_table($pdo);
    ensure_hero_promotion_requests_table($pdo);
    $portal = strtoupper(trim($portal));
    if (!isset(hero_portal_codes()[$portal])) $portal = 'USC';
    $st = $pdo->prepare("SELECT * FROM hero_slides WHERE is_active=1 AND UPPER(portal_code)=? AND LOWER(COALESCE(theme,''))<>'tala' AND LOWER(COALESCE(primary_url,'')) NOT LIKE '%tala.php%' AND LOWER(COALESCE(secondary_url,'')) NOT LIKE '%tala.php%' ORDER BY sort_order,id");
    $st->execute([$portal]);
    $slides = $st->fetchAll();
    foreach ($slides as &$slide) {
        foreach (['background_image', 'panel_image'] as $imageField) {
            $imagePath = trim((string)($slide[$imageField] ?? ''));
            if ($imagePath !== '' && str_starts_with($imagePath, 'uploads/hero/') && !is_file(app_root($imagePath))) $slide[$imageField] = null;
        }
        $slide['_promotion_request_id'] = null;
        $slide['_promotion_source_portal'] = $portal;
        $slide['_promotion_source_name'] = portal_display_name($portal);
    }
    unset($slide);
    if ($portal !== 'USC') return $slides;

    $promo = $pdo->query("SELECT pr.id AS promotion_request_id,pr.source_portal,pr.display_order,pr.display_from,pr.display_until,h.*
    FROM hero_promotion_requests pr
    INNER JOIN hero_slides h ON h.id=pr.source_slide_id AND UPPER(h.portal_code)=UPPER(pr.source_portal)
    WHERE UPPER(pr.target_portal)='USC' AND pr.status='approved' AND h.is_active=1 AND LOWER(COALESCE(h.theme,''))<>'tala' AND LOWER(COALESCE(h.primary_url,'')) NOT LIKE '%tala.php%' AND LOWER(COALESCE(h.secondary_url,'')) NOT LIKE '%tala.php%'
    AND (pr.display_from IS NULL OR pr.display_from<=NOW())
    AND (pr.display_until IS NULL OR pr.display_until>=NOW())
    ORDER BY pr.display_order,pr.id")->fetchAll();
    foreach ($promo as $p) {
        foreach (['background_image', 'panel_image'] as $imageField) {
            $imagePath = trim((string)($p[$imageField] ?? ''));
            if ($imagePath !== '' && str_starts_with($imagePath, 'uploads/hero/') && !is_file(app_root($imagePath))) $p[$imageField] = null;
        }
        $p['_promotion_request_id'] = (int)$p['promotion_request_id'];
        $p['_promotion_source_portal'] = strtoupper((string)$p['source_portal']);
        $p['_promotion_source_name'] = portal_display_name($p['_promotion_source_portal']);
        $position = max(1, (int)($p['display_order'] ?? count($slides)+1));
        array_splice($slides, min($position-1, count($slides)), 0, [$p]);
    }
    return array_values($slides);
}

function upload_hero_image(array $file, string $prefix = 'hero'): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size>8*1024*1024) throw new RuntimeException('Hero images must be 8 MB or smaller.');
    $tmp = (string)($file['tmp_name'] ?? '');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Use a JPG, PNG, or WebP image.');
    $dir = dirname(__DIR__).'/uploads/hero';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Unable to create the hero upload folder.');
    $name = $prefix.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
    if (!move_uploaded_file($tmp, $dir.'/'.$name)) throw new RuntimeException('Unable to save the uploaded image.');
    $relative = 'uploads/hero/'.$name;
    if (function_exists('media_optimize_file')) media_optimize_file($relative, 3200);
    return $relative;
}
function delete_hero_image(?string $path): void {
    $path = trim((string)$path);
    if ($path === '' || !str_starts_with($path, 'uploads/hero/')) return;
    $full = dirname(__DIR__).'/'.$path;
    if (is_file($full)) @unlink($full);
}

/* Generic file helpers */
function file_type_label(?string $mime, ?string $name = ''): string {
    $ext = strtoupper((string)pathinfo((string)$name, PATHINFO_EXTENSION));
    if ($ext !== '') return $ext;
    if (str_starts_with((string)$mime, 'image/')) return 'IMG';
    if ((string)$mime === 'application/pdf') return 'PDF';
    return 'FILE';
}

function format_file_size(int $bytes): string {
    if ($bytes >= 1024*1024) return rtrim(rtrim(number_format($bytes/(1024*1024), 1), '0'), '.').' MB';
    if ($bytes >= 1024) return (string)round($bytes/1024).' KB';
    return $bytes.' B';
}

/* Shared media library */
function media_portal_codes(): array {
    return ['USC' => 'University Student Council'] + admin_campuses();
}
function media_asset(PDO $pdo, int $id, bool $includeDeleted = false): ?array {
    ensure_admin_platform_tables($pdo);
    $sql = 'SELECT * FROM media_library WHERE id=?'.($includeDeleted?'':' AND deleted_at IS NULL');
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch();
    return $row?:null;
}
function media_assets(PDO $pdo, ?string $portal = null, string $kind = 'all'): array {
    ensure_admin_platform_tables($pdo);
    $where = ['deleted_at IS NULL'];
    $params = [];
    if ($portal !== null && $portal !== '') {
        if (strtoupper($portal) === 'USC') $where[] = "(UPPER(campus)=? OR campus IS NULL OR campus='')";
        else $where[] = 'UPPER(campus)=?';
        $params[] = strtoupper($portal);
    }
    if ($kind === 'image') $where[] = "mime_type LIKE 'image/%'";
    elseif ($kind === 'video') $where[] = "mime_type LIKE 'video/%'";
    elseif ($kind === 'document') $where[] = "mime_type NOT LIKE 'image/%' AND mime_type NOT LIKE 'video/%'";
    $st = $pdo->prepare('SELECT * FROM media_library WHERE '.implode(' AND ', $where).' ORDER BY created_at DESC,id DESC');
    $st->execute($params);
    return $st->fetchAll();
}
function media_asset_is_image(array $asset): bool {
    return str_starts_with((string)($asset['mime_type'] ?? ''), 'image/');
}
function media_asset_is_video(array $asset): bool {
    $mime = strtolower((string)($asset['mime_type'] ?? ''));
    $ext = strtolower((string)pathinfo((string)($asset['original_name'] ?? $asset['file_path'] ?? ''), PATHINFO_EXTENSION));
    return str_starts_with($mime, 'video/') || $mime === 'application/mp4' || in_array($ext, ['mp4', 'webm', 'mov'], true);
}
function media_asset_allowed_for_admin(array $asset): bool {
    return admin_can_access_code($asset['campus']?:'USC');
}
function attach_media_to_post(PDO $pdo, int $postId, array $mediaIds, int $maxFiles = 100, ?string $portal = null): array {
    ensure_post_images_table($pdo);
    ensure_admin_platform_tables($pdo);
    $ids = array_values(array_unique(array_filter(array_map('intval', $mediaIds), fn($v) => $v>0)));
    if (!$ids) return [];
    $existing = post_images($pdo, $postId);
    $remaining = max(0, $maxFiles-count($existing));
    $sort = post_media_next_sort_order($pdo, $postId);
    $errors = [];
    $already = array_map('intval', array_filter(array_column($existing, 'media_id')));
    foreach ($ids as $id) {
        if (in_array($id, $already, true)) continue;
        if ($remaining <= 0) {
            $errors[] = 'Maximum of '.$maxFiles.' publication images reached.';
            break;
        }
        $asset = media_asset($pdo, $id);
        if (!$asset || !media_asset_is_image($asset) || !media_asset_allowed_for_admin($asset) || ($portal !== null && strtoupper(trim((string)($asset['campus']?:'USC'))) !== strtoupper($portal))) {
            $errors[] = 'One selected Media Library image is not available for this portal.';
            continue;
        }
        $st = $pdo->prepare('INSERT INTO post_images(post_id,file_path,original_name,media_id,sort_order) VALUES(?,?,?,?,?)');
        $st->execute([$postId, $asset['file_path'], $asset['original_name'], $id, $sort++]);
        $remaining--;
    }
    return $errors;
}

function attach_media_videos_to_post(PDO $pdo, int $postId, array $mediaIds, ?string $portal = null): array {
    if (!db_table_exists($pdo, 'post_videos')) return $mediaIds?['Video support needs the latest database migration. Run it from Maintenance, then try again.']:[];
    ensure_admin_platform_tables($pdo);
    $ids = array_values(array_unique(array_filter(array_map('intval', $mediaIds), fn($v) => $v>0)));
    if (!$ids) return [];
    $existing = post_videos($pdo, $postId);
    $sort = post_media_next_sort_order($pdo, $postId);
    $errors = [];
    $already = array_map('intval', array_filter(array_column($existing, 'media_id')));
    foreach ($ids as $id) {
        if (in_array($id, $already, true)) continue;
        $asset = media_asset($pdo, $id);
        if (!$asset || !media_asset_is_video($asset) || !media_asset_allowed_for_admin($asset) || ($portal !== null && strtoupper(trim((string)($asset['campus']?:'USC'))) !== strtoupper($portal))) {
            $errors[] = 'One selected Media Library video is not available for this portal.';
            continue;
        }
        if ((int)($asset['file_size'] ?? 0) > 250*1024*1024) {
            $errors[] = basename((string)($asset['original_name'] ?? 'Video')).' exceeds the 250 MB publication video limit.';
            continue;
        }
        $st = $pdo->prepare('INSERT INTO post_videos(post_id,file_path,original_name,media_id,mime_type,file_size,sort_order) VALUES(?,?,?,?,?,?,?)');
        $st->execute([$postId, $asset['file_path'], $asset['original_name'], $id, $asset['mime_type'], (int)$asset['file_size'], $sort++]);
    }
    return $errors;
}

function media_usage_counts(PDO $pdo, int $mediaId): array {
    $posts = $legacy = $hero = 0;
    try {
        ensure_post_images_table($pdo);
        $st = $pdo->prepare('SELECT COUNT(*) FROM post_images WHERE media_id=?');
        $st->execute([$mediaId]);
        $posts = (int)$st->fetchColumn();
        if (db_table_exists($pdo, 'post_videos')) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM post_videos WHERE media_id=?');
            $st->execute([$mediaId]);
            $posts += (int)$st->fetchColumn();
        }
    } catch (Throwable $e) {
    }
    // Preserve references from retired archive records if that legacy table exists.
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM tala_attachments WHERE media_id=?');
        $st->execute([$mediaId]);
        $legacy = (int)$st->fetchColumn();
    } catch (Throwable $e) {
    }
    try {
        ensure_hero_slides_table($pdo);
        $st = $pdo->prepare('SELECT COUNT(*) FROM hero_slides WHERE panel_media_id=? OR background_media_id=?');
        $st->execute([$mediaId, $mediaId]);
        $hero = (int)$st->fetchColumn();
    } catch (Throwable $e) {
    }
    return ['posts' => $posts, 'legacy' => $legacy, 'hero' => $hero, 'total' => $posts+$legacy+$hero];
}
function purge_media_asset(PDO $pdo, int $mediaId): bool {
    $asset = media_asset($pdo, $mediaId, true);
    if (!$asset) return false;
    $usage = media_usage_counts($pdo, $mediaId);
    if ($usage['total']>0) return false;
    $full = dirname(__DIR__).'/'.ltrim((string)$asset['file_path'], '/');
    if (is_file($full)) @unlink($full);
    $pdo->prepare('DELETE FROM media_library WHERE id=?')->execute([$mediaId]);
    return true;
}

/* Professional administration platform services */

function admin_current_ip(): string {
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return substr($ip !== ''?$ip:'Unknown', 0, 64);
}
function admin_user_agent(): string {
    return substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device')), 0, 500);
}
function admin_device_label(?string $ua): string {
    $ua = (string)$ua;
    $browser = str_contains($ua, 'Edg/')?'Edge':(str_contains($ua, 'Chrome/')?'Chrome':(str_contains($ua, 'Firefox/')?'Firefox':(str_contains($ua, 'Safari/')?'Safari':'Browser')));
    $os = str_contains($ua, 'Windows')?'Windows':(str_contains($ua, 'Mac OS')?'macOS':(str_contains($ua, 'Android')?'Android':(str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')?'iOS':(str_contains($ua, 'Linux')?'Linux':'Device'))));
    return $browser.' on '.$os;
}
function admin_session_register(PDO $pdo, int $adminId): void {
    ensure_admin_platform_tables($pdo);
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['admin_session_key'])) $_SESSION['admin_session_key'] = bin2hex(random_bytes(24));
    $keyHash = hash('sha256', (string)$_SESSION['admin_session_key']);
    $sessionHash = hash('sha256', session_id());
    $st = $pdo->prepare("INSERT INTO admin_sessions(admin_id,session_key_hash,php_session_hash,ip_address,user_agent,last_seen_at,created_at,revoked_at)
    VALUES(?,?,?,?,?,NOW(),NOW(),NULL)
    ON DUPLICATE KEY UPDATE php_session_hash=VALUES(php_session_hash),ip_address=VALUES(ip_address),user_agent=VALUES(user_agent),last_seen_at=NOW(),revoked_at=NULL");
    $st->execute([$adminId, $keyHash, $sessionHash, admin_current_ip(), admin_user_agent()]);
    $_SESSION['admin_session_touch'] = time();
}
function admin_session_is_valid(PDO $pdo, int $adminId): bool {
    ensure_admin_platform_tables($pdo);
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['admin_session_key'])) {
        admin_session_register($pdo, $adminId);
        return true;
    }
    $keyHash = hash('sha256', (string)$_SESSION['admin_session_key']);
    $st = $pdo->prepare("SELECT id,revoked_at,last_seen_at FROM admin_sessions WHERE admin_id=? AND session_key_hash=? LIMIT 1");
    $st->execute([$adminId, $keyHash]);
    $row = $st->fetch();
    if (!$row || !empty($row['revoked_at'])) return false;
    $idleHours = max(1, min(168, (int)(system_setting($pdo, 'session_idle_hours', '12') ?? 12)));
    if (!empty($row['last_seen_at']) && strtotime((string)$row['last_seen_at']) < time()-($idleHours*3600)) {
        $pdo->prepare("UPDATE admin_sessions SET revoked_at=NOW() WHERE id=?")->execute([(int)$row['id']]);
        return false;
    }
    if ((int)($_SESSION['admin_session_touch'] ?? 0)<time()-60) {
        $pdo->prepare("UPDATE admin_sessions SET last_seen_at=NOW(),ip_address=?,user_agent=? WHERE id=?")->execute([admin_current_ip(), admin_user_agent(), (int)$row['id']]);
        $_SESSION['admin_session_touch'] = time();
    }
    return true;
}
function admin_revoke_current_session(PDO $pdo): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_session_key'])) return;
    try {
        ensure_admin_platform_tables($pdo);
        $pdo->prepare("UPDATE admin_sessions SET revoked_at=NOW() WHERE admin_id=? AND session_key_hash=?")
        ->execute([(int)$_SESSION['admin_id'], hash('sha256', (string)$_SESSION['admin_session_key'])]);
    } catch (Throwable $ignored) {
    }
}
function admin_revoke_other_sessions(PDO $pdo, int $adminId): int {
    ensure_admin_platform_tables($pdo);
    $currentHash = !empty($_SESSION['admin_session_key'])?hash('sha256', (string)$_SESSION['admin_session_key']):'';
    $st = $pdo->prepare("UPDATE admin_sessions SET revoked_at=NOW() WHERE admin_id=? AND revoked_at IS NULL AND session_key_hash<>?");
    $st->execute([$adminId, $currentHash]);
    return $st->rowCount();
}
function admin_revoke_all_sessions(PDO $pdo, int $adminId): int {
    ensure_admin_platform_tables($pdo);
    $st = $pdo->prepare("UPDATE admin_sessions SET revoked_at=NOW() WHERE admin_id=? AND revoked_at IS NULL");
    $st->execute([$adminId]);
    return $st->rowCount();
}
function admin_active_sessions(PDO $pdo, int $adminId): array {
    ensure_admin_platform_tables($pdo);
    $st = $pdo->prepare("SELECT * FROM admin_sessions WHERE admin_id=? AND revoked_at IS NULL ORDER BY last_seen_at DESC,created_at DESC");
    $st->execute([$adminId]);
    return $st->fetchAll();
}
function admin_login_attempt_key(string $username, string $ip): string {
    return hash('sha256', strtolower(trim($username)).'|'.trim($ip));
}
function admin_login_lock_state(PDO $pdo, string $username, string $ip): array {
    ensure_admin_platform_tables($pdo);
    $key = admin_login_attempt_key($username, $ip);
    $st = $pdo->prepare("SELECT attempts,locked_until FROM admin_login_attempts WHERE attempt_key=? LIMIT 1");
    $st->execute([$key]);
    $row = $st->fetch();
    $lockedUntil = $row && !empty($row['locked_until'])?strtotime((string)$row['locked_until']):0;
    return ['locked' => $lockedUntil>time(), 'locked_until' => $lockedUntil, 'attempts' => (int)($row['attempts'] ?? 0)];
}
function admin_login_failure(PDO $pdo, string $username, string $ip): void {
    ensure_admin_platform_tables($pdo);
    $max = max(3, min(10, (int)(system_setting($pdo, 'login_max_attempts', '5') ?? 5)));
    $minutes = max(1, min(60, (int)(system_setting($pdo, 'login_lockout_minutes', '5') ?? 5)));
    $key = admin_login_attempt_key($username, $ip);
    $st = $pdo->prepare("SELECT attempts FROM admin_login_attempts WHERE attempt_key=? LIMIT 1");
    $st->execute([$key]);
    $attempts = (int)($st->fetchColumn()?:0)+1;
    $locked = $attempts >= $max?date('Y-m-d H:i:s', time()+$minutes*60):null;
    $adminId = null;
    if (trim($username) !== '') {
        $adminLookup = $pdo->prepare("SELECT id FROM admins WHERE username=? LIMIT 1");
        $adminLookup->execute([$username]);
        $found = $adminLookup->fetchColumn();
        if ($found !== false) $adminId = (int)$found;
    }
    if (db_column_exists($pdo, 'admin_login_attempts', 'admin_id')) {
        $st = $pdo->prepare("INSERT INTO admin_login_attempts(attempt_key,admin_id,username,ip_address,attempts,locked_until,updated_at) VALUES(?,?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE admin_id=VALUES(admin_id),username=VALUES(username),ip_address=VALUES(ip_address),attempts=VALUES(attempts),locked_until=VALUES(locked_until),updated_at=NOW()");
        $st->execute([$key, $adminId, substr($username, 0, 150), substr($ip, 0, 64), $attempts, $locked]);
    } else {
        $st = $pdo->prepare("INSERT INTO admin_login_attempts(attempt_key,username,ip_address,attempts,locked_until,updated_at) VALUES(?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE username=VALUES(username),ip_address=VALUES(ip_address),attempts=VALUES(attempts),locked_until=VALUES(locked_until),updated_at=NOW()");
        $st->execute([$key, substr($username, 0, 150), substr($ip, 0, 64), $attempts, $locked]);
    }
}
function admin_login_success(PDO $pdo, string $username, string $ip): void {
    ensure_admin_platform_tables($pdo);
    $pdo->prepare("DELETE FROM admin_login_attempts WHERE attempt_key=?")->execute([admin_login_attempt_key($username, $ip)]);
}
function admin_base32_encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    $out = '';
    foreach (str_split($data) as $c) $bits.=str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    foreach (str_split($bits, 5) as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $out.=$alphabet[bindec($chunk)];
    }
    return $out;
}
function admin_base32_decode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper(preg_replace('/[^A-Z2-7]/', '', $data));
    $bits = '';
    $out = '';
    foreach (str_split($data) as $c) {
        $pos = strpos($alphabet, $c);
        if ($pos === false) continue;
        $bits.=str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    foreach (str_split($bits, 8) as $chunk) {
        if (strlen($chunk)<8) break;
        $out.=chr(bindec($chunk));
    }
    return $out;
}
function admin_totp_secret(): string {
    return admin_base32_encode(random_bytes(20));
}
function admin_totp_code(string $secret, ?int $time = null): string {
    $decodedSecret = app_decrypt_sensitive($secret);
    if ($decodedSecret !== null) $secret = $decodedSecret;
    $time = $time ?? time();
    $counter = intdiv($time, 30);
    $bin = '';
    for ($i = 7; $i >= 0; $i--) $bin.=chr(($counter>>($i*8))&0xff);
    $hash = hash_hmac('sha1', $bin, admin_base32_decode($secret), true);
    $offset = ord($hash[19])&0xf;
    $value = ((ord($hash[$offset])&0x7f)<<24)|((ord($hash[$offset+1])&0xff)<<16)|((ord($hash[$offset+2])&0xff)<<8)|(ord($hash[$offset+3])&0xff);
    return str_pad((string)($value%1000000), 6, '0', STR_PAD_LEFT);
}
function admin_verify_totp(string $secret, string $code): bool {
    $decodedSecret = app_decrypt_sensitive($secret);
    if ($decodedSecret !== null) $secret = $decodedSecret;
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6 || trim($secret) === '') return false;
    foreach ([-30, 0, 30] as $offset) if (hash_equals(admin_totp_code($secret, time()+$offset), $code)) return true;
    return false;
}
function admin_notification_categories(): array {
    return [
    'security' => 'Security & account access',
    'concerns' => 'E-Sumbong concerns',
    'content' => 'Publishing & media',
    'promotions' => 'Homepage promotions',
    'system' => 'System administration',
    'general' => 'General updates',
    ];
}
function admin_notification_preferences(PDO $pdo, int $adminId): array {
    ensure_admin_platform_tables($pdo);
    $defaults = array_fill_keys(array_keys(admin_notification_categories()), 1);
    $st = $pdo->prepare("SELECT category,is_enabled FROM admin_notification_preferences WHERE admin_id=?");
    $st->execute([$adminId]);
    foreach ($st->fetchAll() as $row) $defaults[$row['category']] = (int)$row['is_enabled'];
    return $defaults;
}
function admin_save_notification_preferences(PDO $pdo, int $adminId, array $enabled): void {
    ensure_admin_platform_tables($pdo);
    $st = $pdo->prepare("INSERT INTO admin_notification_preferences(admin_id,category,is_enabled) VALUES(?,?,?) ON DUPLICATE KEY UPDATE is_enabled=VALUES(is_enabled)");
    foreach (admin_notification_categories() as $key => $label) $st->execute([$adminId, $key, in_array($key, $enabled, true)?1:0]);
}
function admin_allowed_notification_categories(PDO $pdo, int $adminId): array {
    $prefs = admin_notification_preferences($pdo, $adminId);
    return array_keys(array_filter($prefs, fn($v) => (int)$v === 1));
}
function ensure_admin_platform_tables(PDO $pdo): void {
    if (!app_schema_mutation_allowed()) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_key_hash CHAR(64) NOT NULL UNIQUE,
    php_session_hash CHAR(64) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    INDEX idx_admin_sessions_admin(admin_id,revoked_at,last_seen_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_login_attempts (
    attempt_key CHAR(64) PRIMARY KEY,
    admin_id INT NULL,
    username VARCHAR(150) NULL,
    ip_address VARCHAR(64) NULL,
    attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_admin_login_admin(admin_id),
    INDEX idx_admin_login_lock(locked_until),
    CONSTRAINT fk_login_attempts_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON UPDATE CASCADE ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notification_preferences (
    admin_id INT NOT NULL,
    category VARCHAR(40) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY(admin_id,category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    admin_name VARCHAR(150) NULL,
    role VARCHAR(40) NULL,
    campus VARCHAR(20) NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(80) NOT NULL,
    description VARCHAR(500) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    old_values LONGTEXT NULL,
    new_values LONGTEXT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_created(created_at),
    INDEX idx_activity_admin(admin_id),
    INDEX idx_activity_module(module)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    role_target VARCHAR(40) NULL,
    campus_target VARCHAR(20) NULL,
    title VARCHAR(180) NOT NULL,
    message VARCHAR(500) NOT NULL,
    link VARCHAR(255) NULL,
    kind VARCHAR(30) NOT NULL DEFAULT 'info',
    category VARCHAR(40) NOT NULL DEFAULT 'general',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_admin(admin_id,is_read),
    INDEX idx_notifications_role(role_target,is_read),
    INDEX idx_notifications_campus(campus_target,is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS media_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(100) NULL,
    file_size INT NULL,
    alt_text VARCHAR(255) NULL,
    uploaded_by INT NULL,
    campus VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    deleted_by INT NULL,
    INDEX idx_media_created(created_at),
    INDEX idx_media_campus(campus)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $activityCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM admin_activity_logs")->fetchAll() as $c) $activityCols[$c['Field']] = true;
        if (!isset($activityCols['old_values'])) $pdo->exec("ALTER TABLE admin_activity_logs ADD COLUMN old_values LONGTEXT NULL AFTER entity_id");
        if (!isset($activityCols['new_values'])) $pdo->exec("ALTER TABLE admin_activity_logs ADD COLUMN new_values LONGTEXT NULL AFTER old_values");
        if (!isset($activityCols['ip_address'])) $pdo->exec("ALTER TABLE admin_activity_logs ADD COLUMN ip_address VARCHAR(64) NULL AFTER new_values");
        if (!isset($activityCols['user_agent'])) $pdo->exec("ALTER TABLE admin_activity_logs ADD COLUMN user_agent VARCHAR(500) NULL AFTER ip_address");

        $notificationCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM admin_notifications")->fetchAll() as $c) $notificationCols[$c['Field']] = true;
        if (!isset($notificationCols['category'])) $pdo->exec("ALTER TABLE admin_notifications ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'general' AFTER kind");

        $historyCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM concern_history")->fetchAll() as $c) $historyCols[$c['Field']] = true;
        if (!isset($historyCols['actor_name'])) $pdo->exec("ALTER TABLE concern_history ADD COLUMN actor_name VARCHAR(150) NULL AFTER admin_id");
        if (!isset($historyCols['action'])) $pdo->exec("ALTER TABLE concern_history ADD COLUMN action VARCHAR(80) NOT NULL DEFAULT 'Updated concern' AFTER actor_name");
        if (!isset($historyCols['old_status'])) $pdo->exec("ALTER TABLE concern_history ADD COLUMN old_status VARCHAR(40) NULL AFTER action");
        if (!isset($historyCols['old_assigned_scope'])) $pdo->exec("ALTER TABLE concern_history ADD COLUMN old_assigned_scope VARCHAR(10) NULL AFTER status");
        if (!isset($historyCols['assigned_scope'])) $pdo->exec("ALTER TABLE concern_history ADD COLUMN assigned_scope VARCHAR(10) NULL AFTER old_assigned_scope");
        if (!isset($historyCols['assigned_to'])) $pdo->exec("ALTER TABLE concern_history ADD COLUMN assigned_to INT NULL AFTER assigned_scope");
    } catch (PDOException $e) {
    }

    try {
        $mediaCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM media_library")->fetchAll() as $c) $mediaCols[$c['Field']] = true;
        if (!isset($mediaCols['deleted_by'])) $pdo->exec("ALTER TABLE media_library ADD COLUMN deleted_by INT NULL AFTER deleted_at");
        $postCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM posts")->fetchAll() as $c) $postCols[$c['Field']] = true;
        if (!isset($postCols['deleted_by'])) $pdo->exec("ALTER TABLE posts ADD COLUMN deleted_by INT NULL");
    } catch (PDOException $e) {
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS concern_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    concern_id INT NOT NULL,
    admin_id INT NULL,
    actor_name VARCHAR(150) NULL,
    action VARCHAR(80) NOT NULL DEFAULT 'Updated concern',
    old_status VARCHAR(40) NULL,
    status VARCHAR(40) NOT NULL,
    old_assigned_scope VARCHAR(10) NULL,
    assigned_scope VARCHAR(10) NULL,
    assigned_to INT NULL,
    public_note TEXT NULL,
    internal_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_concern_history(concern_id,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $adminCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM admins")->fetchAll() as $c) $adminCols[$c['Field']] = true;
        if (!isset($adminCols['last_login'])) $pdo->exec("ALTER TABLE admins ADD COLUMN last_login DATETIME NULL AFTER status");
        if (!isset($adminCols['profile_image'])) $pdo->exec("ALTER TABLE admins ADD COLUMN profile_image VARCHAR(255) NULL AFTER email");
        if (!isset($adminCols['force_password_change'])) $pdo->exec("ALTER TABLE admins ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        if (!isset($adminCols['created_by'])) $pdo->exec("ALTER TABLE admins ADD COLUMN created_by INT NULL AFTER force_password_change");
        if (!isset($adminCols['password_changed_at'])) $pdo->exec("ALTER TABLE admins ADD COLUMN password_changed_at DATETIME NULL AFTER last_login");
        if (!isset($adminCols['two_factor_enabled'])) $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER password_changed_at");
        if (!isset($adminCols['two_factor_secret'])) $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_secret VARCHAR(128) NULL AFTER two_factor_enabled");
    } catch (PDOException $e) {
    }

    try {
        $concernCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM concerns")->fetchAll() as $c) $concernCols[$c['Field']] = true;
        if (!isset($concernCols['source_portal'])) $pdo->exec("ALTER TABLE concerns ADD COLUMN source_portal VARCHAR(10) NOT NULL DEFAULT 'USC' AFTER college");
        if (!isset($concernCols['assigned_scope'])) $pdo->exec("ALTER TABLE concerns ADD COLUMN assigned_scope VARCHAR(10) NOT NULL DEFAULT 'USC' AFTER source_portal");
        $pdo->exec("UPDATE concerns SET source_portal='USC' WHERE source_portal IS NULL OR source_portal='' OR UPPER(source_portal) NOT IN ('USC','NLUC','MLUC','SLUC','OUS')");
        $pdo->exec("UPDATE concerns SET assigned_scope=source_portal WHERE assigned_scope IS NULL OR assigned_scope='' OR UPPER(assigned_scope) NOT IN ('USC','NLUC','MLUC','SLUC','OUS')");
        $concernIndexes = [];
        foreach ($pdo->query("SHOW INDEX FROM concerns")->fetchAll() as $idx) $concernIndexes[$idx['Key_name']] = true;
        if (!isset($concernIndexes['idx_concerns_source_portal'])) $pdo->exec("ALTER TABLE concerns ADD INDEX idx_concerns_source_portal(source_portal)");
        if (!isset($concernIndexes['idx_concerns_assigned_scope'])) $pdo->exec("ALTER TABLE concerns ADD INDEX idx_concerns_assigned_scope(assigned_scope)");
        if (!isset($concernCols['student_urgency'])) $pdo->exec("ALTER TABLE concerns ADD COLUMN student_urgency VARCHAR(20) NULL AFTER concern_type");
        if (!isset($concernCols['priority'])) $pdo->exec("ALTER TABLE concerns ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'Normal' AFTER status");
        if (!isset($concernCols['assigned_to'])) $pdo->exec("ALTER TABLE concerns ADD COLUMN assigned_to INT NULL AFTER priority");
        if (!isset($concernCols['internal_note'])) $pdo->exec("ALTER TABLE concerns ADD COLUMN internal_note TEXT NULL AFTER admin_note");
        if (!isset($concernCols['resolved_at'])) $pdo->exec("ALTER TABLE concerns ADD COLUMN resolved_at DATETIME NULL AFTER internal_note");
        $pdo->exec("ALTER TABLE concerns MODIFY status ENUM('Submitted','Received','Under Review','Referred','In Progress','Action Taken','Resolved','Closed') NOT NULL DEFAULT 'Submitted'");
        $pdo->exec("UPDATE concerns SET status='Under Review' WHERE status='In Review'");
    } catch (PDOException $e) {
    }

    try {
        $postCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM posts")->fetchAll() as $c) $postCols[$c['Field']] = true;
        if (!isset($postCols['deleted_at'])) $pdo->exec("ALTER TABLE posts ADD COLUMN deleted_at DATETIME NULL AFTER updated_at");
        if (!isset($postCols['deleted_by'])) $pdo->exec("ALTER TABLE posts ADD COLUMN deleted_by INT NULL");
        if (!isset($postCols['scheduled_at'])) $pdo->exec("ALTER TABLE posts ADD COLUMN scheduled_at DATETIME NULL AFTER published_at");
        try {
            $pdo->exec("ALTER TABLE posts MODIFY status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'published'");
        } catch (PDOException $ignored) {
        }
    } catch (PDOException $e) {
    }

}

function admin_log(PDO $pdo, string $module, string $action, string $description, ?string $entityType = null, ?int $entityId = null, ?array $oldValues = null, ?array $newValues = null): void {
    ensure_admin_platform_tables($pdo);
    $payload = [
    'admin_id' => $_SESSION['admin_id'] ?? null, 'admin_name' => $_SESSION['admin_name'] ?? null, 'role' => admin_role(), 'campus' => admin_scope_code(),
    'module' => $module, 'action' => $action, 'description' => $description, 'entity_type' => $entityType, 'entity_id' => $entityId,
    'old_values' => $oldValues !== null?json_encode($oldValues, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,
    'new_values' => $newValues !== null?json_encode($newValues, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,
    'ip_address' => admin_current_ip(), 'user_agent' => admin_user_agent(), 'created_at' => date('Y-m-d H:i:s')
    ];
    if (db_column_exists($pdo, 'admin_activity_logs', 'record_hash') && function_exists('audit_hash_payload')) {
        $prev = audit_latest_hash($pdo);
        $hash = audit_hash_payload($payload, $prev);
        $st = $pdo->prepare("INSERT INTO admin_activity_logs(admin_id,admin_name,role,campus,module,action,description,entity_type,entity_id,old_values,new_values,ip_address,user_agent,created_at,prev_hash,record_hash) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->execute([$payload['admin_id'], $payload['admin_name'], $payload['role'], $payload['campus'], $module, $action, $description, $entityType, $entityId, $payload['old_values'], $payload['new_values'], $payload['ip_address'], $payload['user_agent'], $payload['created_at'], $prev, $hash]);
        return;
    }
    $st = $pdo->prepare("INSERT INTO admin_activity_logs(admin_id,admin_name,role,campus,module,action,description,entity_type,entity_id,old_values,new_values,ip_address,user_agent) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$payload['admin_id'], $payload['admin_name'], $payload['role'], $payload['campus'], $module, $action, $description, $entityType, $entityId, $payload['old_values'], $payload['new_values'], $payload['ip_address'], $payload['user_agent']]);
}

function admin_notify(PDO $pdo, string $title, string $message, ?string $link = null, string $kind = 'info', ?string $roleTarget = null, ?string $campusTarget = null, ?int $adminId = null, string $category = 'general'): void {
    ensure_admin_platform_tables($pdo);
    $category = array_key_exists($category, admin_notification_categories())?$category:'general';
    if ($adminId) {
        $prefs = admin_notification_preferences($pdo, $adminId);
        if (isset($prefs[$category]) && !$prefs[$category]) return;
    }
    $st = $pdo->prepare("INSERT INTO admin_notifications(admin_id,role_target,campus_target,title,message,link,kind,category) VALUES(?,?,?,?,?,?,?,?)");
    $st->execute([$adminId, $roleTarget, $campusTarget, $title, $message, $link, $kind, $category]);
    try {
        if (system_setting($pdo, 'notification_email_enabled', '0') === '1' && db_table_exists($pdo, 'notification_email_outbox')) {
            $where = ["status='active'", "email IS NOT NULL", "TRIM(email)<>''"];
            $params = [];
            if ($adminId) {
                $where[] = 'id=?';
                $params[] = $adminId;
            }
            elseif ($roleTarget && $campusTarget) {
                $where[] = 'role=?';
                $params[] = $roleTarget;
                $where[] = '(campus=? OR campus IS NULL)';
                $params[] = $campusTarget;
            }
            elseif ($roleTarget) {
                $where[] = 'role=?';
                $params[] = $roleTarget;
            }
            elseif ($campusTarget) {
                $where[] = "(campus=? OR role IN ('admin','sas_director'))";
                $params[] = $campusTarget;
            }
            else {
                $where[] = "role IN ('admin','sas_director')";
            }
            $em = $pdo->prepare('SELECT DISTINCT id,email FROM admins WHERE '.implode(' AND ', $where));
            $em->execute($params);
            foreach ($em->fetchAll() as $recipient) admin_queue_email($pdo, (string)$recipient['email'], $title, $message.($link?"\n\nOpen the administration portal to review: ".$link:''), $category, (int)$recipient['id']);
        }
    } catch (Throwable $ignored) {
    }
}

function admin_notification_where(array &$params): string {
    $conditions = ["admin_id IS NULL AND role_target IS NULL AND campus_target IS NULL"];
    $id = (int)($_SESSION['admin_id'] ?? 0);
    $role = admin_role();
    $scope = admin_scope_code();
    if ($id) {
        $conditions[] = "admin_id=?";
        $params[] = $id;
    }
    if ($role) {
        $conditions[] = "role_target=?";
        $params[] = $role;
    }
    if ($scope) {
        $conditions[] = "campus_target=?";
        $params[] = $scope;
    }
    $recipient = '('.implode(' OR ', $conditions).')';
    global $pdo;
    $visibleRecipient = (($pdo instanceof PDO) && db_column_exists($pdo, 'admin_notifications', 'dismissed_at'))?'('.$recipient.' AND dismissed_at IS NULL)':$recipient;
    if (!$id) return $visibleRecipient;
    $allowed = ($pdo instanceof PDO)?admin_allowed_notification_categories($pdo, $id):array_keys(admin_notification_categories());
    if (!$allowed) return '('.$visibleRecipient.' AND 1=0)';
    $marks = implode(',', array_fill(0, count($allowed), '?'));
    foreach ($allowed as $category) $params[] = $category;
    return '('.$visibleRecipient.' AND category IN ('.$marks.'))';
}

function admin_unread_notifications(PDO $pdo): int {
    ensure_admin_platform_tables($pdo);
    $params = [];
    $where = admin_notification_where($params);
    $st = $pdo->prepare("SELECT COUNT(*) FROM admin_notifications WHERE is_read=0 AND $where");
    $st->execute($params);
    return (int)$st->fetchColumn();
}

function system_setting(PDO $pdo, string $key, ?string $default = null): ?string {
    ensure_admin_platform_tables($pdo);
    $st = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key=?");
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false?$default:(string)$v;
}
function save_system_setting(PDO $pdo, string $key, ?string $value): void {
    ensure_admin_platform_tables($pdo);
    $st = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value,updated_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by)");
    $st->execute([$key, $value, $_SESSION['admin_id'] ?? null]);
}

function can_view_activity(): bool {
    return admin_can_permission('activity.view');
}
function can_view_reports(): bool {
    return admin_can_permission('reports.view');
}
function can_manage_settings(): bool {
    return admin_can_permission('settings.manage');
}
function can_manage_media(): bool {
    return admin_can_permission('media.manage');
}
function can_manage_content(): bool {
    return admin_can_permission('content.manage');
}
function can_assign_concerns(): bool {
    return admin_can_permission('concerns.assign');
}
function can_route_concerns(): bool {
    // Any administrator who is allowed to assign a concern must also be able to
    // route that concern to another USC/campus unit. This keeps the routing
    // workflow consistent for USC, SAS, advisers, and campus SBO case handlers.
    return can_assign_concerns() || admin_can_permission('concerns.route');
}
function can_close_concerns(): bool {
    return admin_can_permission('concerns.close');
}
function can_manage_backups(): bool {
    return admin_can_permission('backups.manage');
}
function can_manage_migrations(): bool {
    return admin_can_permission('migrations.manage');
}
function can_export_private_concerns(): bool {
    return admin_can_permission('privacy.export');
}

/* V70 operational helpers: non-mutating schema checks, pagination, SLA and privacy */
function db_table_exists(PDO $pdo, string $table): bool {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
        $st->execute([$table]);
        return (int)$st->fetchColumn()>0;
    } catch (Throwable $e) {
        return false;
    }
}
function db_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        $st->execute([$table, $column]);
        return (int)$st->fetchColumn()>0;
    } catch (Throwable $e) {
        return false;
    }
}
function pagination_state(int $total, int $perPage = 25, string $key = 'page'): array {
    $perPage = max(10, min(100, $perPage));
    $pages = max(1, (int)ceil($total/$perPage));
    $page = max(1, min($pages, (int)($_GET[$key] ?? 1)));
    return ['total' => $total, 'per_page' => $perPage, 'pages' => $pages, 'page' => $page, 'offset' => ($page-1)*$perPage];
}
function pagination_query_url(int $page, string $key = 'page'): string {
    $q = $_GET;
    $q[$key] = $page;
    return '?'.http_build_query($q);
}
function render_pagination(array $p, string $key = 'page'): string {
    if (($p['pages'] ?? 1) <= 1) return '';
    $html = '<nav class="pagination" aria-label="Pagination">';
    $page = (int)$p['page'];
    $pages = (int)$p['pages'];
    if ($page>1) $html.='<a href="'.e(pagination_query_url($page-1, $key)).'">← Previous</a>';
    else$html.='<span class="is-disabled">← Previous</span>';
    $start = max(1, $page-2);
    $end = min($pages, $page+2);
    if ($start>1) $html.='<a href="'.e(pagination_query_url(1, $key)).'">1</a>'.($start>2?'<span>…</span>':'');
    for ($i = $start; $i <= $end; $i++) $html.=$i === $page?'<strong aria-current="page">'.$i.'</strong>':'<a href="'.e(pagination_query_url($i, $key)).'">'.$i.'</a>';
    if ($end<$pages) $html.=($end<$pages-1?'<span>…</span>':'').'<a href="'.e(pagination_query_url($pages, $key)).'">'.$pages.'</a>';
    if ($page<$pages) $html.='<a href="'.e(pagination_query_url($page+1, $key)).'">Next →</a>';
    else$html.='<span class="is-disabled">Next →</span>';
    return $html.'</nav>';
}
/**
 * Load the centralized English/Filipino public-language moderation vocabulary.
 */
function language_moderation_terms(): array {
    static $terms = null;
    if (is_array($terms)) return $terms;
    $path = __DIR__.'/profanity.php';
    $loaded = is_file($path) ? require $path : [];
    $terms = is_array($loaded) ? $loaded : [];
    return $terms;
}

/**
 * Build a whole-term pattern that tolerates common punctuation and leet-style
 * substitutions without falling back to unsafe substring matching.
 */
function language_moderation_term_pattern(string $term): string {
    $term = function_exists('mb_strtolower') ? mb_strtolower(trim($term), 'UTF-8') : strtolower(trim($term));
    $chars = preg_split('//u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $pattern = '';
    $pendingWordGap = false;
    $hasLetter = false;
    foreach ($chars as $char) {
        if (preg_match('/\s/u', $char)) {
            $pendingWordGap = true;
            continue;
        }
        if ($hasLetter) $pattern .= $pendingWordGap ? '[\p{Z}\p{P}\p{S}_]+' : '[\p{Z}\p{P}\p{S}_]*';
        $pendingWordGap = false;
        $pattern .= match($char) {
            'a' => '[a4@àáâãäå]',
            'e' => '[e3èéêë]',
            'i' => '[i1ìíîï]',
            'o' => '[o0òóôõö]',
            's' => '[s5$]',
            't' => '[t7+]',
            default => preg_quote($char, '~'),
        };
        $hasLetter = true;
    }
    return '~(?<![\p{L}\p{N}])'.$pattern.'(?![\p{L}\p{N}])~iu';
}

/**
 * Return internal match metadata. Matched words are intentionally not shown to
 * students, which avoids turning validation messages into a bypass guide.
 */
function language_moderation_matches(string $text): array {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (trim($text) === '') return [];
    $hits = [];
    foreach (language_moderation_terms() as $language => $terms) {
        if (!is_array($terms)) continue;
        foreach ($terms as $term) {
            $term = trim((string)$term);
            if ($term === '') continue;
            if (@preg_match(language_moderation_term_pattern($term), $text) === 1) {
                $hits[] = ['language' => (string)$language, 'term' => $term];
            }
        }
    }
    return $hits;
}

/**
 * Public E-Sumbong language policy:
 * - Suggestion/Feedback: block profanity while moderation is enabled.
 * - Concern/Complaint: allow and flag by default so a student can quote abusive
 *   wording as evidence. System Administrators may switch formal cases to block.
 */
function concern_language_moderation(PDO $pdo, string $concernType, string $subject, string $message): array {
    $enabled = (string)(system_setting($pdo, 'esumbong_language_moderation_enabled', '1') ?? '1') === '1';
    if (!$enabled) return ['detected' => false, 'action' => 'allow', 'matches' => []];

    $matches = language_moderation_matches($subject."\n".$message);
    if (!$matches) return ['detected' => false, 'action' => 'allow', 'matches' => []];

    $type = strtolower(trim($concernType));
    $formal = in_array($type, ['concern', 'complaint'], true);
    $formalMode = strtolower((string)(system_setting($pdo, 'esumbong_formal_language_mode', 'flag') ?? 'flag'));
    if (!in_array($formalMode, ['flag', 'block'], true)) $formalMode = 'flag';

    $action = ($formal && $formalMode === 'flag') ? 'flag' : 'block';
    return ['detected' => true, 'action' => $action, 'matches' => $matches];
}

/** Internal compatibility name: these hours now define a suggested review reminder, not an SLA/deadline. */
function concern_sla_hours(PDO $pdo, string $priority): int {
    $key = match($priority) {
        'Urgent' => 'esumbong_sla_urgent_hours', 'High' => 'esumbong_sla_high_hours', 'Low' => 'esumbong_sla_low_hours', default => 'esumbong_sla_normal_hours'
    };
    $fallback = match($priority) {
        'Urgent' => 24, 'High' => 48, 'Low' => 168, default => 120
    };
    try {
        return max(1, min(720, (int)(system_setting($pdo, $key, (string)$fallback) ?? $fallback)));
    } catch (Throwable $e) {
        return $fallback;
    }
}
/** Suggested reminder timestamp retained in the existing due_at column for schema compatibility. */
function concern_default_due_at(PDO $pdo, string $priority, ?string $from = null): string {
    $base = $from?strtotime($from):time();
    if (!$base) $base = time();
    return date('Y-m-d H:i:s', $base+concern_sla_hours($pdo, $priority)*3600);
}

/**
 * Determine the initial workflow priority from the student's submission type.
 * Concern/Complaint may be raised by the student urgency indicator; Suggestion
 * and Feedback intentionally remain low-priority, lightweight submissions.
 */
function concern_type_default_priority(string $concernType, string $urgency = 'routine'): string {
    $type = strtolower(trim($concernType));
    $urgency = strtolower(trim($urgency));
    $base = match($type) {
        'complaint' => 'High',
        'suggestion', 'feedback' => 'Low',
        default => 'Normal',
    };
    if (!in_array($type, ['concern', 'complaint'], true)) return $base;
    return match($urgency) {
        'immediate' => 'Urgent',
        'soon' => 'High',
        default => $base,
    };
}

function concern_submission_cooldown_hours(PDO $pdo): int {
    $fallback = 72;
    try {
        return max(24, min(720, (int)(system_setting($pdo, 'esumbong_suggestion_feedback_cooldown_hours', (string)$fallback) ?? $fallback)));
    } catch (Throwable $e) {
        return $fallback;
    }
}

function concern_type_policy(PDO $pdo, string $concernType): array {
    $type = ucfirst(strtolower(trim($concernType)));
    $cooldownHours = concern_submission_cooldown_hours($pdo);
    return match($type) {
        'Complaint' => [
            'formal' => true,
            'default_priority' => 'High',
            'submission_rule' => 'active',
            'student_rule' => 'Only one active Complaint at a time. A new Complaint can be submitted after the current one is Resolved or Closed.',
            'admin_guidance' => 'Prioritize review, verify relevant evidence, route or investigate as needed, document action taken, then resolve or close the case.',
        ],
        'Suggestion' => [
            'formal' => false,
            'default_priority' => 'Low',
            'submission_rule' => 'cooldown',
            'cooldown_hours' => $cooldownHours,
            'student_rule' => 'Suggestions use a '.($cooldownHours === 72?'3-day':$cooldownHours.'-hour').' cooldown. Another Suggestion may be submitted after the cooldown resets.',
            'admin_guidance' => 'Use a lighter review: consider or forward the idea, record any action when appropriate, then close it. A formal resolution summary is optional.',
        ],
        'Feedback' => [
            'formal' => false,
            'default_priority' => 'Low',
            'submission_rule' => 'cooldown',
            'cooldown_hours' => $cooldownHours,
            'student_rule' => 'Feedback uses a '.($cooldownHours === 72?'3-day':$cooldownHours.'-hour').' cooldown. Another Feedback submission may be made after the cooldown resets.',
            'admin_guidance' => 'Acknowledge and review the feedback, forward it when useful, and close it when recorded. Escalate only when the feedback reveals an actionable issue.',
        ],
        default => [
            'formal' => true,
            'default_priority' => 'Normal',
            'submission_rule' => 'active',
            'student_rule' => 'Only one active Concern at a time. A new Concern can be submitted after the current one is Resolved or Closed.',
            'admin_guidance' => 'Review the issue, route it to the responsible unit when needed, record action and student-visible updates, then resolve or close the case.',
        ],
    };
}
/** Keep active-case reminder targets synchronized with priority settings. */
function concern_sync_active_deadlines(PDO $pdo): int {
    if (!db_column_exists($pdo, 'concerns', 'due_at')) return 0;
    $updated = 0;
    foreach (['Urgent', 'High', 'Normal', 'Low'] as $priority) {
        $hours = concern_sla_hours($pdo, $priority);
        try {
            $sql = "UPDATE concerns SET due_at=DATE_ADD(created_at, INTERVAL {$hours} HOUR) WHERE priority=? AND status NOT IN ('Resolved','Closed') AND NOT (due_at <=> DATE_ADD(created_at, INTERVAL {$hours} HOUR))";
            $st = $pdo->prepare($sql);
            $st->execute([$priority]);
            $updated += $st->rowCount();
        } catch (Throwable $ignored) {
        }
    }
    return $updated;
}
function concern_reminder_due(array $concern): bool {
    return !in_array((string)($concern['status'] ?? ''), ['Resolved', 'Closed'], true) && !empty($concern['due_at']) && strtotime((string)$concern['due_at'])<time();
}
// Backward-compatible alias for older templates. This no longer represents a hard deadline.
function concern_is_overdue(array $concern): bool {
    return concern_reminder_due($concern);
}
function concern_due_label(array $concern): string {
    if (empty($concern['due_at'])) return 'No reminder set';
    $ts = strtotime((string)$concern['due_at']);
    if (!$ts) return 'No reminder set';
    $status = (string)($concern['status'] ?? '');
    if (in_array($status, ['Resolved', 'Closed'], true)) return 'Completed';

    $delta = $ts-time();
    $days = (int)floor(abs($delta)/86400);
    $hours = (int)max(1, ceil(abs($delta)/3600));
    if ($delta < 0) {
        return $days >= 1 ? 'Follow-up recommended · '.$days.'d' : 'Follow-up recommended · '.$hours.'h';
    }
    return $days >= 1 ? 'Reminder in '.$days.'d' : 'Reminder in '.$hours.'h';
}
function concern_mask_name(?string $name): string {
    $name = trim((string)$name);
    if ($name === '') return 'Not provided';
    $parts = preg_split('/\s+/', $name)?:[];
    return implode(' ', array_map(fn($p) => mb_substr($p, 0, 1).str_repeat('•', max(2, mb_strlen($p)-1)), $parts));
}
function concern_mask_student_id(?string $id): string {
    $id = trim((string)$id);
    if ($id === '') return 'Not provided';
    return preg_replace('/\d(?=\d{2})/', '•', preg_replace('/\D/', '', $id))?:'Protected';
}
function concern_log_privacy_access(PDO $pdo, int $concernId, string $action = 'Viewed identity', ?string $purpose = null): void {
    try {
        if (db_table_exists($pdo, 'privacy_access_log')) {
            $st = $pdo->prepare('INSERT INTO privacy_access_log(admin_id,concern_id,action,purpose,ip_address) VALUES(?,?,?,?,?)');
            $st->execute([$_SESSION['admin_id'] ?? null, $concernId, $action, $purpose, admin_current_ip()]);
        }
    }
    catch (Throwable $ignored) {
    }
    try {
        if (db_column_exists($pdo, 'concerns', 'sensitive_view_count')) $pdo->prepare('UPDATE concerns SET sensitive_view_count=sensitive_view_count+1 WHERE id=?')->execute([$concernId]);
    } catch (Throwable $ignored) {
    }
    try {
        admin_log($pdo, 'privacy', $action, 'Sensitive E-Sumbong identity access recorded', 'concern', $concernId);
    } catch (Throwable $ignored) {
    }
}
function concern_run_response_reminders(PDO $pdo, ?string $scope = null): int {
    if (!db_column_exists($pdo, 'concerns', 'due_at') || !db_column_exists($pdo, 'admin_notifications', 'group_key')) return 0;
    $where = "status NOT IN ('Resolved','Closed') AND due_at IS NOT NULL AND due_at<NOW()";
    $params = [];
    if ($scope) {
        $where.=' AND UPPER(assigned_scope)=?';
        $params[] = $scope;
    }
    $st = $pdo->prepare("SELECT id,reference_code,subject,assigned_scope,due_at,priority FROM concerns WHERE $where ORDER BY due_at ASC LIMIT 100");
    $st->execute($params);
    $count = 0;
    foreach ($st->fetchAll() as $c) {
        $group = 'review-reminder-'.$c['id'].'-'.date('Ymd', strtotime((string)$c['due_at']));
        $chk = $pdo->prepare('SELECT COUNT(*) FROM admin_notifications WHERE group_key=? AND created_at>=DATE_SUB(NOW(),INTERVAL 1 DAY)');
        $chk->execute([$group]);
        if ((int)$chk->fetchColumn()>0) continue;
        $msg = $c['reference_code'].' has reached its suggested review reminder · '.($c['priority'] ?? 'Normal').' priority · '.($c['assigned_scope'] ?? 'USC').'. This is a reminder only, not a mandatory deadline.';
        try {
            $st2 = $pdo->prepare("INSERT INTO admin_notifications(admin_id,role_target,campus_target,title,message,link,kind,category,group_key,is_read) VALUES(NULL,NULL,?,'E-Sumbong review reminder',? ,?,'info','concerns',?,0)");
            $st2->execute([$c['assigned_scope'], $msg, 'concerns.php?id='.$c['id'], $group]);
            $count++;
        } catch (Throwable $ignored) {
        }
    }
    return $count;
}
// Backward-compatible wrapper. No automatic escalation or deadline enforcement occurs.
function concern_run_overdue_escalation(PDO $pdo, ?string $scope = null): int {
    return concern_run_response_reminders($pdo, $scope);
}
function concern_run_followup_reminders(PDO $pdo, ?string $scope = null): int {
    if (!db_column_exists($pdo, 'concerns', 'follow_up_at') || !db_column_exists($pdo, 'admin_notifications', 'group_key')) return 0;
    $where = "status NOT IN ('Resolved','Closed') AND follow_up_at IS NOT NULL AND follow_up_at<=NOW()";
    $params = [];
    if ($scope) {
        $where.=' AND UPPER(assigned_scope)=?';
        $params[] = $scope;
    }
    $st = $pdo->prepare("SELECT id,reference_code,subject,assigned_scope,follow_up_at FROM concerns WHERE $where ORDER BY follow_up_at ASC LIMIT 100");
    $st->execute($params);
    $count = 0;
    foreach ($st->fetchAll() as $c) {
        $group = 'followup-concern-'.$c['id'].'-'.date('YmdHi', strtotime((string)$c['follow_up_at']));
        $chk = $pdo->prepare('SELECT COUNT(*) FROM admin_notifications WHERE group_key=?');
        $chk->execute([$group]);
        if ((int)$chk->fetchColumn()>0) continue;
        $msg = $c['reference_code'].' needs follow-up · '.($c['assigned_scope'] ?? 'USC').' · scheduled '.date('M j, Y g:i A', strtotime((string)$c['follow_up_at']));
        try {
            $st2 = $pdo->prepare("INSERT INTO admin_notifications(admin_id,role_target,campus_target,title,message,link,kind,category,group_key,is_read) VALUES(NULL,NULL,?,'E-Sumbong follow-up due',?,?, 'warning','concerns',?,0)");
            $st2->execute([$c['assigned_scope'], $msg, 'concerns.php?id='.$c['id'], $group]);
            $count++;
        } catch (Throwable $ignored) {
        }
    }
    return $count;
}

/* V90 advanced privacy, publication, security, and notification helpers */
function concern_colleges_by_campus(): array {
    return [
    'NLUC' => [
    'College of Education (CE)', 'College of Agriculture (CA)', 'College of Arts and Sciences (CAS)', 'College of Veterinary Medicine (CVM)',
    'College of Agroforestry and Forestry (CAFF)', 'Institute of Agribusiness Management (IABM)', 'Institute of Agricultural and Biosystems Engineering (IABE)',
    'Institute of Environmental Studies (IES)', 'College of Information Systems (CIS)'
    ],
    'MLUC' => [
    'Institute of Criminal Justice Education (ICJE)', 'College of Technology (COT)', 'College of Education (CE)', 'College of Engineering (COE)',
    'College of Information Technology (CIT)', 'College of Arts and Sciences (CAS)', 'College of Management (COM)'
    ],
    'SLUC' => [
    'College of Education (CE)', 'College of Arts and Sciences (CAS)', 'College of Community Health & Allied Medical Sciences (CCHAMS)',
    'College of Computer Science (CCS)', 'College of Agriculture (CA)', 'College of Fisheries (CF)'
    ],
    'OUS' => ['Bachelor of Elementary Education', 'Bachelor of Science in Agriculture Major in Horticulture', 'Bachelor of Science in Business Administration'],
    ];
}
function concern_campus_code(?string $value): string {
    $raw = trim((string)$value);
    $upper = strtoupper($raw);
    foreach (admin_campuses() as $code => $name) {
        if ($upper === $code || strcasecmp($raw, $name) === 0 || str_starts_with($upper, $code.' ') || str_starts_with($upper, $code.' —')) return $code;
    }
    return '';
}
function concern_valid_college(string $campusCode, string $college): bool {
    $map = concern_colleges_by_campus();
    return isset($map[$campusCode]) && in_array(trim($college), $map[$campusCode], true);
}
function concern_rate_limit_bucket(string $ip): string {
    return hash('sha256', 'esumbong|'.$ip.'|'.date('Y-m-d'));
}
function concern_submission_rate_limit(PDO $pdo, string $ip): array {
    if (!db_table_exists($pdo, 'submission_rate_limits')) return ['allowed' => true, 'retry_after' => 0];
    $key = concern_rate_limit_bucket($ip);
    $now = time();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM submission_rate_limits WHERE bucket_key=? FOR UPDATE');
        $st->execute([$key]);
        $row = $st->fetch();
        if (!$row) {
            $pdo->prepare('INSERT INTO submission_rate_limits(bucket_key,attempts,window_started_at,last_attempt_at) VALUES(?,1,NOW(),NOW())')->execute([$key]);
            $pdo->commit();
            return ['allowed' => true, 'retry_after' => 0];
        }
        $last = strtotime((string)$row['last_attempt_at'])?:0;
        $window = strtotime((string)$row['window_started_at'])?:$now;
        $blocked = !empty($row['blocked_until'])?(strtotime((string)$row['blocked_until'])?:0):0;
        if ($blocked>$now) {
            $pdo->commit();
            return ['allowed' => false, 'retry_after' => $blocked-$now];
        }
        if ($now-$window >= 3600) {
            $pdo->prepare('UPDATE submission_rate_limits SET attempts=1,window_started_at=NOW(),last_attempt_at=NOW(),blocked_until=NULL WHERE bucket_key=?')->execute([$key]);
            $pdo->commit();
            return ['allowed' => true, 'retry_after' => 0];
        }
        $attempts = (int)$row['attempts']+1;
        if ($now-$last<15 || $attempts>8) {
            $wait = $attempts>8?3600:max(15, 15-($now-$last));
            $pdo->prepare('UPDATE submission_rate_limits SET attempts=?,last_attempt_at=NOW(),blocked_until=DATE_ADD(NOW(),INTERVAL ? SECOND) WHERE bucket_key=?')->execute([$attempts, $wait, $key]);
            $pdo->commit();
            return ['allowed' => false, 'retry_after' => $wait];
        }
        $pdo->prepare('UPDATE submission_rate_limits SET attempts=?,last_attempt_at=NOW() WHERE bucket_key=?')->execute([$attempts, $key]);
        $pdo->commit();
        return ['allowed' => true, 'retry_after' => 0];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['allowed' => true, 'retry_after' => 0];
    }
}
/**
 * Match a concern row to a submitted Student ID while supporting both legacy
 * plaintext identity storage and the encrypted private-identity table.
 */
function concern_row_matches_student_id(array $row, bool $hasPrivateIdentity, string $normalizedStudentId): bool {
    $storedStudentId = trim((string)($row['student_id'] ?? ''));
    if ($hasPrivateIdentity && !empty($row['student_id_cipher'])) {
        $decrypted = app_decrypt_sensitive($row['student_id_cipher']);
        if ($decrypted !== null && trim($decrypted) !== '') $storedStudentId = trim($decrypted);
    }
    $normalizedStoredId = preg_replace('/\D+/', '', $storedStudentId) ?? '';
    return $normalizedStoredId !== '' && hash_equals($normalizedStoredId, $normalizedStudentId);
}

/**
 * Apply the E-Sumbong anti-flood policy for a student and submission type.
 * - Concern / Complaint: block while another case of the same type is active.
 * - Suggestion / Feedback: block for the configured cooldown (72 hours by default),
 *   regardless of whether the previous lightweight submission was already closed.
 */
function concern_submission_restriction(PDO $pdo, string $studentId, string $concernType): ?array {
    if (!db_table_exists($pdo, 'concerns')) return null;

    $normalizedStudentId = preg_replace('/\D+/', '', trim($studentId)) ?? '';
    $concernType = ucfirst(strtolower(trim($concernType)));
    if ($normalizedStudentId === '' || $concernType === '') return null;

    try {
        $hasPrivateIdentity = db_table_exists($pdo, 'concern_private_identity');
        $privateSelect = $hasPrivateIdentity ? ', pi.student_id_cipher' : '';
        $privateJoin = $hasPrivateIdentity ? ' LEFT JOIN concern_private_identity pi ON pi.concern_id=c.id' : '';
        $usesCooldown = in_array($concernType, ['Suggestion', 'Feedback'], true);
        $params = [$concernType];

        if ($usesCooldown) {
            $cooldownHours = concern_submission_cooldown_hours($pdo);
            $cutoff = date('Y-m-d H:i:s', time()-($cooldownHours*3600));
            $where = 'c.concern_type=? AND c.created_at>=?';
            $params[] = $cutoff;
        } else {
            $cooldownHours = 0;
            $where = "c.concern_type=? AND c.status NOT IN ('Resolved','Closed')";
        }

        $sql = "SELECT c.id,c.reference_code,c.status,c.source_portal,c.assigned_scope,c.concern_type,c.student_id,c.subject,c.created_at{$privateSelect}
                FROM concerns c{$privateJoin}
                WHERE {$where}
                ORDER BY c.created_at DESC,c.id DESC";
        $st = $pdo->prepare($sql);
        $st->execute($params);

        foreach ($st->fetchAll() as $row) {
            if (!concern_row_matches_student_id($row, $hasPrivateIdentity, $normalizedStudentId)) continue;
            if ($usesCooldown) {
                $createdTs = strtotime((string)($row['created_at'] ?? '')) ?: 0;
                $retryTs = $createdTs+($cooldownHours*3600);
                if ($retryTs <= time()) continue;
                $row['restriction_kind'] = 'cooldown';
                $row['cooldown_hours'] = $cooldownHours;
                $row['retry_at'] = date('Y-m-d H:i:s', $retryTs);
                $row['retry_seconds'] = max(1, $retryTs-time());
            } else {
                $row['restriction_kind'] = 'active';
                $row['retry_at'] = null;
                $row['retry_seconds'] = null;
            }
            return $row;
        }
    } catch (Throwable $e) {
        app_log_error('E-Sumbong submission restriction check failed', ['error' => $e->getMessage()]);
    }

    return null;
}

/**
 * Backward-compatible helper for callers that specifically need the older
 * "active case" rule without the Suggestion/Feedback cooldown policy.
 */
function concern_find_active_duplicate(PDO $pdo, string $studentId, string $concernType): ?array {
    if (!db_table_exists($pdo, 'concerns')) return null;
    $normalizedStudentId = preg_replace('/\D+/', '', trim($studentId)) ?? '';
    $concernType = ucfirst(strtolower(trim($concernType)));
    if ($normalizedStudentId === '' || $concernType === '') return null;
    try {
        $hasPrivateIdentity = db_table_exists($pdo, 'concern_private_identity');
        $privateSelect = $hasPrivateIdentity ? ', pi.student_id_cipher' : '';
        $privateJoin = $hasPrivateIdentity ? ' LEFT JOIN concern_private_identity pi ON pi.concern_id=c.id' : '';
        $st = $pdo->prepare("SELECT c.id,c.reference_code,c.status,c.source_portal,c.assigned_scope,c.concern_type,c.student_id,c.subject,c.created_at{$privateSelect}
            FROM concerns c{$privateJoin}
            WHERE c.concern_type=? AND c.status NOT IN ('Resolved','Closed')
            ORDER BY c.created_at DESC,c.id DESC");
        $st->execute([$concernType]);
        foreach ($st->fetchAll() as $row) {
            if (concern_row_matches_student_id($row, $hasPrivateIdentity, $normalizedStudentId)) return $row;
        }
    } catch (Throwable $e) {
        app_log_error('E-Sumbong duplicate concern check failed', ['error' => $e->getMessage()]);
    }
    return null;
}

function concern_store_private_identity(PDO $pdo, int $concernId, string $name, string $studentId, string $contactEmail = ''): void {
    if (!db_table_exists($pdo, 'concern_private_identity')) return;
    $hasEmail = db_column_exists($pdo, 'concern_private_identity', 'contact_email_cipher');
    if ($hasEmail) {
        $st = $pdo->prepare('INSERT INTO concern_private_identity(concern_id,student_name_cipher,student_id_cipher,contact_email_cipher) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE student_name_cipher=VALUES(student_name_cipher),student_id_cipher=VALUES(student_id_cipher),contact_email_cipher=VALUES(contact_email_cipher)');
        $st->execute([$concernId, app_encrypt_sensitive($name), app_encrypt_sensitive($studentId), $contactEmail !== ''?app_encrypt_sensitive($contactEmail):null]);
    }
    else {
        $st = $pdo->prepare('INSERT INTO concern_private_identity(concern_id,student_name_cipher,student_id_cipher) VALUES(?,?,?) ON DUPLICATE KEY UPDATE student_name_cipher=VALUES(student_name_cipher),student_id_cipher=VALUES(student_id_cipher)');
        $st->execute([$concernId, app_encrypt_sensitive($name), app_encrypt_sensitive($studentId)]);
    }
}
function concern_private_identity(PDO $pdo, array $concern): array {
    $name = (string)($concern['student_name'] ?? '');
    $id = (string)($concern['student_id'] ?? '');
    if (db_table_exists($pdo, 'concern_private_identity') && !empty($concern['id'])) {
        try {
            $cols = 'student_name_cipher,student_id_cipher'.(db_column_exists($pdo, 'concern_private_identity', 'contact_email_cipher')?',contact_email_cipher':'');
            $st = $pdo->prepare('SELECT '.$cols.' FROM concern_private_identity WHERE concern_id=? LIMIT 1');
            $st->execute([(int)$concern['id']]);
            $r = $st->fetch();
            if ($r) {
                $dn = app_decrypt_sensitive($r['student_name_cipher'] ?? null);
                $di = app_decrypt_sensitive($r['student_id_cipher'] ?? null);
                if ($dn !== null && $dn !== '') $name = $dn;
                if ($di !== null && $di !== '') $id = $di;
                $email = app_decrypt_sensitive($r['contact_email_cipher'] ?? null) ?? '';
            }
        } catch (Throwable $ignored) {
        }
    }
    return ['name' => $name, 'student_id' => $id, 'email' => $email ?? ''];
}
function concern_attachment_dir(): string {
    $d = app_root('uploads/concerns');
    if (!is_dir($d)) @mkdir($d, 0750, true);
    return $d;
}
function concern_store_attachments(PDO $pdo, int $concernId, array $files, ?int $uploadedBy = null, string $source = 'student'): array {
    if (!db_table_exists($pdo, 'concern_attachments') || empty($files['name']) || !is_array($files['name'])) return [];
    $maxFiles = max(1, min(5, (int)(system_setting($pdo, 'esumbong_attachment_max_files', '3') ?? 3)));
    $maxMb = max(1, min(10, (int)(system_setting($pdo, 'esumbong_attachment_max_mb', '5') ?? 5)));
    $maxBytes = $maxMb*1024*1024;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $errors = [];
    $saved = 0;
    foreach ($files['name'] as $i => $original) {
        if ($saved >= $maxFiles) break;
        $err = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) continue;
        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = 'Could not upload '.basename((string)$original).'.';
            continue;
        }
        $tmp = (string)($files['tmp_name'][$i] ?? '');
        $size = (int)($files['size'][$i] ?? 0);
        if ($size <= 0 || $size>$maxBytes) {
            $errors[] = basename((string)$original).' must be '.$maxMb.' MB or smaller.';
            continue;
        }
        $mime = (string)$finfo->file($tmp);
        if (!isset($allowed[$mime])) {
            $errors[] = basename((string)$original).' must be JPG, PNG, WebP, or PDF.';
            continue;
        }
        $name = 'concern_'.$concernId.'_'.bin2hex(random_bytes(10)).'.'.$allowed[$mime];
        $path = concern_attachment_dir().'/'.$name;
        if (!move_uploaded_file($tmp, $path)) {
            $errors[] = 'Unable to save '.basename((string)$original).'.';
            continue;
        }
        $relative = 'uploads/concerns/'.$name;
        $st = $pdo->prepare('INSERT INTO concern_attachments(concern_id,file_path,original_name,mime_type,file_size,sha256,uploaded_by,source) VALUES(?,?,?,?,?,?,?,?)');
        $st->execute([$concernId, $relative, basename((string)$original), $mime, $size, hash_file('sha256', $path)?:null, $uploadedBy, $source]);
        $saved++;
    }
    return $errors;
}
function concern_attachments(PDO $pdo, int $concernId): array {
    if (!db_table_exists($pdo, 'concern_attachments')) return [];
    $st = $pdo->prepare('SELECT * FROM concern_attachments WHERE concern_id=? ORDER BY created_at,id');
    $st->execute([$concernId]);
    return $st->fetchAll();
}

/**
* Build a short-lived signature for a student-submitted concern attachment.
* The signature is emitted only after the tracker verifies the student's ID.
*/
function concern_public_attachment_signature(int $attachmentId, int $concernId, string $referenceCode, int $expires): string {
    $payload = implode('|', [
    'esumbong-public-attachment-v1',
    (string)$attachmentId,
    (string)$concernId,
    strtoupper(trim($referenceCode)),
    (string)$expires,
    ]);
    return hash_hmac('sha256', $payload, app_data_key());
}
function concern_public_attachment_url(array $attachment, string $referenceCode, bool $download = false, int $ttlSeconds = 900): string {
    $attachmentId = (int)($attachment['id'] ?? 0);
    $concernId = (int)($attachment['concern_id'] ?? 0);
    $ttlSeconds = max(60, min(1800, $ttlSeconds));
    $expires = time()+$ttlSeconds;
    $query = [
    'id' => $attachmentId,
    'code' => strtoupper(trim($referenceCode)),
    'exp' => $expires,
    'sig' => concern_public_attachment_signature($attachmentId, $concernId, $referenceCode, $expires),
    ];
    if ($download) $query['download'] = '1';
    return 'esumbong/track-attachment.php?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}
function concern_public_attachment_signature_is_valid(int $attachmentId, int $concernId, string $referenceCode, int $expires, string $signature): bool {
    if ($expires < time() || $expires > time()+1800) return false;
    if (!preg_match('/^[a-f0-9]{64}$/i', $signature)) return false;
    return hash_equals(concern_public_attachment_signature($attachmentId, $concernId, $referenceCode, $expires), strtolower($signature));
}
function post_slugify(string $title): string {
    $s = strtolower(trim((string)preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));
    return $s !== ''?$s:'publication';
}
function post_unique_slug(PDO $pdo, string $title, ?int $excludeId = null): string {
    $base = post_slugify($title);
    $slug = $base;
    $n = 2;
    do {
        $sql = 'SELECT COUNT(*) FROM posts WHERE slug=?'.($excludeId?' AND id<>?':'');
        $st = $pdo->prepare($sql);
        $params = [$slug];
        if ($excludeId) $params[] = $excludeId;
        $st->execute($params);
        $exists = (int)$st->fetchColumn()>0;
        if ($exists) $slug = $base.'-'.$n++;
    } while ($exists);
    return $slug;
}
function post_save_revision(PDO $pdo, int $postId, ?int $adminId = null, ?string $note = null): void {
    if (!db_table_exists($pdo, 'post_revisions')) return;
    $st = $pdo->prepare('SELECT * FROM posts WHERE id=?');
    $st->execute([$postId]);
    $post = $st->fetch();
    if (!$post) return;
    $images = post_images($pdo, $postId);
    $videos = post_videos($pdo, $postId);
    $snapshot = json_encode(['post' => $post, 'images' => $images, 'videos' => $videos], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $nSt = $pdo->prepare('SELECT COALESCE(MAX(revision_no),0)+1 FROM post_revisions WHERE post_id=?');
    $nSt->execute([$postId]);
    $n = (int)$nSt->fetchColumn();
    $pdo->prepare('INSERT INTO post_revisions(post_id,revision_no,snapshot_json,change_note,created_by) VALUES(?,?,?,?,?)')->execute([$postId, $n, $snapshot, $note, $adminId]);
}
function post_restore_revision_images(PDO $pdo, int $postId, array $snapshotImages): int {
    if (!db_table_exists($pdo, 'post_images')) return 0;
    $current = post_images($pdo, $postId);
    $byPath = [];
    foreach ($current as $img) $byPath[(string)$img['file_path']] = $img;
    $order = 0;
    $restored = 0;
    $seen = [];
    foreach ($snapshotImages as $img) {
        if (!is_array($img)) continue;
        $path = ltrim(trim((string)($img['file_path'] ?? '')), '/');
        if ($path === '' || isset($seen[$path])) continue;
        $seen[$path] = true;
        if (isset($byPath[$path])) {
            $pdo->prepare('UPDATE post_images SET sort_order=? WHERE id=? AND post_id=?')->execute([$order++, (int)$byPath[$path]['id'], $postId]);
            $restored++;
            continue;
        }
        if (!is_file(app_root($path))) continue;
        $mediaId = !empty($img['media_id'])?(int)$img['media_id']:null;
        if ($mediaId) {
            try {
                $m = $pdo->prepare('SELECT COUNT(*) FROM media_library WHERE id=?');
                $m->execute([$mediaId]);
                if (!(int)$m->fetchColumn()) $mediaId = null;
            } catch (Throwable $ignored) {
                $mediaId = null;
            }
        }
        $pdo->prepare('INSERT INTO post_images(post_id,file_path,original_name,media_id,sort_order) VALUES(?,?,?,?,?)')->execute([$postId, $path, $img['original_name'] ?? basename($path), $mediaId, $order++]);
        $restored++;
    }
    // Preserve newer images that are not part of the old revision; place them after the restored ordering instead of deleting data.
    foreach ($current as $img) {
        $path = (string)$img['file_path'];
        if (isset($seen[$path])) continue;
        $pdo->prepare('UPDATE post_images SET sort_order=? WHERE id=? AND post_id=?')->execute([$order++, (int)$img['id'], $postId]);
    }
    return $restored;
}

function post_restore_revision_videos(PDO $pdo, int $postId, array $snapshotVideos): int {
    if (!db_table_exists($pdo, 'post_videos')) return 0;
    $current = post_videos($pdo, $postId);
    $byPath = [];
    foreach ($current as $video) $byPath[(string)$video['file_path']] = $video;
    $order = 0;
    $restored = 0;
    $seen = [];
    foreach ($snapshotVideos as $video) {
        if (!is_array($video)) continue;
        $path = ltrim(trim((string)($video['file_path'] ?? '')), '/');
        if ($path === '' || isset($seen[$path])) continue;
        $seen[$path] = true;
        if (isset($byPath[$path])) {
            $pdo->prepare('UPDATE post_videos SET sort_order=? WHERE id=? AND post_id=?')->execute([$order++, (int)$byPath[$path]['id'], $postId]);
            $restored++;
            continue;
        }
        if (!is_file(app_root($path))) continue;
        $mediaId = !empty($video['media_id'])?(int)$video['media_id']:null;
        if ($mediaId) {
            try {
                $m = $pdo->prepare('SELECT COUNT(*) FROM media_library WHERE id=?');
                $m->execute([$mediaId]);
                if (!(int)$m->fetchColumn()) $mediaId = null;
            } catch (Throwable $ignored) {
                $mediaId = null;
            }
        }
        $mime = trim((string)($video['mime_type'] ?? 'video/mp4')) ?: 'video/mp4';
        $bytes = (int)($video['file_size'] ?? (is_file(app_root($path))?filesize(app_root($path)):0));
        $pdo->prepare('INSERT INTO post_videos(post_id,file_path,original_name,media_id,mime_type,file_size,sort_order) VALUES(?,?,?,?,?,?,?)')->execute([$postId, $path, $video['original_name'] ?? basename($path), $mediaId, $mime, $bytes, $order++]);
        $restored++;
    }
    foreach ($current as $video) {
        $path = (string)$video['file_path'];
        if (isset($seen[$path])) continue;
        $pdo->prepare('UPDATE post_videos SET sort_order=? WHERE id=? AND post_id=?')->execute([$order++, (int)$video['id'], $postId]);
    }
    return $restored;
}

function publish_scheduled_posts(PDO $pdo): int {
    if (!db_column_exists($pdo, 'posts', 'scheduled_at')) return 0;
    try {
        $st = $pdo->query("SELECT id,title,category FROM posts WHERE deleted_at IS NULL AND status='draft' AND scheduled_at IS NOT NULL AND scheduled_at<=NOW() ORDER BY scheduled_at LIMIT 50");
        $rows = $st->fetchAll();
        $count = 0;
        foreach ($rows as $r) {
            $pdo->prepare("UPDATE posts SET status='published',published_at=COALESCE(scheduled_at,NOW()),published_by=COALESCE(published_by,scheduled_by),scheduled_at=NULL WHERE id=? AND status='draft'")->execute([(int)$r['id']]);
            $count++;
        }
        return $count;
    } catch (Throwable $e) {
        return 0;
    }
}
function admin_encrypt_2fa_secret(string $secret): string {
    return (string)app_encrypt_sensitive($secret);
}
function admin_decrypt_2fa_secret(?string $secret): string {
    return (string)(app_decrypt_sensitive($secret) ?? '');
}
function admin_generate_recovery_codes(PDO $pdo, int $adminId, int $count = 8): array {
    if (!db_table_exists($pdo, 'admin_recovery_codes')) return [];
    $pdo->prepare('DELETE FROM admin_recovery_codes WHERE admin_id=?')->execute([$adminId]);
    $codes = [];
    $st = $pdo->prepare('INSERT INTO admin_recovery_codes(admin_id,code_hash) VALUES(?,?)');
    for ($i = 0; $i<$count; $i++) {
        $raw = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        $code = substr($raw, 0, 4).'-'.substr($raw, 4, 4).'-'.substr($raw, 8, 4);
        $st->execute([$adminId, hash('sha256', $code)]);
        $codes[] = $code;
    }
    return $codes;
}
function admin_use_recovery_code(PDO $pdo, int $adminId, string $code): bool {
    if (!db_table_exists($pdo, 'admin_recovery_codes')) return false;
    $hash = hash('sha256', strtoupper(trim($code)));
    $st = $pdo->prepare('SELECT id FROM admin_recovery_codes WHERE admin_id=? AND code_hash=? AND used_at IS NULL LIMIT 1');
    $st->execute([$adminId, $hash]);
    $id = (int)$st->fetchColumn();
    if (!$id) return false;
    $pdo->prepare('UPDATE admin_recovery_codes SET used_at=NOW() WHERE id=? AND used_at IS NULL')->execute([$id]);
    return true;
}
function admin_recovery_code_count(PDO $pdo, int $adminId): int {
    if (!db_table_exists($pdo, 'admin_recovery_codes')) return 0;
    $st = $pdo->prepare('SELECT COUNT(*) FROM admin_recovery_codes WHERE admin_id=? AND used_at IS NULL');
    $st->execute([$adminId]);
    return (int)$st->fetchColumn();
}
function admin_password_strength_error(string $password): ?string {
    if (strlen($password)<12) return 'Password must be at least 12 characters.';
    $classes = (int)(bool)preg_match('/[a-z]/', $password)+(int)(bool)preg_match('/[A-Z]/', $password)+(int)(bool)preg_match('/\d/', $password)+(int)(bool)preg_match('/[^A-Za-z0-9]/', $password);
    if ($classes<3) return 'Use a stronger password with at least three of: lowercase letters, uppercase letters, numbers, and symbols.';
    if (preg_match('/(.)\1{4,}/', $password)) return 'Avoid long repeated character sequences in passwords.';
    return null;
}
function admin_password_reused(PDO $pdo, int $adminId, string $password, int $history = 5): bool {
    if (!db_table_exists($pdo, 'admin_password_history')) return false;
    $history = max(1, min(12, $history));
    $st = $pdo->prepare('SELECT password_hash FROM admin_password_history WHERE admin_id=? ORDER BY created_at DESC,id DESC LIMIT '.$history);
    $st->execute([$adminId]);
    foreach ($st->fetchAll() as $r) if (password_verify($password, (string)$r['password_hash'])) return true;
    return false;
}
function admin_record_password_history(PDO $pdo, int $adminId, string $hash): void {
    if (!db_table_exists($pdo, 'admin_password_history')) return;
    $pdo->prepare('INSERT INTO admin_password_history(admin_id,password_hash) VALUES(?,?)')->execute([$adminId, $hash]);
    $keep = max(5, min(20, (int)(system_setting($pdo, 'password_history_count', '5') ?? 5)));
    $pdo->prepare('DELETE FROM admin_password_history WHERE admin_id=? AND id NOT IN (SELECT id FROM (SELECT id FROM admin_password_history WHERE admin_id=? ORDER BY created_at DESC,id DESC LIMIT '.$keep.') x)')->execute([$adminId, $adminId]);
}
function admin_required_2fa_roles(PDO $pdo): array {
    $raw = (string)(system_setting($pdo, 'security_2fa_required_roles', 'admin') ?? 'admin');
    return array_values(array_intersect(array_keys(admin_roles()), array_filter(array_map('trim', explode(',', $raw)))));
}
function admin_queue_email(PDO $pdo, string $recipient, string $subject, string $body, string $category = 'general', ?int $adminId = null, ?int $concernId = null): void {
    $recipient = trim(str_replace(["\r", "\n"], '', $recipient));
    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    $subject = mb_substr($subject, 0, 190);
    $category = preg_replace('/[^a-z0-9_-]/i', '', mb_substr($category, 0, 40))?:'general';
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !db_table_exists($pdo, 'notification_email_outbox')) return;
    $hasAdmin = db_column_exists($pdo, 'notification_email_outbox', 'admin_id');
    $hasConcern = db_column_exists($pdo, 'notification_email_outbox', 'concern_id');
    if ($hasAdmin && $hasConcern) {
        $pdo->prepare('INSERT INTO notification_email_outbox(admin_id,concern_id,recipient,subject,body,category) VALUES(?,?,?,?,?,?)')->execute([$adminId?:null, $concernId?:null, $recipient, $subject, $body, $category]);
        return;
    }
    $pdo->prepare('INSERT INTO notification_email_outbox(recipient,subject,body,category) VALUES(?,?,?,?)')->execute([$recipient, $subject, $body, $category]);
}
function admin_process_email_outbox(PDO $pdo, int $limit = 10): int {
    if (system_setting($pdo, 'notification_email_enabled', '0') !== '1' || !db_table_exists($pdo, 'notification_email_outbox')) return 0;
    $st = $pdo->prepare("SELECT * FROM notification_email_outbox WHERE status='queued' AND attempts<3 ORDER BY created_at LIMIT ".max(1, min(50, $limit)));
    $st->execute();
    $sent = 0;
    $from = trim(str_replace(["\r", "\n"], '', (string)(system_setting($pdo, 'email_from_address', 'usc@dmmmsu.edu.ph') ?? '')));
    $fromName = trim(str_replace(["\r", "\n"], ' ', (string)(system_setting($pdo, 'email_from_name', 'DMMMSU USC') ?? '')));
    foreach ($st->fetchAll() as $m) {
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        if (filter_var($from, FILTER_VALIDATE_EMAIL)) $headers[] = 'From: '.$fromName.' <'.$from.'>';
        $ok = @mail((string)$m['recipient'], (string)$m['subject'], (string)$m['body'], implode("\r\n", $headers));
        if ($ok) {
            $pdo->prepare("UPDATE notification_email_outbox SET status='sent',attempts=attempts+1,sent_at=NOW(),last_error=NULL WHERE id=?")->execute([(int)$m['id']]);
            $sent++;
        } else {
            $pdo->prepare("UPDATE notification_email_outbox SET attempts=attempts+1,last_error='mail() delivery failed' WHERE id=?")->execute([(int)$m['id']]);
        }
    }
    return $sent;
}

require_once __DIR__.'/advanced.php';
