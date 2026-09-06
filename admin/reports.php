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
if (!can_view_reports()) deny_access('You do not have permission to view system reports.');
$scope = admin_scope_code();
$workflowReady = db_column_exists($pdo, 'concerns', 'due_at');

$preset = (string)($_GET['period'] ?? '6m');
$today = new DateTimeImmutable('today');
$start = null;
$end = $today;
$periodLabel = 'Last 6 months';
if ($preset === 'all') {
    $start = null;
    $periodLabel = 'All time';
}
elseif ($preset === 'year') {
    $start = $today->modify('first day of January');
    $periodLabel = $today->format('Y').' year to date';
}
elseif ($preset === 'semester') {
    $month = (int)$today->format('n');
    $start = $month <= 6?new DateTimeImmutable($today->format('Y').'-01-01'):new DateTimeImmutable($today->format('Y').'-07-01');
    $periodLabel = ($month <= 6?'First':'Second').' semester '.$today->format('Y');
} elseif ($preset === 'custom') {
    $rawStart = (string)($_GET['from'] ?? '');
    $rawEnd = (string)($_GET['to'] ?? '');
    try {
        $start = $rawStart !== ''?new DateTimeImmutable($rawStart):null;
    } catch (Throwable $e) {
        $start = null;
    }
    try {
        $end = $rawEnd !== ''?new DateTimeImmutable($rawEnd):$today;
    } catch (Throwable $e) {
        $end = $today;
    }
    if ($start && $end<$start) {
        [$start, $end] = [$end, $start];
    }
    $periodLabel = ($start?$start->format('M j, Y'):'Beginning').' – '.$end->format('M j, Y');
} else {
    $preset = '6m';
    $start = $today->modify('first day of -5 months');
    $periodLabel = 'Last 6 months';
}
$academicYears = array_values(array_filter(academic_year_rows($pdo, true), fn($ay) => stripos((string)($ay['label'] ?? ''), 'legacy') === false && stripos((string)($ay['label'] ?? ''), 'imported') === false));
$academicYearId = max(0, (int)($_GET['academic_year'] ?? 0));
$selectedAcademicYear = null;
$portalFilter = strtoupper(trim((string)($_GET['portal'] ?? '')));
if ($scope) $portalFilter = $scope;
$statusFilter = trim((string)($_GET['case_status'] ?? ''));
$allowedCaseStatuses = ['Submitted', 'Received', 'Under Review', 'Referred', 'In Progress', 'Action Taken', 'Resolved', 'Closed'];
if (!in_array($statusFilter, $allowedCaseStatuses, true)) $statusFilter = '';
$priorityFilter = trim((string)($_GET['priority'] ?? ''));
$allowedPriorities = ['Urgent', 'High', 'Normal', 'Low'];
if (!in_array($priorityFilter, $allowedPriorities, true)) $priorityFilter = '';
$typeFilter = trim((string)($_GET['concern_type'] ?? ''));
if (strlen($typeFilter)>80) $typeFilter = substr($typeFilter, 0, 80);
$officeOptions = governance_portals();
$officeFilter = strtoupper(trim((string)($_GET['office'] ?? '')));
if ($officeFilter !== '' && !isset($officeOptions[$officeFilter])) $officeFilter = '';
$typeOptions = $pdo->query("SELECT DISTINCT concern_type FROM concerns WHERE concern_type<>'' ORDER BY concern_type")->fetchAll(PDO::FETCH_COLUMN);
if ($academicYearId) {
    foreach ($academicYears as $ay) {
        if ((int)$ay['id'] === $academicYearId) {
            $selectedAcademicYear = $ay;
            break;
        }
    }
    if ($selectedAcademicYear) {
        $start = new DateTimeImmutable((string)$selectedAcademicYear['start_date']);
        $end = new DateTimeImmutable((string)$selectedAcademicYear['end_date']);
        if ($end>$today) $end = $today;
        $periodLabel = 'Academic Year '.(string)$selectedAcademicYear['label'];
    }
}
$endExclusive = $end->modify('+1 day')->format('Y-m-d');

