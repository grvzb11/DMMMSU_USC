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
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT id,reference_code,student_name,student_id,anonymous,source_portal,assigned_scope FROM concerns WHERE id=? LIMIT 1');
$st->execute([$id]);
$c = $st->fetch();
if (!$c) not_found('Concern not found.');
require_admin_concern($c);

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$accessRequestId = null;
$canReveal = is_system_admin();

if (!$canReveal && db_table_exists($pdo, 'concern_identity_access_requests')) {
    try {
        $accessSt = $pdo->prepare("SELECT id FROM concern_identity_access_requests WHERE concern_id=? AND requested_by=? AND status='approved' AND (approved_until IS NULL OR approved_until>NOW()) ORDER BY reviewed_at DESC,id DESC LIMIT 1");
        $accessSt->execute([$id, $adminId]);
        $accessRequestId = (int)($accessSt->fetchColumn() ?: 0);
        $canReveal = $accessRequestId > 0;
    } catch (Throwable $ignored) {
        $canReveal = false;
    }
}

if (!$canReveal) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode([
        'error' => 'System Administrator approval is required before protected student identity can be revealed.'
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

$identity = concern_private_identity($pdo, $c);
$purpose = is_system_admin() ? 'Direct System Administrator access' : 'Approved identity access request #'.$accessRequestId;
concern_log_privacy_access($pdo, $id, 'Viewed student identity', $purpose);

if ($accessRequestId && db_table_exists($pdo, 'concern_identity_access_requests')) {
    try {
        $pdo->prepare('UPDATE concern_identity_access_requests SET last_viewed_at=NOW() WHERE id=?')->execute([$accessRequestId]);
    } catch (Throwable $ignored) {
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
echo json_encode([
    'anonymous' => (bool)$c['anonymous'],
    'name' => !empty($c['anonymous'])?'Anonymous submission':((string)($identity['name'] ?? '')?:'Not provided'),
    'student_id' => !empty($c['anonymous'])?'Not stored':((string)($identity['student_id'] ?? '')?:'Not provided'),
    'email' => !empty($c['anonymous'])?'Not stored':((string)($identity['email'] ?? '')?:'Not provided'),
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
