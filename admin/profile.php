<?php
require_once '../config/database.php';
require_once '_layout.php';
require_once '../config/helpers.php';
require_admin();
ensure_admin_platform_tables($pdo);
$id = (int)($_SESSION['admin_id'] ?? 0);
$error = '';
$notice = '';

function load_my_admin(PDO $pdo, int $id): array|false {
    $st = $pdo->prepare('SELECT id,username,email,profile_image,password_hash,full_name,role,campus,status,force_password_change,last_login,created_at,password_changed_at,two_factor_enabled,two_factor_secret FROM admins WHERE id=? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch();
}
$account = load_my_admin($pdo, $id);
if (!$account) {
    header('Location: logout.php');
    exit;
}
$forceRequired = !empty($account['force_password_change']);
$required2FARoles = admin_required_2fa_roles($pdo);
$twoFactorRequired = in_array((string)$account['role'], $required2FARoles, true);
$passwordRequested = $forceRequired;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'profile_save');
    if ($forceRequired && $action !== 'profile_save') {
        $error = 'Complete the required password change before changing other security settings.';
    } elseif ($action === 'revoke_others') {
        $count = admin_revoke_other_sessions($pdo, $id);
        admin_log($pdo, 'security', 'Signed out other sessions', $count.' other session'.($count === 1?'':'s').' revoked', 'admin', $id, null, ['revoked_sessions' => $count]);
        header('Location: profile.php?sessions_revoked='.$count);
        exit;
    } elseif ($action === 'save_notifications') {
        $enabled = array_values(array_intersect(array_keys(admin_notification_categories()), (array)($_POST['notifications'] ?? [])));
        admin_save_notification_preferences($pdo, $id, $enabled);
        admin_log($pdo, 'notifications', 'Updated notification preferences', 'Personal notification preferences changed', 'admin', $id, null, ['enabled' => $enabled]);
        header('Location: profile.php?preferences_saved=1');
        exit;
    } elseif ($action === 'begin_2fa') {
        $password = (string)($_POST['current_password'] ?? '');
        if (!password_verify($password, (string)$account['password_hash'])) $error = 'Enter your current password before setting up two-step verification.';
        else {
            $_SESSION['two_factor_setup_secret'] = admin_totp_secret();
            $_SESSION['two_factor_setup_at'] = time();
            header('Location: profile.php?two_factor_setup=1');
            exit;
        }
    } elseif ($action === 'confirm_2fa') {
        $secret = (string)($_SESSION['two_factor_setup_secret'] ?? '');
        if ($secret === '' || (int)($_SESSION['two_factor_setup_at'] ?? 0)<time()-600) {
            $error = 'The two-step verification setup expired. Start again.';
        }
        elseif (!admin_verify_totp($secret, (string)($_POST['code'] ?? ''))) $error = 'The verification code is incorrect. Check your authenticator app and try again.';
        else {
            $storedSecret = db_table_exists($pdo, 'admin_recovery_codes')?admin_encrypt_2fa_secret($secret):$secret;
            $pdo->prepare('UPDATE admins SET two_factor_enabled=1,two_factor_secret=? WHERE id=?')->execute([$storedSecret, $id]);
            $codes = admin_generate_recovery_codes($pdo, $id, 8);
            if ($codes) $_SESSION['new_recovery_codes'] = $codes;
            unset($_SESSION['two_factor_setup_secret'], $_SESSION['two_factor_setup_at']);
            admin_log($pdo, 'security', 'Enabled two-step verification', 'Authenticator verification enabled and recovery codes generated', 'admin', $id, ['two_factor' => false], ['two_factor' => true, 'recovery_codes' => count($codes)]);
            header('Location: profile.php?two_factor_enabled=1');
            exit;
        }
    } elseif ($action === 'cancel_2fa_setup') {
        unset($_SESSION['two_factor_setup_secret'], $_SESSION['two_factor_setup_at']);
        header('Location: profile.php');
        exit;
    } elseif ($action === 'regenerate_recovery_codes') {
        $password = (string)($_POST['current_password'] ?? '');
        $code = (string)($_POST['code'] ?? '');
        if (empty($account['two_factor_enabled'])) $error = 'Enable two-step verification before generating recovery codes.';
        elseif (!password_verify($password, (string)$account['password_hash'])) $error = 'Enter your current password to regenerate recovery codes.';
        elseif (!admin_verify_totp((string)($account['two_factor_secret'] ?? ''), $code)) $error = 'Enter a valid authenticator code to regenerate recovery codes.';
        else {
            $codes = admin_generate_recovery_codes($pdo, $id, 8);
            $_SESSION['new_recovery_codes'] = $codes;
            admin_log($pdo, 'security', 'Regenerated recovery codes', 'Previous unused recovery codes were replaced', 'admin', $id, null, ['recovery_codes' => count($codes)]);
            header('Location: profile.php?recovery_codes=1');
            exit;
        }
    } elseif ($action === 'disable_2fa') {
        $password = (string)($_POST['current_password'] ?? '');
        $code = (string)($_POST['code'] ?? '');
        if ($twoFactorRequired) $error = 'Two-step verification is required for your administrator role and cannot be disabled.';
        elseif (!password_verify($password, (string)$account['password_hash'])) $error = 'Enter your current password to disable two-step verification.';
        elseif (!admin_verify_totp((string)($account['two_factor_secret'] ?? ''), $code)) $error = 'Enter a valid authenticator code to disable two-step verification.';
        else {
            $pdo->prepare('UPDATE admins SET two_factor_enabled=0,two_factor_secret=NULL WHERE id=?')->execute([$id]);
            if (db_table_exists($pdo, 'admin_recovery_codes')) $pdo->prepare('DELETE FROM admin_recovery_codes WHERE admin_id=?')->execute([$id]);
            admin_log($pdo, 'security', 'Disabled two-step verification', 'Authenticator verification disabled', 'admin', $id, ['two_factor' => true], ['two_factor' => false]);
            header('Location: profile.php?two_factor_disabled=1');
            exit;
        }
    } else {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $passwordRequested = $forceRequired || ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '');
        $profileImage = $account['profile_image'] ?? null;

        if ($fullName === '' || $username === '') $error = 'Full name and username are required.';
        elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';
        elseif ($forceRequired && $newPassword === '') $error = 'A System Administrator reset your password. Create a new password before continuing.';
        elseif ($newPassword !== '' && admin_password_strength_error($newPassword) !== null) $error = (string)admin_password_strength_error($newPassword);
        elseif ($newPassword !== '' && $newPassword !== $confirmPassword) $error = 'New password and confirmation do not match.';
        elseif ($newPassword !== '' && !password_verify($currentPassword, (string)$account['password_hash'])) $error = 'Enter your current or temporary password to create a new password.';
        elseif ($newPassword !== '' && (password_verify($newPassword, (string)$account['password_hash']) || admin_password_reused($pdo, $id, $newPassword, (int)(system_setting($pdo, 'password_history_count', '5') ?? 5)))) $error = 'Choose a password you have not used recently.';

        if ($error === '') {
            $originalProfileImage = $profileImage;
            $newProfileImage = null;
            try {
                if (!empty($_POST['remove_profile_image'])) $profileImage = null;
                if (!empty($_FILES['profile_image']['name'])) {
                    $rl = max(5, (int)(system_setting($pdo, 'security_rate_limit_uploads_per_hour', '40') ?? 40));
                    platform_rate_limit_or_429($pdo, 'admin-upload', ((string)($_SESSION['admin_id'] ?? 0)).'|'.admin_current_ip(), $rl, 3600, 900);
                    $newProfileImage = admin_store_profile_image($_FILES['profile_image']);
                    $profileImage = $newProfileImage;
                }
                $oldValues = ['full_name' => $account['full_name'], 'username' => $account['username'], 'email' => $account['email']];
                if ($newPassword !== '') {
                    admin_record_password_history($pdo, $id, (string)$account['password_hash']);
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE admins SET full_name=?,username=?,email=?,profile_image=?,password_hash=?,force_password_change=0,password_changed_at=NOW() WHERE id=?')->execute([$fullName, $username, $email?:null, $profileImage, $hash, $id]);
                    $_SESSION['force_password_change'] = 0;
                    admin_revoke_other_sessions($pdo, $id);
                } else {
                    $pdo->prepare('UPDATE admins SET full_name=?,username=?,email=?,profile_image=? WHERE id=?')->execute([$fullName, $username, $email?:null, $profileImage, $id]);
                }
                if ($originalProfileImage && $originalProfileImage !== $profileImage) admin_delete_profile_image($originalProfileImage);
                $_SESSION['admin_name'] = $fullName;
                $_SESSION['admin_email'] = $email?:null;
                $_SESSION['admin_profile_image'] = $profileImage;
                admin_log($pdo, 'accounts', 'Updated own profile', $fullName, 'admin', $id, $oldValues, ['full_name' => $fullName, 'username' => $username, 'email' => $email?:null]);
                if ($newPassword !== '') admin_log($pdo, 'security', $forceRequired?'Completed required password change':'Changed own password', $fullName, 'admin', $id, null, ['password_changed' => true]);
                header('Location: profile.php?saved=1');
                exit;
            } catch (PDOException $e) {
                if ($newProfileImage) admin_delete_profile_image($newProfileImage);
                $error = str_contains(strtolower($e->getMessage()), 'duplicate')?'That username is already in use.':'Unable to update your account.';
            } catch (Throwable $e) {
                if ($newProfileImage) admin_delete_profile_image($newProfileImage);
                $error = $e->getMessage();
            }
        }
    }
    $account = load_my_admin($pdo, $id)?:$account;
    $forceRequired = !empty($account['force_password_change']);
}

