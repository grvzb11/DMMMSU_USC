<?php
require_once '../config/database.php';
require_once '_layout.php';
require_once '../config/migrations.php';
require_once '../config/maintenance.php';
require_admin();
if (!can_manage_backups() && !can_manage_migrations()) deny_access('System Administrator access is required for database maintenance.');

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'run_migrations') {
            if (!can_manage_migrations()) deny_access();
            // Always take a safety database snapshot before schema changes when possible.
            try {
                $safety = maintenance_create_database_backup($pdo, (int)($_SESSION['admin_id'] ?? 0));
            } catch (Throwable $backupError) {
                $safety = null;
                app_log_error('Pre-migration backup failed', ['error' => $backupError->getMessage()]);
            }
            $ran = migration_run_pending($pdo, (int)($_SESSION['admin_id'] ?? 0));
            if ($safety && $ran && db_column_exists($pdo, 'schema_migrations', 'safety_backup_filename')) {
                $st = $pdo->prepare('UPDATE schema_migrations SET safety_backup_filename=? WHERE version=?');
                foreach ($ran as $version) $st->execute([(string)$safety['filename'], (string)$version]);
            }
            admin_log($pdo, 'system', 'Ran database migrations', $ran?'Applied '.implode(', ', $ran):'Database already current');
            $notice = $ran?'Database upgraded successfully. '.count($ran).' migration'.(count($ran) === 1?'':'s').' applied'.($safety?' after creating a safety backup.':'.'):'Database is already up to date.';
        } elseif ($action === 'backup_database') {
            if (!can_manage_backups()) deny_access();
            $b = maintenance_create_database_backup($pdo, (int)($_SESSION['admin_id'] ?? 0));
            $v = maintenance_verify_backup($pdo, $b['filename']);
            admin_log($pdo, 'system', 'Created database backup', $b['filename'].' · '.($v['ok']?'verified':'verification failed'));
            $notice = 'Database backup created and '.($v['ok']?'verified':'saved; verification needs attention').': '.$b['filename'];
        } elseif ($action === 'backup_full') {
            if (!can_manage_backups()) deny_access();
            $b = maintenance_create_full_backup($pdo, (int)($_SESSION['admin_id'] ?? 0));
            $v = maintenance_verify_backup($pdo, $b['filename']);
            admin_log($pdo, 'system', 'Created full backup', $b['filename'].' · '.($v['ok']?'verified':'verification failed'));
            $notice = 'Full database + uploads + encryption-key backup created and '.($v['ok']?'verified':'saved; verification needs attention').': '.$b['filename'];
        } elseif ($action === 'verify_backup') {
            if (!can_manage_backups()) deny_access();
            $filename = basename((string)($_POST['filename'] ?? ''));
            $v = maintenance_verify_backup($pdo, $filename);
            admin_log($pdo, 'system', 'Verified backup', $filename.' · '.$v['message']);
            if (!$v['ok']) throw new RuntimeException($v['message']);
            $notice = 'Backup verified: '.$filename.'. '.$v['message'];
        } elseif ($action === 'run_scheduled') {
            if (!can_manage_backups()) deny_access();
            $r = maintenance_run_scheduled_tasks($pdo);
            admin_log($pdo, 'system', 'Ran scheduled maintenance', 'Published '.$r['published'].' scheduled item(s); processed '.$r['emails'].' email(s)'.($r['backup']?'; created '.$r['backup']['filename']:''));
            $notice = 'Scheduled maintenance completed. '.$r['published'].' publication(s) released, '.$r['emails'].' email(s) processed'.($r['backup']?', and a backup was created.':'.');
        } elseif ($action === 'restore') {
            if (!can_manage_backups()) deny_access();
            $filename = basename((string)($_POST['filename'] ?? ''));
            $confirm = trim((string)($_POST['confirm'] ?? ''));
            if ($confirm !== 'RESTORE') throw new RuntimeException('Type RESTORE exactly to confirm a database restore.');
            if (!str_ends_with(strtolower($filename), '.sql')) throw new RuntimeException('Use a database (.sql) backup for web restore. Full ZIP archives are retained for disaster recovery and uploads restoration.');
            $count = maintenance_restore_database_backup($pdo, $filename, (int)($_SESSION['admin_id'] ?? 0));
            // Re-apply any migrations newer than the restored snapshot.
            $ran = migration_run_pending($pdo, (int)($_SESSION['admin_id'] ?? 0));
            admin_log($pdo, 'security', 'Restored database backup', $filename.' · '.$count.' SQL statements · '.count($ran).' post-restore migrations');
            $notice = 'Database restored from '.$filename.'. Current migrations were re-applied.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        app_log_error('Maintenance action failed', ['action' => $action, 'error' => $e->getMessage(), 'admin_id' => $_SESSION['admin_id'] ?? null]);
    }
}

