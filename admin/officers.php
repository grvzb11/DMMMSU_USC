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
if (!admin_can_permission('officers.manage')) deny_access('You do not have permission to manage officer history.');

// Officer History was introduced through migrations. Older/partially migrated databases
// must never crash this page: show a clear setup state instead of an HTTP 500.
$officerTableReady = db_table_exists($pdo, 'officers');
$academicYearTableReady = db_table_exists($pdo, 'academic_years');
$requiredOfficerColumns = ['id', 'academic_year_id', 'portal_code', 'full_name', 'position_title', 'office_name', 'email', 'start_date', 'end_date', 'sort_order', 'is_archived', 'created_by'];
$missingOfficerColumns = [];
if ($officerTableReady) {
    foreach ($requiredOfficerColumns as $column) {
        if (!db_column_exists($pdo, 'officers', $column)) $missingOfficerColumns[] = $column;
    }
}
if (!$officerTableReady || !$academicYearTableReady || $missingOfficerColumns) {
    app_log_error('Officer History schema is not ready', [
    'officers_table' => $officerTableReady,
    'academic_years_table' => $academicYearTableReady,
    'missing_columns' => $missingOfficerColumns,
    ]);
    admin_header('Leadership Management', 'Governance');
?>
<div class="page-intro">
    <div>
        <span class="page-kicker">OFFICER HISTORY</span>
        <h2>Finish the governance setup</h2>
        <p>The Officer History module needs the latest database migration before it can be used.</p>
    </div>
</div>
<section class="panel">
    <div class="panel__body">
        <div class="notice notice--warning">
            Your records are safe. No officer data has been deleted. Run the pending database migrations, then return to this page.
        </div>
        <div class="form-actions">
            <?php if (can_manage_migrations()) : ?>
                <a class="btn" href="maintenance.php">Open Maintenance</a>
            <?php endif; ?>
            <a class="btn btn--soft" href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</section>
<?php
admin_footer();
exit;
}

