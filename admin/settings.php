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
if (!can_manage_settings()) deny_access('Only the System Administrator can change global settings.');
ensure_admin_platform_tables($pdo);

$settingDefaults = [
'portal_name' => 'University Student Council', 'university_name' => 'Don Mariano Marcos Memorial State University', 'usc_email' => 'usc@dmmmsu.edu.ph', 'usc_facebook' => 'https://www.facebook.com/usc.dmmmsu',
'maintenance_mode' => '0', 'default_upload_limit' => '10', 'login_max_attempts' => '5', 'login_lockout_minutes' => '5', 'session_idle_hours' => '12',
'password_max_age_days' => '180', 'dormant_account_days' => '90', 'security_require_2fa_for_admin' => '0', 'security_2fa_required_roles' => 'admin', 'password_history_count' => '5',
'esumbong_sla_urgent_hours' => '24', 'esumbong_sla_high_hours' => '48', 'esumbong_sla_normal_hours' => '120', 'esumbong_sla_low_hours' => '168', 'esumbong_suggestion_feedback_cooldown_hours' => '72', 'esumbong_language_moderation_enabled' => '1', 'esumbong_formal_language_mode' => 'flag', 'privacy_retention_days' => '730', 'esumbong_attachment_max_files' => '3', 'esumbong_attachment_max_mb' => '5',
'backup_reminder_days' => '7', 'auto_publish_scheduled' => '1', 'auto_backup_enabled' => '0', 'auto_backup_type' => 'database', 'auto_backup_interval_hours' => '24', 'auto_backup_retention_count' => '14',
'notification_email_enabled' => '0', 'esumbong_email_updates_enabled' => '1', 'email_from_name' => 'DMMMSU USC', 'email_from_address' => 'usc@dmmmsu.edu.ph',
'notification_retention_days' => '180', 'storage_warning_percent' => '80', 'storage_critical_percent' => '90', 'offsite_backup_enabled' => '0', 'offsite_backup_path' => '', 'media_auto_optimize' => '1', 'media_thumbnail_enabled' => '1', 'security_rate_limit_tracking_per_hour' => '30', 'security_rate_limit_uploads_per_hour' => '40'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = [];
    $new = [];
    foreach ($settingDefaults as $k => $default) {
        $old[$k] = system_setting($pdo, $k, $default);
        if ($k === 'security_2fa_required_roles') {
            $roles = (array)($_POST[$k] ?? []);
            $roles = array_values(array_intersect(array_keys(admin_roles()), array_map('strval', $roles)));
            $value = implode(',', $roles);
        } else {
            $value = trim((string)($_POST[$k] ?? $old[$k]));
        }

        if ($k === 'login_max_attempts') $value = (string)max(3, min(10, (int)$value));
        elseif ($k === 'login_lockout_minutes') $value = (string)max(1, min(60, (int)$value));
        elseif ($k === 'session_idle_hours') $value = (string)max(1, min(168, (int)$value));
        elseif ($k === 'default_upload_limit') $value = (string)max(1, min(50, (int)$value));
        elseif ($k === 'password_max_age_days') $value = (string)max(30, min(730, (int)$value));
        elseif ($k === 'dormant_account_days') $value = (string)max(30, min(730, (int)$value));
        elseif (str_starts_with($k, 'esumbong_sla_')) $value = (string)max(1, min(720, (int)$value));
        elseif ($k === 'esumbong_suggestion_feedback_cooldown_hours') $value = (string)max(24, min(720, (int)$value));
        elseif ($k === 'esumbong_formal_language_mode') $value = in_array($value, ['flag', 'block'], true)?$value:'flag';
        elseif ($k === 'privacy_retention_days') $value = (string)max(30, min(3650, (int)$value));
        elseif ($k === 'backup_reminder_days') $value = (string)max(1, min(90, (int)$value));
        elseif ($k === 'password_history_count') $value = (string)max(3, min(12, (int)$value));
        elseif ($k === 'esumbong_attachment_max_files') $value = (string)max(1, min(5, (int)$value));
        elseif ($k === 'esumbong_attachment_max_mb') $value = (string)max(1, min(10, (int)$value));
        elseif ($k === 'auto_backup_interval_hours') $value = (string)max(1, min(168, (int)$value));
        elseif ($k === 'auto_backup_retention_count') $value = (string)max(3, min(100, (int)$value));
        elseif ($k === 'auto_backup_type') $value = in_array($value, ['database', 'full'], true)?$value:'database';
        elseif ($k === 'notification_retention_days') $value = (string)max(30, min(1095, (int)$value));
        elseif ($k === 'storage_warning_percent') $value = (string)max(50, min(95, (int)$value));
        elseif ($k === 'storage_critical_percent') $value = (string)max(60, min(99, (int)$value));
        elseif (in_array($k, ['security_rate_limit_tracking_per_hour', 'security_rate_limit_uploads_per_hour'], true)) $value = (string)max(5, min(500, (int)$value));
        elseif ($k === 'offsite_backup_path') $value = substr($value, 0, 500);
        elseif (in_array($k, ['maintenance_mode', 'security_require_2fa_for_admin', 'auto_publish_scheduled', 'auto_backup_enabled', 'notification_email_enabled', 'esumbong_email_updates_enabled', 'esumbong_language_moderation_enabled', 'offsite_backup_enabled', 'media_auto_optimize', 'media_thumbnail_enabled'], true)) $value = $value === '1'?'1':'0';
        elseif ($k === 'email_from_address' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) $value = $old[$k] ?? 'usc@dmmmsu.edu.ph';

        save_system_setting($pdo, $k, $value);
        $new[$k] = $value;
    }

    admin_log($pdo, 'settings', 'Updated system settings', 'Updated global portal and security configuration', null, null, $old, $new);
    header('Location: settings.php?saved=1');
    exit;
}

