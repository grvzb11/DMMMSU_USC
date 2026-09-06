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
if (!admin_can_permission('announcements.manage')) deny_access('You do not have permission to manage announcements.');
if (!db_table_exists($pdo, 'announcements')) {
    admin_header('Announcements', 'Content');
    echo '<div class="notice notice--warning">Run pending migrations before using announcements.</div>';
    admin_footer();
    exit;
}
$scope = admin_scope_code();
$portals = ['ALL' => 'All portals']+governance_portals();
if ($scope) $portals = [$scope => $portals[$scope] ?? $scope];
$years = academic_year_rows($pdo, true);
$activeYear = active_academic_year($pdo);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = (string)($_POST['action'] ?? 'save');
        if ($action === 'archive') {
            $id = (int)($_POST['id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM announcements WHERE id=?');
            $st->execute([$id]);
            $old = $st->fetch();
            if (!$old) throw new RuntimeException('Announcement not found.');
            if ($scope && strtoupper($old['portal_code']) !== $scope) deny_access();
            $pdo->prepare("UPDATE announcements SET status='archived',updated_by=? WHERE id=?")->execute([$_SESSION['admin_id'] ?? null, $id]);
            admin_log($pdo, 'content', 'Archived announcement', $old['title'], 'announcement', $id, ['status' => $old['status']], ['status' => 'archived']);
            header('Location: announcements.php?updated=1');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $portal = $scope?:strtoupper(trim((string)($_POST['portal_code'] ?? 'ALL')));
        if (!isset($portals[$portal])) throw new RuntimeException('Choose a valid portal.');
        $title = trim((string)($_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        $priority = in_array($_POST['priority'] ?? 'info', ['info', 'warning', 'emergency'], true)?$_POST['priority']:'info';
        $audience = in_array($_POST['audience'] ?? 'public', ['public', 'admin', 'both'], true)?$_POST['audience']:'public';
        $status = in_array($_POST['status'] ?? 'draft', ['draft', 'active', 'archived'], true)?$_POST['status']:'draft';
        $starts = trim((string)($_POST['starts_at'] ?? ''));
        $ends = trim((string)($_POST['ends_at'] ?? ''));
        $linkLabel = trim((string)($_POST['link_label'] ?? ''));
        $linkUrl = trim((string)($_POST['link_url'] ?? ''));
        $year = (int)($_POST['academic_year_id'] ?? ($activeYear['id'] ?? 0));
        if ($title === '' || $message === '') throw new RuntimeException('Title and message are required.');
        if ($ends !== '' && $starts !== '' && strtotime($ends)<strtotime($starts)) throw new RuntimeException('End time must be after the start time.');
        if ($linkUrl !== '' && preg_match('~^(?:javascript|data):~i', $linkUrl)) throw new RuntimeException('Use a safe link URL.');
        $values = [$year?:null, $portal, $title, $message, $priority, $audience, $status, $starts !== ''?date('Y-m-d H:i:s', strtotime($starts)):null, $ends !== ''?date('Y-m-d H:i:s', strtotime($ends)):null, $linkLabel?:null, $linkUrl?:null];
        if ($id) {
            $st = $pdo->prepare('SELECT * FROM announcements WHERE id=?');
            $st->execute([$id]);
            $old = $st->fetch();
            if (!$old) throw new RuntimeException('Announcement not found.');
            if ($scope && strtoupper($old['portal_code']) !== $scope) deny_access();
            $pdo->prepare('UPDATE announcements SET academic_year_id=?,portal_code=?,title=?,message=?,priority=?,audience=?,status=?,starts_at=?,ends_at=?,link_label=?,link_url=?,updated_by=? WHERE id=?')->execute([...$values, $_SESSION['admin_id'] ?? null, $id]);
            admin_log($pdo, 'content', 'Updated announcement', $title, 'announcement', $id, ['title' => $old['title'], 'priority' => $old['priority'], 'status' => $old['status']], ['title' => $title, 'priority' => $priority, 'status' => $status]);
        } else {
            $pdo->prepare('INSERT INTO announcements(academic_year_id,portal_code,title,message,priority,audience,status,starts_at,ends_at,link_label,link_url,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([...$values, $_SESSION['admin_id'] ?? null, $_SESSION['admin_id'] ?? null]);
            $id = (int)$pdo->lastInsertId();
            admin_log($pdo, 'content', 'Created announcement', $title, 'announcement', $id, null, ['priority' => $priority, 'status' => $status, 'portal_code' => $portal]);
        }
        if ($status === 'active' && in_array($audience, ['admin', 'both'], true)) admin_notify($pdo, $title, $message, 'announcements.php', 'warning', null, $portal === 'ALL'?null:$portal, null, 'content');
        header('Location: announcements.php?saved=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM announcements WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch()?:null;
    if ($edit && $scope && strtoupper($edit['portal_code']) !== $scope) $edit = null;
}
$filterPortal = $scope?:strtoupper(trim((string)($_GET['portal'] ?? 'ALL')));
if (!isset($portals[$filterPortal])) $filterPortal = 'ALL';
$statusFilter = (string)($_GET['status'] ?? 'current');
$where = [];
$params = [];
if ($scope) {
    $where[] = 'portal_code=?';
    $params[] = $scope;
} elseif ($filterPortal !== 'ALL') {
    $where[] = 'portal_code=?';
    $params[] = $filterPortal;
}
if ($statusFilter === 'archived') $where[] = "status='archived'";
elseif ($statusFilter === 'draft') $where[] = "status='draft'";
else $where[] = "status<>'archived'";
$st = $pdo->prepare('SELECT a.*,ay.label academic_year_label FROM announcements a LEFT JOIN academic_years ay ON ay.id=a.academic_year_id'.($where?' WHERE '.implode(' AND ', $where):'').' ORDER BY FIELD(a.priority,\'emergency\',\'warning\',\'info\'),a.updated_at DESC LIMIT 200');
$st->execute($params);
$rows = $st->fetchAll();
admin_header('Announcements', 'Content');
admin_content_tabs('announcements.php');
?>
<?php if ($error) : ?>
    <div class="notice notice--error">
        <?=e($error)?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['saved']) || isset($_GET['updated'])) : ?>
    <div class="notice">
        Announcement updated.
    </div>
<?php endif; ?>
<div class="announcement-page-context">
    <span class="page-kicker">ADVISORIES &amp; ALERTS</span>
    <p>Create concise, time-sensitive notices for students and administrators. Use News &amp; Updates for full articles.</p>
</div>
<div class="announcement-workspace">
    <section class="panel announcement-form-panel" id="announcement-form">
        <div class="panel__head announcement-panel-head">
            <div>
                <span class="panel-kicker"><?=$edit?'UPDATE NOTICE':'NEW NOTICE'?></span>
                <h2><?=$edit?'Edit announcement':'New announcement'?></h2>
                <p><?=$edit?'Update the notice details and publishing settings.':'Create a short advisory, alert, or campus-wide notice.'?></p>
            </div>
            <?php if ($edit) : ?>
                <a class="btn btn--soft btn--small" href="announcements.php">Cancel</a>
            <?php endif; ?>
        </div>
        <form method="post" class="panel__body governance-form announcement-form" data-unsaved-warning="1">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="id" value="<?=e((string)($edit['id']??''))?>">
            <div class="announcement-form-grid announcement-form-grid--2">
                <label>
                    Academic year
                    <select name="academic_year_id">
                        <?php foreach ($years as $y) : ?>
                            <option value="<?=$y['id']?>" <?=((int)($edit['academic_year_id']??($activeYear['id']??0))===(int)$y['id'])?'selected':''?>><?=e($y['label'])?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if (!$scope) : ?>
                    <label>
                        Portal
                        <select name="portal_code">
                            <?php foreach ($portals as $code => $name) : ?>
                                <option value="<?=e($code)?>" <?=($edit['portal_code']??'ALL')===$code?'selected':''?>><?=e($name)?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
            </div>
            <label>
                Title
                <input name="title" required maxlength="190" value="<?=e($edit['title']??'')?>" placeholder="Enter announcement title">
            </label>
            <label>
                Message
                <textarea class="announcement-message" name="message" rows="6" required placeholder="Write the announcement message..."><?=e($edit['message']??'')?></textarea>
            </label>
            <div class="announcement-subsection">
                <div class="announcement-subsection__head">
                    <strong>Publishing settings</strong>
                    <span>Choose priority, audience, and status.</span>
                </div>
                <div class="announcement-form-grid announcement-form-grid--3">
                    <label>
                        Priority
                        <select name="priority">
                            <option value="info" <?=($edit['priority']??'info')==='info'?'selected':''?>>Information</option>
                            <option value="warning" <?=($edit['priority']??'')==='warning'?'selected':''?>>Warning</option>
                            <option value="emergency" <?=($edit['priority']??'')==='emergency'?'selected':''?>>Emergency</option>
                        </select>
                    </label>
                    <label>
                        Audience
                        <select name="audience">
                            <option value="public" <?=($edit['audience']??'public')==='public'?'selected':''?>>Public</option>
                            <option value="admin" <?=($edit['audience']??'')==='admin'?'selected':''?>>Administrators</option>
                            <option value="both" <?=($edit['audience']??'')==='both'?'selected':''?>>Both</option>
                        </select>
                    </label>
                    <label>
                        Status
                        <select name="status">
                            <option value="draft" <?=($edit['status']??'draft')==='draft'?'selected':''?>>Draft</option>
                            <option value="active" <?=($edit['status']??'')==='active'?'selected':''?>>Active</option>
                            <option value="archived" <?=($edit['status']??'')==='archived'?'selected':''?>>Archived</option>
                        </select>
                    </label>
                </div>
            </div>
            <div class="announcement-subsection">
                <div class="announcement-subsection__head">
                    <strong>Visibility period</strong>
                    <span>Leave blank to start immediately or keep the notice open-ended.</span>
                </div>
                <div class="announcement-form-grid announcement-form-grid--2">
                    <label>
                        Starts
                        <input type="datetime-local" name="starts_at" value="<?=!empty($edit['starts_at'])?e(date('Y-m-d\TH:i',strtotime($edit['starts_at']))):''?>">
                    </label>
                    <label>
                        Ends
                        <input type="datetime-local" name="ends_at" value="<?=!empty($edit['ends_at'])?e(date('Y-m-d\TH:i',strtotime($edit['ends_at']))):''?>">
                    </label>
                </div>
            </div>
            <details class="announcement-optional" <?=(!empty($edit['link_label'])||!empty($edit['link_url']))?'open':''?>>
                <summary><span>Optional link</span><small>Add a related page or resource</small></summary>
                <div class="announcement-optional__body announcement-form-grid announcement-form-grid--2">
                    <label>
                        Link label
                        <input name="link_label" value="<?=e($edit['link_label']??'')?>" placeholder="Example: Read advisory">
                    </label>
                    <label>
                        Link URL
                        <input name="link_url" value="<?=e($edit['link_url']??'')?>" placeholder="news/article.php?... or https://...">
                    </label>
                </div>
            </details>
            <div class="announcement-form-actions">
                <button class="btn"><?=$edit?'Save changes':'Save announcement'?></button>
            </div>
        </form>
    </section>
    <section class="panel announcement-queue-panel">
        <div class="panel__head announcement-panel-head announcement-queue-head">
            <div>
                <span class="panel-kicker">QUEUE</span>
                <h2>Announcement queue</h2>
                <p><?=count($rows)?> record<?=count($rows)===1?'':'s'?> in this view.</p>
            </div>
        </div>
        <form class="announcement-queue-filters" method="get">
            <?php if (!$scope) : ?>
                <label>
                    <span>Portal</span>
                    <select name="portal" onchange="this.form.submit()">
                        <?php foreach ($portals as $code => $name) : ?>
                            <option value="<?=e($code)?>" <?=$filterPortal===$code?'selected':''?>><?=e($name)?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <label>
                <span>Status</span>
                <select name="status" onchange="this.form.submit()">
                    <option value="current" <?=$statusFilter==='current'?'selected':''?>>Current</option>
                    <option value="draft" <?=$statusFilter==='draft'?'selected':''?>>Drafts</option>
                    <option value="archived" <?=$statusFilter==='archived'?'selected':''?>>Archived</option>
                </select>
            </label>
        </form>
        <div class="announcement-admin-list announcement-admin-list--refined">
            <?php foreach ($rows as $r) : ?>
                <article class="announcement-admin-item announcement-admin-item--refined announcement-admin-item--<?=e($r['priority'])?>">
                    <div class="announcement-admin-item__body">
                        <div class="announcement-admin-meta announcement-admin-meta--refined">
                            <span class="announcement-status-dot announcement-status-dot--<?=e($r['status'])?>"></span>
                            <span><?=e(ucfirst($r['status']))?></span>
                            <span><?=e(ucfirst($r['priority']))?></span>
                            <span><?=e(strtoupper($r['portal_code']))?></span>
                            <?php if ($r['academic_year_label']) : ?>
                                <span><?=e($r['academic_year_label'])?></span>
                            <?php endif; ?>
                        </div>
                        <h3><?=e($r['title'])?></h3>
                        <p><?=e($r['message'])?></p>
                        <small><?=e($r['starts_at']?date('M j, Y · g:i A',strtotime($r['starts_at'])):'Starts immediately')?><?= $r['ends_at']?' · ends '.e(date('M j, Y · g:i A',strtotime($r['ends_at']))):' · no expiration' ?></small>
                    </div>
                    <div class="announcement-item-actions">
                        <a class="btn btn--soft btn--small" href="?edit=<?=$r['id']?>#announcement-form">Edit</a>
                        <?php if ($r['status'] !== 'archived') : ?>
                            <form method="post" data-confirm="Archive this announcement? It will stop appearing publicly but remain in history.">
                                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                <input type="hidden" name="action" value="archive">
                                <input type="hidden" name="id" value="<?=$r['id']?>">
                                <button class="btn btn--soft btn--small">Archive</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$rows) : ?>
                <div class="empty-state announcement-empty-state">
                    <strong>No announcements found</strong><span>There are no records for the selected filters.</span>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php
admin_footer();
?>
