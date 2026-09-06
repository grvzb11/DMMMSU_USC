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
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/helpers.php';
require_once __DIR__.'/../config/case_report_template.php';
require_once __DIR__.'/../config/case_pdf.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) not_found('Concern not found.');

$st = $pdo->prepare('SELECT c.*,a.full_name assigned_name FROM concerns c LEFT JOIN admins a ON a.id=c.assigned_to WHERE c.id=? LIMIT 1');
$st->execute([$id]);
$concern = $st->fetch();
if (!$concern) not_found('Concern not found.');
require_admin_concern($concern);

$attachments = concern_attachments($pdo, $id);
$imageAttachments = [];
$otherAttachmentNames = [];
foreach ($attachments as $attachment) {
    $name = trim((string)($attachment['original_name'] ?? ''));
    if ($name === '') continue;
    $mime = strtolower(trim((string)($attachment['mime_type'] ?? '')));
    $relative = ltrim(trim((string)($attachment['file_path'] ?? '')), '/');
    $fullPath = $relative !== '' ? dirname(__DIR__).'/'.$relative : '';
    if ($mime === 'image/jpeg' && $fullPath !== '' && is_file($fullPath)) {
        $imageAttachments[] = ['name' => $name, 'path' => $fullPath];
    } else {
        $otherAttachmentNames[] = $name;
    }
}

$sealPath = __DIR__.'/../public/assets/images/usc-report-seal.jpg';
$markPath = __DIR__.'/../public/assets/images/usc-report-mark.jpg';
$templatePath = __DIR__.'/../storage/templates/USC Template.docx';
$pdf = new CasePdfDocument($sealPath, $markPath, $templatePath);

$reference = (string)($concern['reference_code'] ?? ('Concern #'.$id));
$submitted = !empty($concern['created_at']) ? date('F j, Y - g:i A', strtotime((string)$concern['created_at'])) : 'Not available';
$status = (string)($concern['status'] ?? 'Submitted');
$priority = (string)($concern['priority'] ?? 'Normal');
$subject = trim((string)($concern['subject'] ?? ''));
$type = trim((string)($concern['concern_type'] ?? ''));
$message = trim((string)($concern['message'] ?? ''));
$studentUpdate = trim((string)($concern['admin_note'] ?? ''));
$resolution = trim((string)($concern['resolution_summary'] ?? ''));

$pdf->reportHeading('E-Sumbong Case Report', $reference);
$pdf->metaRow([
    'Submitted: '.$submitted,
    'Status: '.$status,
    'Priority: '.$priority,
]);
$pdf->divider(5, 14);

$pdf->sectionLabel('Case summary');
$pdf->detailRow('Subject', $subject !== '' ? $subject : 'Not provided');
$pdf->detailRow('Concern type', $type !== '' ? $type : 'Not provided');

$pdf->spacer(5);
$pdf->sectionLabel('Student message');
$pdf->justifiedParagraph($message !== '' ? $message : 'Not provided', 10.0, 15.0);

if ($studentUpdate !== '') {
    $pdf->spacer(7);
    $pdf->sectionLabel('Student-facing update');
    $pdf->justifiedParagraph($studentUpdate, 10.0, 15.0);
}

if ($resolution !== '') {
    $pdf->spacer(7);
    $pdf->sectionLabel('Resolution / Action Taken');
    $pdf->justifiedParagraph($resolution, 10.0, 15.0);
}

if ($imageAttachments || $otherAttachmentNames) {
    $pdf->spacer(8);
    $pdf->sectionLabel('Case evidence / attachments');
    $unrenderedImages = $pdf->evidenceGallery($imageAttachments, 225.0, 180.0, 12.0, 14.0);
    foreach ($unrenderedImages as $unrenderedImage) {
        if ($unrenderedImage !== '') $otherAttachmentNames[] = $unrenderedImage;
    }
    if ($otherAttachmentNames) {
        $pdf->attachmentList($otherAttachmentNames);
    }
}

$pdf->spacer(8);
$pdf->simpleNote('Privacy note: Protected student identity details (name, Student ID, and contact email) are intentionally excluded from this case record. Use the secured E-Sumbong administration page when identity verification is required.');

$binary = $pdf->output();
$filenameBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', $reference) ?: ('concern-'.$id);
$filename = $filenameBase.'-case-report.pdf';

try {
    admin_log(
        $pdo,
        'concerns',
        'Downloaded case PDF',
        (string)($concern['reference_code'] ?? ('Concern #'.$id)),
        'concern',
        $id,
        null,
        ['report' => $filename]
    );
} catch (Throwable $ignored) {
    // Ignore audit logging failure for report generation.
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.strlen($binary));
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
echo $binary;
exit;
