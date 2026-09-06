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

// Security router for PHP's built-in development/LAN server.
// Apache deployments continue to use the project's .htaccess rules instead.
$projectRoot = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$rawPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$path = str_replace('\\', '/', rawurldecode($rawPath));
if ($path === '' || $path[0] !== '/') $path = '/'.$path;

$segments = array_values(array_filter(explode('/', trim($path, '/')), static fn($v) => $v !== ''));
$first = strtolower((string)($segments[0] ?? ''));
$basename = strtolower((string)basename($path));
$extension = strtolower((string)pathinfo($basename, PATHINFO_EXTENSION));

$blockedDirectories = ['config', 'database', 'storage', 'backups', 'scripts', 'tests', 'docs', 'src'];
$blockedExtensions = ['sql', 'log', 'bak', 'ini', 'md'];
$blockedUploadExtensions = ['php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'com', 'bat', 'cmd', 'ps1'];
$isDotFile = $basename !== '' && str_starts_with($basename, '.');
$isBlockedDirectory = in_array($first, $blockedDirectories, true);
$isBlockedExtension = in_array($extension, $blockedExtensions, true);
$isPrivateConcernUpload = ($first === 'uploads' && strtolower((string)($segments[1] ?? '')) === 'concerns');
$isBlockedUpload = ($first === 'uploads' && in_array($extension, $blockedUploadExtensions, true));

if ($isDotFile || $isBlockedDirectory || $isBlockedExtension || $isBlockedUpload || $isPrivateConcernUpload) {
    http_response_code(404);
    $status = 404;
    $title = 'Page not found';
    $message = 'The page or record you requested could not be found.';
    require $projectRoot.'/errors/_template.php';
    return true;
}

$requested = $projectRoot.'/'.ltrim($path, '/');
$real = realpath($requested);
if ($real !== false && str_starts_with($real, $projectRoot.DIRECTORY_SEPARATOR) && is_file($real)) {
    return false;
}

if ($path === '/' || $path === '') return false;

http_response_code(404);
$status = 404;
$title = 'Page not found';
$message = 'The page or record you requested could not be found.';
require $projectRoot.'/errors/_template.php';
return true;
