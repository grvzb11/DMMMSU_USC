<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_once '../config/maintenance.php';
require_admin();
if (!can_manage_backups()) deny_access('Only a System Administrator can download backups.');
$file = basename((string)($_GET['file'] ?? ''));
if (!preg_match('/^[A-Za-z0-9_.-]+\.(sql|zip)$/i', $file)) not_found('Backup file not found.');
$root = realpath(maintenance_backup_dir());
$path = realpath(maintenance_backup_dir().'/'.$file);
if (!$root || !$path || !str_starts_with($path, $root.DIRECTORY_SEPARATOR) || !is_file($path)) not_found('Backup file not found.');
admin_log($pdo, 'system', 'Downloaded backup', $file);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.str_replace('"', '', $file).'"');
header('Content-Length: '.filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
