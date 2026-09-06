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

if (!db_table_exists($pdo, 'concern_identity_access_requests')) {
    http_response_code(503);
    exit('Identity access controls are not ready. Run migration 2026090503 in the Migration Center.');
}

$action = strtolower(trim((string)($_POST['action'] ?? '')));
$concernId = (int)($_POST['concern_id'] ?? 0);
$requestId = (int)($_POST['request_id'] ?? 0);

if ($concernId <= 0 && $requestId > 0) {
    $lookup = $pdo->prepare('SELECT concern_id FROM concern_identity_access_requests WHERE id=? LIMIT 1');
    $lookup->execute([$requestId]);
    $concernId = (int)($lookup->fetchColumn() ?: 0);
}
if ($concernId <= 0) not_found('Concern not found.');

$caseSt = $pdo->prepare('SELECT * FROM concerns WHERE id=? LIMIT 1');
$caseSt->execute([$concernId]);
$concern = $caseSt->fetch();
if (!$concern) not_found('Concern not found.');
require_admin_concern($concern);

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$adminName = trim((string)($_SESSION['admin_name'] ?? 'Administrator'));
$reference = (string)($concern['reference_code'] ?? ('Concern #'.$concernId));
$redirect = 'concerns.php?id='.$concernId;

if ($action === 'request') {
    if (is_system_admin()) {
        header('Location: '.$redirect.'&identity_access=not_required#protected-student-details');
        exit;
    }

    $reason = trim((string)($_POST['reason'] ?? ''));
    $reason = preg_replace('/\s+/u', ' ', $reason) ?? $reason;
    if (strlen($reason) < 10 || strlen($reason) > 500) {
        header('Location: '.$redirect.'&identity_access=reason_required#protected-student-details');
        exit;
    }

    $check = $pdo->prepare("SELECT id,status,approved_until FROM concern_identity_access_requests WHERE concern_id=? AND requested_by=? AND (status='pending' OR (status='approved' AND (approved_until IS NULL OR approved_until>NOW()))) ORDER BY requested_at DESC LIMIT 1");
    $check->execute([$concernId, $adminId]);
    $existing = $check->fetch();
    if ($existing) {
        $state = $existing['status'] === 'approved' ? 'already_approved' : 'already_pending';
        header('Location: '.$redirect.'&identity_access='.rawurlencode($state).'#protected-student-details');
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO concern_identity_access_requests(concern_id,requested_by,requester_name,reason,status) VALUES(?,?,?,?, 'pending')");
    $insert->execute([$concernId, $adminId ?: null, $adminName, $reason]);
    $newId = (int)$pdo->lastInsertId();

    admin_log($pdo, 'privacy', 'Requested student identity access', $reference.' · '.$reason, 'concern', $concernId, null, ['request_id' => $newId, 'status' => 'pending']);
    admin_notify(
        $pdo,
        'Identity access request',
        $adminName.' requested access to protected student identity for '.$reference.'.',
        'concerns.php?id='.$concernId.'#protected-student-details',
        'warning',
        'admin',
        null,
        null,
        'privacy'
    );

    header('Location: '.$redirect.'&identity_access=requested#protected-student-details');
    exit;
}

if (in_array($action, ['approve', 'reject', 'revoke'], true)) {
    if (!is_system_admin()) deny_access('Only the System Administrator can review identity access requests.');
    if ($requestId <= 0) not_found('Identity access request not found.');

    $requestSt = $pdo->prepare('SELECT * FROM concern_identity_access_requests WHERE id=? AND concern_id=? LIMIT 1');
    $requestSt->execute([$requestId, $concernId]);
    $request = $requestSt->fetch();
    if (!$request) not_found('Identity access request not found.');

    $reviewNote = trim((string)($_POST['review_note'] ?? ''));
    $reviewNote = preg_replace('/\s+/u', ' ', $reviewNote) ?? $reviewNote;
    if (strlen($reviewNote) > 500) $reviewNote = substr($reviewNote, 0, 500);

    if ($action === 'approve') {
        if ((string)$request['status'] !== 'pending') {
            header('Location: '.$redirect.'&identity_access=already_reviewed#protected-student-details');
            exit;
        }
        $approvedUntil = date('Y-m-d H:i:s', time() + 86400);
        $update = $pdo->prepare("UPDATE concern_identity_access_requests SET status='approved',reviewed_by=?,reviewed_at=NOW(),review_note=?,approved_until=? WHERE id=? AND status='pending'");
        $update->execute([$adminId ?: null, $reviewNote !== '' ? $reviewNote : 'Approved by System Administrator.', $approvedUntil, $requestId]);
        $resultMessage = 'Access approved for 24 hours.';
        $notifyKind = 'success';
        $logAction = 'Approved student identity access';
        $redirectState = 'approved';
    } elseif ($action === 'reject') {
        if ((string)$request['status'] !== 'pending') {
            header('Location: '.$redirect.'&identity_access=already_reviewed#protected-student-details');
            exit;
        }
        $update = $pdo->prepare("UPDATE concern_identity_access_requests SET status='rejected',reviewed_by=?,reviewed_at=NOW(),review_note=?,approved_until=NULL WHERE id=? AND status='pending'");
        $update->execute([$adminId ?: null, $reviewNote !== '' ? $reviewNote : 'Request rejected by System Administrator.', $requestId]);
        $resultMessage = 'Access request was rejected.';
        $notifyKind = 'warning';
        $logAction = 'Rejected student identity access';
        $redirectState = 'rejected';
    } else {
        if ((string)$request['status'] !== 'approved') {
            header('Location: '.$redirect.'&identity_access=already_reviewed#protected-student-details');
            exit;
        }
        $update = $pdo->prepare("UPDATE concern_identity_access_requests SET status='revoked',reviewed_by=?,reviewed_at=NOW(),review_note=?,approved_until=NOW() WHERE id=? AND status='approved'");
        $update->execute([$adminId ?: null, $reviewNote !== '' ? $reviewNote : 'Access revoked by System Administrator.', $requestId]);
        $resultMessage = 'Previously approved identity access was revoked.';
        $notifyKind = 'warning';
        $logAction = 'Revoked student identity access';
        $redirectState = 'revoked';
    }

    if (!empty($request['requested_by'])) {
        admin_notify(
            $pdo,
            'Identity access '.$redirectState,
            $reference.' · '.$resultMessage,
            'concerns.php?id='.$concernId.'#protected-student-details',
            $notifyKind,
            null,
            null,
            (int)$request['requested_by'],
            'privacy'
        );
    }
    admin_log($pdo, 'privacy', $logAction, $reference.' · Request #'.$requestId, 'concern', $concernId, ['status' => $request['status']], ['status' => $redirectState, 'request_id' => $requestId]);

    header('Location: '.$redirect.'&identity_access='.rawurlencode($redirectState).'#protected-student-details');
    exit;
}

if ($action === 'cancel') {
    if ($requestId <= 0) not_found('Identity access request not found.');
    $cancel = $pdo->prepare("UPDATE concern_identity_access_requests SET status='cancelled',updated_at=NOW() WHERE id=? AND concern_id=? AND requested_by=? AND status='pending'");
    $cancel->execute([$requestId, $concernId, $adminId]);
    if ($cancel->rowCount()) {
        admin_log($pdo, 'privacy', 'Cancelled student identity access request', $reference.' · Request #'.$requestId, 'concern', $concernId);
    }
    header('Location: '.$redirect.'&identity_access=cancelled#protected-student-details');
    exit;
}

http_response_code(400);
exit('Invalid identity access action.');
