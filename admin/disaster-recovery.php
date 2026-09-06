<?php
require_once '../config/database.php';
require_once '_layout.php';
require_once '../config/maintenance.php';
require_once '../config/migrations.php';
if (!admin_can_permission('disaster_recovery.view') && !can_manage_backups()) deny_access('You do not have permission to view disaster recovery.');
$canManage = admin_can_permission('disaster_recovery.manage') || can_manage_backups();
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$canManage) deny_access();
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_offsite') {
            $enabled = !empty($_POST['offsite_backup_enabled'])?'1':'0';
            $path = trim((string)($_POST['offsite_backup_path'] ?? ''));
            save_system_setting($pdo, 'offsite_backup_enabled', $enabled);
            save_system_setting($pdo, 'offsite_backup_path', $path);
            admin_log($pdo, 'system', 'Updated off-site backup settings', 'Secondary recovery destination '.($enabled === '1'?'enabled':'disabled'));
            $notice = 'Secondary backup destination saved.';
        } elseif ($action === 'copy_latest') {
            $backups = maintenance_list_backups($pdo);
            if (!$backups) throw new RuntimeException('Create a backup first.');
            $r = offsite_backup_copy($pdo, $backups[0]['filename']);
            if (!$r['ok']) throw new RuntimeException($r['message']);
            admin_log($pdo, 'system', 'Copied backup off-site', $backups[0]['filename']);
            $notice = $r['message'];
        } elseif ($action === 'verify_audit') {
            $r = audit_verify_chain($pdo);
            if (!$r['ok']) throw new RuntimeException($r['message']);
            admin_log($pdo, 'security', 'Verified audit integrity', $r['message']);
            $notice = $r['message'];
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        app_log_error('Disaster recovery action failed', ['error' => $e->getMessage()]);
    }
}

