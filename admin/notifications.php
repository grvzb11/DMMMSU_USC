<?php
require_once '../config/database.php';
require_once '_layout.php';
ensure_admin_platform_tables($pdo);

$params = [];
$where = admin_notification_where($params);
$view = ($_GET['view'] ?? 'all') === 'unread'?'unread':'all';
$category = trim((string)($_GET['category'] ?? 'all'));
$categories = admin_notification_categories();
if ($category !== 'all' && !isset($categories[$category])) $category = 'all';
$returnParts = [];
if ($view === 'unread') $returnParts['view'] = 'unread';
if ($category !== 'all') $returnParts['category'] = $category;
$returnView = $returnParts?'?'.http_build_query($returnParts):'';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'read' && $id) {
        $st = $pdo->prepare("UPDATE admin_notifications SET is_read=1 WHERE id=? AND $where");
        $st->execute(array_merge([$id], $params));
    }
    elseif ($action === 'read_all') {
        $st = $pdo->prepare("UPDATE admin_notifications SET is_read=1 WHERE $where");
        $st->execute($params);
    }
    elseif ($action === 'dismiss' && $id && db_column_exists($pdo, 'admin_notifications', 'dismissed_at')) {
        $st = $pdo->prepare("UPDATE admin_notifications SET is_read=1,dismissed_at=NOW() WHERE id=? AND $where");
        $st->execute(array_merge([$id], $params));
    }
    elseif ($action === 'dismiss_read' && db_column_exists($pdo, 'admin_notifications', 'dismissed_at')) {
        $st = $pdo->prepare("UPDATE admin_notifications SET dismissed_at=NOW() WHERE is_read=1 AND $where");
        $st->execute($params);
    }
    header('Location: notifications.php'.$returnView);
    exit;
}

if (isset($_GET['open'])) {
    $id = (int)$_GET['open'];
    $st = $pdo->prepare("SELECT id,link FROM admin_notifications WHERE id=? AND $where LIMIT 1");
    $st->execute(array_merge([$id], $params));
    $notification = $st->fetch();
    if ($notification) {
        $st = $pdo->prepare("UPDATE admin_notifications SET is_read=1 WHERE id=? AND $where");
        $st->execute(array_merge([$id], $params));
        $link = trim((string)($notification['link'] ?? ''));
        if ($link !== '' && !preg_match('~^(?:https?:)?//~i', $link) && !str_contains($link, "\r") && !str_contains($link, "\n")) {
            header('Location: '.$link);
            exit;
        }
    }
    header('Location: notifications.php'.$returnView);
    exit;
}

$categoryWhere = $category !== 'all'?' AND category=?':'';
$categoryParams = $category !== 'all'?[$category]:[];
$countSt = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(is_read=0),0) AS unread FROM admin_notifications WHERE $where$categoryWhere");
$countSt->execute(array_merge($params, $categoryParams));
$counts = $countSt->fetch()?:['total' => 0, 'unread' => 0];
$total = (int)$counts['total'];
$unread = (int)$counts['unread'];

$listSql = "SELECT * FROM admin_notifications WHERE $where$categoryWhere";
$listParams = array_merge($params, $categoryParams);
if ($view === 'unread') $listSql.=" AND is_read=0";
$listSql.=" ORDER BY is_read ASC,created_at DESC LIMIT 100";
$st = $pdo->prepare($listSql);
$st->execute($listParams);
$items = $st->fetchAll();

function notification_day_label(string $date): string {
    $ts = strtotime($date);
    if (date('Y-m-d', $ts) === date('Y-m-d')) return 'Today';
    if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
    return date('F j, Y', $ts);
}
function notification_kind_label(string $kind): string {
    return match(strtolower($kind)) {
        'success' => 'Completed',
        'warning' => 'Attention',
        'danger' => 'Important',
        default => 'Update'
    };
}

