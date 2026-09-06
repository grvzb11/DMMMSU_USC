<?php
require_once '../config/database.php';
require_once '_layout.php';
$q = trim($_GET['q'] ?? '');
$scope = admin_scope_code();
$like = '%'.$q.'%';
$posts = $concerns = $media = $accounts = $announcements = $officers = $activities = $academicYears = [];
if ($q !== '') {
    $where = $scope?' AND category=?':'';
    $st = $pdo->prepare("SELECT id,title,category,status FROM posts WHERE deleted_at IS NULL AND (title LIKE ? OR excerpt LIKE ?)".$where." ORDER BY updated_at DESC LIMIT 8");
    $params = [$like, $like];
    if ($scope) $params[] = strtolower($scope);
    $st->execute($params);
    $posts = $st->fetchAll();
    $cw = $scope?' AND (UPPER(source_portal)=? OR UPPER(assigned_scope)=?)':'';
    $privacySearch = db_column_exists($pdo, 'concerns', 'privacy_level') && !admin_can_permission('concerns.sensitive') ? " AND COALESCE(privacy_level,'normal')='normal'" : '';
    $st = $pdo->prepare("SELECT id,reference_code,subject,CONCAT(source_portal,CASE WHEN assigned_scope<>source_portal THEN CONCAT(' → ',assigned_scope) ELSE '' END) campus,status FROM concerns WHERE (reference_code LIKE ? OR subject LIKE ? OR message LIKE ?)".$cw.$privacySearch." ORDER BY created_at DESC LIMIT 8");
    $params = [$like, $like, $like];
    if ($scope) array_push($params, $scope, $scope);
    $st->execute($params);
    $concerns = $st->fetchAll();
    if (can_manage_media()) {
        $mw = $scope?' AND UPPER(campus)=?':'';
        $st = $pdo->prepare("SELECT id,original_name,alt_text,campus,mime_type,file_path FROM media_library WHERE deleted_at IS NULL AND (original_name LIKE ? OR alt_text LIKE ?)".$mw." ORDER BY created_at DESC LIMIT 8");
        $params = [$like, $like];
        if ($scope) $params[] = $scope;
        $st->execute($params);
        $media = $st->fetchAll();
    }
    if (can_manage_accounts()) {
        $st = $pdo->prepare("SELECT id,full_name,username,role,campus,status FROM admins WHERE full_name LIKE ? OR username LIKE ? OR email LIKE ? LIMIT 8");
        $st->execute([$like, $like, $like]);
        $accounts = $st->fetchAll();
    }
    if (admin_can_permission('announcements.manage') && db_table_exists($pdo, 'announcements')) {
        $aw = $scope?" AND (portal_code=? OR portal_code='ALL')":'';
        $st = $pdo->prepare("SELECT id,title,message,portal_code,status,priority FROM announcements WHERE (title LIKE ? OR message LIKE ?)".$aw." ORDER BY updated_at DESC,id DESC LIMIT 8");
        $params = [$like, $like];
        if ($scope) $params[] = $scope;
        $st->execute($params);
        $announcements = $st->fetchAll();
    }
    if (admin_can_permission('officers.manage') && db_table_exists($pdo, 'officers')) {
        $ow = $scope?' AND portal_code=?':'';
        $st = $pdo->prepare("SELECT id,full_name,position_title,office_name,portal_code FROM officers WHERE is_archived=0 AND (full_name LIKE ? OR position_title LIKE ? OR office_name LIKE ?)".$ow." ORDER BY updated_at DESC,id DESC LIMIT 8");
        $params = [$like, $like, $like];
        if ($scope) $params[] = $scope;
        $st->execute($params);
        $officers = $st->fetchAll();
    }
    if (can_view_activity()) {
        $st = $pdo->prepare("SELECT id,action,description,module,created_at FROM admin_activity_logs WHERE action LIKE ? OR description LIKE ? OR module LIKE ? ORDER BY id DESC LIMIT 8");
        $st->execute([$like, $like, $like]);
        $activities = $st->fetchAll();
    }
    if (admin_can_permission('academic.manage') && db_table_exists($pdo, 'academic_years')) {
        $st = $pdo->prepare("SELECT id,label,start_date,end_date,status FROM academic_years WHERE label LIKE ? ORDER BY start_date DESC LIMIT 8");
        $st->execute([$like]);
        $academicYears = $st->fetchAll();
    }
}
admin_header('Search', 'Administration');
?>
<div class="page-intro">
    <div>
        <h2>Search administration</h2>
        <p>Find publications, concerns, announcements, governance records<?=can_manage_media()?', reusable media':''?><?=can_manage_accounts()?', and accounts':''?> from one permission-aware search.</p>
    </div>
