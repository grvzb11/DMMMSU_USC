<?php
require_once '../config/database.php';
require_once '_layout.php';
require_once '../config/helpers.php';
require_admin();

if (!admin_can_permission('concerns.templates')) deny_access('Your role cannot manage E-Sumbong response templates.');

if (!db_table_exists($pdo, 'concern_response_templates')) {
    admin_header('Response Templates', 'E-Sumbong');
    echo '<div class="notice notice--error">The response-template migration is not installed yet. A System Administrator can apply it from <a href="maintenance.php">Maintenance Center</a>.</div>';
    admin_footer();
    exit;
}

$role = admin_role();
$canAddTemplate = ($role === 'admin');
$templateLimit = 10;
$isGlobalTemplateManager = in_array($role, ['admin', 'usc', 'sas_director'], true);
$campusScope = $isGlobalTemplateManager?null:admin_campus();
if (!$isGlobalTemplateManager && !$campusScope) deny_access('A campus assignment is required to manage response templates.');

$starterTemplates = [
[
'title' => 'Acknowledgement',
'concern_type' => null,
'response_text' => 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.',
'campus' => null,
],
[
'title' => 'Under review',
'concern_type' => null,
'response_text' => 'Your concern is currently under review. We are checking the details and coordinating the next appropriate action.',
'campus' => null,
],
[
'title' => 'Referred to responsible unit',
'concern_type' => null,
'response_text' => 'Your concern has been referred to the appropriate office or unit for further review and action.',
'campus' => null,
],
[
'title' => 'Need more details',
'concern_type' => null,
'response_text' => 'We need a few more details to proceed with this concern. Please check the latest update and provide the requested information if available.',
'campus' => null,
],
[
'title' => 'Follow-up sent',
'concern_type' => null,
'response_text' => 'A follow-up has been sent to the responsible office or unit. We will update your tracking page once a response or action is recorded.',
'campus' => null,
],
[
'title' => 'Awaiting responsible unit',
'concern_type' => null,
'response_text' => 'Your concern is awaiting feedback or action from the responsible office or unit. We are continuing to monitor its progress.',
'campus' => null,
],
[
'title' => 'Scheduled for action',
'concern_type' => null,
'response_text' => 'Your concern has been scheduled for action. Please continue checking your tracking page for the next recorded update.',
'campus' => null,
],
[
'title' => 'Action completed',
'concern_type' => null,
'response_text' => 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.',
'campus' => null,
],
[
'title' => 'Resolved',
'concern_type' => null,
'response_text' => 'Your concern has been resolved based on the action taken by the responsible office or unit. Please review the latest public update for details.',
'campus' => null,
],
[
'title' => 'Case closed',
'concern_type' => null,
'response_text' => 'This concern has been closed. Thank you for using E-Sumbong. If you still need help, you may submit a new concern with updated details.',
'campus' => null,
],
];

$selectedPortal = $isGlobalTemplateManager?admin_selected_portal():$campusScope;
$campuses = ['USC' => 'University Student Council']+admin_campuses();
$error = '';

// Maintain the built-in starter library and exact-duplicate cleanup.
try {
    $rows = $pdo->query("SELECT id,title,COALESCE(campus,'') AS campus,COALESCE(concern_type,'') AS concern_type,COALESCE(response_text,'') AS response_text FROM concern_response_templates WHERE is_active=1 ORDER BY id ASC")->fetchAll();
    $seen = [];
    $duplicateIds = [];
    foreach ($rows as $r) {
        $key = mb_strtolower(trim((string)$r['title'])).'|'.strtoupper(trim((string)$r['campus'])).'|'.mb_strtolower(trim((string)$r['concern_type'])).'|'.trim((string)$r['response_text']);
        if (isset($seen[$key])) $duplicateIds[] = (int)$r['id'];
        else $seen[$key] = (int)$r['id'];
    }
    if ($duplicateIds) {
        $placeholders = implode(',', array_fill(0, count($duplicateIds), '?'));
        $pdo->prepare("DELETE FROM concern_response_templates WHERE id IN ($placeholders)")->execute($duplicateIds);
    }

    $existingKeys = [];
    $rows = $pdo->query("SELECT title,COALESCE(campus,'') AS campus FROM concern_response_templates")->fetchAll();
    foreach ($rows as $r) {
        $existingKeys[mb_strtolower(trim((string)$r['title'])).'|'.strtoupper(trim((string)$r['campus']))] = true;
    }

    $activeTemplateCount = (int)$pdo->query('SELECT COUNT(*) FROM concern_response_templates WHERE is_active=1')->fetchColumn();
    $insertStarter = $pdo->prepare('INSERT INTO concern_response_templates(title,response_text,concern_type,campus,is_active,created_by) VALUES(?,?,?,?,1,?)');
    foreach ($starterTemplates as $tpl) {
        if ($activeTemplateCount >= $templateLimit) break;
        $key = mb_strtolower(trim((string)$tpl['title'])).'|'.strtoupper(trim((string)($tpl['campus'] ?? '')));
        if (isset($existingKeys[$key])) continue;
        $insertStarter->execute([$tpl['title'], $tpl['response_text'], $tpl['concern_type'], $tpl['campus'], null]);
        $existingKeys[$key] = true;
        $activeTemplateCount++;
    }
} catch (Throwable $e) {
    // Keep the page usable even if starter-template maintenance cannot run.
}

