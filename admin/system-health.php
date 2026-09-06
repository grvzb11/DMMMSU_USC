<?php
require_once '../config/database.php';
require_once '_layout.php';
require_once '../config/migrations.php';
require_once '../config/maintenance.php';
if (!admin_can_permission('system.health')) deny_access('Only authorized administrators can view System Health.');

function health_dir_size(string $path): int {
    $bytes = 0;
    if (!is_dir($path)) return 0;
    try {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) if ($f->isFile()) $bytes+=$f->getSize();
    } catch (Throwable $ignored) {
    }
    return $bytes;
}
function health_item(string $label, string $detail, string $state = 'ok'): array {
    return ['label' => $label, 'detail' => $detail, 'state' => $state];
}

$checks = [];
$dbVersion = 'Unknown';
$dbSize = 0;
try {
    $pdo->query('SELECT 1');
    $dbVersion = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    $st = $pdo->query("SELECT COALESCE(SUM(data_length+index_length),0) FROM information_schema.TABLES WHERE table_schema=DATABASE()");
    $dbSize = (int)$st->fetchColumn();
    $checks['database'] = health_item('Healthy', 'MySQL '.$dbVersion.' · '.format_file_size($dbSize), 'ok');
} catch (Throwable $e) {
    $checks['database'] = health_item('Issue', 'Database query failed', 'bad');
}
$uploadRoot = dirname(__DIR__).'/uploads';
$storageRoot = dirname(__DIR__).'/storage';
$backupRoot = dirname(__DIR__).'/backups';
$uploadOk = is_dir($uploadRoot) && is_writable($uploadRoot);
$storageOk = is_dir($storageRoot) && is_writable($storageRoot);
$backupOk = is_dir($backupRoot) && is_writable($backupRoot);
$checks['uploads'] = health_item($uploadOk?'Healthy':'Attention', $uploadOk?'Upload directory is writable':'Upload directory is not writable', $uploadOk?'ok':'warn');
$checks['logs'] = health_item($storageOk?'Healthy':'Attention', $storageOk?'Storage/log directory is writable':'Storage/log directory is not writable', $storageOk?'ok':'warn');
$checks['backups'] = health_item($backupOk?'Healthy':'Attention', $backupOk?'Protected backup directory is writable':'Backup directory is not writable', $backupOk?'ok':'warn');

$activeAdmins = $inactiveAdmins = $twoFactor = $activeSessions = $lockedAttempts = $pendingPasswords = $dormant = $passwordDue = 0;
try {
    $activeAdmins = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE LOWER(status)='active'")->fetchColumn();
    $inactiveAdmins = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE LOWER(status)<>'active'")->fetchColumn();
    $twoFactor = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE LOWER(status)='active' AND two_factor_enabled=1")->fetchColumn();
    $pendingPasswords = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE LOWER(status)='active' AND force_password_change=1")->fetchColumn();
} catch (Throwable $ignored) {
}
try {
    $activeSessions = (int)$pdo->query("SELECT COUNT(*) FROM admin_sessions WHERE revoked_at IS NULL AND last_seen_at>=DATE_SUB(NOW(),INTERVAL 30 MINUTE)")->fetchColumn();
    $lockedAttempts = (int)$pdo->query("SELECT COUNT(*) FROM admin_login_attempts WHERE locked_until>NOW()")->fetchColumn();
} catch (Throwable $ignored) {
}
$dormantDays = 90;
$passwordAgeDays = 180;
try {
    $dormantDays = max(30, (int)(system_setting($pdo, 'dormant_account_days', '90') ?? 90));
    $passwordAgeDays = max(30, (int)(system_setting($pdo, 'password_max_age_days', '180') ?? 180));
} catch (Throwable $ignored) {
}
try {
    if (db_column_exists($pdo, 'admins', 'last_activity_at')) {
        $st = $pdo->query("SELECT COUNT(*) FROM admins WHERE LOWER(status)='active' AND COALESCE(last_activity_at,last_login,created_at)<DATE_SUB(NOW(),INTERVAL ".$dormantDays." DAY)");
        $dormant = (int)$st->fetchColumn();
    }
    $st = $pdo->query("SELECT COUNT(*) FROM admins WHERE LOWER(status)='active' AND COALESCE(password_changed_at,created_at)<DATE_SUB(NOW(),INTERVAL ".$passwordAgeDays." DAY)");
    $passwordDue = (int)$st->fetchColumn();
} catch (Throwable $ignored) {
}