function report_date_clause(?DateTimeImmutable $start, string $endExclusive, string $field, array &$params): string {
    $parts = [];
    if ($start) {
        $parts[] = "$field>=?";
        $params[] = $start->format('Y-m-d');
    }
    $parts[] = "$field<?";
    $params[] = $endExclusive;
    return implode(' AND ', $parts);
}
function report_scalar(PDO $pdo, string $sql, array $params = []): int|float {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return is_numeric($v)?(float)$v:0;
}

$portalMap = ['USC' => 'University Student Council']+admin_campuses();
if ($scope) $portalMap = isset($portalMap[$scope])?[$scope => $portalMap[$scope]]:[];
elseif ($portalFilter !== '' && isset($portalMap[$portalFilter])) $portalMap = [$portalFilter => $portalMap[$portalFilter]];
$portalRows = [];
foreach ($portalMap as $code => $portalName) {
    $cp = [$code];
    $date = report_date_clause($start, $endExclusive, 'created_at', $cp);
    $caseExtra = '';
    if ($statusFilter !== '') {
        $caseExtra.=' AND status=?';
        $cp[] = $statusFilter;
    }
    if ($priorityFilter !== '') {
        $caseExtra.=' AND priority=?';
        $cp[] = $priorityFilter;
    }
    if ($typeFilter !== '') {
        $caseExtra.=' AND concern_type=?';
        $cp[] = $typeFilter;
    }
    if ($officeFilter !== '') {
        $caseExtra.=' AND UPPER(assigned_scope)=?';
        $cp[] = $officeFilter;
    }
    $total = (int)report_scalar($pdo, "SELECT COUNT(*) FROM concerns WHERE UPPER(source_portal)=? AND $date$caseExtra", $cp);
    $rp = [$code];
    $rdate = report_date_clause($start, $endExclusive, 'created_at', $rp);
    $resolvedExtra = '';
    if ($statusFilter !== '') {
        $resolvedExtra.=' AND status=?';
        $rp[] = $statusFilter;
    }
    if ($priorityFilter !== '') {
        $resolvedExtra.=' AND priority=?';
        $rp[] = $priorityFilter;
    }
    if ($typeFilter !== '') {
        $resolvedExtra.=' AND concern_type=?';
        $rp[] = $typeFilter;
    }
    if ($officeFilter !== '') {
        $resolvedExtra.=' AND UPPER(assigned_scope)=?';
        $rp[] = $officeFilter;
    }
    $resolved = (int)report_scalar($pdo, "SELECT COUNT(*) FROM concerns WHERE UPPER(source_portal)=? AND $rdate$resolvedExtra AND status IN ('Resolved','Closed')", $rp);
    $op = [$code];
    $odate = report_date_clause($start, $endExclusive, 'created_at', $op);
    $overdueExtra = '';
    if ($statusFilter !== '') {
        $overdueExtra.=' AND status=?';
        $op[] = $statusFilter;
    }
    if ($priorityFilter !== '') {
        $overdueExtra.=' AND priority=?';
        $op[] = $priorityFilter;
    }
    if ($typeFilter !== '') {
        $overdueExtra.=' AND concern_type=?';
        $op[] = $typeFilter;
    }
    if ($officeFilter !== '') {
        $overdueExtra.=' AND UPPER(assigned_scope)=?';
        $op[] = $officeFilter;
    }
    $overdue = $workflowReady?(int)report_scalar($pdo, "SELECT COUNT(*) FROM concerns WHERE UPPER(source_portal)=? AND $odate$overdueExtra AND status NOT IN ('Resolved','Closed') AND due_at IS NOT NULL AND due_at<NOW()", $op):0;
    $pp = [strtolower($code)];
    $pdate = report_date_clause($start, $endExclusive, 'published_at', $pp);
    $posts = (int)report_scalar($pdo, "SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND category=? AND $pdate", $pp);
    $portalRows[] = ['portal' => $code, 'portal_name' => $portalName, 'concerns' => $total, 'resolved' => $resolved, 'open' => max(0, $total-$resolved), 'overdue' => $overdue, 'posts' => $posts];
}
$totals = ['concerns' => 0, 'open' => 0, 'resolved' => 0, 'overdue' => 0, 'posts' => 0];
foreach ($portalRows as $row) foreach ($totals as $key => $unused) $totals[$key]+=$row[$key];
$totals['rate'] = $totals['concerns']?(int)round(($totals['resolved']/$totals['concerns'])*100):0;

