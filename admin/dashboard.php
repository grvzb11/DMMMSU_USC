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
ensure_admin_platform_tables($pdo);
$scope = admin_scope_code();

$postWhere = ['deleted_at IS NULL'];
$postParams = [];
$concWhere = [];
$concParams = [];
if ($scope) {
    $postWhere[] = 'category=?';
    $postParams[] = strtolower($scope);
    $concWhere[] = '(UPPER(source_portal)=? OR UPPER(assigned_scope)=?)';
    array_push($concParams, $scope, $scope);
}
function dash_count(PDO $pdo, string $table, array $where, array $params, string $extra = ''): int {
    $sql = "SELECT COUNT(*) FROM $table".($where || $extra?' WHERE ':'').implode(' AND ', $where).($extra?($where?' AND ':'').$extra:'');
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
}
$counts = [];
$counts['posts'] = dash_count($pdo, 'posts', $postWhere, $postParams);
$counts['published'] = dash_count($pdo, 'posts', $postWhere, $postParams, "status='published'");
$counts['drafts'] = dash_count($pdo, 'posts', $postWhere, $postParams, "status='draft'");
$counts['postReview'] = dash_count($pdo, 'posts', $postWhere, $postParams, "status='review'");
$counts['concerns'] = dash_count($pdo, 'concerns', $concWhere, $concParams);
$counts['open'] = dash_count($pdo, 'concerns', $concWhere, $concParams, "status NOT IN ('Resolved','Closed')");
$counts['urgent'] = dash_count($pdo, 'concerns', $concWhere, $concParams, "priority='Urgent' AND status NOT IN ('Resolved','Closed')");
$workflowReady = db_column_exists($pdo, 'concerns', 'due_at');
$counts['overdue'] = $workflowReady?dash_count($pdo, 'concerns', $concWhere, $concParams, "status NOT IN ('Resolved','Closed') AND due_at IS NOT NULL AND due_at<NOW()"):0;
$counts['unassigned'] = dash_count($pdo, 'concerns', $concWhere, $concParams, "status NOT IN ('Resolved','Closed') AND assigned_to IS NULL");
$counts['resolvedMonth'] = dash_count($pdo, 'concerns', $concWhere, $concParams, "status IN ('Resolved','Closed') AND resolved_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01')");
$unread = admin_unread_notifications($pdo);

$st = $pdo->prepare('SELECT id,reference_code,subject,status,priority,created_at FROM concerns'.($concWhere?' WHERE '.implode(' AND ', $concWhere):'').' ORDER BY FIELD(priority,"Urgent","High","Normal","Low"),created_at DESC LIMIT 5');
$st->execute($concParams);
$recentConcerns = $st->fetchAll();
$st = $pdo->prepare('SELECT id,title,category,status,published_at FROM posts WHERE '.implode(' AND ', $postWhere).' ORDER BY updated_at DESC LIMIT 5');
$st->execute($postParams);
$recentPosts = $st->fetchAll();

$activityWhere = [];
$activityParams = [];
if ($scope) {
    $activityWhere[] = '(campus=? OR campus IS NULL)';
    $activityParams[] = $scope;
}
if (!can_view_activity()) {
    $activityWhere[] = 'admin_id=?';
    $activityParams[] = (int)($_SESSION['admin_id'] ?? 0);
}
$st = $pdo->prepare('SELECT * FROM admin_activity_logs'.($activityWhere?' WHERE '.implode(' AND ', $activityWhere):'').' ORDER BY created_at DESC LIMIT 6');
$st->execute($activityParams);
$activity = $st->fetchAll();

$role = admin_role();
$roleLabel = admin_role_label();
$workspace = $scope?($scope.' Administration'):'University-wide Administration';
$subtitle = match($role) {
    'admin' => 'System governance, access control, content operations, and platform health.',
    'usc' => 'University-wide council operations across USC and campus portals.',
    'sas_director' => 'University-wide Student Affairs and Services oversight, student concerns, and campus activity.',
    'sbo_adviser' => 'Full operational control of the assigned campus website, shared with Student Affairs and Services and the Student Body Organization.',
    'campus_sbo' => 'Full operational control of the assigned campus website, shared with Student Affairs and Services and the Adviser.',
    default => 'USC administration workspace.'
};

$attentionTotal = $counts['urgent']+$counts['overdue']+$counts['drafts']+(can_publish_content()?$counts['postReview']:0);