$scope = admin_scope_code();
$portals = governance_portals();
if ($scope) $portals = [$scope => $portals[$scope] ?? $scope];
$error = '';
$loadError = '';
try {
    $years = academic_year_rows($pdo, true);
    $activeYear = active_academic_year($pdo);
} catch (Throwable $e) {
    app_log_error('Officer History academic-year load failed', ['error' => $e->getMessage()]);
    $years = [];
    $activeYear = null;
    $loadError = 'Academic-year information could not be loaded. Run pending migrations and try again.';
}
$photoReady = db_column_exists($pdo, 'officers', 'photo_path');
// Keep the migration safety bucket in the database, but do not expose it as a normal
// academic-year choice in Officer management. Existing imported records remain intact.
$isLegacyAcademicYear = static function (array $year) : bool {
    return strcasecmp(trim((string)($year['label'] ?? '')), 'Legacy / Imported') === 0;
};
$legacyAcademicYearId = 0;
$visibleYears = [];
foreach ($years as $yearRow) {
    if ($isLegacyAcademicYear($yearRow)) {
        $legacyAcademicYearId = (int)($yearRow['id'] ?? 0);
        continue;
    }
    $visibleYears[] = $yearRow;
}
$visibleYearIds = [];
foreach ($visibleYears as $yearRow) $visibleYearIds[(int)$yearRow['id']] = true;
$defaultVisibleYearId = (int)($activeYear['id'] ?? 0);
if ($defaultVisibleYearId <= 0 || !isset($visibleYearIds[$defaultVisibleYearId])) {
    $defaultVisibleYearId = (int)($visibleYears[0]['id'] ?? 0);
}
$uscUniversityPositions = [
'Adviser',
'President',
'Vice President',
'Ethical Standards Officer',
'Executive Secretary',
];
$uscCabinetPositions = [
'Student Affairs and Services',
'Environmental Affairs',
'Student Information and Communications Technology',
'Linkages',
'Sports and Youth Development',
'Budget and Finance',
'Health and Wellness',
'Ways and Means',
'Gender and Development',
'Audit Commissioner',
];
$uscPositionValues = array_merge($uscUniversityPositions, $uscCabinetPositions);
$csboElectedPositions = [
'Adviser',
'Chairperson',
'Vice Chairperson',
'Secretary',
'Treasurer',
'Auditor',
'Press Relations Officer (P.R.O.)',
'Business Manager',
'Supreme Governor',
];
$csboCommitteeHeadPositions = [
'Student Affairs and Services',
'Student Information and Technology',
'Sports and Youth Development',
'Budget and Finance',
'Environmental Affairs',
'Ways and Means',
'Health and Wellness',
'Gender and Development',
'Linkages',
];
$csboPositionValues = array_merge($csboElectedPositions, $csboCommitteeHeadPositions);
$normalizeOfficerPosition = static function ($value) : string {
    $value = preg_replace('/\s+/', ' ', trim((string)$value));
    return strtolower((string)$value);
};
$uscPositionOrder = [];
foreach ($uscPositionValues as $index => $positionName) $uscPositionOrder[$normalizeOfficerPosition($positionName)] = $index;
$csboPositionOrder = [];
foreach ($csboPositionValues as $index => $positionName) $csboPositionOrder[$normalizeOfficerPosition($positionName)] = $index;
$officialPositionsForPortal = static function (string $portal) use($uscPositionValues, $csboPositionValues): array {
    return strtoupper($portal) === 'USC'?$uscPositionValues:$csboPositionValues;
};
$officialPositionOrderForPortal = static function (string $portal) use($uscPositionOrder, $csboPositionOrder): array {
    return strtoupper($portal) === 'USC'?$uscPositionOrder:$csboPositionOrder;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'save');
    $newPhoto = null;
    try {
        if (in_array($action, ['archive', 'restore'], true)) {
            $id = (int)($_POST['id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM officers WHERE id=?');
            $st->execute([$id]);
            $old = $st->fetch();
            if (!$old) throw new RuntimeException('Officer record not found.');
            if ($scope && strtoupper($old['portal_code']) !== $scope) deny_access();
            $arch = $action === 'archive'?1:0;
            $pdo->prepare('UPDATE officers SET is_archived=? WHERE id=?')->execute([$arch, $id]);
            admin_log($pdo, 'governance', $arch?'Archived officer record':'Restored officer record', $old['full_name'].' · '.$old['position_title'], 'officer', $id, ['is_archived' => (int)$old['is_archived']], ['is_archived' => $arch]);
            $returnPortal = $scope?:strtoupper((string)($old['portal_code'] ?? 'USC'));
            $returnYear = (int)($old['academic_year_id'] ?? 0);
            header('Location: officers.php?updated=1&portal='.rawurlencode($returnPortal).'&year='.$returnYear);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $year = (int)($_POST['academic_year_id'] ?? 0);
        $portal = $scope?:strtoupper(trim((string)($_POST['portal_code'] ?? 'USC')));
        if (!isset($portals[$portal])) throw new RuntimeException('Choose a valid portal.');
        $name = trim((string)($_POST['full_name'] ?? ''));
        $position = trim((string)($_POST['position_title'] ?? ''));
        $officeProvided = array_key_exists('office_name', $_POST);
        $emailProvided = array_key_exists('email', $_POST);
        $startProvided = array_key_exists('start_date', $_POST);
        $endProvided = array_key_exists('end_date', $_POST);
        $sortProvided = array_key_exists('sort_order', $_POST);
        $office = trim((string)($_POST['office_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $start = trim((string)($_POST['start_date'] ?? ''));
        $end = trim((string)($_POST['end_date'] ?? ''));
        $sort = (int)($_POST['sort_order'] ?? 0);
        if ($name === '' || $position === '') throw new RuntimeException('Officer name and position are required.');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');

        $old = null;
        if ($id) {
            $st = $pdo->prepare('SELECT * FROM officers WHERE id=?');
            $st->execute([$id]);
            $old = $st->fetch();
            if (!$old) throw new RuntimeException('Officer record not found.');
            if ($scope && strtoupper($old['portal_code']) !== $scope) deny_access();
            // Optional legacy metadata is intentionally no longer shown in the Officers UI.
            // Preserve any existing values when an officer is edited so no historical data is lost.
            if (!$officeProvided) $office = trim((string)($old['office_name'] ?? ''));
            if (!$emailProvided) $email = trim((string)($old['email'] ?? ''));
            if (!$startProvided) $start = trim((string)($old['start_date'] ?? ''));
            if (!$endProvided) $end = trim((string)($old['end_date'] ?? ''));
            if (!$sortProvided) $sort = (int)($old['sort_order'] ?? 0);
        }
        $officialPortalPositions = $officialPositionsForPortal($portal);
        if (!in_array($position, $officialPortalPositions, true)) {
            $legacyPosition = $old?trim((string)($old['position_title'] ?? '')):'';
            $label = $portal === 'USC'?'University Student Council':'Campus Student Body Organization';
            if (!$old || $position !== $legacyPosition) throw new RuntimeException('Choose an official '.$label.' position.');
        }

        $oldPhoto = $photoReady?trim((string)($old['photo_path'] ?? '')):'';
        $photoPath = $oldPhoto;
        $removePhoto = $photoReady && !empty($_POST['remove_photo']);
        if ($removePhoto) $photoPath = '';
        if ($photoReady && !empty($_FILES['photo']['name'])) {
            $rl = max(5, (int)(system_setting($pdo, 'security_rate_limit_uploads_per_hour', '40') ?? 40));
            platform_rate_limit_or_429($pdo, 'admin-upload', ((string)($_SESSION['admin_id'] ?? 0)).'|'.admin_current_ip(), $rl, 3600, 900);
            $newPhoto = officer_store_photo($_FILES['photo']);
            $photoPath = $newPhoto;
        }

        $basePayload = [$year?:null, $portal, $name, $position, $office?:null, $email?:null];
        if ($id) {
            if ($photoReady) {
                $pdo->prepare('UPDATE officers SET academic_year_id=?,portal_code=?,full_name=?,position_title=?,office_name=?,email=?,photo_path=?,start_date=?,end_date=?,sort_order=? WHERE id=?')
                ->execute([...$basePayload, $photoPath?:null, $start?:null, $end?:null, $sort, $id]);
            } else {
                $pdo->prepare('UPDATE officers SET academic_year_id=?,portal_code=?,full_name=?,position_title=?,office_name=?,email=?,start_date=?,end_date=?,sort_order=? WHERE id=?')
                ->execute([...$basePayload, $start?:null, $end?:null, $sort, $id]);
            }
            admin_log($pdo, 'governance', 'Updated officer record', $name.' · '.$position, 'officer', $id,
            ['full_name' => $old['full_name'], 'position_title' => $old['position_title'], 'portal_code' => $old['portal_code']],
            ['full_name' => $name, 'position_title' => $position, 'portal_code' => $portal, 'photo_updated' => $photoReady && $photoPath !== $oldPhoto]);
        } else {
            if ($photoReady) {
                $pdo->prepare('INSERT INTO officers(academic_year_id,portal_code,full_name,position_title,office_name,email,photo_path,start_date,end_date,sort_order,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([...$basePayload, $photoPath?:null, $start?:null, $end?:null, $sort, $_SESSION['admin_id'] ?? null]);
            } else {
                $pdo->prepare('INSERT INTO officers(academic_year_id,portal_code,full_name,position_title,office_name,email,start_date,end_date,sort_order,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)')
                ->execute([...$basePayload, $start?:null, $end?:null, $sort, $_SESSION['admin_id'] ?? null]);
            }
            $id = (int)$pdo->lastInsertId();
            admin_log($pdo, 'governance', 'Added officer record', $name.' · '.$position, 'officer', $id, null, ['portal_code' => $portal, 'academic_year_id' => $year, 'photo_added' => (bool)$photoPath]);
        }
        if ($photoReady && $oldPhoto !== '' && $oldPhoto !== $photoPath) officer_delete_photo($oldPhoto);
        header('Location: officers.php?saved=1&portal='.rawurlencode($portal).'&year='.$year);
        exit;
    } catch (Throwable $e) {
        if ($newPhoto) officer_delete_photo($newPhoto);
        $error = $e->getMessage();
    }
}

$filterPortal = $scope?:strtoupper(trim((string)($_GET['portal'] ?? 'USC')));
if (!isset($portals[$filterPortal])) $filterPortal = (string)array_key_first($portals);
$filterYear = (int)($_GET['year'] ?? $defaultVisibleYearId);
if ($filterYear>0 && !isset($visibleYearIds[$filterYear])) $filterYear = $defaultVisibleYearId;
$prefillPosition = trim((string)($_GET['position'] ?? ''));
$filterOfficialPositions = $officialPositionsForPortal($filterPortal);
if (!in_array($prefillPosition, $filterOfficialPositions, true)) $prefillPosition = '';
$showArchived = !empty($_GET['archived']);
$yearLabels = [];
foreach ($visibleYears as $yearRow) $yearLabels[(int)$yearRow['id']] = (string)$yearRow['label'];
$selectedYearLabel = $filterYear>0?($yearLabels[$filterYear] ?? 'Selected period'):'All academic years';
$rows = [];
$edit = null;
try {
    $where = ['o.portal_code=?'];
    $params = [$filterPortal];
    if ($filterYear) {
        $where[] = 'o.academic_year_id=?';
        $params[] = $filterYear;
    }
    if (!$showArchived) $where[] = 'o.is_archived=0';
    $st = $pdo->prepare('SELECT o.*,ay.label academic_year_label FROM officers o LEFT JOIN academic_years ay ON ay.id=o.academic_year_id WHERE '.implode(' AND ', $where).' ORDER BY o.is_archived,o.sort_order,o.position_title,o.full_name');
    $st->execute($params);
    $rows = $st->fetchAll();
    if (isset($_GET['edit'])) {
        $st = $pdo->prepare('SELECT * FROM officers WHERE id=?');
        $st->execute([(int)$_GET['edit']]);
        $edit = $st->fetch()?:null;
        if ($edit && $scope && strtoupper((string)$edit['portal_code']) !== $scope) $edit = null;
    }
} catch (Throwable $e) {
    app_log_error('Officer History roster load failed', ['error' => $e->getMessage(), 'portal' => $filterPortal, 'academic_year_id' => $filterYear]);
    $loadError = 'Officer records could not be loaded. Run pending migrations from Maintenance, then try again.';
    $rows = [];
    $edit = null;
}

// For every active roster, always show the complete official position structure.
// Existing records fill their matching slot; positions without a record remain visibly Vacant.
$displayRows = $rows;
$officialRosterMode = !$showArchived && $filterYear>0;
$filledOfficialPositions = 0;
$currentOfficialPositions = $officialPositionsForPortal($filterPortal);
$currentOfficialOrder = $officialPositionOrderForPortal($filterPortal);
if ($officialRosterMode) {
    $matched = [];
    $extras = [];
    foreach ($rows as $row) {
        $key = $normalizeOfficerPosition($row['position_title'] ?? '');
        if (isset($currentOfficialOrder[$key]) && !isset($matched[$key])) $matched[$key] = $row;
        else $extras[] = $row;
    }
    $displayRows = [];
    foreach ($currentOfficialPositions as $officialPosition) {
        $key = $normalizeOfficerPosition($officialPosition);
        if (isset($matched[$key])) {
            $displayRows[] = $matched[$key];
            $filledOfficialPositions++;
        } else {
            $displayRows[] = [
            '_vacant' => true,
            'id' => 0,
            'full_name' => 'Vacant',
            'position_title' => $officialPosition,
            'academic_year_label' => $selectedYearLabel,
            'start_date' => null,
            'end_date' => null,
            'is_archived' => 0,
            'office_name' => $filterPortal,
            'portal_code' => $filterPortal,
            'photo_path' => null,
            ];
        }
    }
    foreach ($extras as $row) $displayRows[] = $row;
}

admin_header('Leadership Management', 'Governance');
$officialTotal = count($currentOfficialPositions);
$vacantOfficialPositions = $officialRosterMode?max(0, $officialTotal-$filledOfficialPositions):0;
?>
<?php admin_leadership_tabs('officers.php'); ?>
<div class="page-intro officer-page-intro officer-page-topbar">
    <div>
        <span class="page-kicker">STUDENT LEADERSHIP</span>
        <h2>Manage Leadership Roster</h2>
        <p>
            <?php if ($scope) : ?>
                Manage <?=e($portals[$filterPortal]??$filterPortal)?> officers for the selected academic year.
            <?php else: ?>
                Manage USC and campus officers, positions, and academic-year terms.
            <?php endif; ?>
        </p>
    </div>
</div>
<?php if ($error) : ?>
    <div class="notice notice--error">
        <?=e($error)?>
    </div>
<?php endif; ?>
<?php if ($loadError) : ?>
    <div class="notice notice--warning">
        <?=e($loadError)?>
        <?php if (can_manage_migrations()) : ?>
            <a href="maintenance.php">Open Maintenance</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['saved']) || isset($_GET['updated'])) : ?>
    <div class="notice">
        Officer history updated.
    </div>
<?php endif; ?>
<?php if (!$photoReady) : ?>
    <div class="notice notice--warning">
        Run pending migrations to enable officer photos for the public “Meet the Officers” section.
    </div>
<?php endif; ?>
<div class="governance-two-column officer-history-workspace">
    <section class="panel officer-form-panel" id="officer-form">
        <div class="panel__head">
            <div>
                <span class="panel-kicker"><?=$edit?'UPDATE RECORD':'NEW RECORD'?></span>
                <h2><?=$edit?'Edit Officer':'Add Officer'?></h2>
                <p><?=$edit?'Update officer details for the selected term.':'Create a new officer record for the selected portal and term.'?></p>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="panel__body governance-form" data-unsaved-warning="1">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="id" value="<?=e((string)($edit['id']??''))?>">
            <div class="officer-form-grid officer-form-grid--compact">
                <?php
                $formAcademicYearId = (int)($edit['academic_year_id'] ?? $defaultVisibleYearId);
                ?>
                <?php if ($edit && $legacyAcademicYearId>0 && $formAcademicYearId === $legacyAcademicYearId) : ?>
                    <label>
                        Academic year
                        <input type="hidden" name="academic_year_id" value="<?=e((string)$legacyAcademicYearId)?>">
                        <span class="officer-readonly-value">Historical imported record</span>
                    </label>
                <?php else: ?>
                    <label>
                        Academic year
                        <select name="academic_year_id">
                            <option value="0">Unassigned</option>
                            <?php foreach ($visibleYears as $y) : ?>
                                <option value="<?=$y['id']?>" <?=$formAcademicYearId===(int)$y['id']?'selected':''?>><?=e($y['label'])?><?=$y['is_archived']?' · archived':''?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if (!$scope) : ?>
                    <label>
                        Portal
                        <select name="portal_code" id="officer-portal-select">
                            <?php foreach ($portals as $code => $label) : ?>
                                <option value="<?=e($code)?>" <?=($edit['portal_code']??$filterPortal)===$code?'selected':''?>><?=e($label)?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
            </div>
            <label>
                Full name
                <input name="full_name" required value="<?=e($edit['full_name']??'')?>" placeholder="Enter officer full name">
            </label>
            <?php $formPortal=strtoupper((string)($edit['portal_code']??$filterPortal));$currentPosition=trim((string)($edit['position_title']??$prefillPosition));?>
            <label>
                Position
                <select name="position_title" id="officer-position-select" required>
                    <option value="" <?=$currentPosition===''?'selected':''?> disabled>Choose position</option>
                    <?php if ($formPortal === 'USC') : ?>
                        <optgroup label="University Officers">
                        <?php foreach ($uscUniversityPositions as $positionOption) : ?>
                            <option value="<?=e($positionOption)?>" <?=$currentPosition===$positionOption?'selected':''?>><?=e($positionOption)?></option>
                        <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Cabinet Secretaries">
                        <?php foreach ($uscCabinetPositions as $positionOption) : ?>
                            <option value="<?=e($positionOption)?>" <?=$currentPosition===$positionOption?'selected':''?>><?=e($positionOption)?></option>
                        <?php endforeach; ?>
                        </optgroup>
                        <?php
                        $formOfficialPositions = $uscPositionValues;
                        ?>
                    <?php else: ?>
                        <optgroup label="CSBO Officers">
                        <?php foreach ($csboElectedPositions as $positionOption) : ?>
                            <option value="<?=e($positionOption)?>" <?=$currentPosition===$positionOption?'selected':''?>><?=e($positionOption)?></option>
                        <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Committee Heads">
                        <?php foreach ($csboCommitteeHeadPositions as $positionOption) : ?>
                            <option value="<?=e($positionOption)?>" <?=$currentPosition===$positionOption?'selected':''?>><?=e($positionOption)?></option>
                        <?php endforeach; ?>
                        </optgroup>
                        <?php
                        $formOfficialPositions = $csboPositionValues;
                        ?>
                    <?php endif; ?>
                    <?php if ($currentPosition !== '' && !in_array($currentPosition, $formOfficialPositions, true)) : ?>
                        <option value="<?=e($currentPosition)?>" selected><?=e($currentPosition)?> · legacy position</option>
                    <?php endif; ?>
                </select>
            </label>
            <?php if ($photoReady) : ?>
                <div class="officer-photo-field">
                    <div class="officer-photo-field__label">
                        <strong>Officer photo</strong><span>JPG, PNG or WebP · 4 MB max</span>
                    </div>
                    <div class="officer-file-picker">
                        <input class="officer-file-picker__input" id="officer-photo-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                        <label class="officer-file-picker__button" for="officer-photo-input">
                            Select photo
                        </label>
                        <span class="officer-file-picker__name" id="officer-photo-name">No file selected</span>
                    </div>
                </div>
                <?php if (!empty($edit['photo_path'])) : ?>
                    <div class="officer-current-photo">
                        <img src="../<?=e($edit['photo_path'])?>" alt="Current officer photo">
                        <div class="officer-current-photo__copy">
                            <strong>Current photo</strong>
                            <span>Select a new photo above to replace it.</span>
                        </div>
                        <label class="officer-remove-photo">
                            <input type="checkbox" name="remove_photo" value="1">
                            <span>Remove photo</span>
                        </label>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="form-actions officer-form-actions">
                <button class="btn"><?=$edit?'Save Changes':'Add Officer'?></button>
                <?php if ($edit) : ?>
                    <a class="btn btn--soft" href="officers.php?portal=<?=e($filterPortal)?>&year=<?=$filterYear?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <section class="panel officer-roster-panel">
        <div class="panel__head officer-roster-head">
            <div>
                <span class="panel-kicker">ACTIVE ROSTER</span>
                <h2>Officer roster</h2>
                <p><?=e($portals[$filterPortal]??$filterPortal)?> · <?=e($selectedYearLabel)?></p>
            </div>
            <div class="officer-roster-head__meta">
                <?php if ($scope) : ?>
                    <form method="get" class="officer-year-switcher">
                        <label>
                            <span>Roster year</span>
                            <select name="year" onchange="this.form.submit()">
                                <?php foreach ($visibleYears as $y) : ?>
                                    <option value="<?=$y['id']?>" <?=$filterYear===(int)$y['id']?'selected':''?>><?=e($y['label'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </form>
                <?php endif; ?>
                <?php if ($officialRosterMode) : ?>
                    <div class="officer-fill-status">
                        <strong><?=e((string)$filledOfficialPositions)?></strong><span>of <?=e((string)$officialTotal)?> filled</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!$scope) : ?>
            <form class="toolbar officer-roster-toolbar" method="get">
                <div class="officer-toolbar-field">
                    <label class="field-inline">
                        <span>Portal</span>
                        <select name="portal" onchange="this.form.submit()">
                            <?php foreach ($portals as $code => $label) : ?>
                                <option value="<?=e($code)?>" <?=$filterPortal===$code?'selected':''?>><?=e($label)?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="officer-toolbar-field">
                    <label class="field-inline">
                        <span>Academic year</span>
                        <select name="year" onchange="this.form.submit()">
                            <?php foreach ($visibleYears as $y) : ?>
                                <option value="<?=$y['id']?>" <?=$filterYear===(int)$y['id']?'selected':''?>><?=e($y['label'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </form>
        <?php endif; ?>
        <div class="table-wrap officer-roster-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Officer</th>
                        <th>Position</th>
                        <th>Term</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($displayRows as $r) : ?>
                        <?php $isVacant = !empty($r['_vacant']); ?>
                        <tr class="<?=$isVacant?'officer-roster-row--vacant':''?>">
                            <td>
                                <div class="officer-roster-person">
                                    <?php if (!$isVacant && $photoReady && !empty($r['photo_path'])) : ?>
                                        <img src="../<?=e($r['photo_path'])?>" alt="">
                                    <?php else: ?>
                                        <span><?=$isVacant?'—':e(strtoupper(substr(trim((string)$r['full_name']),0,1)))?></span>
                                    <?php endif; ?>
                                    <div class="cell-title">
                                        <strong><?=e($r['full_name'])?></strong><span><?=$isVacant?'Not yet assigned':e(portal_display_name((string)$r['portal_code']))?></span>
                                    </div>
                                </div>
                            </td>
                            <td><strong class="officer-position-title"><?=e($r['position_title'])?></strong></td>
                            <td><?=e($r['academic_year_label']?:'Unassigned')?>
                                <?php if (!$isVacant && ($r['start_date'] || $r['end_date'])) : ?>
                                    <small class="table-subtext"><?=e(($r['start_date']?date('M Y',strtotime($r['start_date'])):'—').' – '.($r['end_date']?date('M Y',strtotime($r['end_date'])):'Present'))?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <?php if ($isVacant) : ?>
                                        <a class="btn btn--soft btn--small" href="?portal=<?=e($filterPortal)?>&year=<?=$filterYear?>&position=<?=urlencode((string)$r['position_title'])?>#officer-form">Add officer</a>
                                    <?php else: ?>
                                        <a class="btn btn--soft btn--small" href="?edit=<?=$r['id']?>&portal=<?=e($filterPortal)?>&year=<?=$filterYear?>">Edit</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$displayRows) : ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    No officer records for this view.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
(function () {
    const input = document.getElementById('officer-photo-input');
    const name = document.getElementById('officer-photo-name');
    if (!input || !name) return;
    input.addEventListener('change', function () {
        name.textContent = this.files && this.files.length ? this.files[0].name : 'No file selected';
        name.title = name.textContent;
    });
})();
</script>
<?php if (!$scope) : ?>
    <script>
(function () {
    const portalSelect = document.getElementById('officer-portal-select');
    const positionSelect = document.getElementById('officer-position-select');
    if (!portalSelect || !positionSelect) return;

    const groups = {
        USC: [
            ['University Officers', <?=json_encode(array_values($uscUniversityPositions),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>],
            ['Cabinet Secretaries', <?=json_encode(array_values($uscCabinetPositions),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>]
        ],
        CSBO: [
            ['CSBO Officers', <?=json_encode(array_values($csboElectedPositions),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>],
            ['Committee Heads', <?=json_encode(array_values($csboCommitteeHeadPositions),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>]
        ]
    };

    function rebuildPositionOptions(portal) {
        const previous = positionSelect.value;
        const selectedGroups = String(portal || '').toUpperCase() === 'USC' ? groups.USC : groups.CSBO;
        const allowed = [];
        positionSelect.replaceChildren();

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Choose position';
        placeholder.disabled = true;
        placeholder.selected = true;
        positionSelect.appendChild(placeholder);

        selectedGroups.forEach(([label, positions]) => {
            const group = document.createElement('optgroup');
            group.label = label;
            positions.forEach(position => {
                allowed.push(position);
                const option = document.createElement('option');
                option.value = position;
                option.textContent = position;
                group.appendChild(option);
            });
            positionSelect.appendChild(group);
        });

        if (allowed.includes(previous)) positionSelect.value = previous;
        else positionSelect.value = '';

    }

    portalSelect.addEventListener('change', function () {
        rebuildPositionOptions(this.value);
    });
})();
</script>
<?php endif; ?>
<?php
admin_footer();
?>
