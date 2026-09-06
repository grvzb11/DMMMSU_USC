<?php
require_once '../config/database.php';
require_once '_layout.php';
if (!can_delete_admin_records()) deny_access('Your role cannot access Trash management.');
ensure_admin_platform_tables($pdo);
$scope = admin_scope_code();
$flash = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'restore';
    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))), fn($v) => $v>0)));
    if (!$ids && $id>0) $ids = [$id];

    $restorePost = function (int $postId) use($pdo, &$flash): bool {
        $st = $pdo->prepare('SELECT title,category FROM posts WHERE id=? AND deleted_at IS NOT NULL');
        $st->execute([$postId]);
        $row = $st->fetch();
        if (!$row) return false;
        require_admin_code(strtoupper((string)$row['category']));
        $pdo->prepare('UPDATE posts SET deleted_at=NULL,deleted_by=NULL WHERE id=?')->execute([$postId]);
        admin_log($pdo, 'news', 'Restored publication', $row['title'], 'post', $postId);
        return true;
    };
    $purgePost = function (int $postId) use($pdo): bool {
        $st = $pdo->prepare('SELECT title,category FROM posts WHERE id=? AND deleted_at IS NOT NULL');
        $st->execute([$postId]);
        $row = $st->fetch();
        if (!$row) return false;
        require_admin_code(strtoupper((string)$row['category']));
        foreach (post_images($pdo, $postId) as $img) delete_post_image($pdo, (int)$img['id'], $postId);
        foreach (post_videos($pdo, $postId) as $video) delete_post_video($pdo, (int)$video['id'], $postId);
        $pdo->prepare('DELETE FROM posts WHERE id=?')->execute([$postId]);
        admin_log($pdo, 'news', 'Permanently deleted publication', $row['title'], 'post', $postId);
        return true;
    };
    $restoreMedia = function (array $mediaIds) use($pdo): int {
        $restored = 0;
        foreach ($mediaIds as $mediaId) {
            $m = media_asset($pdo, (int)$mediaId, true);
            if (!$m || empty($m['deleted_at']) || !admin_can_access_code($m['campus']?:'USC')) continue;
            $pdo->prepare('UPDATE media_library SET deleted_at=NULL,deleted_by=NULL WHERE id=?')->execute([(int)$m['id']]);
            $restored++;
        }
        return $restored;
    };
    $purgeMedia = function (array $mediaIds) use($pdo, &$flashError): array {
        $purged = 0;
        $blocked = 0;
        foreach ($mediaIds as $mediaId) {
            $m = media_asset($pdo, (int)$mediaId, true);
            if (!$m || empty($m['deleted_at']) || !admin_can_access_code($m['campus']?:'USC')) continue;
            $usage = media_usage_counts($pdo, (int)$m['id']);
            if ((int)$usage['total']>0) {
                $blocked++;
                continue;
            }
            if (purge_media_asset($pdo, (int)$m['id'])) $purged++;
        }
        return ['purged' => $purged, 'blocked' => $blocked];
    };

    if (in_array($action, ['bulk_restore', 'bulk_purge'], true)) {
        $selected = array_values(array_filter((array)($_POST['selected'] ?? []), fn($v) => is_string($v) && $v !== ''));
        if (!$selected) {
            $flashError = 'Select at least one Trash item first.';
        }
        else {
            $restored = 0;
            $purged = 0;
            $blocked = 0;
            foreach ($selected as $token) {
                [$selectedType, $rawIds] = array_pad(explode(':', $token, 2), 2, '');
                $selectedIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawIds)), fn($v) => $v>0)));
                if (!$selectedIds) continue;
                if ($selectedType === 'post') {
                    foreach ($selectedIds as $selectedId) {
                        if ($action === 'bulk_restore') {
                            $restored+=(int)$restorePost($selectedId);
                        }
                        elseif (can_permanently_delete_admin_records()) {
                            $purged+=(int)$purgePost($selectedId);
                        }
                    }
                } elseif ($selectedType === 'media') {
                    if ($action === 'bulk_restore') {
                        $restored+=$restoreMedia($selectedIds);
                    }
                    elseif (can_permanently_delete_admin_records()) {
                        $result = $purgeMedia($selectedIds);
                        $purged+=$result['purged'];
                        $blocked+=$result['blocked'];
                    }
                }
            }
            if ($action === 'bulk_restore') {
                $flash = $restored?$restored.' Trash item'.($restored === 1?'':'s').' recovered.':'No selected items were recovered.';
            } else {
                $flash = $purged?$purged.' Trash item'.($purged === 1?'':'s').' permanently deleted.':'';
                if ($blocked) {
                    $flashError = $blocked.' media item'.($blocked === 1?' is':'s are').' still in use and could not be permanently deleted.';
                }
                if (!$purged && !$blocked) $flashError = 'No selected items could be permanently deleted.';
            }
        }
    } elseif ($type === 'post') {
        if ($action === 'restore') {
            $ok = $restorePost($id);
            if ($ok) $flash = 'Publication recovered.';
        }
        elseif ($action === 'purge' && can_permanently_delete_admin_records()) {
            $ok = $purgePost($id);
            if ($ok) $flash = 'Publication permanently deleted.';
        }
    } elseif ($type === 'media') {
        if ($action === 'restore') {
            $restored = $restoreMedia($ids);
            if ($restored) {
                $flash = $restored>1?$restored.' grouped media copies recovered.':'Media asset recovered.';
            }
        } elseif ($action === 'purge' && can_permanently_delete_admin_records()) {
            $result = $purgeMedia($ids);
            if ($result['purged']) $flash = $result['purged']>1?$result['purged'].' grouped media copies permanently deleted.':'Media asset permanently deleted.';
            if ($result['blocked']) $flashError = $result['blocked'].' media item'.($result['blocked'] === 1?' is':'s are').' still in use and could not be permanently deleted.';
        }
    }
    $_SESSION['trash_flash'] = $flash;
    $_SESSION['trash_flash_error'] = $flashError;
    header('Location: trash.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$flash = (string)($_SESSION['trash_flash'] ?? '');
$flashError = (string)($_SESSION['trash_flash_error'] ?? '');
unset($_SESSION['trash_flash'], $_SESSION['trash_flash_error']);

$items = [];
$postWhere = ['p.deleted_at IS NOT NULL'];
$postParams = [];
if ($scope) {
    $postWhere[] = 'UPPER(p.category)=?';
    $postParams[] = $scope;
}
$st = $pdo->prepare('SELECT p.id,p.title AS item_name,UPPER(p.category) AS portal,p.deleted_at,p.deleted_by,a.full_name AS deleted_by_name FROM posts p LEFT JOIN admins a ON a.id=p.deleted_by WHERE '.implode(' AND ', $postWhere));
$st->execute($postParams);
foreach ($st->fetchAll() as $r) $items[] = $r+['type' => 'post', 'type_label' => 'Publication', 'detail' => 'News & Updates', 'usage' => null];

$mediaWhere = ['m.deleted_at IS NOT NULL'];
$mediaParams = [];
if ($scope) {
    if ($scope === 'USC') $mediaWhere[] = "(UPPER(m.campus)=? OR m.campus IS NULL OR m.campus='')";
    else $mediaWhere[] = 'UPPER(m.campus)=?';
    $mediaParams[] = $scope;
}
$st = $pdo->prepare('SELECT m.id,m.original_name AS item_name,UPPER(m.campus) AS portal,m.deleted_at,m.deleted_by,m.mime_type,m.file_path,m.file_size,m.sha256,a.full_name AS deleted_by_name FROM media_library m LEFT JOIN admins a ON a.id=m.deleted_by WHERE '.implode(' AND ', $mediaWhere));
$st->execute($mediaParams);
$mediaItems = [];
foreach ($st->fetchAll() as $r) {
    $usage = media_usage_counts($pdo, (int)$r['id']);
    $r = $r+['type' => 'media', 'type_label' => 'Media asset', 'detail' => str_starts_with((string)$r['mime_type'], 'image/')?'Image':'Document', 'usage' => $usage, 'ids' => [(int)$r['id']], 'copy_count' => 1];
    $hash = trim((string)($r['sha256'] ?? ''));
    $groupKey = $hash !== ''?'media|'.strtoupper((string)($r['portal']?:'USC')).'|'.$hash:'media-id|'.$r['id'];
    if (!isset($mediaItems[$groupKey])) {
        $mediaItems[$groupKey] = $r;
        continue;
    }
    $mediaItems[$groupKey]['ids'][] = (int)$r['id'];
    $mediaItems[$groupKey]['copy_count']++;
    $mediaItems[$groupKey]['usage']['total']+=(int)($usage['total'] ?? 0);
    if (strcmp((string)$r['deleted_at'], (string)$mediaItems[$groupKey]['deleted_at'])>0) {
        foreach (['item_name', 'deleted_at', 'deleted_by', 'deleted_by_name', 'file_path', 'file_size'] as $field) $mediaItems[$groupKey][$field] = $r[$field] ?? $mediaItems[$groupKey][$field];
    }
}
foreach ($mediaItems as $r) $items[] = $r;
foreach ($items as &$item) {
    if (!isset($item['ids'])) $item['ids'] = [(int)$item['id']];
    if (!isset($item['copy_count'])) $item['copy_count'] = 1;
}
unset($item);
usort($items, fn($a, $b) => strcmp((string)$b['deleted_at'], (string)$a['deleted_at']));
admin_header('Trash', 'Administration');
admin_system_management_tabs('trash.php');
?>
<?php if ($flash) : ?>
    <div class="notice">
        <?=e($flash)?>
    </div>
<?php endif ?>
<?php if ($flashError) : ?>
    <div class="notice notice--error">
        <?=e($flashError)?>
    </div>
<?php endif ?>
<?php if ($items) : ?>
    <form method="post" id="trashBulkForm" class="trash-bulk-form">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <section class="panel trash-workspace">
            <div class="trash-bulk-toolbar">
                <label class="trash-select-all">
                    <input type="checkbox" id="trashSelectAll">
                    <span>Select all</span>
                </label>
                <span class="trash-selected-count" id="trashSelectedCount">0 selected</span>
                <div class="trash-bulk-actions">
                    <button class="btn btn--soft btn--small" type="submit" name="action" value="bulk_restore" id="trashRecoverSelected" disabled>Recover</button>
                    <?php if (can_permanently_delete_admin_records()) : ?>
                        <button class="btn btn--danger btn--small" type="submit" name="action" value="bulk_purge" id="trashDeleteSelected" data-confirm="Permanently delete the selected Trash items? This cannot be undone." disabled>Delete</button>
                        <span class="trash-bulk-divider" aria-hidden="true"></span>
                    <?php endif; ?>
                    <button class="trash-bulk-link" type="button" data-trash-all="restore">Recover all</button>
                    <?php if (can_permanently_delete_admin_records()) : ?>
                        <button class="trash-bulk-link trash-bulk-link--danger" type="button" data-trash-all="purge">Delete all</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="trash-table-wrap">
                <table class="trash-table trash-table--selectable">
                    <thead>
                        <tr>
                            <th class="trash-select-col" aria-label="Select"></th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Portal</th>
                            <th>Deleted</th>
                            <th>Deleted by</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item) :$token = $item['type'].':'.implode(',', $item['ids']); ?>
                            <tr>
                                <td class="trash-select-cell">
                                    <input class="trash-row-check" type="checkbox" name="selected[]" value="<?=e($token)?>" aria-label="Select <?=e($item['item_name']?:'Trash item')?>">
                                </td>
                                <td class="trash-item-cell"><strong><?=e($item['item_name']?:'Untitled item')?></strong><span><?=e($item['detail'])?>
                                    <?php if (($item['copy_count'] ?? 1)>1) : ?>
                                        · <strong class="trash-copy-count"><?=$item['copy_count']?> copies grouped</strong>
                                    <?php endif; ?>
                                    <?php if ($item['type'] === 'media' && $item['usage']) : ?>
                                        · <?=$item['usage']['total']?> current use<?=$item['usage']['total']===1?'':'s'?>
                                    <?php endif ?>
                                    </span></td>
                                <td><span class="trash-type-badge"><?=e($item['type_label'])?></span></td>
                                <td><?=e(portal_display_name($item['portal']))?></td>
                                <td><?=e(date('M j, Y · g:i A',strtotime($item['deleted_at'])))?></td>
                                <td><?=e($item['deleted_by_name']?:'Unknown')?></td>
                                <td class="trash-actions">
                                    <button class="trash-row-action trash-row-action--recover" type="button" data-trash-row-action="restore" data-trash-token="<?=e($token)?>"><?=($item['copy_count']??1)>1?'Recover all copies':'Recover'?></button>
                                    <?php if (can_permanently_delete_admin_records()) : ?>
                                        <?php if ($item['type'] === 'media' && !empty($item['usage']['total'])) : ?>
                                            <span class="trash-in-use">In use</span>
                                        <?php else: ?>
                                            <button class="trash-row-action trash-row-action--delete" type="button" data-trash-row-action="purge" data-trash-token="<?=e($token)?>"><?=($item['copy_count']??1)>1?'Delete all copies':'Delete forever'?></button>
                                        <?php endif ?>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    </form>