$base = [];
$baseParams = [];
if ($portalFilter !== '') {
    $base[] = 'UPPER(source_portal)=?';
    $baseParams[] = $portalFilter;
}
$base[] = report_date_clause($start, $endExclusive, 'created_at', $baseParams);
if ($statusFilter !== '') {
    $base[] = 'status=?';
    $baseParams[] = $statusFilter;
}
if ($priorityFilter !== '') {
    $base[] = 'priority=?';
    $baseParams[] = $priorityFilter;
}
if ($typeFilter !== '') {
    $base[] = 'concern_type=?';
    $baseParams[] = $typeFilter;
}
if ($officeFilter !== '') {
    $base[] = 'UPPER(assigned_scope)=?';
    $baseParams[] = $officeFilter;
}
$baseWhere = implode(' AND ', $base);
$st = $pdo->prepare("SELECT SUM(status IN ('Submitted','Received')) new_count,SUM(status IN ('Under Review','Referred','In Progress','Action Taken')) active_count,SUM(status IN ('Resolved','Closed')) resolved_count,SUM(status NOT IN ('Resolved','Closed') AND assigned_to IS NULL) unassigned_count".($workflowReady?",SUM(status NOT IN ('Resolved','Closed') AND due_at IS NOT NULL AND due_at<NOW()) overdue_count":",0 overdue_count")." FROM concerns WHERE $baseWhere");
$st->execute($baseParams);
$ss = $st->fetch()?:[];
$statusSummary = ['new' => (int)($ss['new_count'] ?? 0), 'active' => (int)($ss['active_count'] ?? 0), 'resolved' => (int)($ss['resolved_count'] ?? 0), 'unassigned' => (int)($ss['unassigned_count'] ?? 0), 'overdue' => (int)($ss['overdue_count'] ?? 0)];
$st = $pdo->prepare("SELECT concern_type,COUNT(*) total,SUM(status IN ('Resolved','Closed')) resolved FROM concerns WHERE $baseWhere GROUP BY concern_type ORDER BY total DESC,concern_type LIMIT 12");
$st->execute($baseParams);
$typeRows = $st->fetchAll();
$st = $pdo->prepare("SELECT priority,COUNT(*) total FROM concerns WHERE $baseWhere GROUP BY priority ORDER BY FIELD(priority,'Urgent','High','Normal','Low')");
$st->execute($baseParams);
$priorityRows = $st->fetchAll();
$st = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR,created_at,resolved_at)) avg_hours,AVG(TIMESTAMPDIFF(HOUR,created_at,first_response_at)) first_hours FROM concerns WHERE $baseWhere AND resolved_at IS NOT NULL");
$st->execute($baseParams);
$timing = $st->fetch()?:[];
$avgResolution = $timing['avg_hours'] !== null?round((float)$timing['avg_hours'], 1):null;
$avgFirstResponse = $timing['first_hours'] !== null?round((float)$timing['first_hours'], 1):null;