admin_header('Notifications', 'Administration');
?>
<div class="notifications-page">
    <section class="panel notifications-panel">
        <div class="notifications-toolbar notifications-toolbar--compact">
            <nav class="notifications-tabs" aria-label="Notification filters">
                <a class="notifications-tab <?=$view==='all'?'is-active':''?>" href="notifications.php<?=$category!=='all'?'?category='.urlencode($category):''?>">All <span><?=$total?></span></a>
                <a class="notifications-tab <?=$view==='unread'?'is-active':''?>" href="?view=unread<?=$category!=='all'?'&amp;category='.urlencode($category):''?>">Unread <span><?=$unread?></span></a>
            </nav>
            <div class="notifications-toolbar__actions notifications-toolbar__actions--compact">
                <form class="notification-category-filter" method="get">
                    <input type="hidden" name="view" value="<?=e($view)?>">
                    <select name="category" onchange="this.form.submit()">
                        <option value="all">All categories</option>
                        <?php foreach ($categories as $key => $label) : ?>
                            <option value="<?=e($key)?>" <?=$category===$key?'selected':''?>><?=e($label)?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if ($unread>0) : ?>
                    <form method="post" class="notification-inline-form">
                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                        <input type="hidden" name="action" value="read_all">
                        <button class="notifications-read-all" type="submit">Mark all as read</button>
                    </form>
                <?php endif; ?>
                <?php if ($total>$unread && db_column_exists($pdo, 'admin_notifications', 'dismissed_at')) : ?>
                    <form method="post" class="notification-inline-form" data-confirm="Hide all notifications you have already read?">
                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                        <input type="hidden" name="action" value="dismiss_read">
                        <button class="notifications-read-all" type="submit">Clear read</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="notification-list notification-list--refined">
            <?php $lastDay=null; foreach($items as $n):
                    $day=notification_day_label($n['created_at']);
                    if($day!==$lastDay): $lastDay=$day; ?>
            <div class="notification-day-label">
                <span><?=$day?></span>
            </div>
        <?php endif; ?>
        <article class="notification-row notification-row--refined <?=$n['is_read']?'is-read':'is-unread'?> <?=$n['link']?'is-clickable':''?>"
        <?php if ($n['link']) : ?>
            data-open-url="?open=<?=$n['id']?>&view=<?=e($view)?>" role="link" tabindex="0" aria-label="Open notification: <?=e($n['title'])?>"
        <?php endif; ?>
        >
        <div class="notification-kind notification-kind--<?=e($n['kind'])?>" aria-hidden="true">
            <span></span>
        </div>
        <div class="notification-copy">
            <div class="notification-title-line">
                <strong><?=e($n['title'])?></strong>
                <?php if (!$n['is_read']) : ?>
                    <span class="notification-unread-badge">Unread</span>
                <?php endif; ?>
            </div>
            <p><?=e($n['message'])?></p>
            <div class="notification-meta">
                <span><?=e(admin_notification_categories()[$n['category']??'general']??notification_kind_label((string)$n['kind']))?></span>
                <i></i>
                <time datetime="<?=e(date('c',strtotime($n['created_at'])))?>"><?=e(date('g:i A',strtotime($n['created_at'])))?></time>
            </div>
        </div>
        <?php if (!$n['is_read']) : ?>
            <div class="notification-actions notification-actions--refined">
                <form method="post" class="notification-inline-form">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="action" value="read">
                    <input type="hidden" name="id" value="<?=$n['id']?>">
                    <button class="notification-mark-read" type="submit">Mark as read</button>
                </form>
            </div>
        <?php endif; ?>
        <?php if (db_column_exists($pdo, 'admin_notifications', 'dismissed_at')) : ?>
            <form method="post" class="notification-dismiss-form">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="dismiss">
                <input type="hidden" name="id" value="<?=$n['id']?>">
                <button class="notification-dismiss-button" type="submit" aria-label="Dismiss notification">×</button>
            </form>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?php if (!$items) : ?>
    <div class="notifications-empty">
        <div class="notifications-empty__icon" aria-hidden="true">
            ✓
        </div>
        <strong><?=$view==='unread'?'You are all caught up':'No notifications yet'?></strong>
        <p><?=$view==='unread'?'There are no unread notifications right now.':'New administrative updates will appear here when they are available.'?></p>
        <?php if ($view === 'unread' && $total>0) : ?>
            <a href="notifications.php" class="btn btn--soft btn--small">View all notifications</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>
</section>
</div>
<script>
(() => {
    document.querySelectorAll('.notification-row--refined[data-open-url]').forEach((row) => {
        const open = () => { window.location.href = row.dataset.openUrl; };
        row.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, select, textarea, label')) return;
            open();
        });
        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            if (event.target.closest('a, button, input, select, textarea')) return;
            event.preventDefault();
            open();
        });
    });
})();
</script>
<?php
admin_footer();
?>