$account = load_my_admin($pdo, $id)?:$account;
$required2FARoles = admin_required_2fa_roles($pdo);
$twoFactorRequired = in_array((string)$account['role'], $required2FARoles, true);
$recoveryCodeCount = admin_recovery_code_count($pdo, $id);
$newRecoveryCodes = (array)($_SESSION['new_recovery_codes'] ?? []);
unset($_SESSION['new_recovery_codes']);
$sessions = admin_active_sessions($pdo, $id);
$currentSessionHash = !empty($_SESSION['admin_session_key'])?hash('sha256', (string)$_SESSION['admin_session_key']):'';
$preferences = admin_notification_preferences($pdo, $id);
$setupSecret = (string)($_SESSION['two_factor_setup_secret'] ?? '');
$show2FASetup = $setupSecret !== '' && (int)($_SESSION['two_factor_setup_at'] ?? 0)>time()-600;

admin_header('My Account', 'Account settings');
if (isset($_GET['saved'])) echo '<div class="notice">Your account has been updated.</div>';
if (isset($_GET['sessions_revoked'])) echo '<div class="notice">Other administrator sessions were signed out.</div>';
if (isset($_GET['preferences_saved'])) echo '<div class="notice">Notification preferences saved.</div>';
if (isset($_GET['two_factor_enabled'])) echo '<div class="notice">Two-step verification is now enabled.</div>';
if (isset($_GET['two_factor_disabled'])) echo '<div class="notice">Two-step verification was disabled.</div>';
if (isset($_GET['recovery_codes'])) echo '<div class="notice">New recovery codes were generated. Previous unused codes no longer work.</div>';
if ($twoFactorRequired && !$account['two_factor_enabled']) echo '<div class="notice notice--warning"><strong>Two-step verification required.</strong> Your role requires authenticator verification. Set it up before your next sign-in.</div>';
if ($newRecoveryCodes) {
    echo '<div class="notice notice--warning recovery-code-notice"><strong>Save these recovery codes now.</strong><p>Each code works once. They will not be shown again after you leave this page.</p><code>'.e(implode("\n", $newRecoveryCodes)).'</code></div>';
}
if ($forceRequired) echo '<div class="notice notice--warning"><strong>Password change required.</strong> Enter the temporary password, then create a new private password before continuing.</div>';
if (admin_role() === 'admin' && system_setting($pdo, 'security_require_2fa_for_admin', '0') === '1' && empty($account['two_factor_enabled'])) echo '<div class="notice notice--warning"><strong>Two-step verification required.</strong> System Administrator policy requires an authenticator before other administration pages can be used.</div>';
if ($error) echo '<div class="notice notice--error">'.e($error).'</div>';
$scope = $account['campus']?portal_display_name($account['campus']):'University-wide';
$roleLabel = admin_roles()[$account['role']] ?? 'Administrator';
$statusLabel = ucfirst((string)$account['status']);
$lastSignIn = !empty($account['last_login'])?date('M j, Y · g:i A', strtotime($account['last_login'])):'Not recorded';
$passwordChanged = !empty($account['password_changed_at'])?date('M j, Y', strtotime($account['password_changed_at'])):'Not recorded';
$initial = strtoupper(substr(trim((string)$account['full_name']) !== ''?trim((string)$account['full_name']):'A', 0, 1));
?>
<div class="profile-page-shell">
    <aside class="panel profile-overview-card">
        <div class="profile-overview-avatar" id="profilePreviewWrap">
            <?=admin_avatar_html($account['full_name'],$account['profile_image']??null,'profile-avatar-large')?>
        </div>
        <div class="profile-overview-title">
            <h2><?=e($account['full_name'])?></h2>
            <p>@<?=e($account['username'])?></p>
        </div>
        <div class="profile-overview-details">
            <div>
                <span>Role</span><strong><?=e($roleLabel)?></strong>
            </div>
            <div>
                <span>Portal scope</span><strong><?=e($scope)?></strong>
            </div>
            <div>
                <span>Status</span><strong class="profile-overview-status profile-overview-status--<?=e(strtolower((string)$account['status']))?>"><?=e($statusLabel)?></strong>
            </div>
            <div>
                <span>Last sign-in</span><strong><?=e($lastSignIn)?></strong>
            </div>
        </div>
        <?php if (admin_can_permission('activity.view')) : ?>
            <a class="profile-activity-link" href="activity.php?q=<?=urlencode((string)$account['username'])?>">View account activity <span aria-hidden="true">→</span></a>
        <?php endif; ?>
    </aside>
    <main class="profile-page-main">
        <form method="post" enctype="multipart/form-data" class="panel profile-editor profile-settings-card" data-unsaved-warning="1">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="action" value="profile_save">
            <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">
            <section class="profile-settings-section profile-settings-section--personal">
                <div class="profile-settings-heading">
                    <div>
                        <h2>Personal information</h2>
                        <p>Update the details shown across the administration portal.</p>
                    </div>
                </div>
                <div class="profile-photo-editor profile-photo-editor--dashboard">
                    <div class="profile-photo-mini" id="profileMiniPreview">
                        <?=admin_avatar_html($account['full_name'],$account['profile_image']??null,'profile-photo-preview')?>
                    </div>
                    <div class="profile-photo-copy">
                        <strong>Profile picture</strong><span>JPG, PNG or WebP · maximum 2 MB</span>
                        <div class="profile-photo-actions">
                            <label class="btn btn--secondary profile-file-button" for="profile_image_input">
                                Choose photo
                            </label>
                            <span class="profile-file-name" id="profileFileName">No new photo selected</span>
                            <?php if (!empty($account['profile_image'])) : ?>
                                <button type="button" class="profile-photo-remove-link" id="removeProfilePhoto">Remove</button>
                            <?php endif; ?>
                        </div>
                        <input class="profile-file-native" id="profile_image_input" type="file" name="profile_image" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>
                <div class="grid2 profile-fields">
                    <label>
                        Full name
                        <input name="full_name" required value="<?=e($account['full_name'])?>" autocomplete="name">
                    </label>
                    <label>
                        Username
                        <input name="username" required value="<?=e($account['username'])?>" autocomplete="username">
                    </label>
                </div>
                <label>
                    Email address
                    <input type="email" name="email" value="<?=e($account['email']??'')?>" placeholder="name@dmmmsu.edu.ph" autocomplete="email">
                </label>
            </section>
            <section class="profile-settings-section profile-password-section">
                <div class="profile-settings-heading profile-settings-heading--inline">
                    <div>
                        <h2><?=$forceRequired?'Create a new password':'Security'?></h2>
                        <p><?=$forceRequired?'Replace the temporary password before continuing.':'Manage your password and account security.'?></p>
                    </div>
                    <?php if (!$forceRequired) : ?>
                        <button type="button" class="btn btn--secondary profile-password-toggle" id="passwordToggle" aria-expanded="<?=$passwordRequested?'true':'false'?>"><?=$passwordRequested?'Hide password fields':'Change password'?></button>
                    <?php else: ?>
                        <span class="profile-password-required-badge">Required</span>
                    <?php endif; ?>
                </div>
                <?php if (!$forceRequired) : ?>
                    <div class="profile-password-summary">
                        <div>
                            <strong>Password</strong><span>Changing your password signs out your other active sessions.</span>
                        </div>
                        <small>Last changed <?=e($passwordChanged)?></small>
                    </div>
                <?php endif; ?>
                <div class="profile-password-fields<?=$passwordRequested?' is-open':''?>" id="passwordFields">
                    <div class="grid3">
                        <label>
                            <?=$forceRequired?'Temporary password':'Current password'?>
                            <input type="password" name="current_password" autocomplete="current-password" placeholder="<?=$forceRequired?'Enter temporary password':'Enter current password'?>" <?=$forceRequired?'required':''?>>
                        </label>
                        <label>
                            New password
                            <input type="password" id="newPassword" name="new_password" minlength="12" autocomplete="new-password" placeholder="12+ characters with mixed character types" <?=$forceRequired?'required':''?>>
                            <small id="passwordStrength" aria-live="polite">Use at least 12 characters and three character types.</small>
                        </label>
                        <label>
                            Confirm new password
                            <input type="password" name="confirm_password" minlength="12" autocomplete="new-password" placeholder="Repeat new password" <?=$forceRequired?'required':''?>>
                        </label>
                    </div>
                </div>
            </section>
            <div class="form-actions profile-settings-actions">
                <span>Changes apply to your administrator account.</span>
                <button class="btn">Save changes</button>
            </div>
        </form>
        <?php if (!$forceRequired) : ?>
            <div class="profile-security-grid profile-security-grid--dashboard">
                <div class="profile-security-column">
                    <section class="panel security-card security-card--2fa">
                        <div class="panel__head">
                            <div>
                                <h2>Two-step verification</h2>
                                <p>Add an authenticator code after your password.</p>
                            </div>
                            <span class="security-state <?=!empty($account['two_factor_enabled'])?'is-on':'is-off'?>"><?=!empty($account['two_factor_enabled'])?'Enabled':'Off'?></span>
                        </div>
                        <div class="panel__body">
                            <?php if ($show2FASetup) :$grouped = trim(chunk_split($setupSecret, 4, ' ')); ?>
                                <div class="twofactor-setup">
                                    <p>Add this account to an authenticator app using the setup key, then enter the current 6-digit code.</p>
                                    <div class="twofactor-key">
                                        <span>Setup key</span><strong><?=e($grouped)?></strong>
                                    </div>
                                    <form method="post" class="twofactor-confirm">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="confirm_2fa">
                                        <label>
                                            Authenticator code
                                            <input name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required placeholder="000000">
                                        </label>
                                        <button class="btn">Enable verification</button>
                                    </form>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="cancel_2fa_setup">
                                        <button class="btn btn--soft btn--small">Cancel setup</button>
                                    </form>
                                </div>
                            <?php elseif (!empty($account['two_factor_enabled'])) : ?>
                                <p class="security-copy">Your account requires an authenticator code after a correct password. <strong><?=$recoveryCodeCount?></strong> unused recovery code<?=$recoveryCodeCount===1?'':'s'?> remain.</p>
                                <details class="security-details">
                                    <summary>Generate new recovery codes</summary>
                                    <form method="post" class="security-inline-form">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="regenerate_recovery_codes">
                                        <label>
                                            Current password
                                            <input type="password" name="current_password" required autocomplete="current-password">
                                        </label>
                                        <label>
                                            Authenticator code
                                            <input name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code">
                                        </label>
                                        <button class="btn btn--soft">Replace recovery codes</button>
                                    </form>
                                </details>
                                <?php if ($twoFactorRequired) : ?>
                                    <div class="account-security-note">
                                        <strong>Required for your role</strong><span>System policy requires two-step verification for this administrator role.</span>
                                    </div>
                                <?php else: ?>
                                    <details class="security-details">
                                        <summary>Disable two-step verification</summary>
                                        <form method="post" class="security-inline-form">
                                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                            <input type="hidden" name="action" value="disable_2fa">
                                            <label>
                                                Current password
                                                <input type="password" name="current_password" required>
                                            </label>
                                            <label>
                                                Authenticator code
                                                <input name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                                            </label>
                                            <button class="btn btn--danger">Disable</button>
                                        </form>
                                    </details>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="post" class="security-inline-form">
                                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                    <input type="hidden" name="action" value="begin_2fa">
                                    <label>
                                        Current password
                                        <input type="password" name="current_password" required placeholder="Confirm your password">
                                    </label>
                                    <button class="btn btn--soft">Set up authenticator</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </section>
                    <section class="panel sessions-panel sessions-panel--dashboard">
                        <div class="panel__head">
                            <div>
                                <h2>Active sessions</h2>
                                <p>Devices currently signed in to this administrator account.</p>
                            </div>
                            <?php if (count($sessions)>1) : ?>
                                <form method="post" data-confirm="Sign out all other administrator sessions?">
                                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                    <input type="hidden" name="action" value="revoke_others">
                                    <button class="btn btn--soft btn--small">Sign out other sessions</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="session-list">
                            <?php foreach ($sessions as $session) :$isCurrent = hash_equals($currentSessionHash, (string)$session['session_key_hash']); ?>
                                <div class="session-row">
                                    <div class="session-device">
                                        <span class="session-device-icon">◇</span>
                                        <div>
                                            <strong><?=e(admin_device_label($session['user_agent']??''))?><?=$isCurrent?' · This device':''?><?=$isCurrent?' <span class="session-current-badge">Current</span>':''?></strong><span><?=e($session['ip_address']?:'Unknown IP')?> · Started <?=e(date('M j, g:i A',strtotime($session['created_at'])))?></span>
                                        </div>
                                    </div>
                                    <div class="session-last">
                                        <span>Last active</span><strong><?=e(date('M j, g:i A',strtotime($session['last_seen_at'])))?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
                <section class="panel security-card security-card--notifications">
                    <div class="panel__head">
                        <div>
                            <h2>Notification preferences</h2>
                            <p>Choose which administrative events appear in your notification center.</p>
                        </div>
                    </div>
                    <form method="post" class="panel__body notification-preference-form">
                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                        <input type="hidden" name="action" value="save_notifications">
                        <?php foreach (admin_notification_categories() as $key => $label) : ?>
                            <label>
                                <input type="checkbox" name="notifications[]" value="<?=e($key)?>" <?=!empty($preferences[$key])?'checked':''?>>
                                <span><strong><?=e($label)?></strong><small><?=e(match($key){'security'=>'Password, sign-in, and account-security changes.','concerns'=>'New concerns, routing, assignments, and status changes.','content'=>'News, homepage, and media publishing activity.','promotions'=>'Campus-to-USC homepage promotion requests and reviews.','system'=>'System administration and configuration events.',default=>'Other platform updates.'})?></small></span>
                            </label>
                        <?php endforeach; ?>
                        <div class="notification-preference-actions">
                            <button class="btn btn--soft">Save preferences</button>
                        </div>
                    </form>
                </section>
            </div>
        <?php endif; ?>
    </main>
