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
declare(strict_types=1);
require_once __DIR__.'/app.php';

$DB_HOST = (string)app_env('DB_HOST', '127.0.0.1');
$DB_PORT = (string)app_env('DB_PORT', '3306');
$DB_NAME = (string)app_env('DB_NAME', 'dmmmsu_usc');
$DB_USER = (string)app_env('DB_USER', 'root');
$DB_PASS = (string)app_env('DB_PASS', '');

if (!extension_loaded('pdo_mysql')) {
    $ref = app_error_reference();
    app_log_error('Database driver unavailable', ['reference' => $ref, 'required_extension' => 'pdo_mysql']);
    app_render_error_page(500, 'Server configuration incomplete', 'PHP PDO MySQL support is not enabled. Enable pdo_mysql in php.ini, restart the web server, and try again.', $ref);
}

try {
    $pdo = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);
} catch (PDOException $e) {
    $ref = app_error_reference();
    app_log_error('Database connection failed', ['reference' => $ref, 'host' => $DB_HOST, 'port' => $DB_PORT, 'database' => $DB_NAME, 'error' => $e->getMessage()]);
    app_render_error_page(500, 'Database unavailable', 'The system cannot connect to its database. Verify the server and environment configuration.', $ref);
}

// System maintenance mode affects only public requests. Administrators remain able to sign in.
try {
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $isAdminRequest = str_contains($script, '/admin/');
    if (!$isAdminRequest) {
        $st = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key='maintenance_mode' LIMIT 1");
        $st->execute();
        if ((string)($st->fetchColumn()?:'0') === '1') {
            http_response_code(503);
            header('Retry-After: 1800');
            require app_root('errors/maintenance.php');
            exit;
        }
    }
} catch (Throwable $ignored) {
    // Fresh/legacy databases without system_settings remain accessible until migrations are run.
}