if (isset($_GET['manifest'])) {
    if (!$canManage) deny_access();
    $backups = maintenance_list_backups($pdo);
    $audit = audit_verify_chain($pdo);
    $manifest = ['generated_at' => date(DATE_ATOM), 'application' => 'DMMMSU USC', 'environment' => app_environment(), 'php' => PHP_VERSION, 'pending_migrations' => migration_pending_count($pdo), 'active_academic_year' => active_academic_year($pdo), 'latest_backup' => $backups[0] ?? null, 'audit_integrity' => $audit, 'offsite_enabled' => system_setting($pdo, 'offsite_backup_enabled', '0') === '1'];
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="dmmmsu-usc-recovery-manifest-'.date('Ymd-His').'.json"');
    echo json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

$backups = maintenance_list_backups($pdo);
$latest = $backups[0] ?? null;
$verified = array_values(array_filter($backups, fn($b) => ($b['verification_status'] ?? '') === 'verified'));
$lastVerified = $verified[0] ?? null;
$audit = audit_verify_chain($pdo);
$pending = migration_pending_count($pdo);
$offsiteEnabled = system_setting($pdo, 'offsite_backup_enabled', '0') === '1';
$offsitePath = system_setting($pdo, 'offsite_backup_path', '') ?? '';
$keyOk = is_file(app_storage_path('keys/data.key')) || trim((string)app_env('APP_DATA_KEY', '')) !== '';
$recoveryPoint = $lastVerified?:$latest;
$offsiteCopied = !empty($latest['offsite_copied_at']);
$readinessChecks = [
['ok' => (bool)$lastVerified, 'title' => 'Verified recovery point', 'detail' => $lastVerified?'A protected backup has passed checksum verification.':'Verify at least one protected backup before deployment.'],
['ok' => $pending === 0, 'title' => 'Database schema current', 'detail' => $pending === 0?'No pending migrations.':$pending.' pending migration'.($pending === 1?'':'s').' need review.'],
['ok' => $keyOk, 'title' => 'Encryption key protected', 'detail' => $keyOk?'Protected data can be decrypted during recovery.':'The protected data key is not available.'],
['ok' => (bool)$audit['ok'], 'title' => 'Audit chain intact', 'detail' => (string)$audit['message']],
];
$readyCount = count(array_filter($readinessChecks, fn($c) => $c['ok']));
admin_header('Disaster Recovery', 'System administration');
admin_system_management_tabs('disaster-recovery.php');
?>
<div class="sysadmin-page sysadmin-recovery-page">
    <section class="sysadmin-intro">
        <div>
            <span class="page-kicker">BUSINESS CONTINUITY</span>
            <div class="sysadmin-intro__titleline">
                <h2>Recovery readiness</h2>
                <span class="sysadmin-status-chip <?=$readyCount===count($readinessChecks)?'is-good':'is-warning'?>"><?=$readyCount?> / <?=count($readinessChecks)?> ready</span>
            </div>
            <p>Keep only the controls needed to rebuild the USC system after a server failure.</p>
        </div>
        <?php if ($canManage) : ?>
            <a class="btn btn--soft" href="?manifest=1">Download recovery manifest</a>
        <?php endif; ?>
    </section>
    <?php if ($notice) : ?>
        <div class="notice">
            <?=e($notice)?>
        </div>
    <?php endif; ?>
    <?php if ($error) : ?>
        <div class="notice notice--error">
            <?=e($error)?>
        </div>
    <?php endif; ?>
    <section class="sysadmin-stat-grid sysadmin-stat-grid--4" aria-label="Recovery summary">
        <article class="sysadmin-stat-card <?=$lastVerified?'is-ok':'is-warn'?>">
            <div class="sysadmin-stat-card__top">
                <span>Recovery point</span><i></i>
            </div>
            <strong><?=$recoveryPoint?e(date('M j, Y',strtotime((string)($lastVerified['verified_at']??$recoveryPoint['created_at'])))):'None'?></strong>
            <p><?=$lastVerified?'Verified checksum':($latest?'Latest backup is not verified yet':'Create a protected backup first')?></p>
            <?php if ($recoveryPoint) : ?>
                <small><?=e($recoveryPoint['filename'])?></small>
            <?php endif; ?>
        </article>
        <article class="sysadmin-stat-card <?=$audit['ok']?'is-ok':'is-warn'?>">
            <div class="sysadmin-stat-card__top">
                <span>Audit integrity</span><i></i>
            </div>
            <strong><?=$audit['ok']?'Verified':'Review'?></strong>
            <p><?=e($audit['message'])?></p>
            <small>Integrity chain for administrative events</small>
        </article>
        <article class="sysadmin-stat-card <?=$keyOk?'is-ok':'is-warn'?>">
            <div class="sysadmin-stat-card__top">
                <span>Encryption key</span><i></i>
            </div>
            <strong><?=$keyOk?'Present':'Missing'?></strong>
            <p>Required for protected E-Sumbong identity and 2FA recovery.</p>
            <small><?=$keyOk?'Recovery prerequisite available':'Recovery would be incomplete'?></small>
        </article>
        <article class="sysadmin-stat-card <?=($offsiteEnabled&&$offsiteCopied)?'is-ok':($offsiteEnabled?'is-warn':'is-neutral')?>">
            <div class="sysadmin-stat-card__top">
                <span>Secondary copy</span><i></i>
            </div>
            <strong><?=$offsiteEnabled?($offsiteCopied?'Current':'Pending'):'Optional'?></strong>
            <p><?=$offsiteEnabled?($offsitePath!==''?'Destination configured':'Destination missing'):'Local backups remain the primary recovery source'?></p>
            <small><?=$offsiteCopied?'Latest backup copied off-site':'No current off-site copy recorded'?></small>
        </article>
    </section>
    <div class="sysadmin-two-column sysadmin-recovery-main">
        <section class="panel sysadmin-panel">
            <div class="panel__head sysadmin-panel__head">
                <div>
                    <span class="page-kicker">READINESS CHECKS</span>
                    <h2>Recovery checklist</h2>
                    <p>Review these checks before deployment and after major changes.</p>
                </div>
                <?php if ($canManage) : ?>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                        <input type="hidden" name="action" value="verify_audit">
                        <button class="btn btn--soft btn--compact">Verify audit</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="sysadmin-checklist">
                <?php foreach ($readinessChecks as $check) : ?>
                    <div class="<?=$check['ok']?'is-ok':'is-warn'?>">
                        <i><?=$check['ok']?'✓':'!'?></i>
                        <div>
                            <strong><?=e($check['title'])?></strong><small><?=e($check['detail'])?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="panel sysadmin-panel">
            <div class="panel__head sysadmin-panel__head">
                <div>
                    <span class="page-kicker">SECONDARY COPY</span>
                    <h2>Off-site destination</h2>
                    <p>Optional resilience layer. Local protected backups are never removed.</p>
                </div>
            </div>
            <form method="post" class="panel__body governance-form sysadmin-offsite-form">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="save_offsite">
                <label class="inline-check">
                    <input type="checkbox" name="offsite_backup_enabled" value="1" <?=$offsiteEnabled?'checked':''?> <?=$canManage?'':'disabled'?>>
                    Enable secondary backup copy
                </label>
                <label>
                    Destination path
                    <input name="offsite_backup_path" value="<?=e($offsitePath)?>" placeholder="D:\\USC-Offsite-Backups or \\\\server\\share" <?=$canManage?'':'disabled'?>>
                </label>
                <?php if ($canManage) : ?>
                    <div class="form-actions">
                        <button class="btn">Save destination</button>
                        <?php if ($latest) : ?>
                            <button class="btn btn--soft" name="action" value="copy_latest" data-confirm="Copy the latest backup to the configured secondary destination?">Copy latest now</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </section>
    </div>
    <details class="panel sysadmin-details">
        <summary>
            <div>
                <span class="page-kicker">RECOVERY PROCEDURE</span><strong>Server rebuild sequence</strong><small>Keep this procedure with deployment documentation; open only when needed.</small>
            </div>
            <span class="sysadmin-muted-pill">7 steps</span></summary>
        <ol class="sysadmin-recovery-steps">
            <li>
                Prepare a clean PHP/MySQL server and copy the application files.
            </li>
            <li>
                Restore the latest verified SQL backup or full recovery archive.
            </li>
            <li>
                Restore <code>uploads/</code> and the protected encryption key from the same recovery point.
            </li>
            <li>
                Configure production <code>.env</code> values and database credentials.
            </li>
            <li>
                Run pending migrations from Maintenance; a safety backup is created first.
            </li>
            <li>
                Sign in as System Administrator and verify System Health, audit integrity, media availability, and E-Sumbong tracking.
            </li>
            <li>
                Keep maintenance mode enabled until final verification is complete.
            </li>
        </ol>
    </details>
</div>
<?php
admin_footer();
?>