// Trend follows the selected period, capped to 12 monthly buckets for readability.
$trendStart = $start?:$today->modify('first day of -11 months');
if ($trendStart<$today->modify('first day of -11 months')) $trendStart = $today->modify('first day of -11 months');
$trendParams = [];
$trendParts = [];
if ($portalFilter !== '') {
    $trendParts[] = 'UPPER(source_portal)=?';
    $trendParams[] = $portalFilter;
}
$trendParts[] = report_date_clause($trendStart, $endExclusive, 'created_at', $trendParams);
if ($statusFilter !== '') {
    $trendParts[] = 'status=?';
    $trendParams[] = $statusFilter;
}
if ($priorityFilter !== '') {
    $trendParts[] = 'priority=?';
    $trendParams[] = $priorityFilter;
}
if ($typeFilter !== '') {
    $trendParts[] = 'concern_type=?';
    $trendParams[] = $typeFilter;
}
if ($officeFilter !== '') {
    $trendParts[] = 'UPPER(assigned_scope)=?';
    $trendParams[] = $officeFilter;
}
$st = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') ym,COUNT(*) total,SUM(status IN ('Resolved','Closed')) resolved FROM concerns WHERE ".implode(' AND ', $trendParts)." GROUP BY ym ORDER BY ym");
$st->execute($trendParams);
$raw = $st->fetchAll();
$idx = [];
foreach ($raw as $r) $idx[$r['ym']] = $r;
$monthly = [];
$cursor = $trendStart->modify('first day of this month');
$guard = 0;
while ($cursor <= $end && $guard++<12) {
    $ym = $cursor->format('Y-m');
    $r = $idx[$ym] ?? ['total' => 0, 'resolved' => 0];
    $monthly[] = ['ym' => $ym, 'total' => (int)$r['total'], 'resolved' => (int)$r['resolved']];
    $cursor = $cursor->modify('+1 month');
}