$migrations = migration_status($pdo);
$pending = count(array_filter($migrations, fn($m) => !$m['applied']));
$backups = maintenance_list_backups($pdo);
$zipEnabled = class_exists('ZipArchive');
$lastBackup = $backups[0] ?? null;
$backupCount = count($backups);
$backupTotalBytes = array_sum(array_map(fn($b) => (int)($b['file_size'] ?? 0), $backups));
$schemaCurrent = $pending === 0;
$retention = maintenance_retention_report($pdo);
$lastRun = system_setting($pdo, 'maintenance_last_run_at', '') ?? '';
$autoBackup = system_setting($pdo, 'auto_backup_enabled', '0') === '1';
$pendingMigrations = array_values(array_filter($migrations, fn($m) => empty($m['applied'])));
$appliedMigrations = array_values(array_filter($migrations, fn($m) => !empty($m['applied'])));
admin_header('Maintenance Center', 'System administration');
admin_system_management_tabs('maintenance.php');
?>
<div class="sysadmin-page sysadmin-maintenance-page">
    <section class="sysadmin-intro">
        <div>
            <span class="page-kicker">CONTROLLED MAINTENANCE</span>
            <div class="sysadmin-intro__titleline">
                <h2>Maintenance tasks</h2>
                <span class="sysadmin-status-chip <?=$schemaCurrent?'is-good':'is-warning'?>"><?=$schemaCurrent?'Schema current':$pending.' pending'?></span>
            </div>
            <p>Database upgrades, recovery-point creation, scheduled jobs, and protected backup history.</p>
        </div>
        <?php if (can_manage_settings()) : ?>
            <a class="btn btn--soft" href="settings.php">Settings</a>
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
    <section class="sysadmin-stat-grid sysadmin-stat-grid--3" aria-label="Maintenance status">
        <article class="sysadmin-stat-card <?=$schemaCurrent?'is-ok':'is-warn'?>">
            <div class="sysadmin-stat-card__top">
                <span>Database schema</span><i></i>
            </div>
            <strong><?=$schemaCurrent?'Current':$pending.' pending'?></strong>
            <p><?=count($migrations)?> managed migration<?=count($migrations)===1?'':'s'?></p>
            <small><?=$schemaCurrent?'No upgrade action required':'Review pending upgrades below'?></small>
        </article>
        <article class="sysadmin-stat-card <?=$lastBackup?'is-ok':'is-warn'?>">
            <div class="sysadmin-stat-card__top">
                <span>Latest backup</span><i></i>
            </div>
            <strong><?=$lastBackup?e(date('M j · g:i A',strtotime($lastBackup['created_at']))):'None yet'?></strong>
            <p><?=$lastBackup?e(format_file_size((int)$lastBackup['file_size'])):'Create a recovery point before deployment'?></p>
            <small><?=$backupCount?> protected backup<?=$backupCount===1?'':'s'?> · <?=e(format_file_size($backupTotalBytes))?> total</small>
        </article>
        <article class="sysadmin-stat-card <?=$autoBackup?'is-ok':'is-neutral'?>">
            <div class="sysadmin-stat-card__top">
                <span>Scheduled tasks</span><i></i>
            </div>
            <strong><?=$autoBackup?'Automatic backup enabled':'Manual backup mode'?></strong>
            <p><?=$lastRun!==''?'Last run '.e(date('M j · g:i A',strtotime($lastRun))):'No scheduled run recorded yet'?></p>
            <small><?=$zipEnabled?'Full ZIP recovery supported':'PHP ZipArchive unavailable'?></small>
        </article>
    </section>
    <div class="sysadmin-two-column sysadmin-maintenance-main">
        <section class="panel sysadmin-panel sysadmin-migrations-panel">
            <div class="panel__head sysadmin-panel__head">
                <div>
                    <span class="page-kicker">DATABASE UPGRADES</span>
                    <h2>Migration center</h2>
                    <p>Only pending changes are shown here. Applied history stays collapsed.</p>
                </div>
                <span class="sysadmin-status-chip <?=$schemaCurrent?'is-good':'is-warning'?>"><?=$schemaCurrent?'Up to date':$pending.' pending'?></span>
            </div>
            <?php if ($pendingMigrations) : ?>
                <div class="sysadmin-migration-list">
                    <?php foreach ($pendingMigrations as $m) : ?>
                        <article>
                            <i>!</i>
                            <div>
                                <strong><?=e($m['version'])?></strong>
                                <p><?=e($m['description'])?></p>
                                <small>Waiting to be applied</small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sysadmin-panel-empty">
                    <i>✓</i>
                    <div>
                        <strong>Database schema is current</strong><span>No database upgrades are waiting to be applied.</span>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (can_manage_migrations()) : ?>
                <form method="post" class="sysadmin-panel-action sysadmin-panel-action--split" data-confirm="Run all pending database migrations? The system will first attempt to create a database safety backup.">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="action" value="run_migrations">
                    <div>
                        <strong><?=$pending?'Upgrade available':'No upgrade required'?></strong><small><?=$pending?'A safety database snapshot is attempted first.':'No schema changes are pending.'?></small>
                    </div>
                    <button class="btn" <?=$pending?'':'disabled'?>><?=$pending?'Run pending migrations':'Up to date'?></button>
                </form>
            <?php endif; ?>
            <?php if ($appliedMigrations) : ?>
                <details class="sysadmin-inline-details">
                    <summary>Migration history <span><?=count($appliedMigrations)?> applied</span></summary>
                    <div class="sysadmin-migration-history">
                        <?php foreach ($appliedMigrations as $m) : ?>
                            <article>
                                <div>
                                    <strong><?=e($m['version'])?></strong><span><?=e($m['description'])?></span>
                                </div>
                                <small><?=!empty($m['applied_at'])?e(date('M j, Y',strtotime($m['applied_at']))):'Applied'?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </section>
        <section class="panel sysadmin-panel sysadmin-backup-create">
            <div class="panel__head sysadmin-panel__head">
                <div>
                    <span class="page-kicker">RECOVERY POINTS</span>
                    <h2>Create backup</h2>
                    <p>Choose the backup type based on what you need to recover.</p>
                </div>
            </div>
            <div class="sysadmin-backup-options">
                <form method="post" class="sysadmin-backup-option">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="action" value="backup_database">
                    <span class="sysadmin-backup-option__icon">DB</span>
                    <div>
                        <strong>Database backup</strong><small>Schema, settings, accounts, content, cases, logs, and metadata.</small>
                    </div>
                    <button class="btn btn--soft">Create SQL</button>
                </form>
                <form method="post" class="sysadmin-backup-option <?=$zipEnabled?'is-recommended':'is-disabled'?>">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="action" value="backup_full">
                    <span class="sysadmin-backup-option__icon">ZIP</span>
                    <div>
                        <strong>Full system backup</strong><small>Database, uploads, and protected encryption key in one recovery archive.</small>
                    </div>
                    <button class="btn btn--soft" <?=$zipEnabled?'':'disabled'?>><?=$zipEnabled?'Create ZIP':'Unavailable'?></button>
                </form>
            </div>
            <div class="sysadmin-note">
                <strong>Recovery rule</strong><span>SQL backups do not contain uploaded files. Use a full ZIP backup when you need a complete disaster-recovery point.</span>
            </div>
        </section>
    </div>
    <section class="panel sysadmin-panel sysadmin-automation-panel">
        <div class="panel__head sysadmin-panel__head">
            <div>
                <span class="page-kicker">AUTOMATION</span>
                <h2>Scheduled maintenance</h2>
                <p>Runs publication release, email processing, and configured backup tasks through the included CLI runner.</p>
            </div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="run_scheduled">
                <button class="btn btn--soft">Run tasks now</button>
            </form>
        </div>
        <div class="sysadmin-automation-row">
            <div>
                <span>Backup mode</span><strong><?=$autoBackup?'Automatic':'Manual'?></strong>
            </div>
            <div>
                <span>Retention target</span><strong><?=$retention['target']?> recovery points</strong>
            </div>
            <div>
                <span>Older preserved</span><strong><?=$retention['excess']?></strong>
            </div>
            <div>
                <span>Last scheduled run</span><strong><?=$lastRun!==''?e(date('M j · g:i A',strtotime($lastRun))):'Not recorded'?></strong>
            </div>
        </div>
        <?php if ($retention['excess']>0) : ?>
            <div class="sysadmin-note is-warning">
                <strong>Retention review</strong><span><?=$retention['excess']?> older backup<?=$retention['excess']===1?' is':'s are'?> intentionally preserved. This system does not delete recovery files automatically.</span>
            </div>
        <?php endif; ?>
    </section>
    <section class="panel sysadmin-panel sysadmin-backup-history">
        <div class="panel__head sysadmin-panel__head">
            <div>
                <span class="page-kicker">RECOVERY HISTORY</span>
                <h2>Protected backups</h2>
                <p>Verify, download, or restore system-generated recovery files.</p>
            </div>
            <span class="sysadmin-muted-pill"><?=$backupCount?> backup<?=$backupCount===1?'':'s'?> · <?=e(format_file_size($backupTotalBytes))?></span>
        </div>
        <div class="table-wrap sysadmin-simple-table">
            <table>
                <thead>
                    <tr>
                        <th>Backup</th>
                        <th>Type / size</th>
                        <th>Created</th>
                        <th>Verification</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $b) : ?>
                        <tr>
                            <td>
                                <div class="cell-title sysadmin-backup-name">
                                    <strong><?=e($b['filename'])?></strong><span>SHA-256 <?=e(substr((string)($b['checksum_sha256']??''),0,16))?><?=!empty($b['checksum_sha256'])?'…':''?>
                                    <?php if (!empty($b['restored_at'])) : ?>
                                        · restored <?=e(date('M j, Y',strtotime($b['restored_at'])))?>
                                    <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td><strong><?=e(ucfirst((string)$b['backup_type']))?></strong><small class="sysadmin-table-subtext"><?=e(format_file_size((int)$b['file_size']))?></small></td>
                            <td><?=e(date('M j, Y · g:i A',strtotime($b['created_at'])))?><small class="sysadmin-table-subtext"><?=e($b['created_by_name']??'System')?></small></td>
                            <td>
                                <?php $vs = (string)($b['verification_status'] ?? ''); ?>
                                <span class="sysadmin-status-chip <?=$vs==='verified'?'is-good':($vs==='failed'?'is-warning':'')?>"><?=e($vs!==''?ucfirst($vs):'Not verified')?></span>
                                <?php if (!empty($b['verified_at'])) : ?>
                                    <small class="sysadmin-table-subtext"><?=e(date('M j, Y',strtotime($b['verified_at'])))?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="verify_backup">
                                        <input type="hidden" name="filename" value="<?=e($b['filename'])?>">
                                        <button class="btn btn--soft btn--small">Verify</button>
                                    </form>
                                    <a class="btn btn--soft btn--small" href="download-backup.php?file=<?=rawurlencode($b['filename'])?>">Download</a>
                                    <?php if (str_ends_with(strtolower($b['filename']), '.sql')) : ?>
                                        <details class="restore-details">
                                            <summary class="btn btn--small btn--danger">Restore</summary>
                                            <form method="post" class="restore-popover">
                                                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="filename" value="<?=e($b['filename'])?>">
                                                <strong>Restore this database?</strong><span>This replaces current database tables with this recovery point. Uploaded files are not removed.</span>
                                                <input name="confirm" autocomplete="off" placeholder="Type RESTORE" required pattern="RESTORE">
                                                <button class="btn btn--danger btn--small">Confirm restore</button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$backups) : ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    No protected backups have been created yet.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php
admin_footer();
?>
