<?php
require_once '../config/database.php';
require_once '_layout.php';
if (!admin_can_permission('academic.manage')) deny_access('You do not have permission to manage academic years.');
if (!db_table_exists($pdo, 'academic_years')) {
    admin_header('Leadership Management', 'Governance');
    admin_leadership_tabs('academic-years.php');
    echo '<div class="notice notice--warning">Run pending migrations before using Academic Year Management.</div>';
    admin_footer();
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'save');
    try {
        if ($action === 'set_active') {
            $id = (int)($_POST['id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM academic_years WHERE id=? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch();
            if (!$row) throw new RuntimeException('Academic year not found.');
            $pdo->beginTransaction();
            $pdo->exec('UPDATE academic_years SET is_active=0');
            $pdo->prepare('UPDATE academic_years SET is_active=1,is_archived=0 WHERE id=?')->execute([$id]);
            save_system_setting($pdo, 'active_academic_year_id', (string)$id);
            $pdo->commit();
            admin_log($pdo, 'governance', 'Changed active academic year', (string)$row['label'], 'academic_year', $id, null, ['is_active' => 1]);
            header('Location: academic-years.php?active=1');
            exit;
        }
        if (in_array($action, ['archive', 'restore'], true)) {
            $id = (int)($_POST['id'] ?? 0);
            $archived = $action === 'archive'?1:0;
            $st = $pdo->prepare('SELECT * FROM academic_years WHERE id=? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch();
            if (!$row) throw new RuntimeException('Academic year not found.');
            if (!empty($row['is_active']) && $archived) throw new RuntimeException('Set another academic year as active before archiving this one.');
            $pdo->prepare('UPDATE academic_years SET is_archived=? WHERE id=?')->execute([$archived, $id]);
            admin_log($pdo, 'governance', $archived?'Archived academic year':'Restored academic year', (string)$row['label'], 'academic_year', $id, ['is_archived' => (int)$row['is_archived']], ['is_archived' => $archived]);
            header('Location: academic-years.php?updated=1');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $label = trim((string)($_POST['label'] ?? ''));
        $start = trim((string)($_POST['start_date'] ?? ''));
        $end = trim((string)($_POST['end_date'] ?? ''));
        if ($label === '' || $start === '' || $end === '') throw new RuntimeException('Label, start date, and end date are required.');
        if (strtotime($end)<strtotime($start)) throw new RuntimeException('End date must be after the start date.');
        if ($id) {
            $oldSt = $pdo->prepare('SELECT * FROM academic_years WHERE id=?');
            $oldSt->execute([$id]);
            $old = $oldSt->fetch();
            if (!$old) throw new RuntimeException('Academic year not found.');
            $pdo->prepare('UPDATE academic_years SET label=?,start_date=?,end_date=? WHERE id=?')->execute([$label, $start, $end, $id]);
            admin_log($pdo, 'governance', 'Updated academic year', $label, 'academic_year', $id, ['label' => $old['label'], 'start_date' => $old['start_date'], 'end_date' => $old['end_date']], ['label' => $label, 'start_date' => $start, 'end_date' => $end]);
        }
        else {
            $pdo->prepare('INSERT INTO academic_years(label,start_date,end_date,created_by) VALUES(?,?,?,?)')->execute([$label, $start, $end, $_SESSION['admin_id'] ?? null]);
            $id = (int)$pdo->lastInsertId();
            admin_log($pdo, 'governance', 'Created academic year', $label, 'academic_year', $id, null, ['label' => $label, 'start_date' => $start, 'end_date' => $end]);
        }
        header('Location: academic-years.php?saved=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM academic_years WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch()?:null;
}
$rows = academic_year_rows($pdo, true);
$active = active_academic_year($pdo);
$rows = array_values(array_filter($rows, static function ($row) : bool {
    $label = strtolower(trim((string)($row['label'] ?? '')));
    return $label !== 'legacy / imported' && $label !== 'legacy/imported' && $label !== 'legacy';
}));
admin_header('Leadership Management', 'Governance');
?>
<?php admin_leadership_tabs('academic-years.php'); ?>
<div class="page-intro leadership-academic-intro">
    <div>
        <span class="page-kicker">LEADERSHIP TERMS</span>
        <h2>Manage Academic Years</h2>
        <p>Create and maintain the academic-year terms used by USC and campus leadership rosters.</p>
    </div>
</div>
<?php if ($error) : ?>
    <div class="notice notice--error">
        <?=e($error)?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['saved']) || isset($_GET['updated']) || isset($_GET['active'])) : ?>
    <div class="notice">
        Academic year settings updated.
    </div>
<?php endif; ?>
<div class="governance-two-column academic-years-workspace">
    <section class="panel academic-years-form-panel">
        <div class="panel__head">
            <div>
                <span class="panel-kicker"><?=$edit?'UPDATE YEAR':'NEW PERIOD'?></span>
                <h2><?=$edit?'Edit academic year':'Add academic year'?></h2>
                <p>Use a clear label like 2027-2028 and set the official start and end dates for the term.</p>
            </div>
        </div>
        <form method="post" class="panel__body governance-form" data-unsaved-warning="1">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="id" value="<?=e((string)($edit['id']??''))?>">
            <label>
                Academic year label
                <input name="label" required value="<?=e($edit['label']??'')?>" placeholder="2027-2028">
            </label>
            <div class="grid2">
                <label>
                    Start date
                    <input type="date" name="start_date" required value="<?=e($edit['start_date']??'')?>">
                </label>
                <label>
                    End date
                    <input type="date" name="end_date" required value="<?=e($edit['end_date']??'')?>">
                </label>
            </div>
            <div class="academic-years-form-tip">
                <strong>Tip:</strong> Set only one active academic year at a time so new officer and governance records are saved under the correct term.
            </div>
            <div class="form-actions academic-years-form-actions">
                <button class="btn"><?=$edit?'Save changes':'Add academic year'?></button>
                <?php if ($edit) : ?>
                    <a class="btn btn--soft" href="academic-years.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <section class="panel academic-years-history-panel">
        <div class="panel__head academic-years-history-head">
            <div>
                <span class="panel-kicker">YEAR RECORDS</span>
                <h2>Academic year history</h2>
                <p>Choose the active term for new records. Previous academic years remain available automatically.</p>
            </div>
        </div>
        <div class="table-wrap academic-years-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Academic year</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr class="<?=!empty($row['is_active'])?'academic-year-row--active':''?>">
                            <td>
                                <div class="cell-title">
                                    <strong><?=e($row['label'])?></strong><span><?=!empty($row['is_active'])?'Current academic year':(!empty($row['is_archived'])?'Archived academic year':'Available academic year')?></span>
                                </div>
                            </td>
                            <td><strong class="academic-year-date"><?=e(date('M j, Y',strtotime($row['start_date'])))?> – <?=e(date('M j, Y',strtotime($row['end_date'])))?></strong></td>
                            <td><?=!empty($row['is_active'])?'<span class="status-badge status-badge--active">Active</span>':(!empty($row['is_archived'])?'<span class="status-badge">Archived</span>':'<span class="status-badge">Inactive</span>')?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn--soft btn--small" href="?edit=<?=$row['id']?>">Edit</a>
                                    <?php if (empty($row['is_active']) && !$row['is_archived']) : ?>
                                        <form method="post">
                                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="id" value="<?=$row['id']?>">
                                            <button class="btn btn--small">Set active</button>
                                        </form>
                                        <form method="post" data-confirm="Archive this academic year? Existing records will remain preserved.">
                                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                            <input type="hidden" name="action" value="archive">
                                            <input type="hidden" name="id" value="<?=$row['id']?>">
                                            <button class="btn btn--soft btn--small">Archive</button>
                                        </form>
                                    <?php elseif (!empty($row['is_archived'])) : ?>
                                        <form method="post" data-confirm="Restore this academic year to the available year list?">
                                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="id" value="<?=$row['id']?>">
                                            <button class="btn btn--soft btn--small">Restore</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php
admin_footer();
?>