$uploadBytes = health_dir_size($uploadRoot);
$logBytes = health_dir_size($storageRoot);
$backupBytes = health_dir_size($backupRoot);
$diskFree = @disk_free_space(dirname(__DIR__));
$diskTotal = @disk_total_space(dirname(__DIR__));
$diskPct = ($diskFree !== false && $diskTotal)?(int)round((1-$diskFree/$diskTotal)*100):null;
$storageWarn = 80;
$storageCritical = 90;
try {
    $storageWarn = max(50, (int)(system_setting($pdo, 'storage_warning_percent', '80') ?? 80));
    $storageCritical = max($storageWarn+1, (int)(system_setting($pdo, 'storage_critical_percent', '90') ?? 90));
} catch (Throwable $ignored) {
}
$activeAy = null;
$activeAyLabel = '';
$auditIntegrity = ['ok' => false, 'checked' => 0, 'message' => 'Audit verification unavailable.'];
$offsiteEnabled = false;
$offsitePath = '';
$offsiteLast = '';
try {
    $activeAy = active_academic_year($pdo);
    $activeAyLabel = academic_year_label($activeAy);
} catch (Throwable $ignored) {
}
try {
    $auditIntegrity = audit_verify_chain($pdo);
} catch (Throwable $e) {
    $auditIntegrity = ['ok' => false, 'checked' => 0, 'message' => 'Audit verification could not be completed.'];
    app_log_error('System Health audit verification failed', ['error' => $e->getMessage()]);
}
try {
    $offsiteEnabled = system_setting($pdo, 'offsite_backup_enabled', '0') === '1';
    $offsitePath = (string)(system_setting($pdo, 'offsite_backup_path', '') ?? '');
} catch (Throwable $ignored) {
}
$missingMedia = 0;
$verifiedMedia = 0;
try {
    $media = $pdo->query("SELECT file_path,last_verified_at FROM media_library WHERE deleted_at IS NULL")->fetchAll();
    foreach ($media as $m) {
        if (!is_file(dirname(__DIR__).'/'.ltrim((string)$m['file_path'], '/'))) $missingMedia++;
        if (!empty($m['last_verified_at'])) $verifiedMedia++;
    }
} catch (Throwable $ignored) {
}