$settings = [];
foreach ($settingDefaults as $k => $d) $settings[$k] = system_setting($pdo, $k, $d);
$requiredRoles = array_filter(array_map('trim', explode(',', (string)$settings['security_2fa_required_roles'])));

admin_header('System Settings', 'System administration');
?>
<div class="settings-page settings-page--v80">
    <?php if (isset($_GET['saved'])) : ?>
        <div class="notice settings-notice">
            System settings saved successfully.
        </div>
    <?php endif; ?>
    <form method="post" class="settings-form-v80" data-unsaved-warning="1">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <div class="settings-workspace-head">
            <div>
                <span class="page-kicker">CONFIGURATION</span>
                <h2>Manage system settings</h2>
                <p>Only configurable policies are kept here. Monitoring, backups, storage health, and recovery operations remain in their dedicated pages.</p>
            </div>
            <div class="settings-workspace-links">
                <a class="btn btn--soft btn--compact" href="system-health.php">System Health</a>
                <a class="btn btn--soft btn--compact" href="maintenance.php">Maintenance</a>
            </div>
        </div>
        <nav class="settings-category-nav" aria-label="System settings categories">
            <button type="button" class="is-active" data-settings-tab="general">General</button>
            <button type="button" data-settings-tab="security">Security</button>
            <button type="button" data-settings-tab="esumbong">E-Sumbong</button>
            <button type="button" data-settings-tab="notifications">Notifications</button>
            <button type="button" data-settings-tab="automation">Automation</button>
        </nav>
        <div class="settings-panel-stack">
            <section class="settings-tab-panel is-active" data-settings-panel="general">
                <div class="settings-tab-heading">
                    <div>
                        <span class="page-kicker">GENERAL</span>
                        <h2>Identity &amp; platform</h2>
                        <p>Core public-facing information and the few platform-wide behaviors that need direct configuration.</p>
                    </div>
                </div>
                <div class="settings-split-layout settings-split-layout--equal">
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Institution identity</h3>
                                <p>Used across the public website and administration portal.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80 settings-grid-v80--2">
                            <label class="settings-control-v80">
                                <span>Portal name</span>
                                <input name="portal_name" value="<?=e($settings['portal_name'])?>">
                            </label>
                            <label class="settings-control-v80">
                                <span>University name</span>
                                <input name="university_name" value="<?=e($settings['university_name'])?>">
                            </label>
                            <label class="settings-control-v80">
                                <span>USC email</span>
                                <input type="email" name="usc_email" value="<?=e($settings['usc_email'])?>">
                            </label>
                            <label class="settings-control-v80">
                                <span>USC Facebook URL</span>
                                <input name="usc_facebook" value="<?=e($settings['usc_facebook'])?>">
                            </label>
                        </div>
                    </section>
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Platform behavior</h3>
                                <p>Controls upload limits and public-site availability.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80">
                            <label class="settings-control-v80">
                                <span>Default upload limit</span>
                                <small>Applies to administrator uploads outside the Media Library.</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="50" name="default_upload_limit" value="<?=e($settings['default_upload_limit'])?>">
                                    <span>MB</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Maintenance mode</span>
                                <small>Restricts public pages while administrator access remains available.</small>
                                <select name="maintenance_mode">
                                    <option value="0" <?=$settings['maintenance_mode']==='0'?'selected':''?>>Off</option>
                                    <option value="1" <?=$settings['maintenance_mode']==='1'?'selected':''?>>On</option>
                                </select>
                            </label>
                        </div>
                        <div class="settings-state-note <?=($settings['maintenance_mode']==='1')?'is-warning':'is-good'?>">
                            <span></span>
                            <div>
                                <strong><?=($settings['maintenance_mode']==='1')?'Maintenance mode enabled':'Normal public operation'?></strong>
                                <small><?=($settings['maintenance_mode']==='1')?'Public access is restricted; administrators can still work normally.':'Public services are available.'?></small>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
            <section class="settings-tab-panel" data-settings-panel="security">
                <div class="settings-tab-heading">
                    <div>
                        <span class="page-kicker">SECURITY</span>
                        <h2>Authentication policy</h2>
                        <p>Organize administrator sign-in rules, session handling, password policy, and required two-step verification.</p>
                    </div>
                    <a class="btn btn--soft btn--compact" href="account-security.php">Account Security</a>
                </div>
                <div class="settings-split-layout settings-split-layout--equal">
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Access protection</h3>
                                <p>Protect sign-in attempts and unattended administrator sessions.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80 settings-grid-v80--3">
                            <label class="settings-control-v80">
                                <span>Failed attempts</span><small>Before temporary lockout.</small>
                                <input type="number" min="3" max="10" name="login_max_attempts" value="<?=e($settings['login_max_attempts'])?>">
                            </label>
                            <label class="settings-control-v80">
                                <span>Lockout duration</span><small>Recommended: 5–15 minutes.</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="60" name="login_lockout_minutes" value="<?=e($settings['login_lockout_minutes'])?>">
                                    <span>min</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Session idle timeout</span><small>Inactive sessions are revoked.</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="168" name="session_idle_hours" value="<?=e($settings['session_idle_hours'])?>">
                                    <span>hours</span>
                                </div>
                            </label>
                        </div>
                    </section>
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Password policy</h3>
                                <p>Keep administrator credentials refreshed without making resets excessive.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80 settings-grid-v80--3">
                            <label class="settings-control-v80">
                                <span>Password age warning</span><small>Flags older passwords.</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="30" max="730" name="password_max_age_days" value="<?=e($settings['password_max_age_days'])?>">
                                    <span>days</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Dormant account threshold</span><small>Used for warnings only.</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="30" max="730" name="dormant_account_days" value="<?=e($settings['dormant_account_days'])?>">
                                    <span>days</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Password history</span><small>Recent passwords that cannot be reused.</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="3" max="12" name="password_history_count" value="<?=e($settings['password_history_count'])?>">
                                    <span>passwords</span>
                                </div>
                            </label>
                        </div>
                    </section>
                </div>
                <section class="panel settings-card-v80">
                    <div class="settings-card-v80__head">
                        <div>
                            <h3>Two-step verification</h3>
                            <p>Select which administrative roles must use an authenticator.</p>
                        </div>
                    </div>
                    <div class="settings-card-v80__body">
                        <div class="settings-role-grid-v80">
                            <?php foreach (admin_roles() as $roleKey => $roleLabel) : ?>
                                <label class="settings-role-choice">
                                    <input type="checkbox" name="security_2fa_required_roles[]" value="<?=e($roleKey)?>" <?=in_array($roleKey,$requiredRoles,true)?'checked':''?>>
                                    <?=e($roleLabel)?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <details class="settings-advanced-v80">
                    <summary><span>Advanced security controls</span><small>Rate limits for public tracking and administrative uploads</small></summary>
                    <div class="settings-advanced-v80__body settings-grid-v80 settings-grid-v80--2">
                        <label class="settings-control-v80 settings-control-v80--short">
                            <span>Public tracking searches</span>
                            <div class="settings-input-suffix">
                                <input type="number" min="5" max="500" name="security_rate_limit_tracking_per_hour" value="<?=e($settings['security_rate_limit_tracking_per_hour'])?>">
                                <span>/ hour</span>
                            </div>
                        </label>
                        <label class="settings-control-v80 settings-control-v80--short">
                            <span>Administrative uploads</span>
                            <div class="settings-input-suffix">
                                <input type="number" min="5" max="500" name="security_rate_limit_uploads_per_hour" value="<?=e($settings['security_rate_limit_uploads_per_hour'])?>">
                                <span>/ hour</span>
                            </div>
                        </label>
                    </div>
                </details>
            </section>
            <section class="settings-tab-panel" data-settings-panel="esumbong">
                <div class="settings-tab-heading">
                    <div>
                        <span class="page-kicker">E-SUMBONG</span>
                        <h2>Case handling policy</h2>
                        <p>Configure review reminders and evidence limits used by the concern-management workflow.</p>
                    </div>
                    <a class="btn btn--soft btn--compact" href="concerns.php">E-Sumbong cases</a>
                </div>
                <div class="settings-split-layout settings-split-layout--equal">
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Review reminders</h3>
                                <p>Suggested follow-up intervals based on priority. These are guidance only and do not mark a case late or failed when the suggested time passes.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80 settings-grid-v80--2">
                            <label class="settings-control-v80">
                                <span>Urgent</span><small>Suggested reminder: 24 hours</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="720" name="esumbong_sla_urgent_hours" value="<?=e($settings['esumbong_sla_urgent_hours'])?>">
                                    <span>hours</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>High</span><small>Suggested reminder: 48 hours</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="720" name="esumbong_sla_high_hours" value="<?=e($settings['esumbong_sla_high_hours'])?>">
                                    <span>hours</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Normal</span><small>Suggested reminder: 5 days</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="720" name="esumbong_sla_normal_hours" value="<?=e($settings['esumbong_sla_normal_hours'])?>">
                                    <span>hours</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Low</span><small>Suggested reminder: 7 days</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="720" name="esumbong_sla_low_hours" value="<?=e($settings['esumbong_sla_low_hours'])?>">
                                    <span>hours</span>
                                </div>
                            </label>
                        </div>
                    </section>
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Submission &amp; evidence</h3>
                                <p>Control lightweight-submission cooldowns and private evidence limits.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80">
                            <label class="settings-control-v80">
                                <span>Suggestion &amp; Feedback cooldown</span>
                                <small>Recommended: 72 hours (3 days). Concern and Complaint remain blocked while an active case of that type exists.</small>
                                <div class="settings-input-suffix">
                                    <input type="number" min="24" max="720" name="esumbong_suggestion_feedback_cooldown_hours" value="<?=e($settings['esumbong_suggestion_feedback_cooldown_hours'])?>">
                                    <span>hours</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Student language moderation</span>
                                <small>Checks English and Filipino profanity in the Subject and Message.</small>
                                <select name="esumbong_language_moderation_enabled">
                                    <option value="1" <?=$settings['esumbong_language_moderation_enabled']==='1'?'selected':''?>>Enabled</option>
                                    <option value="0" <?=$settings['esumbong_language_moderation_enabled']==='0'?'selected':''?>>Disabled</option>
                                </select>
                            </label>
                            <label class="settings-control-v80">
                                <span>Concern / Complaint language handling</span>
                                <small>Flag is recommended so quoted offensive wording in an incident report is not suppressed.</small>
                                <select name="esumbong_formal_language_mode">
                                    <option value="flag" <?=$settings['esumbong_formal_language_mode']==='flag'?'selected':''?>>Allow and flag for staff review</option>
                                    <option value="block" <?=$settings['esumbong_formal_language_mode']==='block'?'selected':''?>>Block submission</option>
                                </select>
                            </label>
                            <label class="settings-control-v80">
                                <span>Attachments per concern</span>
                                <input type="number" min="1" max="5" name="esumbong_attachment_max_files" value="<?=e($settings['esumbong_attachment_max_files'])?>">
                            </label>
                            <label class="settings-control-v80">
                                <span>Maximum file size</span>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="10" name="esumbong_attachment_max_mb" value="<?=e($settings['esumbong_attachment_max_mb'])?>">
                                    <span>MB</span>
                                </div>
                            </label>
                        </div>
                        <div class="settings-managed-note">
                            The cooldown resets automatically from each Suggestion or Feedback submission time. When language moderation is enabled, Suggestion/Feedback profanity is blocked; Concern/Complaint can be flagged for contextual staff review or blocked according to the selected policy. Evidence limits apply across the concern form and officer-side case handling screens.
                        </div>
                    </section>
                </div>
            </section>
            <section class="settings-tab-panel" data-settings-panel="notifications">
                <div class="settings-tab-heading">
                    <div>
                        <span class="page-kicker">NOTIFICATIONS</span>
                        <h2>Email delivery</h2>
                        <p>Control whether platform and student-visible E-Sumbong updates are queued for email.</p>
                    </div>
                </div>
                <div class="settings-split-layout settings-split-layout--equal">
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Delivery controls</h3>
                                <p>Email delivery depends on the PHP server mail configuration.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-list-v80">
                            <label class="settings-row-v80">
                                <div>
                                    <span>Email notifications</span><small>Administrative security, case, publishing, promotion, and system notifications.</small>
                                </div>
                                <select name="notification_email_enabled">
                                    <option value="0" <?=$settings['notification_email_enabled']==='0'?'selected':''?>>Off</option>
                                    <option value="1" <?=$settings['notification_email_enabled']==='1'?'selected':''?>>On</option>
                                </select>
                            </label>
                            <label class="settings-row-v80">
                                <div>
                                    <span>Student E-Sumbong email updates</span><small>Queues receipts and student-visible status updates when a contact email was provided.</small>
                                </div>
                                <select name="esumbong_email_updates_enabled">
                                    <option value="0" <?=$settings['esumbong_email_updates_enabled']==='0'?'selected':''?>>Off</option>
                                    <option value="1" <?=$settings['esumbong_email_updates_enabled']==='1'?'selected':''?>>On</option>
                                </select>
                            </label>
                        </div>
                    </section>
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Sender identity</h3>
                                <p>Displayed on outgoing platform emails.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80">
                            <label class="settings-control-v80">
                                <span>Sender name</span>
                                <input name="email_from_name" value="<?=e($settings['email_from_name'])?>">
                            </label>
                            <label class="settings-control-v80">
                                <span>Sender address</span>
                                <input type="email" name="email_from_address" value="<?=e($settings['email_from_address'])?>">
                            </label>
                        </div>
                    </section>
                </div>
                <details class="settings-advanced-v80">
                    <summary><span>Notification retention</span><small>How long old notification records remain before maintenance dismisses them</small></summary>
                    <div class="settings-advanced-v80__body">
                        <label class="settings-control-v80 settings-control-v80--short">
                            <span>Retention period</span>
                            <div class="settings-input-suffix">
                                <input type="number" min="30" max="1095" name="notification_retention_days" value="<?=e($settings['notification_retention_days'])?>">
                                <span>days</span>
                            </div>
                        </label>
                    </div>
                </details>
            </section>
            <section class="settings-tab-panel" data-settings-panel="automation">
                <div class="settings-tab-heading">
                    <div>
                        <span class="page-kicker">AUTOMATION</span>
                        <h2>Publishing, backup &amp; media</h2>
                        <p>Keep scheduled publishing, backups, and media processing aligned with the maintenance runner.</p>
                    </div>
                    <a class="btn btn--soft btn--compact" href="disaster-recovery.php">Disaster Recovery</a>
                </div>
                <div class="settings-split-layout settings-split-layout--equal">
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Publishing &amp; backup</h3>
                                <p>Background jobs that require the maintenance runner or cron/task scheduler.</p>
                            </div>
                        </div>
                        <div class="settings-card-v80__body settings-list-v80">
                            <label class="settings-row-v80">
                                <div>
                                    <span>Scheduled publishing</span><small>Publishes due News &amp; Updates entries automatically.</small>
                                </div>
                                <select name="auto_publish_scheduled">
                                    <option value="1" <?=$settings['auto_publish_scheduled']==='1'?'selected':''?>>Enabled</option>
                                    <option value="0" <?=$settings['auto_publish_scheduled']==='0'?'selected':''?>>Disabled</option>
                                </select>
                            </label>
                            <label class="settings-row-v80">
                                <div>
                                    <span>Automatic backup</span><small>Requires the maintenance runner to be scheduled on the server.</small>
                                </div>
                                <select name="auto_backup_enabled">
                                    <option value="0" <?=$settings['auto_backup_enabled']==='0'?'selected':''?>>Off</option>
                                    <option value="1" <?=$settings['auto_backup_enabled']==='1'?'selected':''?>>On</option>
                                </select>
                            </label>
                        </div>
                        <div class="settings-card-v80__body settings-grid-v80 settings-grid-v80--3 settings-card-v80__body--bordered">
                            <label class="settings-control-v80">
                                <span>Backup type</span>
                                <select name="auto_backup_type">
                                    <option value="database" <?=$settings['auto_backup_type']==='database'?'selected':''?>>Database SQL</option>
                                    <option value="full" <?=$settings['auto_backup_type']==='full'?'selected':''?>>Full ZIP</option>
                                </select>
                            </label>
                            <label class="settings-control-v80">
                                <span>Backup interval</span>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="168" name="auto_backup_interval_hours" value="<?=e($settings['auto_backup_interval_hours'])?>">
                                    <span>hours</span>
                                </div>
                            </label>
                            <label class="settings-control-v80">
                                <span>Backup reminder</span>
                                <div class="settings-input-suffix">
                                    <input type="number" min="1" max="90" name="backup_reminder_days" value="<?=e($settings['backup_reminder_days'])?>">
                                    <span>days</span>
                                </div>
                            </label>
                        </div>
                    </section>
                    <section class="panel settings-card-v80">
                        <div class="settings-card-v80__head">
                            <div>
                                <h3>Media handling</h3>
                                <p>Automatic image optimization and preview generation for the Media Library.</p>
                            </div>
                            <a class="btn btn--soft btn--compact" href="storage.php">Storage</a>
                        </div>
                        <div class="settings-card-v80__body settings-list-v80">
                            <label class="settings-row-v80">
                                <div>
                                    <span>Media auto-optimization</span><small>Resize oversized uploaded images when supported by the server.</small>
                                </div>
                                <select name="media_auto_optimize">
                                    <option value="1" <?=$settings['media_auto_optimize']==='1'?'selected':''?>>On</option>
                                    <option value="0" <?=$settings['media_auto_optimize']==='0'?'selected':''?>>Off</option>
                                </select>
                            </label>
                            <label class="settings-row-v80">
                                <div>
                                    <span>Generate thumbnails</span><small>Create lightweight previews for supported image files.</small>
                                </div>
                                <select name="media_thumbnail_enabled">
                                    <option value="1" <?=$settings['media_thumbnail_enabled']==='1'?'selected':''?>>On</option>
                                    <option value="0" <?=$settings['media_thumbnail_enabled']==='0'?'selected':''?>>Off</option>
                                </select>
                            </label>
                        </div>
                    </section>
                </div>
                <details class="settings-advanced-v80">
                    <summary><span>Advanced backup &amp; storage thresholds</span><small>Retention target and disk-usage warning levels</small></summary>
                    <div class="settings-advanced-v80__body settings-grid-v80 settings-grid-v80--3">
                        <label class="settings-control-v80">
                            <span>Backup retention target</span><small>Older backups are flagged for review, not deleted.</small>
                            <input type="number" min="3" max="100" name="auto_backup_retention_count" value="<?=e($settings['auto_backup_retention_count'])?>">
                        </label>
                        <label class="settings-control-v80">
                            <span>Storage warning</span>
                            <div class="settings-input-suffix">
                                <input type="number" min="50" max="95" name="storage_warning_percent" value="<?=e($settings['storage_warning_percent'])?>">
                                <span>%</span>
                            </div>
                        </label>
                        <label class="settings-control-v80">
                            <span>Storage critical</span>
                            <div class="settings-input-suffix">
                                <input type="number" min="60" max="99" name="storage_critical_percent" value="<?=e($settings['storage_critical_percent'])?>">
                                <span>%</span>
                            </div>
                        </label>
                    </div>
                    <div class="settings-managed-elsewhere">
                        <strong>Secondary/off-site backup</strong><span>Configure the destination and copy policy in Disaster Recovery to avoid duplicate controls.</span><a href="disaster-recovery.php">Open Disaster Recovery →</a>
                    </div>
                </details>
            </section>
        </div>
        <div class="settings-savebar-v80">
            <div>
                <strong>System-wide settings</strong><span>Changes apply to the whole administration portal where relevant.</span>
            </div>
            <button class="btn">Save settings</button>
        </div>
    </form>
</div>
<script>
(function () {
    const tabs = [...document.querySelectorAll('[data-settings-tab]')];
    const panels = [...document.querySelectorAll('[data-settings-panel]')];
    if (!tabs.length || !panels.length) return;
    const activate = (name) => {
        tabs.forEach(btn => btn.classList.toggle('is-active', btn.dataset.settingsTab === name));
        panels.forEach(panel => panel.classList.toggle('is-active', panel.dataset.settingsPanel === name));
        try { sessionStorage.setItem('uscSettingsTab', name); } catch (e) { }
    };
    tabs.forEach(btn => btn.addEventListener('click', () => activate(btn.dataset.settingsTab)));
    let initial = 'general';
    try {
        const saved = sessionStorage.getItem('uscSettingsTab');
        if (saved && tabs.some(t => t.dataset.settingsTab === saved)) initial = saved;
    } catch (e) { }
    activate(initial);
})();
</script>
<?php
admin_footer();
?>
