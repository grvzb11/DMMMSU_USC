<?php
require_once '../config/database.php';
require_once '_layout.php';
if (!can_view_activity()) deny_access('Your role cannot view the audit log.');
ensure_admin_platform_tables($pdo);

$q = trim($_GET['q'] ?? '');
$module = trim($_GET['module'] ?? 'all');
$scope = admin_scope_code();
$modules = [
'auth' => 'Authentication',
'accounts' => 'Accounts',
'news' => 'News & Updates',
'concerns' => 'E-Sumbong',
'homepage' => 'Homepage',
'media' => 'Media Library',
'settings' => 'Settings',
'security' => 'Security',
'privacy' => 'Privacy access',
'backups' => 'Backups',
'reports' => 'Reports',
'notifications' => 'Notifications',
'system' => 'System',
];
if ($module !== 'all' && !isset($modules[$module])) $module = 'all';
$fromDate = trim((string)($_GET['from'] ?? ''));
$toDate = trim((string)($_GET['to'] ?? ''));
$adminFilter = (int)($_GET['admin'] ?? 0);

$where = [];
$params = [];
$where[] = "LOWER(COALESCE(module,''))<>'tala'";
if ($scope) {
    $where[] = '(campus=? OR campus IS NULL)';
    $params[] = $scope;
}
if ($q !== '') {
    $where[] = '(admin_name LIKE ? OR description LIKE ? OR action LIKE ?)';
    $like = '%'.$q.'%';
    array_push($params, $like, $like, $like);
}
if ($module !== 'all') {
    $where[] = 'module=?';
    $params[] = $module;
}
if ($adminFilter>0) {
    $where[] = 'admin_id=?';
    $params[] = $adminFilter;
}
if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
    $where[] = 'created_at>=?';
    $params[] = $fromDate.' 00:00:00';
}
if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $where[] = 'created_at<=?';
    $params[] = $toDate.' 23:59:59';
}
$base = ' FROM admin_activity_logs'.($where?' WHERE '.implode(' AND ', $where):'');
$countSt = $pdo->prepare('SELECT COUNT(*)'.$base);
$countSt->execute($params);
$resultTotal = (int)$countSt->fetchColumn();
$pager = pagination_state($resultTotal, 25);
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!can_export_reports()) deny_access('Your role cannot export audit data.');
    admin_log($pdo, 'reports', 'Exported activity log', 'Exported filtered audit log as CSV');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity-log-'.date('Ymd').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Administrator', 'Role', 'Campus', 'Module', 'Action', 'Description', 'IP', 'Device']);
    $ex = $pdo->prepare('SELECT *'.$base.' ORDER BY created_at DESC LIMIT 10000');
    $ex->execute($params);
    while ($r = $ex->fetch()) fputcsv($out, [$r['created_at'], $r['admin_name'], $r['role'], $r['campus'], $r['module'], $r['action'], $r['description'], $r['ip_address'], admin_device_label($r['user_agent'] ?? '')]);
    fclose($out);
    exit;
}
$sql = 'SELECT *'.$base.' ORDER BY created_at DESC LIMIT '.$pager['per_page'].' OFFSET '.$pager['offset'];
$st = $pdo->prepare($sql);
$st->execute($params);
$logs = $st->fetchAll();
$admins = $pdo->query("SELECT id,full_name FROM admins ORDER BY full_name")->fetchAll();
$hasFilters = ($q !== '' || $module !== 'all' || $adminFilter>0 || $fromDate !== '' || $toDate !== '');
$auditIntegrity = audit_verify_chain($pdo);
$adminName = '';
if ($adminFilter>0) {
    foreach ($admins as $a) {
        if ($adminFilter === (int)$a['id']) {
            $adminName = (string)$a['full_name'];
            break;
        }
    }
}
$activeFilterChips = [];
if ($q !== '') $activeFilterChips[] = ['label' => 'Search', 'value' => $q];
if ($module !== 'all') $activeFilterChips[] = ['label' => 'Module', 'value' => $modules[$module] ?? ucfirst($module)];
if ($adminFilter>0 && $adminName !== '') $activeFilterChips[] = ['label' => 'Administrator', 'value' => $adminName];
if ($fromDate !== '' || $toDate !== '') $activeFilterChips[] = ['label' => 'Date', 'value' => trim(($fromDate !== ''?$fromDate:'Any').' → '.($toDate !== ''?$toDate:'Any'))];