$migrations = [];
$pendingMigrations = 0;
try {
    $migrations = migration_status($pdo);
    $pendingMigrations = count(array_filter($migrations, static fn($m) => empty($m['applied'])));
} catch (Throwable $e) {
    $pendingMigrations = 0;
    app_log_error('System Health migration status failed', ['error' => $e->getMessage()]);
}
$checks['migrations'] = health_item($pendingMigrations?'Attention':'Current', $pendingMigrations?$pendingMigrations.' migration'.($pendingMigrations === 1?'':'s').' pending':'Database schema is current', $pendingMigrations?'warn':'ok');
$backupList = [];
$latestBackup = null;
try {
    $backupList = maintenance_list_backups($pdo);
    $latestBackup = $backupList[0] ?? null;
} catch (Throwable $e) {
    app_log_error('System Health backup list failed', ['error' => $e->getMessage()]);
}
if ($latestBackup) $offsiteLast = (string)($latestBackup['offsite_copied_at'] ?? '');
$backupReminder = 7;
try {
    $backupReminder = max(1, (int)(system_setting($pdo, 'backup_reminder_days', '7') ?? 7));
} catch (Throwable $ignored) {
}
$backupAge = $latestBackup?max(0, (int)floor((time()-strtotime((string)$latestBackup['created_at']))/86400)):null;
$backupDue = $backupAge === null || $backupAge>$backupReminder;
$checks['backup_freshness'] = health_item($backupDue?'Attention':'Current', $latestBackup?('Latest backup '.$backupAge.' day'.($backupAge === 1?'':'s').' ago'):'No protected backup has been created', $backupDue?'warn':'ok');
$pendingAccounts = $scheduledPosts = $reviewPosts = $overdueConcerns = $queuedEmails = $failedEmails = 0;
$recordCounts = [];
try {
    $pendingAccounts = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE status='pending'")->fetchColumn();
} catch (Throwable $ignored) {
}
try {
    $scheduledPosts = db_column_exists($pdo, 'posts', 'scheduled_at')?(int)$pdo->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status='draft' AND scheduled_at IS NOT NULL AND scheduled_at>NOW()")->fetchColumn():0;
    $reviewPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status='review'")->fetchColumn();
} catch (Throwable $ignored) {
}
try {
    $overdueConcerns = (int)$pdo->query("SELECT COUNT(*) FROM concerns WHERE status NOT IN ('Resolved','Closed') AND due_at IS NOT NULL AND due_at<NOW()")->fetchColumn();
} catch (Throwable $ignored) {
}
try {
    if (db_table_exists($pdo, 'notification_email_outbox')) {
        $queuedEmails = (int)$pdo->query("SELECT COUNT(*) FROM notification_email_outbox WHERE status='queued'")->fetchColumn();
        $failedEmails = (int)$pdo->query("SELECT COUNT(*) FROM notification_email_outbox WHERE status='queued' AND attempts>=3")->fetchColumn();
    }
} catch (Throwable $ignored) {
}
foreach (['admins', 'posts', 'concerns', 'media_library', 'admin_activity_logs'] as $table) {
    try {
        $recordCounts[$table] = (int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    } catch (Throwable $ignored) {
        $recordCounts[$table] = 0;
    }
}
$latestVerification = (string)($latestBackup['verification_status'] ?? '');
$lastMaintenance = '';
try {
    $lastMaintenance = (string)(system_setting($pdo, 'maintenance_last_run_at', '') ?? '');
} catch (Throwable $ignored) {
}
$phpLimits = ['upload_max_filesize' => (string)ini_get('upload_max_filesize'), 'post_max_size' => (string)ini_get('post_max_size'), 'memory_limit' => (string)ini_get('memory_limit')];
$loginMaxAttempts = '5';
$loginLockoutMinutes = '5';
try {
    $loginMaxAttempts = (string)(system_setting($pdo, 'login_max_attempts', '5') ?? '5');
    $loginLockoutMinutes = (string)(system_setting($pdo, 'login_lockout_minutes', '5') ?? '5');
} catch (Throwable $ignored) {
}

$prodWarnings = [];
$env = app_environment();
$dbUser = (string)app_env('DB_USER', 'root');
$dbPass = (string)app_env('DB_PASS', '');
if (app_is_production() && ($dbUser === 'root' || $dbPass === '')) $prodWarnings[] = 'Production database credentials should use a dedicated limited user with a strong password.';
if (app_is_production() && !app_is_https()) $prodWarnings[] = 'Production mode is not currently being served over HTTPS.';
if (app_is_production() && !app_env_bool('APP_CSP_ENFORCE', false)) $prodWarnings[] = 'Content Security Policy is still report-only; enforce it after confirming required resources.';
if (app_is_production() && app_env_bool('APP_INSTALLER_ENABLED', false)) $prodWarnings[] = 'The installer is enabled in production and should be disabled after initial setup.';
if (!is_file(dirname(__DIR__).'/.env')) $prodWarnings[] = 'No .env file is present; local fallback configuration is being used.';
if (!class_exists('ZipArchive')) $prodWarnings[] = 'PHP ZipArchive is unavailable, so full database + uploads backups cannot be created in the web interface.';
if ($missingMedia) $prodWarnings[] = $missingMedia.' Media Library record'.($missingMedia === 1?' points':'s point').' to a missing physical file.';
if ($latestBackup && db_column_exists($pdo, 'backup_history', 'verification_status') && $latestVerification !== 'verified') $prodWarnings[] = 'The latest protected backup has not been successfully verified.';
if ($failedEmails) $prodWarnings[] = $failedEmails.' queued email notification'.($failedEmails === 1?' has':'s have').' reached the delivery retry limit.';
if (!$auditIntegrity['ok']) $prodWarnings[] = 'Audit-log integrity verification needs attention: '.$auditIntegrity['message'];
if ($offsiteEnabled && $offsitePath === '') $prodWarnings[] = 'Secondary backup copy is enabled but no destination path is configured.';
if ($offsiteEnabled && $latestBackup && !$offsiteLast) $prodWarnings[] = 'The latest protected backup has not yet been copied to the secondary destination.';

$recentSecurity = [];
try {
    $recentSecurity = $pdo->query("SELECT action,description,created_at,ip_address FROM admin_activity_logs WHERE module IN ('security','privacy','backups') ORDER BY created_at DESC,id DESC LIMIT 8")->fetchAll();
} catch (Throwable $ignored) {
}
$twoFactorPercent = $activeAdmins>0?(int)round(($twoFactor/$activeAdmins)*100):0;
$diskState = $diskPct !== null && $diskPct >= $storageCritical?'bad':(($diskPct !== null && $diskPct >= $storageWarn)?'warn':'ok');
$securityAttention = $lockedAttempts+$dormant+$passwordDue;
$writableCount = (int)$uploadOk+(int)$storageOk+(int)$backupOk;
$mediaState = $missingMedia>0?'warn':'ok';
$databaseState = $checks['database']['state'] === 'bad'?'bad':($pendingMigrations?'warn':'ok');
$backupState = $backupDue?'warn':'ok';
$securityState = $lockedAttempts>0?'warn':'ok';
$platformHealthy = !array_filter($checks, fn($c) => $c['state'] === 'bad') && !$pendingMigrations && !$backupDue && !$prodWarnings && $diskState === 'ok';
$healthAttentionCount = count($prodWarnings)+($pendingMigrations>0?1:0)+($backupDue?1:0)+($diskState !== 'ok'?1:0)+($securityAttention>0?1:0)+($missingMedia>0?1:0)+($writableCount<3?1:0);
if (!$platformHealthy && $healthAttentionCount === 0) $healthAttentionCount = 1;
admin_header('System Health', 'System administration');
admin_system_management_tabs('system-health.php');
?>
<div class="sysadmin-page sysadmin-health-page">
    <section class="sysadmin-intro">
        <div>
            <span class="page-kicker">PLATFORM STATUS</span>
            <div class="sysadmin-intro__titleline">
                <h2>System overview</h2>
                <span class="sysadmin-status-chip <?=$platformHealthy?'is-good':'is-warning'?>"><?=$platformHealthy?'Operational':'Review '.$healthAttentionCount.' item'.($healthAttentionCount===1?'':'s')?></span>
            </div>
            <p>Core availability, recovery, storage, and access checks in one concise view.</p>
        </div>
        <?php if (can_manage_settings()) : ?>
            <a class="btn btn--soft" href="settings.php">Settings</a>
        <?php endif; ?>
    </section>
    <section class="sysadmin-stat-grid sysadmin-stat-grid--4" aria-label="System health summary">
        <article class="sysadmin-stat-card is-<?=e($databaseState)?>">
            <div class="sysadmin-stat-card__top">
                <span>Data layer</span><i></i>
            </div>
            <strong><?=e($checks['database']['label'])?></strong>
            <p><?=e($checks['database']['detail'])?></p>
            <small>Schema: <?=$pendingMigrations?$pendingMigrations.' pending':'current'?></small>
        </article>
        <article class="sysadmin-stat-card is-<?=e($backupState)?>">
            <div class="sysadmin-stat-card__top">
                <span>Recovery</span><i></i>
            </div>
            <strong><?=$latestBackup?'Protected':'Needs backup'?></strong>
            <p><?=$latestBackup?'Latest '.e(date('M j, Y · g:i A',strtotime((string)$latestBackup['created_at']))):'No protected recovery point yet.'?></p>
            <small><?=e(format_file_size($backupBytes))?> protected storage</small>
        </article>
        <article class="sysadmin-stat-card is-<?=e($diskState)?>">
            <div class="sysadmin-stat-card__top">
                <span>Storage</span><i></i>
            </div>
            <strong><?=$diskPct===null?'Unavailable':$diskPct.'% used'?></strong>
            <p><?=$diskFree===false?'Disk availability could not be determined.':e(format_file_size((int)$diskFree).' free')?></p>
            <?php if ($diskPct !== null) : ?>
                <div class="sysadmin-meter">
                    <span style="width:<?=max(0,min(100,$diskPct))?>%"></span>
                </div>
            <?php endif; ?>
            <small><?=$missingMedia?$missingMedia.' missing media file'.($missingMedia===1?'':'s'):'Media files intact'?></small>
        </article>
        <article class="sysadmin-stat-card is-<?=e($securityState)?>">
            <div class="sysadmin-stat-card__top">
                <span>Access security</span><i></i>
            </div>
            <strong><?=$securityAttention?$securityAttention.' item'.($securityAttention===1?'':'s').' to review':'Stable'?></strong>
            <p><?=$activeSessions?> active session<?=($activeSessions===1?'':'s')?> · <?=$lockedAttempts?> lockout<?=($lockedAttempts===1?'':'s')?></p>
            <small><?=$twoFactor?> of <?=$activeAdmins?> active accounts use 2-step verification</small>
        </article>
    </section>
    <div class="sysadmin-two-column sysadmin-health-main">
        <section class="panel sysadmin-panel">
            <div class="panel__head sysadmin-panel__head">
                <div>
                    <span class="page-kicker">SECURITY &amp; RUNTIME</span>
                    <h2>Security posture</h2>
                    <p>Only the safeguards that need regular administrator review.</p>
                </div>
                <span class="sysadmin-muted-pill">Live checks</span>
            </div>
            <div class="sysadmin-mini-grid">
                <article>
                    <span>Login protection</span><strong><?=e($loginMaxAttempts)?> attempts</strong><small><?=e($loginLockoutMinutes)?> min lockout</small>
                </article>
                <article>
                    <span>2-step verification</span><strong><?=$twoFactorPercent?>%</strong><small><?=$twoFactor?> of <?=$activeAdmins?> active accounts</small>
                    <div class="sysadmin-meter">
                        <span style="width:<?=$twoFactorPercent?>%"></span>
                    </div>
                </article>
                <article>
                    <span>Account hygiene</span><strong><?=$dormant?> dormant</strong><small><?=$passwordDue?> password warning<?=($passwordDue===1?'':'s')?></small>
                </article>
                <article>
                    <span>Writable paths</span><strong><?=$writableCount?> / 3 ready</strong><small>Uploads · logs · backups</small>
                </article>
            </div>
        </section>
        <section class="panel sysadmin-panel">
            <div class="panel__head sysadmin-panel__head">
                <div>
                    <span class="page-kicker">AUDIT TRAIL</span>
                    <h2>Recent security activity</h2>
                    <p>Latest privacy and security events.</p>
                </div>
                <a class="btn btn--soft btn--compact" href="activity.php">View all</a>
            </div>
            <div class="sysadmin-activity-list">
                <?php $recentSecurityDisplay=array_slice($recentSecurity,0,4); if($recentSecurityDisplay):foreach($recentSecurityDisplay as $event):?>
                <article>
                    <i aria-hidden="true"></i>
                    <div>
                        <strong><?=e($event['action'])?></strong><small><?=e($event['description']?:'Administrative event')?></small>
                    </div>
                    <time><?=e(date('M j · g:i A',strtotime($event['created_at'])))?></time>
                </article>
            <?php endforeach;else:?>
            <div class="empty-state">
                No security activity has been recorded yet.
            </div>
        <?php endif; ?>
    </div>
</section>
</div>
<details class="panel sysadmin-details">
    <summary>
        <div>
            <span class="page-kicker">TECHNICAL DIAGNOSTICS</span><strong>Advanced system checks</strong><small>Open this section only when troubleshooting.</small>
        </div>
        <span class="sysadmin-muted-pill">6 checks</span></summary>
    <div class="sysadmin-diagnostic-grid">
        <article>
            <span>Schema migrations</span><strong><?=$pendingMigrations?$pendingMigrations.' pending':'Up to date'?></strong><small>Explicit upgrades only</small>
        </article>
        <article>
            <span>Writable paths</span><strong><?=$writableCount?> / 3 ready</strong><small>Uploads · logs · backups</small>
        </article>
        <article>
            <span>Email queue</span><strong><?=$queuedEmails?> queued</strong><small><?=$failedEmails?> at retry limit</small>
        </article>
        <article>
            <span>PHP limits</span><strong><?=e($phpLimits['upload_max_filesize'])?> upload</strong><small>POST <?=e($phpLimits['post_max_size'])?> · memory <?=e($phpLimits['memory_limit'])?></small>
        </article>
        <article>
            <span>Audit integrity</span><strong><?=$auditIntegrity['ok']?'Verified':'Review'?></strong><small><?=e($auditIntegrity['message'])?></small>
        </article>
        <article>
            <span>Secondary backup</span><strong><?=$offsiteEnabled?($offsiteLast?'Current':'Pending'):'Disabled'?></strong><small><?=e($offsiteEnabled?($offsitePath?:'Destination not configured'):'Optional resilience layer')?></small>
        </article>
    </div>
</details>
</div>
<?php
admin_footer();
?>
