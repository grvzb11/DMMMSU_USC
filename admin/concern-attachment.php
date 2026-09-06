<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
if (!$id || !db_table_exists($pdo, 'concern_attachments')) not_found('Attachment not found.');
$st = $pdo->prepare('SELECT ca.*,c.source_portal,c.assigned_scope,c.id concern_id FROM concern_attachments ca INNER JOIN concerns c ON c.id=ca.concern_id WHERE ca.id=? LIMIT 1');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) not_found('Attachment not found.');
require_admin_concern($a);
$full = app_root(ltrim((string)$a['file_path'], '/'));
if (!is_file($full)) not_found('The attachment file is missing.');
$mimeType = (string)($a['mime_type'] ?: 'application/octet-stream');
$inlineView = isset($_GET['view']) && $_GET['view'] === '1' && str_starts_with(strtolower($mimeType), 'image/');
$isPreview = $inlineView && isset($_GET['preview']) && $_GET['preview'] === '1';

if (!$isPreview) {
    concern_log_privacy_access(
        $pdo,
        (int)$a['concern_id'],
        $inlineView ? 'Viewed concern evidence' : 'Downloaded concern evidence',
        'Case evidence review'
    );
}

$filename = str_replace(['"', "\r", "\n"], '_', basename((string)$a['original_name']));
header('Content-Type: '.$mimeType);
header('Content-Length: '.filesize($full));
header('Content-Disposition: '.($inlineView ? 'inline' : 'attachment').'; filename="'.$filename.'"');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
readfile($full);
