<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__).'/config/app.php';

$skipDb = in_array('--skip-db', $argv, true);
$checks = 0;
$failed = 0;
$warnings = 0;
function pf_line(string $state, string $label, string $detail = ''): void {
    $suffix = $detail !== ''?' — '.$detail:'';
    echo '['.$state.'] '.$label.$suffix.PHP_EOL;
}
function pf_check(bool $ok, string $label, string $failureDetail = '', bool $warningOnly = false, string $successDetail = ''): void {
    global $checks, $failed, $warnings;
    $checks++;
    if ($ok) {
        pf_line('PASS', $label, $successDetail);
        return;
    }
    if ($warningOnly) {
        $warnings++;
        pf_line('WARN', $label, $failureDetail);
        return;
    }
    $failed++;
    pf_line('FAIL', $label, $failureDetail);
}

$root = dirname(__DIR__);
pf_check(version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP 8.1+ required', 'Current PHP: '.PHP_VERSION, false, 'PHP '.PHP_VERSION);
foreach (['pdo', 'mbstring', 'openssl', 'fileinfo'] as $ext) pf_check(extension_loaded($ext), 'PHP extension: '.$ext, 'Enable the '.$ext.' extension in php.ini.');
pf_check(extension_loaded('pdo_mysql'), 'PHP extension: pdo_mysql', 'Enable extension=pdo_mysql in php.ini; the application cannot use MySQL without it.');
pf_check(extension_loaded('gd'), 'Optional PHP extension: gd', 'Image optimization/thumbnails will be limited without GD.', true);
pf_check(class_exists('ZipArchive'), 'Optional PHP extension: zip', 'Full ZIP backups are unavailable without ZipArchive.', true);

$requiredFiles = ['index.php', 'config/app.php', 'config/database.php', 'config/helpers.php', 'config/security.php', 'config/migrations.php', 'database/dmmmsu_usc.production.sql', 'admin/login.php', 'esumbong/esumbong.php', 'news/updates.php'];
foreach ($requiredFiles as $rel) pf_check(is_file($root.'/'.$rel), 'Required file: '.$rel, 'File is missing.');

$writableDirs = ['storage', 'storage/logs', 'storage/cache', 'storage/keys', 'backups', 'uploads', 'uploads/posts', 'uploads/hero', 'uploads/admin-profiles', 'uploads/concerns', 'uploads/thumbnails'];
foreach ($writableDirs as $rel) {
    $path = $root.'/'.$rel;
    if (!is_dir($path)) @mkdir($path, 0750, true);
    pf_check(is_dir($path) && is_writable($path), 'Writable directory: '.$rel, 'Create the directory and grant the web/PHP account write permission.');
}

$envFile = $root.'/.env';
$environment = app_environment();
pf_check(is_file($envFile), 'Environment file (.env)', 'Not present. Local defaults may work for XAMPP, but deployment should use an explicit .env.', !app_is_production());
if (app_is_production()) {
    pf_check(trim((string)app_env('APP_URL', '')) !== '', 'Production APP_URL', 'Set APP_URL in .env.');
    pf_check(trim((string)app_env('DB_PASS', '')) !== '', 'Production database password', 'Do not deploy with a blank DB password.');
    pf_check(app_env_bool('APP_CSP_ENFORCE', false), 'Production CSP enforcement', 'Set APP_CSP_ENFORCE=true after validating the site.');
}

$keyEnv = trim((string)app_env('APP_DATA_KEY', ''));
$keyFile = $root.'/storage/keys/data.key';
pf_check($keyEnv !== '' || is_file($keyFile), 'Protected-data encryption key', 'Set APP_DATA_KEY or preserve storage/keys/data.key before using encrypted records.');

if (!$skipDb && extension_loaded('pdo_mysql')) {
    $host = (string)app_env('DB_HOST', '127.0.0.1');
    $port = (string)app_env('DB_PORT', '3306');
    $name = (string)app_env('DB_NAME', 'dmmmsu_usc');
    $user = (string)app_env('DB_USER', 'root');
    $pass = (string)app_env('DB_PASS', '');
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
        pf_check(true, 'Database connection', '', false, "{$host}:{$port}/{$name}");
        $tables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=".$pdo->quote($name))->fetchColumn();
        pf_check($tables>0, 'Database schema detected', 'Database is reachable but contains no tables.', false, $tables.' table(s)');
    } catch (Throwable $e) {
        pf_check(false, 'Database connection', $e->getMessage());
    }
} elseif ($skipDb) {
    pf_line('SKIP', 'Database connection', '--skip-db requested');
} else {
    pf_line('SKIP', 'Database connection', 'pdo_mysql is unavailable, already reported above');
}

echo PHP_EOL.$checks.' preflight check(s), '.$failed.' failure(s), '.$warnings.' warning(s).'.PHP_EOL;
exit($failed?1:0);
