<?php
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/config/helpers.php';

$id = (int)($_GET['id'] ?? 0);
$code = strtoupper(trim((string)($_GET['code'] ?? '')));
$expires = (int)($_GET['exp'] ?? 0);
$signature = strtolower(trim((string)($_GET['sig'] ?? '')));
$download = !empty($_GET['download']);

if (!$id || $code === '' || !$expires || $signature === '' || !db_table_exists($pdo, 'concern_attachments')) {
    not_found('Attachment not found.');
}

$st = $pdo->prepare("SELECT ca.*, c.reference_code, c.id AS concern_id
FROM concern_attachments ca
INNER JOIN concerns c ON c.id=ca.concern_id
WHERE ca.id=? AND UPPER(c.reference_code)=?
AND LOWER(COALESCE(ca.source,'student')) IN ('student','student_followup','admin')
LIMIT 1");
$st->execute([$id, $code]);
$attachment = $st->fetch();
if (!$attachment) not_found('Attachment not found.');

if (!concern_public_attachment_signature_is_valid(
(int)$attachment['id'],
(int)$attachment['concern_id'],
(string)$attachment['reference_code'],
$expires,
$signature
)) {
    app_render_error_page(403, 'Access expired', 'This protected attachment link is invalid or has expired. Return to Track Concern and verify your Student ID again.');
}

$mime = strtolower(trim((string)$attachment['mime_type']));
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
if (!in_array($mime, $allowed, true)) not_found('Attachment type is not available for public viewing.');

$full = app_root(ltrim((string)$attachment['file_path'], '/'));
if (!is_file($full)) not_found('The attachment file is missing.');

$filename = str_replace(['"', "\r", "\n"], '_', basename((string)$attachment['original_name']));
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: sandbox");
header('Cache-Control: no-store, private');
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($full));
header('Content-Disposition: '.($download?'attachment':'inline').'; filename="'.$filename.'"');
readfile($full);