try {
    $activeTemplateCount = (int)$pdo->query('SELECT COUNT(*) FROM concern_response_templates WHERE is_active=1')->fetchColumn();
} catch (Throwable $e) {
    $activeTemplateCount = 0;
}

$templateCampus = function (array $row) : string {
    return strtoupper(trim((string)($row['campus'] ?? '')));
};
$canManageTemplate = function (array $row) use ($isGlobalTemplateManager, $campusScope, $templateCampus): bool {
    if ($isGlobalTemplateManager) return true;
    $rowCampus = $templateCampus($row);
    return $rowCampus !== '' && $rowCampus === $campusScope;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'save');
    $id = (int)($_POST['id'] ?? 0);

    if ($action !== 'deactivate' && $id === 0 && !$canAddTemplate) {
        deny_access('Only the System Administrator can create response templates.');
    }
    if ($action !== 'deactivate' && $id === 0 && $canAddTemplate && $activeTemplateCount >= $templateLimit) {
        $error = 'The response template library is limited to '.$templateLimit.' active templates. Disable one before creating another.';
    }

    if ($action === 'deactivate') {
        $st = $pdo->prepare('SELECT * FROM concern_response_templates WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) not_found('Template not found.');
        if (!$canManageTemplate($row)) deny_access('Global templates are read-only for campus accounts. You can only deactivate templates for your assigned campus.');

        $pdo->prepare('UPDATE concern_response_templates SET is_active=0 WHERE id=?')->execute([$id]);
        admin_log($pdo, 'concerns', 'Deactivated response template', (string)$row['title'], 'response_template', $id);
        header('Location: response-templates.php?disabled=1');
        exit;
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $text = trim((string)($_POST['response_text'] ?? ''));
    $type = trim((string)($_POST['concern_type'] ?? ''));

    if ($isGlobalTemplateManager) {
        $campus = strtoupper(trim((string)($_POST['campus'] ?? '')));
        if ($campus !== '' && !isset($campuses[$campus])) $campus = '';
    } else {
        $campus = (string)$campusScope;
    }

    if (!$error && ($title === '' || $text === '')) {
        $error = 'Template title and response text are required.';
    }
    if (!$error) {
        if ($id) {
            $st = $pdo->prepare('SELECT * FROM concern_response_templates WHERE id=? LIMIT 1');
            $st->execute([$id]);
            $old = $st->fetch();
            if (!$old) not_found('Template not found.');
            if (!$canManageTemplate($old)) deny_access('Global templates are read-only for campus accounts. You can only edit templates for your assigned campus.');

            $pdo->prepare('UPDATE concern_response_templates SET title=?,response_text=?,concern_type=?,campus=?,is_active=1 WHERE id=?')
            ->execute([$title, $text, $type?:null, $campus?:null, $id]);
            admin_log($pdo, 'concerns', 'Updated response template', $title, 'response_template', $id);
        } else {
            $pdo->prepare('INSERT INTO concern_response_templates(title,response_text,concern_type,campus,is_active,created_by) VALUES(?,?,?,?,1,?)')
            ->execute([$title, $text, $type?:null, $campus?:null, $_SESSION['admin_id'] ?? null]);
            $id = (int)$pdo->lastInsertId();
            admin_log($pdo, 'concerns', 'Created response template', $title, 'response_template', $id);
        }
        header('Location: response-templates.php?saved=1');
        exit;
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM concern_response_templates WHERE id=? LIMIT 1');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch()?:null;
    if ($edit && !$canManageTemplate($edit)) deny_access('Global templates are read-only for campus accounts. You can only edit templates for your assigned campus.');
}

$where = ['t.is_active=1'];
$params = [];
if (!$isGlobalTemplateManager) {
    $where[] = '(t.campus IS NULL OR UPPER(t.campus)=?)';
    $params[] = $campusScope;
} elseif ($selectedPortal) {
    $where[] = '(t.campus IS NULL OR UPPER(t.campus)=?)';
    $params[] = $selectedPortal;
}

$st = $pdo->prepare("SELECT t.*,a.full_name creator_name FROM concern_response_templates t LEFT JOIN admins a ON a.id=t.created_by WHERE ".implode(' AND ', $where)." ORDER BY CASE WHEN t.campus IS NULL THEN 0 ELSE 1 END, CASE t.title WHEN 'Acknowledgement' THEN 1 WHEN 'Under review' THEN 2 WHEN 'Referred to responsible unit' THEN 3 WHEN 'Need more details' THEN 4 WHEN 'Follow-up sent' THEN 5 WHEN 'Awaiting responsible unit' THEN 6 WHEN 'Scheduled for action' THEN 7 WHEN 'Action completed' THEN 8 WHEN 'Resolved' THEN 9 WHEN 'Case closed' THEN 10 ELSE 99 END, t.title");
$st->execute($params);
$templates = $st->fetchAll();

$defaultCampus = '';
if ($edit) {
    $defaultCampus = strtoupper(trim((string)($edit['campus'] ?? '')));
} elseif ($isGlobalTemplateManager && $selectedPortal) {
    $defaultCampus = $selectedPortal;
} elseif (!$isGlobalTemplateManager) {
    $defaultCampus = (string)$campusScope;
}

$globalCount = 0;
$localCount = 0;
$readonlyCount = 0;
foreach ($templates as $tplSummary) {
    $rowCampus = $templateCampus($tplSummary);
    if ($rowCampus === '') $globalCount++;
    else $localCount++;
    if (!$canManageTemplate($tplSummary)) $readonlyCount++;
}

admin_header('Response Templates', 'E-Sumbong');
?>
<?php if (isset($_GET['saved'])) : ?>
    <div class="notice">
        Response template saved.
    </div>
<?php endif; ?>
<?php if (isset($_GET['disabled'])) : ?>
    <div class="notice">
        Template disabled. Existing case history was not changed.
    </div>
<?php endif; ?>
<?php if ($error) : ?>
    <div class="notice notice--error">
        <?=e($error)?>
    </div>
<?php endif; ?>
<div class="response-template-preferred-layout <?=$canAddTemplate||$edit?'has-editor':'library-only'?>">
    <?php if ($canAddTemplate || $edit) : ?>
        <section class="panel response-template-preferred-editor">
            <div class="panel__head">
                <div>
                    <span class="panel-kicker"><?=$edit?'UPDATE TEMPLATE':'NEW TEMPLATE'?></span>
                    <h2><?=$edit?'Edit template':'New template'?></h2>
                    <p><?= $edit ? 'Update the wording or scope of this response.' : 'The library is limited to '.e((string)$templateLimit).' active templates.' ?></p>
                </div>
            </div>
            <?php if (!$edit && $activeTemplateCount >= $templateLimit) : ?>
                <div class="panel__body response-template-limit-state">
                    <div class="response-template-limit-icon">
                        10
                    </div>
                    <strong>Template limit reached</strong>
                    <p>You already have <?=e((string)$templateLimit)?> active response templates. Disable one from the library before adding a replacement.</p>
                </div>
            <?php else: ?>
                <form class="panel__body response-template-preferred-form" method="post">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="id" value="<?=intval($edit['id']??0)?>">
                    <label>
                        Template title
                        <input name="title" required maxlength="150" value="<?=e($edit['title']??'')?>" placeholder="e.g. Referred to responsible unit">
                    </label>
                    <label>
                        Concern type <span class="field-optional">Optional</span>
                        <select name="concern_type">
                            <option value="">Any type</option>
                            <?php foreach (['Concern', 'Complaint', 'Suggestion', 'Feedback'] as $type) : ?>
                                <option value="<?=e($type)?>" <?=$edit&&$edit['concern_type']===$type?'selected':''?>><?=e($type)?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php if ($isGlobalTemplateManager) : ?>
                        <label>
                            Portal scope
                            <select name="campus">
                                <option value="" <?=$defaultCampus===''?'selected':''?>>Available to all portals</option>
                                <?php foreach ($campuses as $code => $name) : ?>
                                    <option value="<?=e($code)?>" <?=$defaultCampus===$code?'selected':''?>><?=e($code)?> — <?=e($name)?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php else: ?>
                        <div class="response-template-preferred-scope">
                            <span>Portal scope</span>
                            <strong><?=e($campusScope.' — '.portal_display_name($campusScope))?></strong>
                            <small>Campus accounts can manage only their own portal templates.</small>
                        </div>
                    <?php endif; ?>
                    <label>
                        Student-visible response
                        <textarea name="response_text" required rows="7" maxlength="1200" placeholder="Write a clear progress update…"><?=e($edit['response_text']??'')?></textarea>
                    </label>
                    <div class="response-template-preferred-actions">
                        <?php if ($edit) : ?>
                            <a class="btn btn--soft" href="response-templates.php">Cancel</a>
                        <?php else: ?>
                            <button type="reset" class="btn btn--soft">Clear</button>
                        <?php endif; ?>
                        <button class="btn"><?=$edit?'Save changes':'Save template'?></button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <section class="panel response-template-preferred-library">
        <div class="panel__head response-template-preferred-library-head">
            <div>
                <span class="panel-kicker">TEMPLATE LIBRARY</span>
                <h2><?=count($templates)?> active template<?=count($templates)===1?'':'s'?></h2>
                <p>
                    <?php if (!$canAddTemplate && !$isGlobalTemplateManager) : ?>
                        Templates are created by the System Administrator. Global templates remain read-only for campus accounts.
                    <?php elseif (!$canAddTemplate) : ?>
                        Templates are created by the System Administrator. You can review and maintain templates already available to your scope.
                    <?php elseif ($selectedPortal) : ?>
                        Global and <?=e($selectedPortal)?> templates are shown for the selected portal. Maximum <?=e((string)$templateLimit)?> active templates.
                    <?php else: ?>
                        Showing templates available across all portals. Maximum <?=e((string)$templateLimit)?> active templates.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="response-template-preferred-list">
            <?php foreach($templates as $t):
                    $rowCampus=$templateCampus($t);
                    $isGlobal=$rowCampus==='';
                    $manageable=$canManageTemplate($t);
                  ?>
            <article class="response-template-preferred-item">
                <div class="response-template-preferred-copy">
                    <div class="response-template-preferred-title">
                        <strong><?=e($t['title'])?></strong>
                        <?php if ($isGlobal) : ?>
                            <span class="response-template-badge is-global">Global</span>
                        <?php else: ?>
                            <span class="response-template-badge"><?=e($rowCampus)?></span>
                        <?php endif; ?>
                        <?php if (!$manageable) : ?>
                            <span class="response-template-badge is-readonly">Read only</span>
                        <?php endif; ?>
                    </div>
                    <span class="response-template-preferred-meta"><?= $isGlobal ? 'All portals' : e($rowCampus.' — '.portal_display_name($rowCampus)) ?><?=!empty($t['concern_type'])?' · '.e($t['concern_type']):''?></span>
                    <p><?=e($t['response_text'])?></p>
                </div>
                <div class="response-template-preferred-item-actions">
                    <?php if ($manageable) : ?>
                        <a class="btn btn--soft btn--small" href="?edit=<?=$t['id']?>">Edit</a>
                        <form method="post" data-confirm="Deactivate this response template? Existing concern history will remain unchanged.">
                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="id" value="<?=$t['id']?>">
                            <button class="btn btn--danger btn--small">Deactivate</button>
                        </form>
                    <?php else: ?>
                        <span class="response-template-use-note">Available in case review</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$templates) : ?>
            <div class="empty-state">
                No active templates are available in this scope.
            </div>
        <?php endif; ?>
    </div>
</section>
</div>
<?php
admin_footer();
?>