</div>
<script>
(function () {
    const input = document.getElementById('profile_image_input'), fileName = document.getElementById('profileFileName'), removeBtn = document.getElementById('removeProfilePhoto'), removeField = document.getElementById('remove_profile_image');
    const preview = document.querySelector('#profileMiniPreview .profile-photo-preview'), mainPreview = document.querySelector('#profilePreviewWrap .profile-avatar-large'); const initial = <?=json_encode($initial)?>;
    function showInitial(target) { if (!target) return; target.classList.remove('admin-user__avatar--photo'); target.innerHTML = ''; target.textContent = initial; }
    input?.addEventListener('change', function () { const file = this.files && this.files[0]; if (!file) { fileName.textContent = 'No new photo selected'; return; } fileName.textContent = file.name; removeField.value = '0'; const reader = new FileReader(); reader.onload = function (ev) { [preview, mainPreview].forEach(function (target) { if (!target) return; target.classList.add('admin-user__avatar--photo'); target.innerHTML = '<img alt="Profile preview">'; target.querySelector('img').src = ev.target.result; }); }; reader.readAsDataURL(file); });
    removeBtn?.addEventListener('click', function () { removeField.value = '1'; if (input) input.value = ''; if (fileName) fileName.textContent = 'Photo will be removed after saving'; showInitial(preview); showInitial(mainPreview); this.hidden = true; });
    const toggle = document.getElementById('passwordToggle'), fields = document.getElementById('passwordFields'); toggle?.addEventListener('click', function () { const open = fields.classList.toggle('is-open'); toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); toggle.textContent = open ? 'Hide password fields' : 'Change password'; if (open) fields.querySelector('input')?.focus(); });
    const password = document.getElementById('newPassword'), strength = document.getElementById('passwordStrength'); password?.addEventListener('input', () => { const v = password.value; if (!v) { strength.textContent = 'Use at least 12 characters and three character types.'; return; } let classes = 0; classes += /[a-z]/.test(v); classes += /[A-Z]/.test(v); classes += /\d/.test(v); classes += /[^A-Za-z0-9]/.test(v); strength.textContent = v.length >= 12 && classes >= 3 ? 'Strong password format' : 'Needs 12+ characters and at least three character types'; });
})();
</script>
<?php
admin_footer();
?>