admin_header('Activity Log', 'Audit trail');
$exportQuery = $_GET;
unset($exportQuery['page']);
$exportQuery['export'] = 'csv';
?>
<section class="activity-page" aria-label="Audit trail workspace">
    <div class="notice <?=!empty($auditIntegrity['ok'])?'':'notice--warning'?>">
        <strong>Audit integrity:</strong> <?=e((string)$auditIntegrity['message'])?>
        <?php if (!empty($auditIntegrity['ok'])) : ?>
            · Hash chain verified.
        <?php endif; ?>
    </div>
    <form class="activity-filter-card<?= $hasFilters?' is-filtered':'' ?>" method="get" id="activityFilterForm">
        <div class="activity-filter-card__head">
            <div class="activity-filter-heading">
                <span class="activity-filter-icon" aria-hidden="true"></span>
                <div>
                    <strong>Filter activity</strong>
                    <small>Narrow the audit trail by keyword, module, administrator, or date.</small>
                </div>
            </div>
            <div class="activity-filter-head-actions">
                <?php if ($hasFilters) : ?>
                    <span class="activity-filter-state">Filters active</span>
                <?php endif; ?>
                <?php if (can_export_reports()) : ?>
                    <a class="btn btn--soft activity-export-btn" href="?<?=e(http_build_query($exportQuery))?>"><span aria-hidden="true">↓</span> Export CSV</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="activity-filter-grid">
            <label class="activity-filter-control activity-filter-control--search">
                <span>Search</span>
                <div class="activity-searchbox">
                    <input type="search" name="q" value="<?=e($q)?>" placeholder="Search action, details, or administrator…" aria-label="Search activity log">
                </div>
            </label>
            <label class="activity-filter-control activity-filter-control--module">
                <span>Module</span>
                <select name="module" id="activityModule">
                    <option value="all">All modules</option>
                    <?php foreach ($modules as $k => $v) : ?>
                        <option value="<?=e($k)?>" <?=$module===$k?'selected':''?>><?=e($v)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="activity-filter-control activity-filter-control--admin">
                <span>Administrator</span>
                <select name="admin" id="activityAdmin">
                    <option value="0">All administrators</option>
                    <?php foreach ($admins as $a) : ?>
                        <option value="<?=$a['id']?>" <?=$adminFilter===(int)$a['id']?'selected':''?>><?=e($a['full_name'])?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="activity-date-range" role="group" aria-label="Activity date range">
                <label class="activity-filter-control activity-filter-control--date">
                    <span>From</span>
                    <input type="date" name="from" value="<?=e($fromDate)?>">
                </label>
                <label class="activity-filter-control activity-filter-control--date">
                    <span>To</span>
                    <input type="date" name="to" value="<?=e($toDate)?>">
                </label>
            </div>
            <div class="activity-filter-submit">
                <button class="btn activity-apply-btn" type="submit">Apply</button>
                <?php if ($hasFilters) : ?>
                    <a class="activity-inline-reset" href="activity.php">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($hasFilters) : ?>
            <div class="activity-filter-summary" aria-label="Active filters">
                <span class="activity-filter-summary__label">Active filters</span>
                <div class="activity-filter-chips">
                    <?php foreach ($activeFilterChips as $chip) : ?>
                        <span class="activity-filter-chip"><b><?=e($chip['label'])?>:</b> <?=e($chip['value'])?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
    <section class="panel activity-log-panel">
        <div class="table-wrap activity-table-wrap">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th class="activity-col-action">Activity</th>
                        <th class="activity-col-user">Administrator</th>
                        <th class="activity-col-access">Access</th>
                        <th class="activity-col-module">Module</th>
                        <th class="activity-col-date">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $l):
                                        $logModule=strtolower((string)$l['module']);
                                        $moduleLabel=$modules[$logModule] ?? ucwords(str_replace(['_','-'],' ',$logModule));
                                        $campus=strtoupper(trim((string)($l['campus']??'')));
                                        $accessLabel=$campus!=='' ? portal_display_name($campus) : (in_array((string)$l['role'],['admin','usc','sas_director'],true)?'University-wide':'Assigned campus');
                                        $created=strtotime((string)$l['created_at']);
                                    ?>
                    <tr>
                        <td class="activity-cell-main">
                            <div class="activity-action-copy">
                                <strong><?=e($l['action'])?></strong>
                                <span><?=e($l['description'])?></span>
                            </div>
                            <?php
                            $oldValues = !empty($l['old_values'])?json_decode((string)$l['old_values'], true):null;
                            $newValues = !empty($l['new_values'])?json_decode((string)$l['new_values'], true):null;
                            $hasDiff = is_array($oldValues) || is_array($newValues);
                            ?>
                            <?php if ($hasDiff) : ?>
                                <details class="activity-diff">
                                    <summary>View changes <span aria-hidden="true">›</span></summary>
                                    <div class="activity-diff-grid">
                                        <div>
                                            <b>Before</b>
                                            <pre><?=e(json_encode($oldValues,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)?:'—')?></pre>
                                        </div>
                                        <div>
                                            <b>After</b>
                                            <pre><?=e(json_encode($newValues,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)?:'—')?></pre>
                                        </div>
                                    </div>
                                </details>
                            <?php endif; ?>
                            <?php if (in_array($logModule, ['auth', 'security'], true) && !empty($l['ip_address'])) : ?>
                                <small class="activity-security-meta"><?=e($l['ip_address'])?> · <?=e(admin_device_label($l['user_agent']??''))?></small>
                            <?php endif; ?>
                        </td>
                        <td class="activity-user-cell">
                            <strong><?=e($l['admin_name']?:'System')?></strong>
                            <span><?=e(admin_role_label($l['role']?:'admin'))?></span>
                        </td>
                        <td><span class="activity-access"><?=e($accessLabel)?></span></td>
                        <td><span class="activity-module activity-module--<?=e(preg_replace('/[^a-z0-9-]/','',$logModule))?>"><?=e($moduleLabel)?></span></td>
                        <td class="activity-date-cell">
                            <strong><?=e(date('M j, Y',$created))?></strong>
                            <span><?=e(date('g:i A',$created))?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$logs) : ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state activity-empty-state">
                                <span class="activity-empty-icon" aria-hidden="true">≡</span><strong>No activity found</strong><span>Clear the filters or try a different search term.</span><a href="activity.php" class="btn btn--soft">Reset activity view</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?=render_pagination($pager)?>
</section>
</section>
<?php
admin_footer();
?>
