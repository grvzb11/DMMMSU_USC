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
require_once '_layout.php';
$scope = admin_scope_code();
$portalOptions = ['USC' => 'University Student Council']+admin_campuses();
$statuses = ['Submitted', 'Received', 'Under Review', 'Referred', 'In Progress', 'Action Taken', 'Resolved', 'Closed'];
$activeStatuses = ['Submitted', 'Received', 'Under Review', 'Referred', 'In Progress', 'Action Taken'];
$completedStatuses = ['Resolved', 'Closed'];
$priorities = ['Low', 'Normal', 'High', 'Urgent'];
$canRouteAcross = can_route_concerns();
$workflowReady = db_column_exists($pdo, 'concerns', 'due_at');
$advancedCaseReady = db_column_exists($pdo, 'concerns', 'resolution_summary');
$caseAttachmentMaxFiles = max(1, min(5, (int)(system_setting($pdo, 'esumbong_attachment_max_files', '3') ?? 3)));
$caseAttachmentMaxMb = max(1, min(10, (int)(system_setting($pdo, 'esumbong_attachment_max_mb', '5') ?? 5)));
if ($workflowReady) {
    concern_sync_active_deadlines($pdo);
    concern_run_response_reminders($pdo, $scope);
    concern_run_followup_reminders($pdo, $scope);
}
$responseTemplates = [];
if (db_table_exists($pdo, 'concern_response_templates')) {
    try {
        $tw = $scope?' AND (campus IS NULL OR UPPER(campus)=?)':'';
        $ts = $pdo->prepare("SELECT id,title,response_text,concern_type,campus FROM concern_response_templates WHERE is_active=1$tw ORDER BY title");
        $tp = [];
        if ($scope) $tp[] = $scope;
        $ts->execute($tp);
        $responseTemplates = $ts->fetchAll();
    } catch (Throwable $ignored) {
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM concerns WHERE id=?');
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) not_found('Concern not found.');
    require_admin_concern($existing);

    // Saved case evidence is removable independently from the rest of the case update form.
    // Only administrator-added evidence can be removed here; the student's original submission is protected.
    $removeEvidenceId = (int)($_POST['remove_case_evidence'] ?? 0);
    if ($removeEvidenceId > 0) {
        if (!db_table_exists($pdo, 'concern_attachments')) not_found('Case evidence not found.');
        $evidenceSt = $pdo->prepare("SELECT * FROM concern_attachments WHERE id=? AND concern_id=? AND LOWER(source)='admin' LIMIT 1");
        $evidenceSt->execute([$removeEvidenceId, $id]);
        $evidence = $evidenceSt->fetch();
        if (!$evidence) not_found('Case evidence not found.');

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM concern_attachments WHERE id=? AND concern_id=? AND LOWER(source)='admin'");
            $del->execute([$removeEvidenceId, $id]);
            if ($del->rowCount() !== 1) throw new RuntimeException('Unable to remove case evidence.');

            if (db_table_exists($pdo, 'concern_history')) {
                $history = $pdo->prepare('INSERT INTO concern_history(concern_id,admin_id,actor_name,action,old_status,status,old_assigned_scope,assigned_scope,assigned_to,public_note,internal_note) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
                $history->execute([
                    $id,
                    $_SESSION['admin_id'] ?? null,
                    $_SESSION['admin_name'] ?? null,
                    'Removed case evidence',
                    $existing['status'] ?? null,
                    $existing['status'] ?? null,
                    concern_assigned_scope($existing),
                    concern_assigned_scope($existing),
                    !empty($existing['assigned_to'])?(int)$existing['assigned_to']:null,
                    '',
                    'Removed case evidence: '.basename((string)$evidence['original_name']),
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        // Delete the physical file only when no other attachment record references it.
        $storedPath = trim((string)($evidence['file_path'] ?? ''));
        if ($storedPath !== '') {
            $refSt = $pdo->prepare('SELECT COUNT(*) FROM concern_attachments WHERE file_path=?');
            $refSt->execute([$storedPath]);
            if ((int)$refSt->fetchColumn() === 0) {
                $candidate = app_root(ltrim(str_replace('\\', '/', $storedPath), '/'));
                $evidenceDir = realpath(concern_attachment_dir());
                $realFile = is_file($candidate)?realpath($candidate):false;
                if ($evidenceDir && $realFile && str_starts_with($realFile, $evidenceDir.DIRECTORY_SEPARATOR)) {
                    @unlink($realFile);
                }
            }
        }

        admin_log(
            $pdo,
            'concerns',
            'Removed case evidence',
            ($existing['reference_code'] ?? ('Concern #'.$id)).' · '.basename((string)$evidence['original_name']),
            'concern',
            $id,
            ['attachment_id' => $removeEvidenceId, 'file' => $evidence['original_name']],
            ['removed' => true]
        );
        header('Location: concerns.php?id='.$id.'&evidence_removed=1');
        exit;
    }

    $status = in_array($_POST['status'] ?? '', $statuses, true)?$_POST['status']:$existing['status'];
    if (in_array($status, $completedStatuses, true) && !can_close_concerns()) deny_access('Your role cannot resolve or close concerns.');
    if (in_array((string)$existing['status'], $completedStatuses, true) && !in_array($status, $completedStatuses, true) && !can_close_concerns()) {
        deny_access('Your role cannot reopen completed concerns.');
    }
    $priority = in_array($_POST['priority'] ?? '', $priorities, true)?$_POST['priority']:'Normal';
    $assignedTo = can_assign_concerns() && !empty($_POST['assigned_to'])?(int)$_POST['assigned_to']:($existing['assigned_to'] ?? null);
    $assignedScope = concern_assigned_scope($existing);

    if (can_assign_concerns()) {
        $requestedScope = strtoupper(trim((string)($_POST['assigned_scope'] ?? $assignedScope)));
        if ($canRouteAcross && isset($portalOptions[$requestedScope])) $assignedScope = $requestedScope;
        elseif ($scope) $assignedScope = $scope;

        if ($assignedTo) {
            $ast = $pdo->prepare("SELECT id,role,campus,status FROM admins WHERE id=? LIMIT 1");
            $ast->execute([$assignedTo]);
            $assignee = $ast->fetch();
            if (!$assignee || $assignee['status'] !== 'active') {
                http_response_code(422);
                exit('Selected personnel is not available.');
            }
            $assigneeScope = null;
            if (in_array($assignee['role'], ['usc', 'sas_director'], true)) $assigneeScope = 'USC';
            elseif (!empty($assignee['campus']) && isset(admin_campuses()[strtoupper($assignee['campus'])])) $assigneeScope = strtoupper($assignee['campus']);
            if ($assigneeScope) {
                if ($assignedScope !== $assigneeScope) {
                    http_response_code(422);
                    exit('Selected personnel does not belong to the selected unit.');
                }
                if (!$canRouteAcross && $scope !== $assigneeScope) deny_access('You cannot assign this concern outside your portal.');
                $assignedScope = $assigneeScope;
            }
        }
    }

    $publicNote = trim($_POST['admin_note'] ?? '');
    $internalNote = trim($_POST['internal_note'] ?? '');
    $resolutionSummary = trim((string)($_POST['resolution_summary'] ?? ($existing['resolution_summary'] ?? '')));
    $escalationReason = trim((string)($_POST['escalation_reason'] ?? ($existing['escalation_reason'] ?? '')));
    $casePolicy = concern_type_policy($pdo, (string)($existing['concern_type'] ?? 'Concern'));
    $requiresResolutionSummary = !empty($casePolicy['formal']);
    if ($advancedCaseReady && $requiresResolutionSummary && in_array($status, $completedStatuses, true) && $resolutionSummary === '') {
        header('Location: concerns.php?id='.$id.'&validation=resolution&status='.rawurlencode($status));
        exit;
    }
    $previousScope = concern_assigned_scope($existing);
    $previousStatus = (string)$existing['status'];
    $previousPriority = (string)($existing['priority'] ?? 'Normal');
    $previousAssignedTo = !empty($existing['assigned_to'])?(int)$existing['assigned_to']:null;
    $isReopening = in_array($previousStatus, $completedStatuses, true) && !in_array($status, $completedStatuses, true);
    if ($isReopening && $internalNote === '') {
        header('Location: concerns.php?id='.$id.'&view=completed&validation=reopen&status='.rawurlencode($status));
        exit;
    }
    if ($assignedScope !== $previousScope) {
        $routingNote = 'Routing changed from '.$previousScope.' to '.$assignedScope.'.';
        $internalNote = $internalNote !== ''?$routingNote."\n".$internalNote:$routingNote;
    }
    $resolvedAt = in_array($status, $completedStatuses, true)?(!empty($existing['resolved_at'])?$existing['resolved_at']:date('Y-m-d H:i:s')):null;
    if ($workflowReady) {
        $followInput = trim((string)($_POST['follow_up_at'] ?? ''));
        // The review reminder is guidance only, guidance only and does not mark a case late.
        // Active cases follow the configured reminder interval for their priority,
        // measured from the original submission time. Closed/resolved cases keep
        // their historical reminder target unless the priority is changed.
        $isCompleted = in_array($status, $completedStatuses, true);
        if (!$isCompleted || $previousPriority !== $priority || empty($existing['due_at'])) {
            $dueAt = concern_default_due_at($pdo, $priority, $existing['created_at'] ?? null);
        } else {
            $dueAt = (string)$existing['due_at'];
        }
        $followUpAt = $followInput !== ''?date('Y-m-d H:i:s', strtotime($followInput)?:time()):null;
        $firstResponse = $existing['first_response_at'] ?? null;
        if (!$firstResponse && ($publicNote !== '' || !in_array($status, ['Submitted'], true))) $firstResponse = date('Y-m-d H:i:s');
        $lastPublic = $existing['last_public_update_at'] ?? null;
        if ($publicNote !== '' && $publicNote !== (string)($existing['admin_note'] ?? '')) $lastPublic = date('Y-m-d H:i:s');
        $retention = null;
        if ($resolvedAt) {
            $days = max(30, min(3650, (int)(system_setting($pdo, 'privacy_retention_days', '730') ?? 730)));
            $retention = date('Y-m-d', strtotime($resolvedAt.' +'.$days.' days'));
        }
        if ($advancedCaseReady) {
            $pdo->prepare('UPDATE concerns SET status=?,priority=?,assigned_to=?,assigned_scope=?,due_at=?,follow_up_at=?,first_response_at=?,last_public_update_at=?,admin_note=?,internal_note=?,resolution_summary=?,escalation_reason=?,resolved_at=?,retention_until=? WHERE id=?')
            ->execute([$status, $priority, $assignedTo?:null, $assignedScope, $dueAt, $followUpAt, $firstResponse, $lastPublic, $publicNote, $internalNote, $resolutionSummary, $escalationReason, $resolvedAt, $retention, $id]);
        } else {
            $pdo->prepare('UPDATE concerns SET status=?,priority=?,assigned_to=?,assigned_scope=?,due_at=?,follow_up_at=?,first_response_at=?,last_public_update_at=?,admin_note=?,internal_note=?,resolved_at=?,retention_until=? WHERE id=?')
            ->execute([$status, $priority, $assignedTo?:null, $assignedScope, $dueAt, $followUpAt, $firstResponse, $lastPublic, $publicNote, $internalNote, $resolvedAt, $retention, $id]);
        }
    } else {
        $pdo->prepare('UPDATE concerns SET status=?,priority=?,assigned_to=?,assigned_scope=?,admin_note=?,internal_note=?,resolved_at=? WHERE id=?')
        ->execute([$status, $priority, $assignedTo?:null, $assignedScope, $publicNote, $internalNote, $resolvedAt, $id]);
    }
    $changeParts = [];
    if ($previousStatus !== $status) $changeParts[] = 'Status '.$previousStatus.' → '.$status;
    if ($previousPriority !== $priority) $changeParts[] = 'Priority '.$previousPriority.' → '.$priority;
    if ($previousScope !== $assignedScope) $changeParts[] = 'Route '.$previousScope.' → '.$assignedScope;
    if ($previousAssignedTo !== ($assignedTo?:null)) $changeParts[] = 'Assignee changed';
    $historyAction = $isReopening?'Reopened concern':($previousScope !== $assignedScope?'Referred concern':($previousAssignedTo !== ($assignedTo?:null)?'Reassigned concern':($previousStatus !== $status?'Changed status':'Updated concern')));
    $pdo->prepare('INSERT INTO concern_history(concern_id,admin_id,actor_name,action,old_status,status,old_assigned_scope,assigned_scope,assigned_to,public_note,internal_note) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$id, $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? null, $historyAction, $previousStatus, $status, $previousScope, $assignedScope, $assignedTo?:null, $publicNote, $internalNote]);

    admin_log($pdo, 'concerns', $historyAction, ($existing['reference_code'].' · '.($changeParts?implode(' · ', $changeParts):'Notes updated')), 'concern', $id,
    ['status' => $previousStatus, 'priority' => $previousPriority, 'assigned_scope' => $previousScope, 'assigned_to' => $previousAssignedTo],
    ['status' => $status, 'priority' => $priority, 'assigned_scope' => $assignedScope, 'assigned_to' => $assignedTo?:null, 'due_at' => $workflowReady?($dueAt ?? null):null, 'follow_up_at' => $workflowReady?($followUpAt ?? null):null, 'resolution_summary' => $advancedCaseReady?$resolutionSummary:null]);
    if ($advancedCaseReady && !empty($_FILES['case_attachments']['name'][0])) {
        $rl = max(5, (int)(system_setting($pdo, 'security_rate_limit_uploads_per_hour', '40') ?? 40));
        platform_rate_limit_or_429($pdo, 'admin-upload', ((string)($_SESSION['admin_id'] ?? 0)).'|'.admin_current_ip(), $rl, 3600, 900);
        $attachmentErrors = concern_store_attachments($pdo, $id, $_FILES['case_attachments'], (int)($_SESSION['admin_id'] ?? 0), 'admin');
        if ($attachmentErrors) admin_log($pdo, 'concerns', 'Evidence upload warning', implode(' ', $attachmentErrors), 'concern', $id);
    }
    if ($assignedScope !== $previousScope) {
        $routeMessage = $existing['reference_code'].' was routed from '.$previousScope.' to '.$assignedScope;
        admin_notify($pdo, 'Concern referred to '.$assignedScope, $routeMessage, 'concerns.php?id='.$id, 'warning', null, $assignedScope, null, 'concerns');
        admin_notify($pdo, 'Concern routing changed', $routeMessage, 'concerns.php?id='.$id, 'warning', 'admin', null, null, 'concerns');
    } elseif ($status !== $existing['status']) {
        $statusMessage = $existing['reference_code'].' is now '.$status;
        admin_notify($pdo, 'Concern status changed', $statusMessage, 'concerns.php?id='.$id, 'info', null, $assignedScope, null, 'concerns');
        admin_notify($pdo, 'Concern status changed', $statusMessage.' · '.$assignedScope, 'concerns.php?id='.$id, 'info', 'admin', null, null, 'concerns');
    }
    if (($status !== $previousStatus || ($publicNote !== '' && $publicNote !== (string)($existing['admin_note'] ?? ''))) && system_setting($pdo, 'esumbong_email_updates_enabled', '1') === '1' && system_setting($pdo, 'notification_email_enabled', '0') === '1') {
        $identity = concern_private_identity($pdo, $existing);
        $contactEmail = trim((string)($identity['email'] ?? ''));
        if ($contactEmail !== '' && filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $body = 'Your E-Sumbong concern '.$existing['reference_code'].' has an update.\n\nStatus: '.$status;
            if ($publicNote !== '') $body.='\n\nLatest update: '.$publicNote;
            $body.='\n\nUse your reference number on E-Sumbong Track Concern for the latest student-visible status.';
            admin_queue_email($pdo, $contactEmail, 'E-Sumbong case update: '.$existing['reference_code'], $body, 'concerns', null, $id);
        }
    }
    // If this update routed the concern away from the current administrator's
    // accessible scope, do not redirect back to a detail page that will now
    // correctly fail authorization. Return to the queue with a clear success
    // notice instead. Global administrators and units that still retain access
    // continue back to the updated concern as usual.
    $postUpdateConcern = $existing;
    $postUpdateConcern['assigned_scope'] = $assignedScope;
    $postUpdateConcern['assigned_to'] = $assignedTo?:null;
    if (!admin_can_access_concern($postUpdateConcern)) {
        header('Location: concerns.php?routed=1&to='.rawurlencode($assignedScope));
        exit;
    }
    $wasCompleted = in_array($previousStatus, $completedStatuses, true);
    $isNowCompleted = in_array($status, $completedStatuses, true);
    if (!$wasCompleted && $isNowCompleted) {
        header('Location: concerns.php?view=completed&completed=1&ref='.rawurlencode((string)$existing['reference_code']));
        exit;
    }
    if ($wasCompleted && !$isNowCompleted) {
        header('Location: concerns.php?view=active&reopened=1&ref='.rawurlencode((string)$existing['reference_code']));
        exit;
    }
    header('Location: concerns.php?id='.$id.'&updated=1&view='.($isNowCompleted?'completed':'active'));
    exit;
}

$edit = null;
$history = [];
$attachments = [];
$studentEvidence = [];
$adminEvidence = [];
$identityProtected = false;
$identityAccessReady = db_table_exists($pdo, 'concern_identity_access_requests');
$identityAccessRequest = null;
$identityPendingRequests = [];
$identityActiveApprovals = [];
$identityCanReveal = is_system_admin();
if (isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT c.*,a.full_name assigned_name FROM concerns c LEFT JOIN admins a ON a.id=c.assigned_to WHERE c.id=?');
    $st->execute([(int)$_GET['id']]);
    $edit = $st->fetch();
    if ($edit) {
        require_admin_concern($edit);
        $st = $pdo->prepare('SELECT h.*,COALESCE(h.actor_name,a.full_name) admin_name,assignee.full_name assigned_name FROM concern_history h LEFT JOIN admins a ON a.id=h.admin_id LEFT JOIN admins assignee ON assignee.id=h.assigned_to WHERE h.concern_id=? ORDER BY h.created_at DESC');
        $st->execute([$edit['id']]);
        $history = $st->fetchAll();
        $attachments = concern_attachments($pdo, (int)$edit['id']);
        $studentEvidence = array_values(array_filter($attachments, static fn(array $attachment): bool => strtolower((string)($attachment['source'] ?? 'student')) !== 'admin'));
        $adminEvidence = array_values(array_filter($attachments, static fn(array $attachment): bool => strtolower((string)($attachment['source'] ?? '')) === 'admin'));
        if (db_table_exists($pdo, 'concern_private_identity')) {
            try {
                $ps = $pdo->prepare('SELECT COUNT(*) FROM concern_private_identity WHERE concern_id=?');
                $ps->execute([(int)$edit['id']]);
                $identityProtected = (int)$ps->fetchColumn()>0;
            } catch (Throwable $ignored) {
            }
        }
        if ($identityAccessReady && empty($edit['anonymous'])) {
            try {
                $currentAdminId = (int)($_SESSION['admin_id'] ?? 0);
                if (is_system_admin()) {
                    $rq = $pdo->prepare("SELECT r.*,a.full_name,a.role,a.campus FROM concern_identity_access_requests r LEFT JOIN admins a ON a.id=r.requested_by WHERE r.concern_id=? AND r.status='pending' ORDER BY r.requested_at ASC,r.id ASC");
                    $rq->execute([(int)$edit['id']]);
                    $identityPendingRequests = $rq->fetchAll();
                    $aq = $pdo->prepare("SELECT r.*,a.full_name,a.role,a.campus FROM concern_identity_access_requests r LEFT JOIN admins a ON a.id=r.requested_by WHERE r.concern_id=? AND r.status='approved' AND (r.approved_until IS NULL OR r.approved_until>NOW()) ORDER BY r.approved_until ASC,r.id ASC");
                    $aq->execute([(int)$edit['id']]);
                    $identityActiveApprovals = $aq->fetchAll();
                    $identityCanReveal = true;
                } elseif ($currentAdminId > 0) {
                    $rq = $pdo->prepare("SELECT * FROM concern_identity_access_requests WHERE concern_id=? AND requested_by=? ORDER BY requested_at DESC,id DESC LIMIT 1");
                    $rq->execute([(int)$edit['id'], $currentAdminId]);
                    $identityAccessRequest = $rq->fetch() ?: null;
                    if ($identityAccessRequest && (string)$identityAccessRequest['status'] === 'approved') {
                        $until = trim((string)($identityAccessRequest['approved_until'] ?? ''));
                        $identityCanReveal = ($until === '' || strtotime($until) > time());
                        if (!$identityCanReveal) $identityAccessRequest['status'] = 'expired';
                    }
                }
            } catch (Throwable $ignored) {
                $identityAccessReady = false;
                $identityAccessRequest = null;
                $identityPendingRequests = [];
                $identityActiveApprovals = [];
                $identityCanReveal = is_system_admin();
            }
        }
    }
}

$view = strtolower(trim((string)($_GET['view'] ?? 'active')));
if (!in_array($view, ['active', 'completed'], true)) $view = 'active';
$isCompletedView = $view === 'completed';
$visibleStatuses = $isCompletedView ? $completedStatuses : $activeStatuses;
$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter !== 'all' && !in_array($statusFilter, $visibleStatuses, true)) $statusFilter = 'all';
$unit = trim($_GET['unit'] ?? 'all');
$priorityFilter = $_GET['priority'] ?? 'all';
$queueFilter = $_GET['queue'] ?? 'all';
$allowedQueues = $isCompletedView ? ['all'] : ['all', 'attention', 'overdue', 'unassigned', 'followup', 'new'];
if (!in_array($queueFilter, $allowedQueues, true)) $queueFilter = 'all';
$hasActiveFilters = ($q !== '' || $statusFilter !== 'all' || in_array($priorityFilter, $priorities, true) || $queueFilter !== 'all' || (!$scope && isset($portalOptions[$unit])));
$where = [];
$params = [];
if ($scope) {
    $where[] = '(UPPER(c.source_portal)=? OR UPPER(c.assigned_scope)=?)';
    array_push($params, $scope, $scope);
}
$where[] = $isCompletedView ? "c.status IN ('Resolved','Closed')" : "c.status NOT IN ('Resolved','Closed')";
if ($q !== '') {
    $where[] = '(c.reference_code LIKE ? OR c.subject LIKE ? OR c.student_name LIKE ? OR c.message LIKE ?)';
    $like = '%'.$q.'%';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($statusFilter, $visibleStatuses, true)) {
    $where[] = 'c.status=?';
    $params[] = $statusFilter;
}
if (in_array($priorityFilter, $priorities, true)) {
    $where[] = 'c.priority=?';
    $params[] = $priorityFilter;
}
if (!$scope && isset($portalOptions[$unit])) {
    $where[] = 'UPPER(c.assigned_scope)=?';
    $params[] = $unit;
}
if (!$isCompletedView) {
    if ($queueFilter === 'unassigned') $where[] = 'c.assigned_to IS NULL';
    elseif ($queueFilter === 'new') $where[] = "c.status IN ('Submitted','Received')";
    elseif (in_array($queueFilter, ['attention','overdue'], true) && $workflowReady) $where[] = "c.due_at IS NOT NULL AND c.due_at<NOW()";
    elseif ($queueFilter === 'followup' && $workflowReady) $where[] = "c.follow_up_at IS NOT NULL AND c.follow_up_at<=NOW()";
}
$from = ' FROM concerns c LEFT JOIN admins a ON a.id=c.assigned_to'.($where?' WHERE '.implode(' AND ', $where):'');
$countSt = $pdo->prepare('SELECT COUNT(*)'.$from);
$countSt->execute($params);
$resultTotal = (int)$countSt->fetchColumn();
$pager = pagination_state($resultTotal, 25);
$orderPrefix = (!$isCompletedView && $workflowReady)?"CASE WHEN c.due_at IS NOT NULL AND c.due_at<NOW() THEN 0 ELSE 1 END,":'';
$orderTail = $isCompletedView?'COALESCE(c.resolved_at,c.updated_at) DESC,c.created_at DESC':'FIELD(c.priority,"Urgent","High","Normal","Low"),c.created_at DESC';
$sql = 'SELECT c.*,a.full_name assigned_name'.$from.' ORDER BY '.$orderPrefix.$orderTail.' LIMIT '.$pager['per_page'].' OFFSET '.$pager['offset'];
$st = $pdo->prepare($sql);
$st->execute($params);
$concerns = $st->fetchAll();

$assignWhere = ["status='active'", "role IN ('usc','sas_director','campus_sas_head','sbo_adviser','campus_sbo')"];
$assignParams = [];
if (!$canRouteAcross && $scope) {
    $assignWhere[] = '(campus=? OR campus IS NULL)';
    $assignParams[] = $scope;
}
$st = $pdo->prepare('SELECT id,full_name,role,campus FROM admins WHERE '.implode(' AND ', $assignWhere).' ORDER BY full_name');
$st->execute($assignParams);
$assignees = $st->fetchAll();

$baseSummaryWhere = [];
$baseSummaryParams = [];
if ($scope) {
    $baseSummaryWhere[] = '(UPPER(source_portal)=? OR UPPER(assigned_scope)=?)';
    array_push($baseSummaryParams, $scope, $scope);
}
$baseSummarySql = $baseSummaryWhere?' WHERE '.implode(' AND ', $baseSummaryWhere):'';
$sumSt = $pdo->prepare("SELECT COUNT(*) total_all,SUM(status NOT IN ('Resolved','Closed')) active_count,SUM(status IN ('Submitted','Received')) new_count,SUM(status IN ('Under Review','Referred','In Progress','Action Taken')) progress_count,SUM(status='Resolved') resolved_count,SUM(status='Closed') closed_count,SUM(status IN ('Resolved','Closed')) completed_count,SUM(status IN ('Resolved','Closed') AND COALESCE(resolved_at,updated_at,created_at)>=DATE_FORMAT(CURDATE(),'%Y-%m-01')) completed_month_count,SUM(priority='Urgent' AND status NOT IN ('Resolved','Closed')) urgent_count".($workflowReady?",SUM(status NOT IN ('Resolved','Closed') AND due_at IS NOT NULL AND due_at<NOW()) overdue_count":",0 overdue_count")." FROM concerns".$baseSummarySql);
$sumSt->execute($baseSummaryParams);
$sr = $sumSt->fetch()?:[];
$summary = [
    'total' => (int)($sr['total_all'] ?? 0),
    'active' => (int)($sr['active_count'] ?? 0),
    'new' => (int)($sr['new_count'] ?? 0),
    'progress' => (int)($sr['progress_count'] ?? 0),
    'resolved' => (int)($sr['resolved_count'] ?? 0),
    'closed' => (int)($sr['closed_count'] ?? 0),
    'completed' => (int)($sr['completed_count'] ?? 0),
    'completed_month' => (int)($sr['completed_month_count'] ?? 0),
    'urgent' => (int)($sr['urgent_count'] ?? 0),
    'overdue' => (int)($sr['overdue_count'] ?? 0),
];

admin_header($edit?'Review '.((string)($edit['concern_type'] ?? 'Concern')):'E-Sumbong', 'Student services');
if (isset($_GET['updated'])) echo '<div class="notice">Concern updated. Public tracking and the case history are now current.</div>';
if (isset($_GET['routed'])) {
    $routedTo = portal_display_name((string)($_GET['to'] ?? ''));
    echo '<div class="notice">Concern routed successfully'.($routedTo !== ''?' to '.e($routedTo):'').'. It has been returned to your queue because the destination unit now owns the case.</div>';
}
if (isset($_GET['evidence_removed'])) echo '<div class="notice">Case evidence removed. The case history and student tracker are now current.</div>';
if (isset($_GET['completed'])) echo '<div class="notice">'.e((string)($_GET['ref'] ?? 'Case')).' was completed and moved out of the Active Queue.</div>';
if (isset($_GET['reopened'])) echo '<div class="notice">'.e((string)($_GET['ref'] ?? 'Case')).' was reopened and returned to the Active Queue.</div>';
if (($_GET['validation'] ?? '') === 'resolution') echo '<div class="notice notice--error">Add a resolution / action summary before marking this formal case Resolved or Closed.</div>';
if (($_GET['validation'] ?? '') === 'reopen') echo '<div class="notice notice--error">Add an Internal Note explaining why this completed case is being reopened.</div>';

if ($edit) :$sourcePortal = concern_portal_code($edit);
$assignedScope = concern_assigned_scope($edit);
$validationStatus = (string)($_GET['status'] ?? '');
$formStatus = (in_array(($_GET['validation'] ?? ''), ['resolution','reopen'], true) && in_array($validationStatus, $statuses, true)) ? $validationStatus : (string)$edit['status'];
$detailCasePolicy = concern_type_policy($pdo, (string)($edit['concern_type'] ?? 'Concern'));
$isLanguageReview = (($edit['sensitivity'] ?? 'standard') === 'language_review');
$detailReturnView = in_array((string)$edit['status'], $completedStatuses, true)?'completed':'active';
?>
<div class="page-intro review-page-intro">
    <div class="review-heading">
        <span class="page-kicker">CASE MANAGEMENT</span>
        <div class="review-heading__line">
            <h2><?=e($edit['reference_code'])?></h2>
            <span class="review-heading__date"><?=e(date('M j, Y · g:i A',strtotime($edit['created_at'])))?></span>
        </div>
        <p>Review the <?=e(strtolower((string)$edit['concern_type']))?>, route it when needed, and record the appropriate student-facing update.</p>
    </div>
    <div class="page-actions review-page-actions">
        <a class="btn btn--soft" href="concerns.php?view=<?=e($detailReturnView)?>">← Back to <?= $detailReturnView === 'completed' ? 'Completed Cases' : 'Active Queue' ?></a>
    </div>
</div>
<div class="case-layout case-layout--review">
    <main class="case-main">
        <section class="panel case-detail-card case-detail-card--clean">
            <div class="case-record-top">
                <div class="case-summary-badges">
                    <?=status_badge($edit['status'])?>
                    <span class="priority-badge priority-<?=e(strtolower($edit['priority']))?>"><?=e($edit['priority'])?></span>
                    <?php if ($isLanguageReview) : ?>
                        <span class="language-review-badge">Language review</span>
                    <?php endif; ?>
                </div>
                <div class="case-record-routing">
                    <span>From <strong><?=e($sourcePortal)?></strong></span>
                    <i aria-hidden="true">→</i>
                    <span>Handled by <strong><?=e($assignedScope)?></strong></span>
                </div>
            </div>

            <?php if ($isLanguageReview) : ?>
                <div class="case-language-review-notice">
                    <strong>Review wording in context</strong>
                    <span>Automated language moderation detected potentially offensive wording. Do not assume misconduct from the wording alone; the student may be quoting an incident or abusive statement.</span>
                </div>
            <?php endif; ?>

            <div class="case-title-block case-title-block--clean case-title-block--with-action">
                <div>
                    <span>Subject</span>
                    <h2 class="case-subject"><?=e($edit['subject'])?></h2>
                </div>
                <a class="case-pdf-download" href="concern-report.php?id=<?=$edit['id']?>" title="Download this case as a PDF report">
                    <span class="case-pdf-download__icon" aria-hidden="true">PDF</span>
                    <span>Download PDF</span>
                </a>
            </div>

            <div class="case-content-overview">
                <div class="case-message-section case-message-section--clean">
                    <div class="case-message-label">Student message</div>
                    <div class="message-box case-message-box case-message-box--clean">
                        <?php
                        $caseMessageText = trim((string)($edit['message'] ?? ''));
                        $caseMessageParagraphs = preg_split('/(?:\r\n|\r|\n){2,}/', $caseMessageText) ?: [];
                        if (!$caseMessageParagraphs && $caseMessageText !== '') {
                            $caseMessageParagraphs = [$caseMessageText];
                        }
                        ?>
                        <?php if (!$caseMessageParagraphs) : ?>
                            <p>Not provided.</p>
                        <?php else : ?>
                            <?php foreach ($caseMessageParagraphs as $caseMessageParagraph) : ?>
                                <?php
                                $caseMessageParagraph = trim((string)$caseMessageParagraph);
                                if ($caseMessageParagraph === '') continue;
                                // Keep intentional paragraph breaks, but normalize single line breaks
                                // inside a paragraph so the admin view reads like continuous prose.
                                $caseMessageParagraph = preg_replace('/\s*(?:\r\n|\r|\n)\s*/u', ' ', $caseMessageParagraph) ?? $caseMessageParagraph;
                                $caseMessageParagraph = preg_replace('/[ \t]+/u', ' ', $caseMessageParagraph) ?? $caseMessageParagraph;
                                ?>
                                <p><?=e($caseMessageParagraph)?></p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="case-facts-stack" aria-label="Case overview">
                    <div class="case-fact case-fact--stacked">
                        <span>Concern type</span>
                        <strong><?=e($edit['concern_type'])?></strong>
                    </div>
                    <div class="case-fact case-fact--stacked">
                        <span>Campus</span>
                        <strong><?=e(student_campus_display($edit['campus']??'')?:'Not provided')?></strong>
                    </div>
                    <div class="case-fact case-fact--stacked">
                        <span>College / Program</span>
                        <strong><?=e($edit['college']?:'Not provided')?></strong>
                    </div>
                </aside>
            </div>

            <section class="case-privacy-strip" id="protected-student-details">
                <div class="case-privacy-head">
                    <div>
                        <span class="case-private-kicker">PRIVATE</span>
                        <strong>Protected student details</strong>
                        <small class="case-privacy-subtitle">Identity is restricted and every access is recorded.</small>
                    </div>
                    <?php if (empty($edit['anonymous'])) : ?>
                        <div class="case-privacy-head__actions">
                            <?php if (is_system_admin()) : ?>
                                <span class="identity-access-badge identity-access-badge--admin">System Administrator access</span>
                                <button class="privacy-reveal-btn" type="button" id="revealIdentityBtn" data-id="<?=$edit['id']?>">Reveal identity</button>
                            <?php elseif (!$identityAccessReady) : ?>
                                <span class="identity-access-badge identity-access-badge--warning">Migration required</span>
                            <?php elseif ($identityCanReveal) : ?>
                                <span class="identity-access-badge identity-access-badge--approved">Access approved</span>
                                <button class="privacy-reveal-btn" type="button" id="revealIdentityBtn" data-id="<?=$edit['id']?>">Reveal identity</button>
                            <?php elseif ($identityAccessRequest && (string)$identityAccessRequest['status'] === 'pending') : ?>
                                <span class="identity-access-badge identity-access-badge--pending">Awaiting System Administrator approval</span>
                            <?php else : ?>
                                <button class="privacy-request-btn" type="button" id="requestIdentityAccessBtn">Request identity access</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="case-privacy-values">
                    <div>
                        <span>Student</span>
                        <strong id="privateStudentName"><?=!empty($edit['anonymous'])?'Anonymous submission':($identityProtected?'Protected identity':e(concern_mask_name($edit['student_name']??null)))?></strong>
                    </div>
                    <div>
                        <span>Student ID</span>
                        <strong id="privateStudentId"><?=!empty($edit['anonymous'])?'Not stored':($identityProtected?'Protected':e(concern_mask_student_id($edit['student_id']??null)))?></strong>
                    </div>
                    <div>
                        <span>Contact email</span>
                        <strong id="privateContactEmail"><?=!empty($edit['anonymous'])?'Not stored':($identityProtected?'Protected / optional':'Not provided')?></strong>
                    </div>
                </div>

                <?php if (empty($edit['anonymous']) && !is_system_admin()) : ?>
                    <div class="identity-access-status">
                        <?php if (!$identityAccessReady) : ?>
                            <div class="identity-access-status__copy">
                                <strong>Identity approval controls are not ready.</strong>
                                <span>Ask the System Administrator to run migration <code>2026090503</code> in the Migration Center.</span>
                            </div>
                        <?php elseif ($identityCanReveal && $identityAccessRequest) : ?>
                            <div class="identity-access-status__copy">
                                <strong>Approved for this case</strong>
                                <span>
                                    Your access is limited to this concern<?php if (!empty($identityAccessRequest['approved_until'])) : ?> and expires <?=e(date('M j, Y · g:i A', strtotime((string)$identityAccessRequest['approved_until'])))?><?php endif; ?>.
                                </span>
                            </div>
                        <?php elseif ($identityAccessRequest && (string)$identityAccessRequest['status'] === 'pending') : ?>
                            <div class="identity-access-status__copy">
                                <strong>Access request pending</strong>
                                <span>Requested <?=e(date('M j, Y · g:i A', strtotime((string)$identityAccessRequest['requested_at'])))?>. The identity remains hidden until the System Administrator approves it.</span>
                            </div>
                            <form method="post" action="concern-identity-access.php" class="identity-access-inline-form">
                                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="concern_id" value="<?=$edit['id']?>">
                                <input type="hidden" name="request_id" value="<?=$identityAccessRequest['id']?>">
                                <button type="submit" class="identity-access-link-btn">Cancel request</button>
                            </form>
                        <?php elseif ($identityAccessRequest && (string)$identityAccessRequest['status'] === 'rejected') : ?>
                            <div class="identity-access-status__copy">
                                <strong>Previous request was not approved</strong>
                                <span><?=e(trim((string)($identityAccessRequest['review_note'] ?? '')) ?: 'You may submit a new request with a clear case-related reason.')?></span>
                            </div>
                        <?php elseif ($identityAccessRequest && (string)$identityAccessRequest['status'] === 'revoked') : ?>
                            <div class="identity-access-status__copy">
                                <strong>Previous access was revoked</strong>
                                <span><?=e(trim((string)($identityAccessRequest['review_note'] ?? '')) ?: 'Request access again only when it is necessary for handling this case.')?></span>
                            </div>
                        <?php else : ?>
                            <div class="identity-access-status__copy">
                                <strong>System Administrator approval required</strong>
                                <span>Protected identity is not available to this account until a case-specific access request is approved.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (is_system_admin() && $identityAccessReady && ($identityPendingRequests || $identityActiveApprovals)) : ?>
                    <div class="identity-access-admin-panel">
                        <div class="identity-access-admin-panel__head">
                            <div>
                                <strong>Identity access control</strong>
                                <span>Approve only when identity is necessary to handle this case.</span>
                            </div>
                            <?php if ($identityPendingRequests) : ?><span class="identity-access-count"><?=count($identityPendingRequests)?> pending</span><?php endif; ?>
                        </div>

                        <?php foreach ($identityPendingRequests as $request) : ?>
                            <article class="identity-access-request-card">
                                <div class="identity-access-request-card__body">
                                    <div class="identity-access-request-card__person">
                                        <strong><?=e((string)($request['full_name'] ?: $request['requester_name'] ?: 'Administrator'))?></strong>
                                        <span><?=e(admin_role_label((string)($request['role'] ?? '')))?></span>
                                    </div>
                                    <p><?=e((string)$request['reason'])?></p>
                                    <small>Requested <?=e(date('M j, Y · g:i A', strtotime((string)$request['requested_at'])))?></small>
                                </div>
                                <div class="identity-access-request-card__actions">
                                    <form method="post" action="concern-identity-access.php">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="concern_id" value="<?=$edit['id']?>">
                                        <input type="hidden" name="request_id" value="<?=$request['id']?>">
                                        <button type="submit" class="identity-approve-btn">Approve 24h</button>
                                    </form>
                                    <form method="post" action="concern-identity-access.php">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="concern_id" value="<?=$edit['id']?>">
                                        <input type="hidden" name="request_id" value="<?=$request['id']?>">
                                        <button type="submit" class="identity-reject-btn">Reject</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>

                        <?php if ($identityActiveApprovals) : ?>
                            <div class="identity-access-active-list">
                                <span class="identity-access-active-list__label">Active approvals</span>
                                <?php foreach ($identityActiveApprovals as $approval) : ?>
                                    <div class="identity-access-active-item">
                                        <div>
                                            <strong><?=e((string)($approval['full_name'] ?: $approval['requester_name'] ?: 'Administrator'))?></strong>
                                            <span>Until <?=e(!empty($approval['approved_until']) ? date('M j, Y · g:i A', strtotime((string)$approval['approved_until'])) : 'revoked')?></span>
                                        </div>
                                        <form method="post" action="concern-identity-access.php">
                                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="concern_id" value="<?=$edit['id']?>">
                                            <input type="hidden" name="request_id" value="<?=$approval['id']?>">
                                            <button type="submit" class="identity-access-link-btn">Revoke</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (empty($edit['anonymous']) && !is_system_admin() && $identityAccessReady && !$identityCanReveal && (!$identityAccessRequest || in_array((string)$identityAccessRequest['status'], ['rejected','revoked','cancelled','expired'], true))) : ?>
                <dialog class="identity-request-dialog" id="identityAccessRequestDialog" aria-labelledby="identityAccessRequestTitle">
                    <form method="post" action="concern-identity-access.php" class="identity-request-dialog__panel">
                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                        <input type="hidden" name="action" value="request">
                        <input type="hidden" name="concern_id" value="<?=$edit['id']?>">
                        <div class="identity-request-dialog__head">
                            <div>
                                <span>PROTECTED IDENTITY</span>
                                <h3 id="identityAccessRequestTitle">Request identity access</h3>
                            </div>
                            <button type="button" class="identity-request-dialog__close" data-close-identity-request aria-label="Close">×</button>
                        </div>
                        <p>Explain why viewing the student's identity is necessary to handle <strong><?=e($edit['reference_code'])?></strong>. The System Administrator will review this request.</p>
                        <label>
                            Reason for access
                            <textarea name="reason" minlength="10" maxlength="500" required placeholder="Example: Identity is required to verify the student's enrollment before coordinating with the responsible office."></textarea>
                        </label>
                        <div class="identity-request-dialog__actions">
                            <button type="button" class="identity-dialog-cancel" data-close-identity-request>Cancel</button>
                            <button type="submit" class="identity-dialog-submit">Send request</button>
                        </div>
                    </form>
                </dialog>
            <?php endif; ?>

            <?php if ($studentEvidence) : ?>
                <section class="case-inline-evidence">
                    <div class="case-inline-evidence__head">
                        <div>
                            <h3>Student evidence</h3>
                            <p>Files submitted with this concern.</p>
                        </div>
                        <span class="case-history-count"><?=count($studentEvidence)?> file<?=count($studentEvidence)===1?'':'s'?></span>
                    </div>
                    <div class="case-inline-evidence__list">
                        <?php foreach ($studentEvidence as $evidenceIndex => $a) : ?>
                            <?php $evidenceIsImage = str_starts_with(strtolower((string)($a['mime_type'] ?? '')), 'image/'); ?>
                            <article class="case-evidence-card <?= $evidenceIsImage ? 'case-evidence-card--image' : '' ?>">
                                <?php if ($evidenceIsImage) : ?>
                                    <a class="case-evidence-card__preview" href="concern-attachment.php?id=<?=$a['id']?>&amp;view=1" target="_blank" rel="noopener" aria-label="View <?=e($a['original_name'])?>">
                                        <img src="concern-attachment.php?id=<?=$a['id']?>&amp;view=1&amp;preview=1" alt="Evidence preview" loading="lazy">
                                    </a>
                                <?php else : ?>
                                    <a class="case-evidence-card__preview case-evidence-card__preview--file" href="concern-attachment.php?id=<?=$a['id']?>" aria-label="Download <?=e($a['original_name'])?>">
                                        <span><?=e(strtoupper(substr(file_type_label($a['mime_type'],$a['original_name']),0,3)))?></span>
                                    </a>
                                <?php endif; ?>
                                <div class="case-evidence-card__body">
                                    <div class="case-evidence-card__meta">
                                        <strong title="<?=e($a['original_name'])?>">
                                            <?=$evidenceIsImage ? 'Image evidence ' : 'Evidence file '?><?=((int)$evidenceIndex)+1?>
                                        </strong>
                                        <small><?=e(file_type_label($a['mime_type'],$a['original_name']))?> · <?=e(format_file_size((int)$a['file_size']))?></small>
                                    </div>
                                    <div class="case-evidence-card__actions">
                                        <?php if ($evidenceIsImage) : ?>
                                            <a class="case-evidence-action case-evidence-action--view" href="concern-attachment.php?id=<?=$a['id']?>&amp;view=1" target="_blank" rel="noopener">View</a>
                                        <?php endif; ?>
                                        <a class="case-evidence-action" href="concern-attachment.php?id=<?=$a['id']?>">Download</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </section>
        <section class="panel case-history-panel <?= !$history ? 'case-history-panel--empty' : '' ?>">
            <div class="panel__head">
                <div>
                    <h2>Case history</h2>
                    <p>Recorded administrative updates</p>
                </div>
                <span class="case-history-count"><?=count($history)?> update<?=count($history)===1?'':'s'?></span>
            </div>
            <div class="panel__body timeline case-timeline">
                <?php foreach ($history as $h) : ?>
                    <div class="timeline-item case-history-item">
                        <i></i>
                        <div>
                            <div class="case-history-title">
                                <strong><?=e($h['action']?:'Updated concern')?></strong><span><?=status_badge($h['status'])?></span>
                            </div>
                            <?php if (!empty($h['old_assigned_scope']) && !empty($h['assigned_scope']) && $h['old_assigned_scope'] !== $h['assigned_scope']) : ?>
                                <p class="case-history-routing"><?=e($h['old_assigned_scope'])?> → <?=e($h['assigned_scope'])?></p>
                            <?php endif; ?>
                            <?php if (!empty($h['assigned_name'])) : ?>
                                <p class="case-history-assignee">Assigned to <?=e($h['assigned_name'])?></p>
                            <?php endif; ?>
                            <?php if (trim((string)$h['public_note']) !== '') : ?>
                                <div class="history-note history-note--public">
                                    <span>Student-visible update</span>
                                    <p><?=nl2br(e($h['public_note']))?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (trim((string)$h['internal_note']) !== '') : ?>
                                <div class="history-note history-note--internal">
                                    <span>Internal note</span>
                                    <p><?=nl2br(e($h['internal_note']))?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (trim((string)$h['public_note']) === '' && trim((string)$h['internal_note']) === '') : ?>
                                <p class="case-history-muted">Administrative record updated.</p>
                            <?php endif; ?>
                            <small><?=e($h['admin_name']?:'Administrator')?> · <?=e(date('M j, Y · g:i A',strtotime($h['created_at'])))?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$history) : ?>
                    <div class="empty-state case-history-empty">
                        No history entries yet.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <aside class="case-side case-side--review">
        <section class="panel case-control-panel case-control-panel--clean">
            <div class="case-control-head case-control-head--clean">
                <div>
                    <span class="case-control-kicker">CASE UPDATE</span>
                    <h2>Manage <?=e(strtolower((string)$edit['concern_type']))?></h2>
                    <p><?=!empty($detailCasePolicy['formal'])?'Update status, student communication, routing, and evidence.':'Use a lighter review flow; route when useful, record action, and close when complete.'?></p>
                </div>
            </div>
            <form id="caseUpdateForm" method="post" enctype="multipart/form-data" data-unsaved-warning="1">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="id" value="<?=$edit['id']?>">

                <div class="case-editor">
                    <section class="case-editor-section">
                        <div class="case-editor-section__head">
                            <h3>Workflow</h3>
                            <span>Status and timing</span>
                        </div>
                        <div class="case-editor-grid case-editor-grid--two">
                            <label>
                                Status
                                <select name="status" id="caseStatusSelect">
                                    <?php foreach ($statuses as $s) :
                                        $statusNeedsClosePermission = in_array($s, $completedStatuses, true);
                                        $statusReopensCompleted = in_array((string)$edit['status'], $completedStatuses, true) && !in_array($s, $completedStatuses, true);
                                        $statusDisabled = ($statusNeedsClosePermission || $statusReopensCompleted) && !can_close_concerns();
                                    ?>
                                        <option value="<?=e($s)?>" <?=$formStatus===$s?'selected':''?> <?=$statusDisabled?'disabled':''?>><?=e($s)?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (in_array((string)$edit['status'], $completedStatuses, true) && can_close_concerns()) : ?>
                                    <small class="case-field-help">To reopen, choose an active status and add an Internal Note explaining why further action is needed.</small>
                                <?php endif; ?>
                            </label>
                            <label>
                                Priority
                                <select name="priority" id="casePrioritySelect">
                                    <?php foreach ($priorities as $p) : ?>
                                        <option value="<?=$p?>" data-sla-hours="<?=concern_sla_hours($pdo, $p)?>" <?=$edit['priority']===$p?'selected':''?>><?=$p?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <?php if ($workflowReady) : ?>
                                <label>
                                    Suggested review reminder
                                    <input type="datetime-local" id="caseDueAt" value="<?=!empty($edit['due_at'])?e(date('Y-m-d\TH:i',strtotime($edit['due_at']))):''?>" data-created-at="<?=e(date('Y-m-d\TH:i:s', strtotime((string)$edit['created_at'])))?>" readonly aria-readonly="true">
                                    <span class="case-field-help" id="caseDueHelp">Suggested from priority · <?=e(concern_due_label($edit))?></span>
                                </label>
                                <label>
                                    Follow-up reminder
                                    <input type="datetime-local" name="follow_up_at" value="<?=!empty($edit['follow_up_at'])?e(date('Y-m-d\TH:i',strtotime($edit['follow_up_at']))):''?>">
                                </label>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="case-editor-section">
                        <div class="case-editor-section__head">
                            <div>
                                <h3>Concern Updates</h3>
                                <span>Student-facing update and internal record</span>
                            </div>
                            <?php if (admin_role() === 'admin') : ?>
                                <a class="case-editor-utility-link" href="response-templates.php">Manage templates</a>
                            <?php endif; ?>
                        </div>
                        <div class="case-editor-grid">
                            <?php if ($responseTemplates) : ?>
                                <label>
                                    Response template
                                    <select id="responseTemplate">
                                        <option value="">Choose a template (optional)</option>
                                        <?php foreach ($responseTemplates as $t) : ?>
                                            <option value="<?=e($t['response_text'])?>"><?=e($t['title'])?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endif; ?>
                            <label>
                                <span class="case-label-row"><span>Student update</span><small>Visible in Track Concern</small></span>
                                <textarea name="admin_note" placeholder="Add a concise progress update for the student"><?=e($edit['admin_note']??'')?></textarea>
                            </label>
                            <label>
                                <span class="case-label-row"><span>Internal note</span><small>Administrators only</small></span>
                                <textarea name="internal_note" placeholder="Add an internal administrative note"><?=e($edit['internal_note']??'')?></textarea>
                            </label>
                        </div>
                    </section>

                    <?php if (can_assign_concerns()) : ?>
                        <section class="case-editor-section" id="caseRoutingSection">
                            <div class="case-editor-section__head">
                                <h3 id="caseRoutingTitle">Routing</h3>
                                <span id="caseRoutingHelp">Route to any USC or campus unit, then choose its personnel</span>
                            </div>
                            <div class="case-editor-grid">
                                <label>
                                    <span id="caseAssignedUnitLabel">Assigned unit</span>
                                    <?php if ($canRouteAcross) : ?>
                                        <select name="assigned_scope" id="assignedScopeSelect">
                                            <?php foreach ($portalOptions as $code => $name) : ?>
                                                <option value="<?=e($code)?>" <?=$assignedScope===$code?'selected':''?>><?=e($name)?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input value="<?=e($portalOptions[$assignedScope] ?? $assignedScope)?>" readonly>
                                        <input type="hidden" name="assigned_scope" value="<?=e($assignedScope)?>">
                                    <?php endif; ?>
                                </label>
                                <label>
                                    <span id="caseAssignedPersonnelLabel">Assigned personnel</span>
                                    <select name="assigned_to" id="assignedPersonnelSelect" data-current-scope="<?=e($assignedScope)?>">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($assignees as $a) :
                                            $aScope = $a['role'] === 'usc' || $a['role'] === 'sas_director'
                                                ? 'USC'
                                                : strtoupper((string)($a['campus'] ?? ''));
                                            $aLabel = trim((string)$a['full_name']).' — '.admin_role_label($a['role']);
                                        ?>
                                            <option value="<?=$a['id']?>" data-scope="<?=e($aScope)?>" data-label="<?=e($aLabel)?>" <?=((int)$edit['assigned_to']===(int)$a['id'])?'selected':''?>><?=e($aLabel)?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="case-field-help case-assignee-help" id="caseAssigneeHelp">Only active accounts from the selected unit are shown.</small>
                                </label>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="case-editor-section case-editor-section--evidence">
                        <div class="case-editor-section__head">
                            <h3>Case Evidence</h3>
                            <span>Supporting files for this concern</span>
                        </div>
                        <div class="case-evidence-upload" data-max-files="<?=e((string)$caseAttachmentMaxFiles)?>">
                            <div class="case-evidence-upload__head">
                                <div class="case-evidence-upload__info">
                                    <strong><?=$adminEvidence?'Add more case evidence':'Attach case evidence'?></strong>
                                    <small><?=$adminEvidence?'Previously saved files are listed below.':'Optional supporting files'?></small>
                                </div>
                                <span class="case-evidence-upload__formats">JPG, PNG, WebP or PDF</span>
                            </div>
                            <div class="case-evidence-upload__controls">
                                <div class="case-upload-picker">
                                    <input class="case-upload-picker__input" id="caseAttachmentsInput" type="file" name="case_attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf">
                                    <label class="case-upload-picker__button" for="caseAttachmentsInput">Choose files</label>
                                </div>
                            </div>
                            <div class="case-evidence-upload__files" id="caseAttachmentFiles" aria-live="polite">
                                <div class="case-evidence-upload__empty" id="caseAttachmentEmpty">No new files selected</div>
                            </div>
                            <?php if ($adminEvidence) : ?>
                                <div class="case-evidence-saved">
                                    <div class="case-evidence-saved__head">
                                        <strong>Saved case evidence</strong>
                                        <span><?=count($adminEvidence)?> file<?=count($adminEvidence)===1?'':'s'?></span>
                                    </div>
                                    <div class="case-evidence-saved__list">
                                        <?php foreach ($adminEvidence as $a) : ?>
                                            <div class="case-evidence-saved__item">
                                                <span class="case-evidence-saved__type"><?=e(strtoupper(substr(file_type_label($a['mime_type'],$a['original_name']),0,3)))?></span>
                                                <span class="case-evidence-saved__details">
                                                    <strong title="<?=e($a['original_name'])?>"><?=e($a['original_name'])?></strong>
                                                    <small><?=e(file_type_label($a['mime_type'],$a['original_name']))?> · <?=e(format_file_size((int)$a['file_size']))?></small>
                                                </span>
                                                <span class="case-evidence-saved__actions">
                                                    <a class="case-evidence-saved__action" href="concern-attachment.php?id=<?=(int)$a['id']?>">Download</a>
                                                    <button
                                                        class="case-evidence-saved__remove"
                                                        type="submit"
                                                        name="remove_case_evidence"
                                                        value="<?=(int)$a['id']?>"
                                                        formnovalidate
                                                        onclick="return confirm('Remove this saved case evidence? This cannot be undone.');"
                                                    >Remove</button>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <?php if ($advancedCaseReady) : ?>
                        <section class="case-editor-section case-status-context" id="caseReferralSection" <?=$formStatus==='Referred'?'':'hidden'?>>
                            <div class="case-editor-section__head">
                                <h3>Referral Details</h3>
                                <span>Shown only when this concern is referred</span>
                            </div>
                            <div class="case-editor-grid">
                                <label>
                                    Escalation / referral reason
                                    <textarea name="escalation_reason" placeholder="Explain why this concern is being referred"><?=e($edit['escalation_reason']??'')?></textarea>
                                </label>
                            </div>
                        </section>

                        <section class="case-editor-section case-status-context" id="caseResolutionSection" <?=in_array($formStatus,['Resolved','Closed'],true)?'':'hidden'?>>
                            <div class="case-editor-section__head">
                                <h3><?=!empty($detailCasePolicy['formal'])?'Resolution':'Closing note'?></h3>
                                <span><?=!empty($detailCasePolicy['formal'])?'Required when resolving or closing':'Optional for this lightweight submission'?></span>
                            </div>
                            <div class="case-editor-grid">
                                <label class="case-resolution-field">
                                    Resolution / action summary
                                    <span class="case-field-help"><?=!empty($detailCasePolicy['formal'])?'Required before Resolved or Closed.':'Optional: record what was considered, forwarded, or acknowledged before closing.'?></span>
                                    <textarea name="resolution_summary" id="caseResolutionSummary" placeholder="<?=!empty($detailCasePolicy['formal'])?'Summarize the completed action':'Optional closing note'?>" aria-describedby="caseResolutionError"><?=e($edit['resolution_summary']??'')?></textarea>
                                    <span class="case-resolution-error" id="caseResolutionError" role="alert" <?=($_GET['validation'] ?? '') === 'resolution'?'':'hidden'?>>Add a concise resolution / action summary before saving this status.</span>
                                </label>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="case-control-actions case-control-actions--clean">
                    <span class="case-control-actions__note">Saved changes are recorded in case history.</span>
                    <button class="btn">Save update</button>
                </div>
            </form>
        </section>
    </aside>
</div>
<script>
(() => {
    const reveal = document.getElementById('revealIdentityBtn');
    reveal?.addEventListener('click', async () => {
        reveal.disabled = true;
        reveal.textContent = 'Loading…';
        try {
            const body = new URLSearchParams({ id: reveal.dataset.id, csrf: <?=json_encode(csrf_token())?> });
            const r = await fetch('concern-identity.php', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, credentials: 'same-origin', body });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.error || 'Unable to load identity');
            document.getElementById('privateStudentName').textContent = data.name;
            document.getElementById('privateStudentId').textContent = data.student_id;
            const email = document.getElementById('privateContactEmail');
            if (email) email.textContent = data.email;
            reveal.remove();
        } catch (e) {
            reveal.disabled = false;
            reveal.textContent = 'Reveal identity';
            alert(e.message || 'Unable to reveal identity.');
        }
    });

    const identityRequestButton = document.getElementById('requestIdentityAccessBtn');
    const identityRequestDialog = document.getElementById('identityAccessRequestDialog');
    identityRequestButton?.addEventListener('click', () => {
        if (identityRequestDialog?.showModal) identityRequestDialog.showModal();
    });
    document.querySelectorAll('[data-close-identity-request]').forEach(button => {
        button.addEventListener('click', () => identityRequestDialog?.close());
    });
    const template = document.getElementById('responseTemplate'); const note = document.querySelector('textarea[name="admin_note"]');
    template?.addEventListener('change', () => { if (template.value && note) { if (note.value.trim() && !confirm('Replace the current public note with this template?')) { template.value = ''; return; } note.value = template.value; note.dispatchEvent(new Event('input', { bubbles: true })); } });
    const unit = document.getElementById('assignedScopeSelect'), person = document.getElementById('assignedPersonnelSelect');
    if (person) {
        const sourceOptions = [...person.options].slice(1).map(option => ({
            value: option.value,
            scope: option.dataset.scope || '',
            label: option.dataset.label || option.textContent.trim(),
            selected: option.selected
        }));
        const initialPersonnel = person.value;
        let firstSync = true;
        const syncPersonnel = () => {
            const selectedUnit = unit?.value || person.dataset.currentScope || '';
            const previousValue = firstSync ? initialPersonnel : person.value;
            const matches = sourceOptions.filter(item => item.scope === selectedUnit);
            person.replaceChildren();

            const unassigned = document.createElement('option');
            unassigned.value = '';
            unassigned.textContent = 'Unassigned';
            person.appendChild(unassigned);

            matches.forEach(item => {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.label;
                option.dataset.scope = item.scope;
                person.appendChild(option);
            });

            if (previousValue && matches.some(item => item.value === previousValue)) person.value = previousValue;
            else person.value = '';

            if (!matches.length) {
                const empty = document.createElement('option');
                empty.value = '';
                empty.textContent = 'No active personnel for this unit';
                empty.disabled = true;
                person.appendChild(empty);
            }
            firstSync = false;
        };
        unit?.addEventListener('change', syncPersonnel);
        syncPersonnel();
    }
    const statusSelect = document.getElementById('caseStatusSelect');
    const caseUpdateForm = document.getElementById('caseUpdateForm');
    const prioritySelect = document.getElementById('casePrioritySelect');
    const dueAtInput = document.getElementById('caseDueAt');
    const dueHelp = document.getElementById('caseDueHelp');

    const formatLocalDateTimeInput = date => {
        const pad = value => String(value).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    };
    const formatReminderPreview = date => new Intl.DateTimeFormat(undefined, {
        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'
    }).format(date);
    const syncPriorityReminder = () => {
        if (!prioritySelect || !dueAtInput) return;
        const selected = prioritySelect.selectedOptions[0];
        const hours = Number(selected?.dataset.slaHours || 0);
        const createdAt = new Date(dueAtInput.dataset.createdAt || '');
        if (!Number.isFinite(hours) || hours <= 0 || Number.isNaN(createdAt.getTime())) return;
        const due = new Date(createdAt.getTime() + hours * 60 * 60 * 1000);
        dueAtInput.value = formatLocalDateTimeInput(due);
        if (dueHelp) {
            const target = hours % 24 === 0 && hours >= 24
                ? `${hours} hours (${hours / 24} day${hours / 24 === 1 ? '' : 's'})`
                : `${hours} hour${hours === 1 ? '' : 's'}`;
            dueHelp.textContent = `Suggested from priority · ${target} · ${formatReminderPreview(due)}`;
        }
    };
    prioritySelect?.addEventListener('change', syncPriorityReminder);
    const referralSection = document.getElementById('caseReferralSection');
    const resolutionSection = document.getElementById('caseResolutionSection');
    const resolutionSummary = document.getElementById('caseResolutionSummary');
    const resolutionError = document.getElementById('caseResolutionError');
    const requiresResolutionSummary = <?=!empty($detailCasePolicy['formal'])?'true':'false'?>;
    const routingTitle = document.getElementById('caseRoutingTitle');
    const routingHelp = document.getElementById('caseRoutingHelp');
    const assignedUnitLabel = document.getElementById('caseAssignedUnitLabel');
    const assignedPersonnelLabel = document.getElementById('caseAssignedPersonnelLabel');

    const syncStatusContext = () => {
        if (!statusSelect) return;
        const isReferred = statusSelect.value === 'Referred';
        const isClosing = ['Resolved','Closed'].includes(statusSelect.value);

        if (referralSection) referralSection.hidden = !isReferred;
        if (resolutionSection) resolutionSection.hidden = !isClosing;

        if (routingTitle) routingTitle.textContent = isReferred ? 'Referral / Routing' : 'Routing';
        if (routingHelp) routingHelp.textContent = isReferred ? 'Choose the USC/campus unit receiving this referral' : 'Route to any USC or campus unit, then choose its personnel';
        if (assignedUnitLabel) assignedUnitLabel.textContent = isReferred ? 'Referred to unit' : 'Assigned unit';
        if (assignedPersonnelLabel) assignedPersonnelLabel.textContent = isReferred ? 'Referred to personnel' : 'Assigned personnel';

        if (!isClosing) {
            resolutionSummary?.removeAttribute('aria-invalid');
            if (resolutionError) resolutionError.hidden = true;
        }
    };

    statusSelect?.addEventListener('change', syncStatusContext);
    resolutionSummary?.addEventListener('input', () => {
        if (resolutionSummary.value.trim() !== '') {
            resolutionSummary.removeAttribute('aria-invalid');
            if (resolutionError) resolutionError.hidden = true;
        }
    });
    caseUpdateForm?.addEventListener('submit', (event) => {
        if (!requiresResolutionSummary || !statusSelect || !resolutionSummary || !['Resolved','Closed'].includes(statusSelect.value) || resolutionSummary.value.trim() !== '') return;
        event.preventDefault();
        if (resolutionSection) resolutionSection.hidden = false;
        resolutionSummary.setAttribute('aria-invalid', 'true');
        if (resolutionError) resolutionError.hidden = false;
        requestAnimationFrame(() => {
            resolutionSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
            resolutionSummary.focus({ preventScroll: true });
        });
    });
    syncStatusContext();
    <?php if (!in_array((string)($edit['status'] ?? ''), ['Resolved', 'Closed'], true)) : ?>
    syncPriorityReminder();
    <?php endif; ?>

    const caseAttachmentsInput = document.getElementById('caseAttachmentsInput');
    const caseAttachmentFiles = document.getElementById('caseAttachmentFiles');
    const caseAttachmentEmpty = document.getElementById('caseAttachmentEmpty');
    const evidenceUpload = caseAttachmentsInput?.closest('.case-evidence-upload');
    const caseAttachmentMaxFiles = Number(evidenceUpload?.dataset.maxFiles || 3);
    let pendingCaseFiles = [];

    const formatCaseFileSize = bytes => {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 KB';
        if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(bytes >= 10 * 1024 * 1024 ? 0 : 1)} MB`;
    };
    const caseFileType = file => {
        const ext = (file.name.split('.').pop() || '').toUpperCase();
        return ext || (file.type.split('/').pop() || 'FILE').toUpperCase();
    };
    const caseFileKey = file => `${file.name}|${file.size}|${file.lastModified}`;
    const syncCaseInputFiles = () => {
        if (!caseAttachmentsInput || typeof DataTransfer === 'undefined') return;
        const transfer = new DataTransfer();
        pendingCaseFiles.forEach(file => transfer.items.add(file));
        caseAttachmentsInput.files = transfer.files;
    };
    const renderCaseAttachmentFiles = () => {
        if (!caseAttachmentFiles) return;
        caseAttachmentFiles.querySelectorAll('.case-evidence-upload__file').forEach(node => node.remove());
        if (caseAttachmentEmpty) caseAttachmentEmpty.hidden = pendingCaseFiles.length > 0;
        pendingCaseFiles.forEach((file, index) => {
            const row = document.createElement('div');
            row.className = 'case-evidence-upload__file';

            const badge = document.createElement('span');
            badge.className = 'case-evidence-upload__type';
            badge.textContent = caseFileType(file).slice(0, 4);

            const details = document.createElement('div');
            details.className = 'case-evidence-upload__file-details';
            const name = document.createElement('strong');
            name.textContent = file.name;
            name.title = file.name;
            const meta = document.createElement('small');
            meta.textContent = `${caseFileType(file)} · ${formatCaseFileSize(file.size)}`;
            details.append(name, meta);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'case-evidence-upload__remove';
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.innerHTML = '<span aria-hidden="true">×</span>';
            remove.addEventListener('click', () => {
                pendingCaseFiles.splice(index, 1);
                syncCaseInputFiles();
                renderCaseAttachmentFiles();
            });

            row.append(badge, details, remove);
            caseAttachmentFiles.appendChild(row);
        });
    };
    caseAttachmentsInput?.addEventListener('change', () => {
        const incoming = Array.from(caseAttachmentsInput.files || []);
        const existingKeys = new Set(pendingCaseFiles.map(caseFileKey));
        for (const file of incoming) {
            if (pendingCaseFiles.length >= caseAttachmentMaxFiles) break;
            const key = caseFileKey(file);
            if (existingKeys.has(key)) continue;
            pendingCaseFiles.push(file);
            existingKeys.add(key);
        }
        syncCaseInputFiles();
        renderCaseAttachmentFiles();
    });
    renderCaseAttachmentFiles();
})();
</script>
<?php else: ?>
<div class="concern-queue-page">
    <div class="page-intro concern-page-intro">
        <div>
            <span class="page-kicker">STUDENT SERVICES</span>
            <h2><?=$isCompletedView?'Completed cases':'Active concern queue'?></h2>
            <p><?=$isCompletedView?'Review resolved and closed E-Sumbong records without crowding the working queue.':'Review concerns that still require action, follow-up, routing, or resolution.'?></p>
        </div>
        <div class="page-actions">
            <?php if (admin_role() === 'admin') : ?>
                <a class="btn btn--soft" href="response-templates.php">Response templates</a>
            <?php endif; ?>
        </div>
    </div>

    <nav class="concern-queue-tabs" aria-label="E-Sumbong case views">
        <a class="concern-queue-tab <?=$view==='active'?'is-active':''?>" href="concerns.php?view=active" <?=$view==='active'?'aria-current="page"':''?>>
            <span>Active Queue</span>
            <strong><?=$summary['active']?></strong>
        </a>
        <a class="concern-queue-tab <?=$view==='completed'?'is-active':''?>" href="concerns.php?view=completed" <?=$view==='completed'?'aria-current="page"':''?>>
            <span>Completed Cases</span>
            <strong><?=$summary['completed']?></strong>
        </a>
    </nav>

    <?php if (!$isCompletedView) : ?>
        <div class="admin-summary-strip concern-summary-strip">
            <span class="concern-stat-card concern-stat-card--total"><span>Active cases</span><strong><?=$summary['active']?></strong><small>Still requires handling</small></span>
            <span class="concern-stat-card concern-stat-card--new"><span>New / received</span><strong><?=$summary['new']?></strong><small>Needs first review</small></span>
            <span class="concern-stat-card concern-stat-card--progress"><span>In progress</span><strong><?=$summary['progress']?></strong><small>Currently being handled</small></span>
            <span class="concern-stat-card concern-stat-card--urgent"><span>Urgent</span><strong><?=$summary['urgent']?></strong><small>Review these cases first</small></span>
            <?php if ($workflowReady) : ?>
                <span class="concern-stat-card concern-stat-card--reminder summary-reminder"><span>Reminder due</span><strong><?=$summary['overdue']?></strong><small>Suggested follow-up window reached</small></span>
            <?php endif; ?>
        </div>
        <?php if ($summary['urgent']) : ?>
            <div class="attention-strip concern-attention">
                <strong><?=$summary['urgent']?> urgent concern<?=$summary['urgent']==1?'':'s'?></strong><span>Review these cases first.</span>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="admin-summary-strip concern-summary-strip concern-summary-strip--completed">
            <span class="concern-stat-card concern-stat-card--resolved"><span>Completed cases</span><strong><?=$summary['completed']?></strong><small>Resolved or closed records</small></span>
            <span class="concern-stat-card concern-stat-card--resolved"><span>Resolved</span><strong><?=$summary['resolved']?></strong><small>Completed with resolution</small></span>
            <span class="concern-stat-card concern-stat-card--closed"><span>Closed</span><strong><?=$summary['closed']?></strong><small>Closed case records</small></span>
            <span class="concern-stat-card concern-stat-card--month"><span>This month</span><strong><?=$summary['completed_month']?></strong><small>Completed during the current month</small></span>
        </div>
    <?php endif; ?>

    <form class="toolbar concern-filter-bar<?=$hasActiveFilters?' concern-filter-bar--active':''?>" method="get">
        <input type="hidden" name="view" value="<?=e($view)?>">
        <div class="concern-filter-bar__head">
            <div>
                <strong><?=$isCompletedView?'Filter completed cases':'Filter active queue'?></strong>
                <span><?=$isCompletedView?'Search and narrow historical resolved/closed cases.':'Search, sort, and narrow cases that still require action.'?></span>
            </div>
            <div class="concern-filter-bar__actions">
                <?php if ($hasActiveFilters) : ?>
                    <a class="toolbar-clear concern-filter-clear" href="concerns.php?view=<?=e($view)?>">Reset filters</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="concern-filter-grid <?=$isCompletedView?'concern-filter-grid--completed':''?>">
            <div class="searchbox concern-searchbox">
                <input name="q" value="<?=e($q)?>" placeholder="Search reference, concern, student, or message">
            </div>
            <label class="concern-filter-field">
                <span>Status</span>
                <select name="status" onchange="this.form.requestSubmit()">
                    <option value="all">All statuses</option>
                    <?php foreach ($visibleStatuses as $s) : ?>
                        <option value="<?=e($s)?>" <?=$statusFilter===$s?'selected':''?>><?=e($s)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="concern-filter-field">
                <span>Priority</span>
                <select name="priority" onchange="this.form.requestSubmit()">
                    <option value="all">All priorities</option>
                    <?php foreach ($priorities as $p) : ?>
                        <option value="<?=$p?>" <?=$priorityFilter===$p?'selected':''?>><?=$p?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if (!$isCompletedView) : ?>
                <label class="concern-filter-field">
                    <span>Queue</span>
                    <select name="queue" onchange="this.form.requestSubmit()">
                        <option value="all">All active cases</option>
                        <option value="new" <?=$queueFilter==='new'?'selected':''?>>New / received</option>
                        <option value="unassigned" <?=$queueFilter==='unassigned'?'selected':''?>>Unassigned</option>
                        <?php if ($workflowReady) : ?>
                            <option value="attention" <?=in_array($queueFilter,['attention','overdue'],true)?'selected':''?>>Reminder due</option>
                            <option value="followup" <?=$queueFilter==='followup'?'selected':''?>>Follow-up due</option>
                        <?php endif; ?>
                    </select>
                </label>
            <?php endif; ?>
            <?php if (!$scope) : ?>
                <label class="concern-filter-field concern-filter-field--unit">
                    <span>Assigned unit</span>
                    <select name="unit" onchange="this.form.requestSubmit()">
                        <option value="all">All units</option>
                        <?php foreach ($portalOptions as $code => $name) : ?>
                            <option value="<?=e($code)?>" <?=$unit===$code?'selected':''?>><?=e($name)?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php else: ?>
                <span class="scope-chip"><?=e($scope)?> portal</span>
            <?php endif; ?>
        </div>
    </form>

    <section class="panel concern-queue-panel">
        <div class="concern-queue-head">
            <div>
                <strong><?=$resultTotal?> <?=$isCompletedView?'completed case':'active concern'?><?=$resultTotal===1?'':'s'?></strong>
                <span><?=$isCompletedView?'Completed records remain searchable with their full case history and evidence.':'Resolved and Closed cases automatically leave this working queue.'?></span>
            </div>
            <span class="concern-results-note"><?=$hasActiveFilters?'Filtered results':($isCompletedView?'Completed cases':'Active cases')?></span>
        </div>
        <div class="table-wrap concern-table-wrap">
            <table class="concern-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Concern</th>
                        <th>Routing</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <?php if ($workflowReady) : ?>
                            <th><?=$isCompletedView?'Completed':'Reminder'?></th>
                        <?php endif; ?>
                        <th>Assigned</th>
                        <th class="concern-col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($concerns as $c):$src=concern_portal_code($c);$dest=concern_assigned_scope($c);$completedAt=(string)((($c['resolved_at'] ?? null) ?: ($c['updated_at'] ?? $c['created_at'])));?>
                        <tr>
                            <td><strong><?=e($c['reference_code'])?></strong><small class="table-sub"><?=e(date('M j, Y',strtotime($c['created_at'])))?></small></td>
                            <td class="cell-title"><strong><?=e($c['subject'])?></strong><span><?=e($c['concern_type'])?></span></td>
                            <td>
                                <div class="concern-routing">
                                    <strong><?=e($src)?><?=$src!==$dest?' → '.e($dest):''?></strong><small>Student: <?=e(student_campus_display($c['campus']??''))?></small>
                                </div>
                            </td>
                            <td><span class="priority-badge priority-<?=e(strtolower($c['priority']))?>"><?=e($c['priority'])?></span></td>
                            <td><?=status_badge($c['status'])?></td>
                            <?php if ($workflowReady) : ?>
                                <?php if ($isCompletedView) : ?>
                                    <td><span class="concern-completed-date"><?=e(date('M j, Y',strtotime($completedAt)))?></span></td>
                                <?php else: ?>
                                    <td><span class="deadline-badge <?=concern_reminder_due($c)?'is-reminder-due':''?>"><?=e(concern_due_label($c))?></span></td>
                                <?php endif; ?>
                            <?php endif; ?>
                            <td><span class="concern-assignee <?=empty($c['assigned_name'])?'is-unassigned':''?>"><?=e($c['assigned_name']?:$dest.' unit')?></span></td>
                            <td class="concern-action-cell"><a class="btn btn--soft concern-review-btn" href="?id=<?=$c['id']?>&amp;view=<?=e($view)?>">Review <span aria-hidden="true">→</span></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$concerns) : ?>
                        <tr>
                            <td colspan="<?=$workflowReady?8:7?>">
                                <div class="empty-state concern-empty-state">
                                    <strong><?=$isCompletedView?'No completed cases found':'No active concerns found'?></strong><span><?=$hasActiveFilters?'Try changing or clearing the current filters.':($isCompletedView?'Resolved and Closed cases will appear here automatically.':'New and in-progress cases will appear here automatically.')?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?=render_pagination($pager)?>
    </section>
</div>
<?php
endif;
admin_footer();
?>