if (isset($_GET['export'])) {
    if (!can_export_reports()) deny_access('Your role cannot export report data.');
    $export = (string)$_GET['export'];
    if ($export === 'summary') {
        admin_log($pdo, 'reports', 'Exported report summary', 'Operational report exported for '.$periodLabel);
        header('Content-Type:text/csv; charset=UTF-8');
        header('Content-Disposition:attachment; filename="portal-report-summary-'.date('Ymd').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Period', $periodLabel]);
        fputcsv($out, []);
        fputcsv($out, ['Portal', 'Concerns', 'Open', 'Reminder Due', 'Resolved', 'Resolution Rate', 'Publications']);
        foreach ($portalRows as $r) {
            $rate = $r['concerns']?round($r['resolved']/$r['concerns']*100):0;
            fputcsv($out, [$r['portal_name'], $r['concerns'], $r['open'], $r['overdue'], $r['resolved'], $rate.'%', $r['posts']]);
        }
        fclose($out);
        exit;
    }
    if ($export === 'summary_excel') {
        admin_log($pdo, 'reports', 'Exported Excel-compatible report', 'Operational report exported for '.$periodLabel);
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="portal-report-'.date('Ymd').'.xls"');
        echo '<html><head><meta charset="UTF-8"></head><body><table border="1"><tr><th colspan="7">DMMMSU University Student Council Operational Report</th></tr><tr><td>Period</td><td colspan="6">'.e($periodLabel).'</td></tr><tr><th>Portal</th><th>Concerns</th><th>Open</th><th>Reminder Due</th><th>Resolved</th><th>Resolution Rate</th><th>Publications</th></tr>';
        foreach ($portalRows as $r) {
            $rate = $r['concerns']?round($r['resolved']/$r['concerns']*100):0;
            echo '<tr><td>'.e($r['portal_name']).'</td><td>'.$r['concerns'].'</td><td>'.$r['open'].'</td><td>'.$r['overdue'].'</td><td>'.$r['resolved'].'</td><td>'.$rate.'%</td><td>'.$r['posts'].'</td></tr>';
        }
        echo '</table></body></html>';
        exit;
    }
    if ($export === 'cases') {
        $includePrivate = !empty($_GET['private']);
        if ($includePrivate && !can_export_private_concerns()) deny_access('Your role cannot export student identity information.');
        $st = $pdo->prepare("SELECT * FROM concerns WHERE $baseWhere ORDER BY created_at DESC LIMIT 10000");
        $st->execute($baseParams);
        $rows = $st->fetchAll();
        if ($includePrivate) {
            foreach ($rows as $r) concern_log_privacy_access($pdo, (int)$r['id'], 'Exported identity', 'Authorized E-Sumbong case export');
            admin_log($pdo, 'privacy', 'Exported concern identities', 'Authorized case export · '.count($rows).' records');
        }
        else admin_log($pdo, 'reports', 'Exported privacy-safe concern data', 'Identity fields masked · '.count($rows).' records');
        header('Content-Type:text/csv; charset=UTF-8');
        header('Content-Disposition:attachment; filename="esumbong-cases-'.($includePrivate?'authorized':'privacy-safe').'-'.date('Ymd').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Reference', 'Portal', 'Campus', 'Concern Type', 'Student Name', 'Student ID', 'Subject', 'Status', 'Priority', 'Assigned Scope', 'Review Reminder', 'Created', 'Resolved']);
        foreach ($rows as $r) {
            if (!empty($r['anonymous'])) {
                $name = 'Anonymous';
                $sid = 'Not stored';
            }
            elseif ($includePrivate) {
                $identity = concern_private_identity($pdo, $r);
                $name = $identity['name'] !== ''?$identity['name']:'Protected';
                $sid = $identity['student_id'] !== ''?$identity['student_id']:'Protected';
            }
            else {
                // Never decrypt private identity for a privacy-safe export. Legacy plaintext values are masked; encrypted-only records remain protected.
                $name = !empty($r['student_name'])?concern_mask_name((string)$r['student_name']):'Protected';
                $sid = !empty($r['student_id'])?concern_mask_student_id((string)$r['student_id']):'Protected';
            }
            fputcsv($out, [$r['reference_code'], portal_display_name((string)$r['source_portal']), student_campus_display((string)$r['campus']), $r['concern_type'], $name, $sid, $r['subject'], $r['status'], $r['priority'], $r['assigned_scope'], $r['due_at'] ?? '', $r['created_at'], $r['resolved_at'] ?? '']);
        }
        fclose($out);
        exit;
    }
}

admin_header('Reports', 'Analytics');
$maxTrend = max(1, ...array_map(fn($r) => $r['total'], $monthly?:[['total' => 0]]));
$maxPriority = max(1, ...array_map(fn($r) => (int)$r['total'], $priorityRows?:[['total' => 0]]));
$casePortalRows = array_values(array_filter($portalRows, fn($r) => ((int)$r['concerns']+(int)$r['open']+(int)$r['resolved']+(int)$r['overdue'])>0));
$quietPortalRows = array_values(array_filter($portalRows, fn($r) => ((int)$r['concerns']+(int)$r['open']+(int)$r['resolved']+(int)$r['overdue']) === 0));
$activeMonthly = array_values(array_filter($monthly, fn($r) => (int)$r['total']>0 || (int)$r['resolved']>0));
$healthyStatusNote = (!$statusSummary['active'] && !$statusSummary['unassigned'] && !$statusSummary['overdue'])?'No active, unassigned, or reminder-due cases.':'';
$advancedOpen = $preset === 'custom' || $statusFilter !== '' || $priorityFilter !== '' || $typeFilter !== '' || $officeFilter !== '';
$queryBase = $_GET;
unset($queryBase['export'], $queryBase['private']);
$query = http_build_query($queryBase);
$exportPrefix = '?'.($query !== ''?$query.'&':'');
$scopeNames = ['USC' => 'University Student Council']+admin_campuses();
$reportScopeLabel = $portalFilter !== ''?($scopeNames[$portalFilter] ?? $portalFilter):'All portals';
?>
<div class="report-context-line">
    <span><?=e($periodLabel)?></span><i aria-hidden="true"></i><span><?=e($reportScopeLabel)?></span>
</div>
<section class="report-kpis" aria-label="Report summary">
    <article class="report-kpi-card report-kpi-card--concerns">
        <span>Concerns</span><strong><?=$totals['concerns']?></strong><small><?=$totals['open']?> open · <?=$totals['overdue']?> reminder due</small>
    </article>
    <article class="report-kpi-card report-kpi-card--resolved">
        <span>Resolved</span><strong><?=$totals['resolved']?></strong><small><?=$totals['rate']?>% resolution rate</small>
    </article>
    <article class="report-kpi-card report-kpi-card--timing">
        <span>Avg. resolution</span><strong><?=$avgResolution===null?'—':e($avgResolution.'h')?></strong><small>First response: <?=$avgFirstResponse===null?'—':e($avgFirstResponse.'h')?></small>
    </article>
    <article class="report-kpi-card report-kpi-card--content">
        <span>Publications</span><strong><?=$totals['posts']?></strong><small>Published records in period</small>
    </article>
</section>
<form method="get" class="panel report-filter-bar report-filter-bar--compact">
    <div class="report-filter-bar__head">
        <div>
            <strong>Reporting period</strong><span>Choose the reporting scope, then refine only when needed.</span>
        </div>
        <details class="report-export-menu">
            <summary class="btn btn--soft">Export</summary>
            <div class="report-export-menu__items">
                <button type="button" onclick="window.print()">Print / Save PDF</button>
                <?php if (can_export_reports()) : ?>
                    <a href="<?=e($exportPrefix)?>export=summary">Summary CSV</a><a href="<?=e($exportPrefix)?>export=summary_excel">Excel</a><a href="<?=e($exportPrefix)?>export=cases">Privacy-safe cases</a>
                    <?php if (can_export_private_concerns()) : ?>
                        <a data-confirm="This export contains student identity information and will be recorded in the privacy audit log. Continue?" href="<?=e($exportPrefix)?>export=cases&amp;private=1">Authorized identity export</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </details>
    </div>
    <div class="report-filter-grid report-filter-grid--primary">
        <label>
            Period
            <select name="period" onchange="this.form.submit()">
                <option value="6m" <?=$preset==='6m'?'selected':''?>>Last 6 months</option>
                <option value="semester" <?=$preset==='semester'?'selected':''?>>Current semester</option>
                <option value="year" <?=$preset==='year'?'selected':''?>>Year to date</option>
                <option value="all" <?=$preset==='all'?'selected':''?>>All time</option>
                <option value="custom" <?=$preset==='custom'?'selected':''?>>Custom</option>
            </select>
        </label>
        <label>
            Academic year
            <select name="academic_year">
                <option value="0">Use period filter</option>
                <?php foreach ($academicYears as $ay) : ?>
                    <option value="<?=$ay['id']?>" <?=$academicYearId===(int)$ay['id']?'selected':''?>><?=e($ay['label'])?><?=($ay['status']??'')==='active'?' · Active':''?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if (!$scope) : ?>
            <label>
                Portal / campus
                <select name="portal">
                    <option value="">All portals</option>
                    <?php foreach ((['USC' => 'University Student Council']+admin_campuses()) as $code => $name) : ?>
                        <option value="<?=e($code)?>" <?=$portalFilter===$code?'selected':''?>><?=e($name)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <button class="btn btn--soft report-filter-submit">Apply</button>
    </div>
    <details class="report-advanced-filters" <?=$advancedOpen?'open':''?>>
        <summary>More filters <span>6 optional filters</span></summary>
        <div class="report-filter-grid report-filter-grid--advanced">
            <label>
                Case status
                <select name="case_status">
                    <option value="">All statuses</option>
                    <?php foreach ($allowedCaseStatuses as $v) : ?>
                        <option value="<?=e($v)?>" <?=$statusFilter===$v?'selected':''?>><?=e($v)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Priority
                <select name="priority">
                    <option value="">All priorities</option>
                    <?php foreach ($allowedPriorities as $v) : ?>
                        <option value="<?=e($v)?>" <?=$priorityFilter===$v?'selected':''?>><?=e($v)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Concern type
                <select name="concern_type">
                    <option value="">All categories</option>
                    <?php foreach ($typeOptions as $v) : ?>
                        <option value="<?=e($v)?>" <?=$typeFilter===$v?'selected':''?>><?=e($v)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Assigned office
                <select name="office">
                    <option value="">All offices</option>
                    <?php foreach ($officeOptions as $code => $name) : ?>
                        <option value="<?=e($code)?>" <?=$officeFilter===$code?'selected':''?>><?=e($name)?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                From
                <input type="date" name="from" value="<?=e($start?->format('Y-m-d')??'')?>">
            </label>
            <label>
                To
                <input type="date" name="to" value="<?=e($end->format('Y-m-d'))?>">
            </label>
        </div>
    </details>
</form>
<section class="panel report-campus-panel">
    <div class="panel__head report-section-head">
        <div>
            <h2>Portal overview</h2>
            <p>Portals with case activity in this period.</p>
        </div>
    </div>
    <div class="table-wrap">
        <?php if ($casePortalRows) : ?>
            <table class="report-table report-table--compact">
                <thead>
                    <tr>
                        <th>Portal</th>
                        <th>Concerns</th>
                        <th>Open</th>
                        <th>Reminder Due</th>
                        <th>Resolved</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($casePortalRows as $r) :$rate = $r['concerns']?round($r['resolved']/$r['concerns']*100):0; ?>
                        <tr>
                            <td>
                                <div class="report-campus-name">
                                    <strong><?=e($r['portal_name'])?></strong>
                                </div>
                            </td>
                            <td class="report-num"><?=$r['concerns']?></td>
                            <td class="report-num"><?=$r['open']?></td>
                            <td class="report-num"><?=$r['overdue']?></td>
                            <td class="report-num"><?=$r['resolved']?></td>
                            <td>
                                <div class="report-rate">
                                    <span><i style="width:<?=$rate?>%"></i></span><strong><?=$rate?>%</strong>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="report-compact-empty">
                No case activity in the selected period.
            </div>
        <?php endif; ?>
        <?php if ($quietPortalRows) : ?>
            <details class="report-hidden-rows">
                <summary><?=count($quietPortalRows)?> inactive portal<?=count($quietPortalRows)===1?'':'s'?> hidden</summary>
                <div>
                    <?php foreach ($quietPortalRows as $r) : ?>
                        <span><?=e($r['portal_name'])?></span>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
    </div>
</section>
<div class="reports-grid reports-grid--quad">
    <section class="panel report-trend-panel">
        <div class="panel__head report-section-head">
            <div>
                <h2>Monthly concern trend</h2>
                <p>Months with recorded case activity only.</p>
            </div>
            <div class="report-legend">
                <span><i class="is-submitted"></i>Submitted</span><span><i class="is-resolved"></i>Resolved</span>
            </div>
        </div>
        <div class="panel__body report-bars report-bars--refined">
            <?php if ($activeMonthly) : ?>
                <?php foreach($activeMonthly as $m):$tw=round($m['total']/$maxTrend*100);$rw=round($m['resolved']/$maxTrend*100);?>
                    <div class="report-bar-row">
                        <span><?=e(date('M Y',strtotime($m['ym'].'-01')))?></span>
                        <div class="report-bar-track">
                            <i style="width:<?=$tw?>%"></i><b style="width:<?=$rw?>%"></b>
                        </div>
                        <small class="report-trend-total"><?=$m['total']?> submitted · <?=$m['resolved']?> resolved</small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="report-compact-empty">
                    No monthly activity to chart.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="panel report-snapshot-panel">
        <div class="panel__head report-section-head">
            <div>
                <h2>Workload snapshot</h2>
                <p>Current case state.</p>
            </div>
        </div>
        <div class="panel__body report-status-list">
            <?php $statusTotal = max(1, $statusSummary['new']+$statusSummary['active']+$statusSummary['resolved']); ?>
            <?php if ($statusSummary['new']>0) : ?>
                <div class="report-status-row">
                    <div>
                        <span class="report-status-dot is-new"></span><strong>New / received</strong>
                    </div>
                    <b><?=$statusSummary['new']?></b><small><?=round($statusSummary['new']/$statusTotal*100)?>%</small>
                </div>
            <?php endif; ?>
            <?php if ($statusSummary['active']>0) : ?>
                <div class="report-status-row">
                    <div>
                        <span class="report-status-dot is-active"></span><strong>In progress</strong>
                    </div>
                    <b><?=$statusSummary['active']?></b><small><?=round($statusSummary['active']/$statusTotal*100)?>%</small>
                </div>
            <?php endif; ?>
            <?php if ($statusSummary['resolved']>0) : ?>
                <div class="report-status-row">
                    <div>
                        <span class="report-status-dot is-resolved"></span><strong>Resolved / closed</strong>
                    </div>
                    <b><?=$statusSummary['resolved']?></b><small><?=round($statusSummary['resolved']/$statusTotal*100)?>%</small>
                </div>
            <?php endif; ?>
            <?php if ($statusSummary['unassigned']>0) : ?>
                <div class="report-status-row">
                    <div>
                        <strong>Unassigned</strong>
                    </div>
                    <b><?=$statusSummary['unassigned']?></b><small>needs owner</small>
                </div>
            <?php endif; ?>
            <?php if ($statusSummary['overdue']>0) : ?>
                <div class="report-status-row">
                    <div>
                        <strong>Reminder due</strong>
                    </div>
                    <b><?=$statusSummary['overdue']?></b><small>suggested follow-up window reached</small>
                </div>
            <?php endif; ?>
            <?php if ($healthyStatusNote !== '') : ?>
                <div class="report-zero-summary">
                    <?=e($healthyStatusNote)?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="panel report-category-panel">
        <div class="panel__head report-section-head">
            <div>
                <h2>Concern categories</h2>
                <p>Most frequent types and resolution rate.</p>
            </div>
        </div>
        <div class="panel__body report-breakdown-list">
            <?php foreach ($typeRows as $r) :$rate = $r['total']?round($r['resolved']/$r['total']*100):0; ?>
                <div class="report-breakdown-row">
                    <div class="report-breakdown-copy">
                        <strong><?=e($r['concern_type'])?></strong><span><?=$r['total']?> case<?=$r['total']==1?'':'s'?> · <?=$rate?>% resolved</span>
                    </div>
                    <div class="report-breakdown-meter">
                        <i style="width:<?=$rate?>%"></i>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$typeRows) : ?>
                <div class="empty-state">
                    No cases in this period.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="panel report-priority-panel">
        <div class="panel__head report-section-head">
            <div>
                <h2>Priority distribution</h2>
                <p>Case volume by priority.</p>
            </div>
        </div>
        <div class="panel__body report-priority-list">
            <?php foreach ($priorityRows as $r) :$pw = round(((int)$r['total']/$maxPriority)*100); ?>
                <div class="report-priority-row">
                    <div class="report-priority-name">
                        <span class="priority-dot priority-dot--<?=e(strtolower($r['priority']))?>"></span><strong><?=e($r['priority'])?></strong>
                    </div>
                    <div class="report-priority-meter">
                        <i class="priority-fill priority-fill--<?=e(strtolower($r['priority']))?>" style="width:<?=$pw?>%"></i>
                    </div>
                    <b><?=$r['total']?></b>
                </div>
            <?php endforeach; ?>
            <?php if (!$priorityRows) : ?>
                <div class="empty-state">
                    No priority data in this period.
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php
admin_footer();
?>