$resolved = max(0, $counts['concerns']-$counts['open']);
$resolutionRate = $counts['concerns']>0?(int)round(($resolved/$counts['concerns'])*100):100;
$contentReady = $counts['posts']>0?(int)round(($counts['published']/$counts['posts'])*100):100;
$avgResolutionHours = null;
try {
    $sql = 'SELECT AVG(TIMESTAMPDIFF(HOUR,created_at,resolved_at)) FROM concerns'.($concWhere?' WHERE '.implode(' AND ', $concWhere).' AND ':' WHERE ')."resolved_at IS NOT NULL";
    $st = $pdo->prepare($sql);
    $st->execute($concParams);
    $v = $st->fetchColumn();
    if ($v !== null) $avgResolutionHours = (float)$v;
} catch (Throwable $ignored) {
}
$systemOps = null;
if (admin_role() === 'admin') {
    require_once '../config/migrations.php';
    require_once '../config/maintenance.php';
    $migrationInfo = migration_status($pdo);
    $pendingMigrations = count(array_filter($migrationInfo, fn($m) => empty($m['applied'])));
    $backups = maintenance_list_backups($pdo);
    $latestBackup = $backups[0] ?? null;
    $backupReminder = max(1, (int)(system_setting($pdo, 'backup_reminder_days', '7') ?? 7));
    $backupAgeDays = $latestBackup?max(0, (int)floor((time()-strtotime((string)$latestBackup['created_at']))/86400)):null;
    $st = $pdo->query("SELECT COUNT(*) FROM admins WHERE status='Active'");
    $activeAdmins = (int)$st->fetchColumn();
    $st = $pdo->query("SELECT COUNT(*) FROM admin_sessions WHERE revoked_at IS NULL AND last_seen_at>=DATE_SUB(NOW(),INTERVAL 30 MINUTE)");
    $activeSessions = (int)$st->fetchColumn();
    $systemOps = ['pending_migrations' => $pendingMigrations, 'latest_backup' => $latestBackup, 'backup_age' => $backupAgeDays, 'backup_due' => $backupAgeDays === null || $backupAgeDays>$backupReminder, 'active_admins' => $activeAdmins, 'active_sessions' => $activeSessions];
}

$activeAy = active_academic_year($pdo);
$activeAyLabel = academic_year_label($activeAy);
$dashboardAnnouncements = [];
$dashboardAnnouncementCount = 0;
$dashboardOfficerCount = 0;
if (db_table_exists($pdo, 'announcements')) {
    $dashboardAnnouncements = active_announcements($pdo, $scope?:'USC', 'admin', 4);
    $aw = $scope?" AND (portal_code=? OR portal_code='ALL')":'';
    $st = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE status='active'".$aw);
    $pa = [];
    if ($scope) $pa[] = $scope;
    $st->execute($pa);
    $dashboardAnnouncementCount = (int)$st->fetchColumn();
}
if (db_table_exists($pdo, 'officers')) {
    $ow = $scope?' AND portal_code=?':'';
    $st = $pdo->prepare('SELECT COUNT(*) FROM officers WHERE is_archived=0'.($activeAy?' AND academic_year_id=?':'').$ow);
    $pa = [];
    if ($activeAy) $pa[] = (int)$activeAy['id'];
    if ($scope) $pa[] = $scope;
    $st->execute($pa);
    $dashboardOfficerCount = (int)$st->fetchColumn();
}
admin_header('Dashboard', $workspace);
?>
<section class="dashboard-hero-card dashboard-hero-card--compact dashboard-hero-card--minimal">
    <div class="dashboard-hero-card__copy">
        <span class="dashboard-role"><?=e(strtoupper($roleLabel))?></span>
        <h2>Good day, <?=e($_SESSION['admin_name']??'Administrator')?>.</h2>
        <p><?=e($subtitle)?></p>
    </div>
    <div class="dashboard-hero-card__actions">
        <?php if (can_manage_content()) : ?>
            <a class="btn" href="posts.php?action=new">New publication</a>
        <?php endif; ?>
        <a class="btn btn--soft" href="concerns.php">Review concerns</a>
        <?php if (can_manage_accounts()) : ?>
            <a class="btn btn--soft" href="accounts.php">Manage accounts</a>
        <?php endif; ?>
    </div>