</div>
<form class="search-hero" method="get">
    <input name="q" autofocus value="<?=e($q)?>" placeholder="Search titles, references, announcements, officers, media, accounts, or audit events">
    <button class="btn">Search</button>
</form>
<?php if ($q !== '') : ?>
    <div class="search-results-grid">
        <?php foreach ([['News & Updates', $posts, 'posts.php?edit=', 'title'], ['E-Sumbong', $concerns, 'concerns.php?id=', 'subject']] as [$title, $rows, $url, $field]) : ?>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2><?=e($title)?></h2>
                        <p><?=count($rows)?> result<?=count($rows)===1?'':'s'?></p>
                    </div>
                </div>
                <div class="panel__body search-result-list">
                    <?php foreach ($rows as $r) : ?>
                        <a href="<?=$url.$r['id']?>"><strong><?=e($r[$field])?></strong><span><?=e($r['status'])?><?=isset($r['campus'])?' · '.e($r['campus']):''?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$rows) : ?>
                        <div class="empty-state">
                            No matches.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <?php if (can_manage_media()) : ?>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Media Library</h2>
                        <p><?=count($media)?> result<?=count($media)===1?'':'s'?></p>
                    </div>
                </div>
                <div class="panel__body search-result-list">
                    <?php foreach ($media as $m) : ?>
                        <a href="media.php?portal=<?=e(strtoupper((string)$m['campus']))?>&q=<?=urlencode((string)$m['original_name'])?>"><strong><?=e($m['original_name']?:'Media asset')?></strong><span><?=e(portal_display_name($m['campus']))?> · <?=e(file_type_label($m['mime_type'],$m['original_name']))?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$media) : ?>
                        <div class="empty-state">
                            No matches.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if (admin_can_permission('announcements.manage')) : ?>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Announcements</h2>
                        <p><?=count($announcements)?> result<?=count($announcements)===1?'':'s'?></p>
                    </div>
                </div>
                <div class="panel__body search-result-list">
                    <?php foreach ($announcements as $a) : ?>
                        <a href="announcements.php?edit=<?=$a['id']?>"><strong><?=e($a['title'])?></strong><span><?=e(strtoupper((string)$a['portal_code']))?> · <?=e(ucfirst((string)$a['priority']))?> · <?=e($a['status'])?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$announcements) : ?>
                        <div class="empty-state">
                            No matches.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if (admin_can_permission('officers.manage')) : ?>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Officer History</h2>
                        <p><?=count($officers)?> result<?=count($officers)===1?'':'s'?></p>
                    </div>
                </div>
                <div class="panel__body search-result-list">
                    <?php foreach ($officers as $o) : ?>
                        <a href="officers.php?edit=<?=$o['id']?>"><strong><?=e($o['full_name'])?></strong><span><?=e($o['position_title'])?> · <?=e($o['portal_code'])?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$officers) : ?>
                        <div class="empty-state">
                            No matches.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if (admin_can_permission('academic.manage')) : ?>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Academic Years</h2>
                        <p><?=count($academicYears)?> result<?=count($academicYears)===1?'':'s'?></p>
                    </div>
                </div>
                <div class="panel__body search-result-list">
                    <?php foreach ($academicYears as $ay) : ?>
                        <a href="academic-years.php?edit=<?=$ay['id']?>"><strong><?=e($ay['label'])?></strong><span><?=e($ay['status'])?> · <?=e($ay['start_date'])?> – <?=e($ay['end_date'])?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$academicYears) : ?>
                        <div class="empty-state">
                            No matches.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if (can_view_activity()) : ?>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Audit Events</h2>
                        <p><?=count($activities)?> result<?=count($activities)===1?'':'s'?></p>
                    </div>
                </div>
                <div class="panel__body search-result-list">
                    <?php foreach ($activities as $ev) : ?>
                        <a href="activity.php?q=<?=urlencode((string)$q)?>"><strong><?=e($ev['action'])?></strong><span><?=e($ev['module'])?> · <?=e(date('M j, Y g:i A',strtotime($ev['created_at'])))?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$activities) : ?>
                        <div class="empty-state">
                            No matches.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if (can_manage_accounts()) : ?>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Accounts</h2>
                        <p><?=count($accounts)?> result<?=count($accounts)===1?'':'s'?></p>
                    </div>
                </div>
                <div class="panel__body search-result-list">
                    <?php foreach ($accounts as $a) : ?>
                        <a href="accounts.php?edit=<?=$a['id']?>"><strong><?=e($a['full_name'])?></strong><span><?=e(admin_role_label($a['role']))?><?=!empty($a['campus'])?' · '.e($a['campus']):''?></span></a>
                    <?php endforeach; ?>
                    <?php if (!$accounts) : ?>
                        <div class="empty-state">
                            No matches.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php
admin_footer();
?>