<?php else: ?>
    <section class="panel trash-workspace">
        <div class="empty-state trash-empty">
            <strong>Trash is empty.</strong><span>Deleted publications and Media Library assets will appear here.</span>
        </div>
    </section>
<?php endif ?>
<p class="trash-policy-note">Permanent deletion is available only to the System Administrator. Media files that are still referenced by published content cannot be deleted forever.</p>
<script>
(() => {
    const form = document.getElementById('trashBulkForm');
    if (!form) return;
    const all = document.getElementById('trashSelectAll');
    const checks = [...form.querySelectorAll('.trash-row-check')];
    const count = document.getElementById('trashSelectedCount');
    const recover = document.getElementById('trashRecoverSelected');
    const del = document.getElementById('trashDeleteSelected');
    const refresh = () => {
        const n = checks.filter(c => c.checked).length;
        count.textContent = n + ' selected';
        if (recover) recover.disabled = n === 0;
        if (del) del.disabled = n === 0;
        if (all) { all.checked = n > 0 && n === checks.length; all.indeterminate = n > 0 && n < checks.length; }
    };
    all?.addEventListener('change', () => { checks.forEach(c => c.checked = all.checked); refresh(); });
    checks.forEach(c => c.addEventListener('change', refresh));
    form.querySelectorAll('[data-trash-all]').forEach(btn => btn.addEventListener('click', () => {
        const mode = btn.dataset.trashAll;
        if (mode === 'purge' && !confirm('Permanently delete every deletable item currently in Trash? Items still in use will be preserved. This cannot be undone.')) return;
        checks.forEach(c => c.checked = true); refresh();
        const submit = document.createElement('button'); submit.type = 'submit'; submit.name = 'action'; submit.value = mode === 'purge' ? 'bulk_purge' : 'bulk_restore'; submit.hidden = true; form.appendChild(submit); submit.click(); submit.remove();
    }));
    form.querySelectorAll('[data-trash-row-action]').forEach(btn => btn.addEventListener('click', () => {
        const token = btn.dataset.trashToken || ''; const mode = btn.dataset.trashRowAction;
        if (mode === 'purge' && !confirm('Permanently delete this Trash item? This cannot be undone.')) return;
        checks.forEach(c => c.checked = c.value === token); refresh();
        const submit = document.createElement('button'); submit.type = 'submit'; submit.name = 'action'; submit.value = mode === 'purge' ? 'bulk_purge' : 'bulk_restore'; submit.hidden = true; form.appendChild(submit); submit.click(); submit.remove();
    }));
    refresh();
})();
</script>
<?php
admin_footer();
?>
