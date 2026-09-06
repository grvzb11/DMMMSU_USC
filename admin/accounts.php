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
require_once '../config/helpers.php';
require_system_admin();

$roles = admin_roles();
$campuses = admin_campuses();
$campusDisplayNames = [
    'NLUC' => 'North La Union Campus',
    'MLUC' => 'Mid La Union Campus',
    'SLUC' => 'South La Union Campus',
    'OUS' => 'Open University System',
];
$error = '';
$securityActivityReady = db_column_exists($pdo, 'admins', 'last_activity_at');
$passwordMaxAge = max(30, min(730, (int)(system_setting($pdo, 'password_max_age_days', '180') ?? 180)));
$dormantDays = max(30, min(730, (int)(system_setting($pdo, 'dormant_account_days', '90') ?? 90)));
$activeSystemAdminCount = function (int $excludeId = 0) use ($pdo): int {
    if ($excludeId>0) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE role='admin' AND status='active' AND id<>?");
        $st->execute([$excludeId]);
        return (int)$st->fetchColumn();
    }
    return (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role='admin' AND status='active'")->fetchColumn();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = in_array((string)($_POST['status'] ?? 'active'), ['active', 'inactive', 'pending', 'suspended', 'archived'], true)?(string)$_POST['status']:'active';
        $statusReason = trim((string)($_POST['status_reason'] ?? ''));
        $targetSt = $pdo->prepare('SELECT id,full_name,role,status,campus FROM admins WHERE id=? LIMIT 1');
        $targetSt->execute([$id]);
        $target = $targetSt->fetch();
        if (!$target) {
            $error = 'Account not found.';
        } elseif ($id === (int)($_SESSION['admin_id'] ?? 0)) {
            $error = 'You cannot deactivate your own signed-in account.';
        } elseif (($target['role'] ?? '') === 'admin' && ($target['status'] ?? 'active') === 'active' && $status !== 'active' && $activeSystemAdminCount($id) === 0) {
            $error = 'At least one active System Administrator must remain.';
        } else {
            if (db_column_exists($pdo, 'admins', 'approved_at') && $status === 'active') $pdo->prepare('UPDATE admins SET status=?,approved_at=COALESCE(approved_at,NOW()),approved_by=COALESCE(approved_by,?) WHERE id=?')->execute([$status, $_SESSION['admin_id'] ?? null, $id]);
            else $pdo->prepare('UPDATE admins SET status=? WHERE id=?')->execute([$status, $id]);
            if (db_column_exists($pdo, 'admins', 'status_changed_at')) {
                $pdo->prepare("UPDATE admins SET status_reason=?,status_changed_at=NOW(),archived_at=CASE WHEN status='archived' THEN COALESCE(archived_at,NOW()) ELSE NULL END WHERE id=?")->execute([$statusReason?:null, $id]);
            }
            $revoked = $status !== 'active'?admin_revoke_all_sessions($pdo, $id):0;
            admin_log($pdo, 'accounts', 'Changed account status', ($target['full_name'] ?? ('Account #'.$id)).' set to '.$status, 'admin', $id, ['status' => $target['status']], ['status' => $status, 'revoked_sessions' => $revoked]);
            if ($status !== 'active') admin_notify($pdo, 'Administrator access changed', 'Your administrator account status is now '.ucfirst($status).($statusReason !== ''?' — '.$statusReason:'').'.', 'profile.php', 'warning', null, null, $id, 'security');
            header('Location: accounts.php?status_updated=1');
            exit;
        }
    } elseif ($action === 'revoke_sessions') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
            $error = 'Use My Account to manage your own active sessions.';
        }
        else {
            $st = $pdo->prepare('SELECT full_name FROM admins WHERE id=? LIMIT 1');
            $st->execute([$id]);
            $name = $st->fetchColumn();
            if (!$name) {
                $error = 'Account not found.';
            }
            else {
                $count = admin_revoke_all_sessions($pdo, $id);
                admin_log($pdo, 'security', 'Forced account sign-out', $name.' · '.$count.' active session'.($count === 1?'':'s').' revoked', 'admin', $id, null, ['revoked_sessions' => $count]);
                admin_notify($pdo, 'Signed out by System Administrator', 'Your active administrator sessions were ended. Sign in again to continue.', 'profile.php', 'warning', null, null, $id, 'security');
                header('Location: accounts.php?edit='.$id.'&sessions_revoked='.$count);
                exit;
            }
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'sbo_adviser';
        $campus = strtoupper(trim($_POST['campus'] ?? ''));
        $status = in_array((string)($_POST['status'] ?? ($id?'active':'pending')), ['active', 'inactive', 'pending', 'suspended', 'archived'], true)?(string)($_POST['status'] ?? ($id?'active':'pending')):($id?'active':'pending');
        $statusReason = trim((string)($_POST['status_reason'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $forcePasswordChange = !empty($_POST['force_password_change']);
        $removeProfile = !empty($_POST['remove_profile_image']);
        $profileImage = null;
        $oldProfileImage = null;
        $existing = null;
        $isSelf = $id>0 && $id === (int)($_SESSION['admin_id'] ?? 0);
        if ($id) {
            $pst = $pdo->prepare('SELECT id,full_name,username,email,profile_image,role,campus,status,force_password_change,password_changed_at,password_hash,approved_at,approved_by FROM admins WHERE id=? LIMIT 1');
            $pst->execute([$id]);
            $existing = $pst->fetch()?:null;
            if ($existing) {
                $oldProfileImage = $existing['profile_image']?:null;
                $profileImage = $oldProfileImage;
            } else {
                $error = 'Account not found.';
            }
        }

        $legacySasAccount = $existing && ($existing['role'] ?? '') === 'campus_sas_head' && $role === 'campus_sas_head';
        if (!isset($roles[$role]) && !$legacySasAccount) $error = 'Choose a valid account role.';
        if ($fullName === '' || $username === '') $error = 'Full name and username are required.';
        if (in_array($role, ['campus_sas_head', 'sbo_adviser', 'campus_sbo'], true) && !isset($campuses[$campus])) $error = 'Campus-scoped legacy Student Affairs and Services, Adviser, and Student Body Organization accounts require a campus assignment.';
        if (in_array($role, ['admin', 'usc', 'sas_director'], true)) $campus = '';
        if (!$id && $password === '') $error = 'New accounts require a temporary password.';
        if (!$id && $password !== '' && admin_password_strength_error($password) !== null) $error = (string)admin_password_strength_error($password);
        if ($id && $password !== '' && admin_password_strength_error($password) !== null) $error = (string)admin_password_strength_error($password);
        if ($id && $password !== '' && $existing && (password_verify($password, (string)($existing['password_hash'] ?? '')) || admin_password_reused($pdo, $id, $password, (int)(system_setting($pdo, 'password_history_count', '5') ?? 5)))) $error = 'Choose a temporary password this administrator has not used recently.';
        if ($id && $password !== '' && $isSelf) $error = 'Use My Account to change your own password. Administrative password reset is for other accounts.';
        if ($existing && ($existing['role'] ?? '') === 'admin' && ($existing['status'] ?? 'active') === 'active' && ($role !== 'admin' || $status !== 'active') && $activeSystemAdminCount($id) === 0) $error = 'At least one active System Administrator must remain.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';

        if ($error === '') {
            $originalProfileImage = $profileImage;
            $newProfileImage = null;
            try {
                if ($removeProfile) $profileImage = null;
                if (!empty($_FILES['profile_image']['name'])) {
                    $rl = max(5, (int)(system_setting($pdo, 'security_rate_limit_uploads_per_hour', '40') ?? 40));
                    platform_rate_limit_or_429($pdo, 'admin-upload', ((string)($_SESSION['admin_id'] ?? 0)).'|'.admin_current_ip(), $rl, 3600, 900);
                    $newProfileImage = admin_store_profile_image($_FILES['profile_image']);
                    $profileImage = $newProfileImage;
                }
                if ($id) {
                    if ($id === (int)($_SESSION['admin_id'] ?? 0) && $status !== 'active') $status = 'active';
                    if ($password !== '') {
                        if (strlen($password)<12) {
                            $error = 'Temporary password must be at least 12 characters.';
                        }
                        else {
                            if ($existing && !empty($existing['password_hash'])) admin_record_password_history($pdo, $id, (string)$existing['password_hash']);
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            if (db_column_exists($pdo, 'admins', 'approved_at') && $status === 'active') $pdo->prepare('UPDATE admins SET full_name=?,username=?,email=?,profile_image=?,role=?,campus=?,status=?,password_hash=?,force_password_change=?,password_changed_at=NOW(),approved_at=COALESCE(approved_at,NOW()),approved_by=COALESCE(approved_by,?) WHERE id=?')
                            ->execute([$fullName, $username, $email?:null, $profileImage, $role, $campus?:null, $status, $hash, $forcePasswordChange?1:0, $_SESSION['admin_id'] ?? null, $id]);
                            else $pdo->prepare('UPDATE admins SET full_name=?,username=?,email=?,profile_image=?,role=?,campus=?,status=?,password_hash=?,force_password_change=?,password_changed_at=NOW() WHERE id=?')
                            ->execute([$fullName, $username, $email?:null, $profileImage, $role, $campus?:null, $status, $hash, $forcePasswordChange?1:0, $id]);
                        }
                    } else {
                        if (db_column_exists($pdo, 'admins', 'approved_at') && $status === 'active') $pdo->prepare('UPDATE admins SET full_name=?,username=?,email=?,profile_image=?,role=?,campus=?,status=?,approved_at=COALESCE(approved_at,NOW()),approved_by=COALESCE(approved_by,?) WHERE id=?')
                        ->execute([$fullName, $username, $email?:null, $profileImage, $role, $campus?:null, $status, $_SESSION['admin_id'] ?? null, $id]);
                        else $pdo->prepare('UPDATE admins SET full_name=?,username=?,email=?,profile_image=?,role=?,campus=?,status=? WHERE id=?')
                        ->execute([$fullName, $username, $email?:null, $profileImage, $role, $campus?:null, $status, $id]);
                    }
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    if (db_column_exists($pdo, 'admins', 'approved_at')) $pdo->prepare('INSERT INTO admins(username,email,profile_image,password_hash,full_name,role,campus,status,force_password_change,created_by,approved_at,approved_by,password_changed_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())')
                    ->execute([$username, $email?:null, $profileImage, $hash, $fullName, $role, $campus?:null, $status, $forcePasswordChange?1:0, $_SESSION['admin_id'] ?? null, $status === 'active'?date('Y-m-d H:i:s'):null, $status === 'active'?($_SESSION['admin_id'] ?? null):null]);
                    else $pdo->prepare('INSERT INTO admins(username,email,profile_image,password_hash,full_name,role,campus,status,force_password_change,created_by,password_changed_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW())')
                    ->execute([$username, $email?:null, $profileImage, $hash, $fullName, $role, $campus?:null, $status, $forcePasswordChange?1:0, $_SESSION['admin_id'] ?? null]);
                }
                if ($error === '') {
                    $savedId = $id?:((int)$pdo->lastInsertId());
                    if (db_column_exists($pdo, 'admins', 'status_changed_at')) {
                        $pdo->prepare("UPDATE admins SET status_reason=?,status_changed_at=NOW(),archived_at=CASE WHEN status='archived' THEN COALESCE(archived_at,NOW()) ELSE NULL END WHERE id=?")->execute([$statusReason?:null, $savedId]);
                    }
                    if ($originalProfileImage && $originalProfileImage !== $profileImage) admin_delete_profile_image($originalProfileImage);
                    if ($savedId === (int)($_SESSION['admin_id'] ?? 0)) {
                        $_SESSION['admin_name'] = $fullName;
                        $_SESSION['admin_email'] = $email?:null;
                        $_SESSION['admin_profile_image'] = $profileImage;
                        $_SESSION['admin_role'] = $role;
                        $_SESSION['admin_campus'] = $campus?:null;
                    }
                    $oldAudit = $existing?['full_name' => $existing['full_name'], 'username' => $existing['username'] ?? null, 'email' => $existing['email'] ?? null, 'role' => $existing['role'], 'campus' => $existing['campus'] ?? null, 'status' => $existing['status']]:null;
                    $newAudit = ['full_name' => $fullName, 'username' => $username, 'email' => $email?:null, 'role' => $role, 'campus' => $campus?:null, 'status' => $status];
                    admin_log($pdo, 'accounts', $id?'Updated account':'Created account', $fullName, 'admin', $savedId, $oldAudit, $newAudit);
                    if ($password !== '' && $id) {
                        $revoked = admin_revoke_all_sessions($pdo, $savedId);
                        admin_log($pdo, 'security', 'Reset account password', $fullName.' · temporary password issued · '.$revoked.' session'.($revoked === 1?'':'s').' revoked'.($forcePasswordChange?' · password change required':''), 'admin', $savedId, null, ['password_reset' => true, 'force_change' => $forcePasswordChange, 'revoked_sessions' => $revoked]);
                        admin_notify($pdo, 'Password reset by System Administrator', $forcePasswordChange?'Your password was reset. Sign in with the temporary password and create a new password before continuing.':'Your administrator password was reset.', 'profile.php?force_password=1', 'warning', null, null, $savedId, 'security');
                    } elseif (!$id && $forcePasswordChange) {
                        admin_notify($pdo, 'Complete your account setup', 'Your account uses a temporary password. Create a new password after your first sign in.', 'profile.php?force_password=1', 'warning', null, null, $savedId, 'security');
                    }
                    header('Location: accounts.php?saved=1');
                    exit;
                }
            } catch (PDOException $e) {
                if ($newProfileImage) admin_delete_profile_image($newProfileImage);
                $error = str_contains(strtolower($e->getMessage()), 'duplicate')?'That username is already in use.':'Unable to save this account.';
            } catch (Throwable $e) {
                if ($newProfileImage) admin_delete_profile_image($newProfileImage);
                $error = $e->getMessage();
            }
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $activitySelect = $securityActivityReady?',last_activity_at':'';
    $st = $pdo->prepare('SELECT admins.id,username,email,profile_image,full_name,role,campus,status,force_password_change,last_login'.$activitySelect.',created_at,password_changed_at,created_by,two_factor_enabled,(SELECT full_name FROM admins creator WHERE creator.id=admins.created_by) AS created_by_name FROM admins WHERE admins.id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch()?:null;
    if ($edit && db_column_exists($pdo, 'admins', 'status_reason')) {
        $rs = $pdo->prepare('SELECT status_reason FROM admins WHERE id=?');
        $rs->execute([(int)$edit['id']]);
        $edit['status_reason'] = $rs->fetchColumn()?:'';
    }
}
$showForm = isset($_GET['action']) || $edit;

$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';
$campusFilter = strtoupper(trim($_GET['campus'] ?? 'all'));
$statusFilter = $_GET['status'] ?? 'all';
$securityFilter = $_GET['security'] ?? 'all';
if (!in_array($securityFilter, ['all', 'dormant', 'password_due', '2fa_off', 'locked'], true)) $securityFilter = 'all';
$loginAttemptAdminReady = db_column_exists($pdo, 'admin_login_attempts', 'admin_id');
$loginAttemptOwner = $loginAttemptAdminReady?'(ala.admin_id=admins.id OR (ala.admin_id IS NULL AND ala.username=admins.username))':'ala.username=admins.username';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(full_name LIKE ? OR username LIKE ? OR email LIKE ?)';
    $like = '%'.$q.'%';
    array_push($params, $like, $like, $like);
}
if (isset($roles[$roleFilter])) {
    $where[] = 'role=?';
    $params[] = $roleFilter;
}
if (isset($campuses[$campusFilter])) {
    $where[] = 'campus=?';
    $params[] = $campusFilter;
}
if (in_array($statusFilter, ['active', 'inactive', 'pending', 'suspended', 'archived'], true)) {
    $where[] = 'status=?';
    $params[] = $statusFilter;
}
if ($securityFilter === 'dormant' && $securityActivityReady) $where[] = "status='active' AND COALESCE(last_activity_at,last_login,created_at)<DATE_SUB(NOW(),INTERVAL ".$dormantDays." DAY)";
elseif ($securityFilter === 'password_due') $where[] = "status='active' AND (password_changed_at IS NULL OR password_changed_at<DATE_SUB(NOW(),INTERVAL ".$passwordMaxAge." DAY))";
elseif ($securityFilter === '2fa_off') $where[] = "status='active' AND two_factor_enabled=0";
elseif ($securityFilter === 'locked') $where[] = "EXISTS(SELECT 1 FROM admin_login_attempts ala WHERE {$loginAttemptOwner} AND ala.locked_until>NOW())";
$base = ' FROM admins'.($where?' WHERE '.implode(' AND ', $where):'');
$countSt = $pdo->prepare('SELECT COUNT(*)'.$base);
$countSt->execute($params);
$resultTotal = (int)$countSt->fetchColumn();
$pager = pagination_state($resultTotal, 25);
$activitySelect = $securityActivityReady?',last_activity_at':'';
$sql = 'SELECT id,username,email,profile_image,full_name,role,campus,status,force_password_change,last_login'.$activitySelect.',created_at,password_changed_at,created_by,two_factor_enabled,(SELECT MAX(attempts) FROM admin_login_attempts ala WHERE '.$loginAttemptOwner.') failed_attempts,(SELECT MAX(locked_until) FROM admin_login_attempts ala WHERE '.$loginAttemptOwner.') locked_until'.$base.' ORDER BY full_name LIMIT '.$pager['per_page'].' OFFSET '.$pager['offset'];
$st = $pdo->prepare($sql);
$st->execute($params);
$accounts = $st->fetchAll();

admin_header($showForm?($edit?'Manage Account':'Create Account'):'Accounts', 'System administration');
if (isset($_GET['saved'])) echo '<div class="notice">Account saved successfully.</div>';
if (isset($_GET['status_updated'])) echo '<div class="notice">Account status updated.</div>';
if (isset($_GET['sessions_revoked'])) echo '<div class="notice">Active sessions for this account were signed out.</div>';
if ($error) echo '<div class="notice notice--error">'.e($error).'</div>';

if ($showForm) :
?>
<div class="account-editor-nav">
    <a class="btn btn--soft" href="accounts.php">Back to accounts</a>
</div>
<form method="post" enctype="multipart/form-data" class="panel account-editor account-editor--refined">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="id" value="<?=e((string)($edit['id']??''))?>">
    <?php
      $accountName=$edit['full_name']??'New administrator';
      $accountUser=$edit['username']??'new-account';
      $accountRole=$edit['role']??'sbo_adviser';
      $accountCampus=$edit['campus']??'';
      $accountStatus=$edit['status']??'active';
      $editingSelf=$edit && (int)$edit['id']===(int)($_SESSION['admin_id']??0);
    ?>
    <div class="account-editor-head">
        <div class="account-editor-head__identity">
            <?=admin_avatar_html($accountName,$edit['profile_image']??null,'account-editor-avatar')?>
            <div class="account-editor-head__copy">
                <span class="account-editor-kicker"><?=$edit?'ADMINISTRATOR ACCOUNT':'NEW ADMINISTRATOR'?></span>
                <h2><?=e($accountName)?></h2>
                <p>@<?=e($accountUser)?></p>
            </div>
        </div>
        <div class="account-editor-head__meta">
            <span><?=e(admin_role_label((string)$accountRole))?></span>
            <span><?=e($accountCampus!==''?($campuses[$accountCampus]??$accountCampus):'University-wide')?></span>
            <span class="account-editor-status account-editor-status--<?=e($accountStatus)?>"><?=e(ucfirst($accountStatus))?></span>
        </div>
    </div>
    <div class="account-editor-grid">
        <section class="account-editor-pane account-editor-pane--identity">
            <div class="account-editor-section-head">
                <div>
                    <span>PROFILE</span>
                    <h3>Account identity</h3>
                </div>
                <p>Basic information shown throughout the administration portal.</p>
            </div>
            <div class="account-photo-row account-photo-row--refined">
                <div class="account-photo-preview">
                    <?=admin_avatar_html($accountName,$edit['profile_image']??null,'account-photo-avatar')?>
                </div>
                <div class="account-photo-control account-photo-control--refined">
                    <span>Profile picture</span>
                    <label class="account-file-picker">
                        <input id="accountProfileImage" type="file" name="profile_image" accept="image/jpeg,image/png,image/webp">
                        <span>Choose photo</span>
                    </label>
                    <small id="accountProfileFileName">JPG, PNG or WebP · maximum 2 MB</small>
                </div>
                <?php if (!empty($edit['profile_image'])) : ?>
                    <label class="account-photo-remove account-photo-remove--refined">
                        <input type="checkbox" name="remove_profile_image" value="1">
                        <span>Remove photo</span>
                    </label>
                <?php endif; ?>
            </div>
            <div class="grid2 account-editor-fields">
                <label>
                    Full name
                    <input name="full_name" required value="<?=e($edit['full_name']??'')?>" placeholder="Full name">
                </label>
                <label>
                    Username
                    <input name="username" required value="<?=e($edit['username']??'')?>" placeholder="Username">
                </label>
            </div>
            <div class="account-editor-fields">
                <label>
                    Email address
                    <input type="email" name="email" value="<?=e($edit['email']??'')?>" placeholder="name@dmmmsu.edu.ph">
                </label>
            </div>
        </section>
        <div class="account-editor-side">
            <section class="account-editor-pane">
                <div class="account-editor-section-head">
                    <div>
                        <span>ACCESS</span>
                        <h3>Role & campus access</h3>
                    </div>
                    <p>Controls what this administrator can manage.</p>
                </div>
                <div class="account-editor-fields">
                    <label>
                        Role
                        <select name="role" id="accountRole" required>
                            <?php if (($edit['role'] ?? '') === 'campus_sas_head') : ?>
                                <option value="campus_sas_head" selected hidden>Student Affairs and Services</option>
                            <?php endif; ?>
                            <?php foreach ($roles as $key => $label) : ?>
                                <option value="<?=e($key)?>" <?=($edit['role']??'sbo_adviser')===$key?'selected':''?>><?=e(admin_role_option_label($key))?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                </div>
                <div class="account-editor-fields">
                    <label>
                        Assigned campus
                        <select name="campus" id="accountCampus">
                            <option value="">University-wide / Not applicable</option>
                            <?php foreach ($campuses as $key => $name) : ?>
                                <option value="<?=e($key)?>" <?=($edit['campus']??'')===$key?'selected':''?>><?=e($campusDisplayNames[$key] ?? $name)?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                </div>
                <div class="account-editor-fields">
                    <label>
                        Status
                        <select name="status">
                            <option value="pending" <?=($edit['status']??'pending')==='pending'?'selected':''?>>Pending approval</option>
                            <option value="active" <?=($edit['status']??'pending')==='active'?'selected':''?>>Active</option>
                            <option value="inactive" <?=($edit['status']??'')==='inactive'?'selected':''?>>Inactive</option>
                            <option value="suspended" <?=($edit['status']??'')==='suspended'?'selected':''?>>Suspended</option>
                            <option value="archived" <?=($edit['status']??'')==='archived'?'selected':''?>>Archived</option>
                        </select>
                        <small>New accounts can remain pending until access is explicitly approved. Suspended and archived accounts cannot sign in.</small>
                    </label>
                    <label>
                        Status reason
                        <input name="status_reason" maxlength="255" value="<?=e((string)($edit['status_reason']??''))?>" placeholder="Optional reason for suspension, archiving, or reactivation">
                    </label>
                </div>
            </section>
            <section class="account-editor-pane account-editor-pane--security">
                <div class="account-editor-section-head">
                    <div>
                        <span>SECURITY</span>
                        <h3>Sign-in security</h3>
                    </div>
                    <p><?=$editingSelf?'Manage your own password from My Account.':'Reset access without exposing the existing password.'?></p>
                </div>
                <?php if ($editingSelf) : ?>
                    <div class="account-security-note">
                        <strong>Your password is private</strong><span>Use <a href="profile.php">My Account</a> to change your own password. Administrative reset is disabled for the account currently signed in.</span>
                    </div>
                <?php else: ?>
                    <div class="account-editor-fields">
                        <label>
                            <?=$edit?'Temporary password':'Temporary password'?>
                            <input type="password" name="password" <?=$edit?'':'required'?> minlength="12" autocomplete="new-password" placeholder="<?=$edit?'Enter only when resetting':'12+ characters, mixed types'?>">
                            <small><?=$edit?'Leave blank to keep the current password.':'Give this temporary password to the account owner securely.'?></small>
                        </label>
                    </div>
                    <label class="account-force-password account-force-password--refined">
                        <span>
                        <input type="checkbox" name="force_password_change" value="1" checked>
                        Require password change at next sign-in</span><small>The administrator must replace the temporary password with a private password.</small>
                    </label>
                    <?php if ($edit && !empty($edit['force_password_change'])) : ?>
                        <div class="account-password-pending">
                            This account is currently required to change its password.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <?php if ($edit) :$activeSessions = admin_active_sessions($pdo, (int)$edit['id']); ?>
        <section class="account-editor-lifecycle">
            <div>
                <span>Created</span><strong><?=e(!empty($edit['created_at'])?date('M j, Y',strtotime($edit['created_at'])):'Not recorded')?></strong><small><?=e($edit['created_by_name']?'by '.$edit['created_by_name']:'Creator not recorded')?></small>
            </div>
            <div>
                <span>Last sign in</span><strong><?=e(!empty($edit['last_login'])?date('M j, Y · g:i A',strtotime($edit['last_login'])):'Never')?></strong>
            </div>
            <?php if ($securityActivityReady) : ?>
                <div>
                    <span>Last activity</span><strong><?=e(!empty($edit['last_activity_at'])?date('M j, Y · g:i A',strtotime($edit['last_activity_at'])):'No activity recorded')?></strong>
                </div>
            <?php endif; ?>
            <div>
                <span>Password changed</span><strong><?=e(!empty($edit['password_changed_at'])?date('M j, Y',strtotime($edit['password_changed_at'])):'Not recorded')?></strong>
            </div>
            <div>
                <span>Security</span><strong><?=!empty($edit['two_factor_enabled'])?'2-step enabled':'Password only'?> · <?=count($activeSessions)?> active session<?=count($activeSessions)===1?'':'s'?></strong>
            </div>
        </section>
    <?php endif; ?>
    <div class="form-actions account-editor-actions">
        <div class="account-editor-secondary-actions">
            <?php if ($edit && !$editingSelf) : ?>
                <button class="btn btn--soft" type="submit" name="action" value="revoke_sessions" formnovalidate data-confirm="Sign out every active session for this administrator?" data-direct-action="1">Sign out all sessions</button>
            <?php endif; ?>
        </div>
        <div>
            <a class="btn btn--soft" href="accounts.php">Cancel</a>
            <button class="btn" name="action" value="save"><?=$edit?'Save changes':'Create account'?></button>
        </div>
    </div>
</form>
<script>
const role = document.getElementById('accountRole'), campus = document.getElementById('accountCampus');
function syncCampus() { const scoped = ['campus_sas_head', 'sbo_adviser', 'campus_sbo'].includes(role.value); campus.required = scoped; campus.disabled = !scoped; if (!scoped) campus.value = ''; }
role?.addEventListener('change', syncCampus); syncCampus();
const profileInput = document.getElementById('accountProfileImage'), profileFileName = document.getElementById('accountProfileFileName');
profileInput?.addEventListener('change', () => { if (profileInput.files?.[0]) profileFileName.textContent = profileInput.files[0].name; });
</script>
<?php else: ?>
<?php $hasFilters = $q !== '' || $roleFilter !== 'all' || $campusFilter !== 'all' || $statusFilter !== 'all' || $securityFilter !== 'all'; ?>
<div class="accounts-list-page">
    <div class="accounts-list-head">
        <div>
            <span class="page-kicker">ACCESS MANAGEMENT</span>
            <h2>Administrative access</h2>
            <p>Manage administrator accounts, role assignments, campus scope, and sign-in access from one directory.</p>
        </div>
        <div class="accounts-list-head__actions">
            <a class="btn btn--soft" href="permissions.php">Roles &amp; permissions</a>
            <a class="btn" href="?action=new">Create account</a>
        </div>
    </div>
    <section class="panel accounts-directory-panel">
        <form class="accounts-filterbar" method="get">
            <label class="accounts-search-field">
                <span class="accounts-search-icon" aria-hidden="true">⌕</span>
                <input name="q" value="<?=e($q)?>" placeholder="Search name, username, or email" aria-label="Search accounts">
            </label>
            <label class="accounts-filter-field">
                <span>Role</span>
                <select name="role">
                    <option value="all">All roles</option>
                    <?php foreach ($roles as $key => $label) : ?>
                        <option value="<?=e($key)?>" <?=$roleFilter===$key?'selected':''?>><?=e(admin_role_option_label($key))?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label class="accounts-filter-field">
                <span>Campus</span>
                <select name="campus">
                    <option value="all">All campuses</option>
                    <?php foreach ($campuses as $key => $name) : ?>
                        <option value="<?=e($key)?>" <?=$campusFilter===$key?'selected':''?>><?=e($campusDisplayNames[$key] ?? $name)?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label class="accounts-filter-field">
                <span>Status</span>
                <select name="status">
                    <option value="all">All statuses</option>
                    <option value="active" <?=$statusFilter==='active'?'selected':''?>>Active</option>
                    <option value="pending" <?=$statusFilter==='pending'?'selected':''?>>Pending approval</option>
                    <option value="inactive" <?=$statusFilter==='inactive'?'selected':''?>>Inactive</option>
                    <option value="suspended" <?=$statusFilter==='suspended'?'selected':''?>>Suspended</option>
                    <option value="archived" <?=$statusFilter==='archived'?'selected':''?>>Archived</option>
                </select>
            </label>
            <label class="accounts-filter-field accounts-filter-field--security">
                <span>Security</span>
                <select name="security">
                    <option value="all">All states</option>
                    <?php if ($securityActivityReady) : ?>
                        <option value="dormant" <?=$securityFilter==='dormant'?'selected':''?>>Dormant</option>
                    <?php endif; ?>
                    <option value="password_due" <?=$securityFilter==='password_due'?'selected':''?>>Password age warning</option>
                    <option value="2fa_off" <?=$securityFilter==='2fa_off'?'selected':''?>>2-step not enabled</option>
                    <option value="locked" <?=$securityFilter==='locked'?'selected':''?>>Sign-in locked</option>
                </select>
            </label>
            <?php if ($hasFilters) : ?>
                <div class="accounts-filter-actions">
                    <a class="btn btn--soft accounts-filter-reset" href="accounts.php">Reset</a>
                </div>
            <?php endif; ?>
        </form>
        <script>
(function () {
    const form = document.querySelector('.accounts-filterbar');
    if (!form) return;
    const selects = [...form.querySelectorAll('select')];
    const search = form.querySelector('input[name="q"]');
    selects.forEach(select => select.addEventListener('change', () => form.requestSubmit()));
    let timer = null;
    if (search) {
        const initial = search.value;
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                if (search.value !== initial || search.value === '') form.requestSubmit();
            }, 450);
        });
        search.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(timer);
                form.requestSubmit();
            }
        });
    }
})();
</script>
        <div class="accounts-directory-head">
            <div>
                <span class="panel-kicker">ACCOUNT DIRECTORY</span>
                <h2><?=$resultTotal?> administrator account<?=$resultTotal===1?'':'s'?></h2>
                <p>Role, scope, account status, and security state at a glance.</p>
            </div>
            <?php if ($hasFilters) : ?>
                <span class="accounts-filter-state">Filtered view</span>
            <?php endif; ?>
        </div>
        <div class="table-wrap accounts-directory-table-wrap">
            <table class="accounts-directory-table">
                <thead>
                    <tr>
                        <th>Administrator</th>
                        <th>Access</th>
                        <th>Status &amp; security</th>
                        <th>Last sign-in</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($accounts as $a):
                              $isSelf=(int)$a['id']===(int)($_SESSION['admin_id']??0);
                              $isDormant=$securityActivityReady&&$a['status']==='active'&&!empty($a['last_activity_at']?:$a['last_login'])&&strtotime((string)($a['last_activity_at']?:$a['last_login']))<strtotime('-'.$dormantDays.' days');
                              $passwordDue=$a['status']==='active'&&(empty($a['password_changed_at'])||strtotime((string)$a['password_changed_at'])<strtotime('-'.$passwordMaxAge.' days'));
                              $isLocked=!empty($a['locked_until'])&&strtotime((string)$a['locked_until'])>time();
                              $scopeLabel=$a['campus']?portal_display_name($a['campus']):'University-wide';
                            ?>
                    <tr>
                        <td>
                            <div class="account-list-user accounts-directory-user">
                                <?=admin_avatar_html($a['full_name'],$a['profile_image']??null,'account-list-avatar')?>
                                <div class="cell-title">
                                    <div class="accounts-user-name-row">
                                        <strong><?=e($a['full_name'])?></strong>
                                        <?php if ($isSelf) : ?>
                                            <span class="accounts-you-badge">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <span>@<?=e($a['username'])?><?=!empty($a['email'])?' · '.e($a['email']):''?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="accounts-access-cell">
                                <strong><?=e(admin_role_label((string)$a['role']))?></strong>
                                <span><?=e($scopeLabel)?></span>
                            </div>
                        </td>
                        <td>
                            <div class="accounts-security-cell">
                                <?=status_badge(ucfirst($a['status']))?>
                                <div class="accounts-security-flags">
                                    <?php if (!empty($a['two_factor_enabled'])) : ?>
                                        <span class="is-good">2-step</span>
                                    <?php endif; ?>
                                    <?php if (!empty($a['force_password_change'])) : ?>
                                        <span class="is-warning">Password change</span>
                                    <?php endif; ?>
                                    <?php if ($isDormant) : ?>
                                        <span class="is-warning">Dormant</span>
                                    <?php endif; ?>
                                    <?php if ($passwordDue) : ?>
                                        <span class="is-warning">Password age</span>
                                    <?php endif; ?>
                                    <?php if ($isLocked) : ?>
                                        <span class="is-danger">Locked</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="accounts-last-login">
                                <strong><?=!empty($a['last_login'])?e(date('M j, Y',strtotime($a['last_login']))):'Never'?></strong>
                                <?php if (!empty($a['last_login'])) : ?>
                                    <span><?=e(date('g:i A',strtotime($a['last_login'])))?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="table-actions accounts-row-actions">
                                <a class="btn btn--soft btn--small" href="?edit=<?=$a['id']?>">Manage</a>
                                <?php if (!$isSelf) : ?>
                                    <form method="post" data-confirm="Change this account status?">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="id" value="<?=$a['id']?>">
                                        <input type="hidden" name="status" value="<?=$a['status']==='active'?'suspended':'active'?>">
                                        <button class="btn btn--small <?=$a['status']==='active'?'btn--danger':'btn--soft'?>"><?=$a['status']==='active'?'Suspend':($a['status']==='pending'?'Approve':'Reactivate')?></button>
                                    </form>
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (!$accounts) : ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state accounts-empty-state">
                                <strong>No accounts found</strong><span>Try clearing or changing the current filters.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>
    <?=render_pagination($pager)?>
</section>
</div>
<?php
endif;
admin_footer();
?>