</section>
<section class="dashboard-summary-strip" aria-label="Dashboard summary">
    <article class="dashboard-summary-card">
        <span>Academic year</span>
        <strong><?=e($activeAyLabel)?></strong>
        <small><?=!empty($activeAy['status'])?e(ucfirst((string)$activeAy['status'])):'Current term'?></small>
    </article>
    <article class="dashboard-summary-card <?=$counts['open']?'dashboard-summary-card--attention':''?>">
        <span>Open concerns</span>
        <strong><?=$counts['open']?></strong>
        <small><?=$counts['overdue']?> reminder due · <?=$counts['unassigned']?> unassigned</small>
    </article>
    <article class="dashboard-summary-card">
        <span>Published content</span>
        <strong><?=$counts['published']?></strong>
        <small><?=$counts['posts']?> total publication<?=$counts['posts']===1?'':'s'?></small>
    </article>
    <article class="dashboard-summary-card">
        <span>Current officers</span>
        <strong><?=$dashboardOfficerCount?></strong>
        <small><?=e($scope?:'University-wide')?> roster</small>
    </article>
</section>
<div class="dashboard-arranged-grid dashboard-arranged-grid--minimal">
    <div class="dashboard-arranged-column dashboard-arranged-column--main">
        <section class="panel dashboard-priority-panel dashboard-priority-panel--minimal">
            <div class="panel__head dashboard-panel-head">
                <div>
                    <span class="panel-eyebrow">PRIORITY</span>
                    <h2>Needs attention</h2>
                    <p>Focus on the most important tasks first.</p>
                </div>
                <span class="attention-total"><?=$attentionTotal?> item<?=$attentionTotal===1?'':'s'?></span>
            </div>
            <div class="panel__body attention-list attention-list--professional attention-list--minimal">
                <?php if ($counts['overdue']) : ?>
                    <a class="attention-row attention-row--reminder" href="concerns.php?queue=attention">
                    <span class="attention-icon">R</span>
                    <div>
                        <strong><?=$counts['overdue']?> review reminder<?=$counts['overdue']==1?'':'s'?> due</strong>
                        <p>Suggested follow-up window reached. Review when workload allows.</p>
                    </div>
                    <span class="attention-arrow">→</span>
                    </a>
                <?php endif; ?>
                <?php if ($counts['unassigned']) : ?>
                    <a class="attention-row" href="concerns.php?queue=unassigned">
                    <span class="attention-icon">A</span>
                    <div>
                        <strong><?=$counts['unassigned']?> unassigned concern<?=$counts['unassigned']==1?'':'s'?></strong>
                        <p>Assign these to a responsible handler.</p>
                    </div>
                    <span class="attention-arrow">→</span>
                    </a>
                <?php endif; ?>
                <?php if (can_publish_content() && $counts['postReview']) : ?>
                    <a class="attention-row" href="posts.php?status=review">
                    <span class="attention-icon">R</span>
                    <div>
                        <strong><?=$counts['postReview']?> post<?=$counts['postReview']==1?'':'s'?> for review</strong>
                        <p>Ready for approval and publishing.</p>
                    </div>
                    <span class="attention-arrow">→</span>
                    </a>
                <?php elseif ($counts['drafts']) : ?>
                    <a class="attention-row" href="posts.php?status=draft">
                    <span class="attention-icon">D</span>
                    <div>
                        <strong><?=$counts['drafts']?> draft publication<?=$counts['drafts']==1?'':'s'?></strong>
                        <p>Finish and publish pending content.</p>
                    </div>
                    <span class="attention-arrow">→</span>
                    </a>
                <?php endif; ?>
                <?php if ($attentionTotal === 0) : ?>
                    <div class="dashboard-clear-state">
                        <span>✓</span>
                        <div>
                            <strong>Workspace is clear</strong>
                            <p>There are no urgent tasks right now.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <div class="dashboard-split-panels dashboard-split-panels--minimal">
            <section class="panel">
                <div class="panel__head dashboard-panel-head">
                    <div>
                        <span class="panel-eyebrow">E-SUMBONG</span>
                        <h2>Recent cases</h2>
                        <p>Latest concern activity in your scope.</p>
                    </div>
                    <a class="panel-link" href="concerns.php">Open queue</a>
                </div>
                <div class="panel__body compact-list compact-list--cases compact-list--dense">
                    <?php foreach (array_slice($recentConcerns, 0, 3) as $c) : ?>
                        <a href="concerns.php?id=<?=$c['id']?>">
                        <span class="priority-dot priority-dot--<?=e(strtolower($c['priority']))?>"></span>
                        <div>
                            <strong><?=e($c['subject'])?></strong><small><?=e($c['reference_code'])?> · <?=e($c['status'])?></small>
                        </div>
                        <time><?=e(date('M j',strtotime($c['created_at'])))?></time>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$recentConcerns) : ?>
                        <div class="empty-state">
                            No concern activity yet.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            <section class="panel">
                <div class="panel__head dashboard-panel-head">
                    <div>
                        <span class="panel-eyebrow">CONTENT</span>
                        <h2>Latest publications</h2>
                        <p>Recently updated content in your workspace.</p>
                    </div>
                    <?php if (can_manage_content()) : ?>
                        <a class="panel-link" href="posts.php">Open publications</a>
                    <?php endif; ?>
                </div>
                <div class="panel__body dashboard-post-list dashboard-post-list--dense">
                    <?php foreach (array_slice($recentPosts, 0, 3) as $p) : ?>
                        <a class="dashboard-post-row" href="posts.php?edit=<?=$p['id']?>">
                        <span class="dashboard-post-row__status status-dot status-dot--<?=e(strtolower((string)$p['status']))?>"></span>
                        <div>
                            <strong><?=e($p['title'])?></strong>
                            <small><?=e(strtoupper((string)$p['category']))?> · <?=e(ucfirst((string)$p['status']))?></small>
                        </div>
                        <time><?=e(date('M j',strtotime($p['published_at']?:date('Y-m-d H:i:s'))))?></time>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$recentPosts) : ?>
                        <div class="empty-state">
                            No publication activity yet.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <div class="dashboard-arranged-column dashboard-arranged-column--side dashboard-arranged-column--side-minimal">
        <aside class="panel dashboard-quick-panel">
            <div class="panel__head dashboard-panel-head">
                <div>
                    <span class="panel-eyebrow">QUICK SUMMARY</span>
                    <h2>At a glance</h2>
                    <p>Only the essentials for today.</p>
                </div>
            </div>
            <div class="panel__body dashboard-quick-grid">
                <article>
                    <span>Unread notifications</span><strong><?=$unread?></strong><small><?=$unread?'Waiting to be reviewed':'All caught up'?></small>
                </article>
                <article>
                    <span>Resolution rate</span><strong><?=$resolutionRate?>%</strong><small><?=$resolved?> resolved</small>
                </article>
                <article>
                    <span>Active advisories</span><strong><?=$dashboardAnnouncementCount?></strong><small>Visible announcements</small>
                </article>
                <article>
                    <span>Avg. resolution</span><strong><?=$avgResolutionHours===null?'—':e(number_format($avgResolutionHours,1).'h')?></strong><small>This month</small>
                </article>
            </div>
        </aside>
        <section class="panel dashboard-activity-panel-minimal">
            <div class="panel__head dashboard-panel-head">
                <div>
                    <span class="panel-eyebrow">ACTIVITY</span>
                    <h2>Recent activity</h2>
                    <p>Latest important changes.</p>
                </div>
                <?php if (can_view_activity()) : ?>
                    <a class="panel-link" href="activity.php">View all</a>
                <?php endif; ?>
            </div>
            <div class="panel__body activity-list activity-list--dashboard-minimal">
                <?php foreach (array_slice($activity, 0, 3) as $a) : ?>
                    <div class="activity-row">
                        <div class="activity-icon">
                            <?=e(strtoupper(substr($a['module'],0,1)))?>
                        </div>
                        <div>
                            <strong><?=e($a['action'])?></strong>
                            <p><?=e($a['description'])?></p>
                            <small><?=e(date('M j',strtotime($a['created_at'])))?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$activity) : ?>
                    <?php foreach (array_slice($recentPosts, 0, 3) as $p) : ?>
                        <div class="activity-row">
                            <div class="activity-icon">
                                N
                            </div>
                            <div>
                                <strong><?=e($p['title'])?></strong>
                                <p><?=e(ucfirst((string)$p['status']))?> publication</p>
                                <small><?=e(date('M j',strtotime($p['published_at']?:date('Y-m-d H:i:s'))))?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php
admin_footer();
?>
