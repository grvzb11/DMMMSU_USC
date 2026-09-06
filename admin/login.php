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
require_once __DIR__.'/../config/app.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/helpers.php';
if (!empty($_SESSION['admin_id'])) {
    header('Location: '.(!empty($_SESSION['force_password_change'])?'profile.php?force_password=1':'dashboard.php'));
    exit;
}

function complete_admin_login(PDO $pdo, array $u): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $u['id'];
    $_SESSION['admin_name'] = $u['full_name'];
    $_SESSION['admin_role'] = $u['role'] ?? 'admin';
    $_SESSION['admin_campus'] = $u['campus'] ?? null;
    $_SESSION['admin_email'] = $u['email'] ?? null;
    $_SESSION['admin_profile_image'] = $u['profile_image'] ?? null;
    $_SESSION['force_password_change'] = !empty($u['force_password_change'])?1:0;
    unset($_SESSION['pending_2fa_admin_id'], $_SESSION['pending_2fa_at'], $_SESSION['pending_2fa_username']);
    admin_session_register($pdo, (int)$u['id']);
    $pdo->prepare('UPDATE admins SET last_login=NOW() WHERE id=?')->execute([$u['id']]);
    admin_login_event($pdo, (int)$u['id'], (string)($u['username'] ?? ''), 'login', 'success', 'Authenticated successfully');
    admin_log($pdo, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', (int)$u['id']);
    header('Location: '.(!empty($_SESSION['force_password_change'])?'profile.php?force_password=1':'dashboard.php'));
    exit;
}

$error = '';
$twoFactorPending = !empty($_SESSION['pending_2fa_admin_id']) && (int)($_SESSION['pending_2fa_at'] ?? 0)>time()-300;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'login';
    if ($action === 'verify_2fa') {
        $pendingId = (int)($_SESSION['pending_2fa_admin_id'] ?? 0);
        if (!$pendingId || (int)($_SESSION['pending_2fa_at'] ?? 0) <= time()-300) {
            unset($_SESSION['pending_2fa_admin_id'], $_SESSION['pending_2fa_at'], $_SESSION['pending_2fa_username']);
            $twoFactorPending = false;
            $error = 'Your verification session expired. Sign in again.';
        } else {
            $st = $pdo->prepare('SELECT * FROM admins WHERE id=? LIMIT 1');
            $st->execute([$pendingId]);
            $u = $st->fetch();
            if (!$u || ($u['status'] ?? 'active') !== 'active') {
                $error = 'This account is not available.';
            }
            else {
                $code = trim((string)($_POST['code'] ?? ''));
                $totpOk = admin_verify_totp((string)($u['two_factor_secret'] ?? ''), $code);
                $recoveryOk = false;
                if (!$totpOk && str_contains($code, '-')) $recoveryOk = admin_use_recovery_code($pdo, (int)$u['id'], $code);
                if (!$totpOk && !$recoveryOk) {
                    admin_login_event($pdo, (int)$u['id'], (string)($u['username'] ?? ''), 'two_factor', 'failed', 'Invalid authenticator or recovery code');
                    $error = 'The verification or recovery code is incorrect or expired.';
                }
                else {
                    admin_login_event($pdo, (int)$u['id'], (string)($u['username'] ?? ''), 'two_factor', 'success', $recoveryOk?'Recovery code used':'Authenticator verified');
                    if ($recoveryOk) {
                        admin_log($pdo, 'security', 'Used recovery code', 'A one-time two-step recovery code was used to sign in', 'admin', (int)$u['id']);
                        admin_notify($pdo, 'Recovery code used', 'A one-time recovery code was used to sign in to your administrator account. Generate a new set if this was unexpected.', 'profile.php', 'warning', null, null, (int)$u['id'], 'security');
                    }
                    complete_admin_login($pdo, $u);
                }
            }
        }
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $ip = admin_current_ip();
        $lock = admin_login_lock_state($pdo, $username, $ip);
        if ($lock['locked']) {
            $minutes = max(1, (int)ceil(($lock['locked_until']-time())/60));
            admin_login_event($pdo, null, $username, 'login', 'locked', 'Rate-limit lockout');
            $error = 'Too many failed sign-in attempts. Try again in about '.$minutes.' minute'.($minutes === 1?'':'s').'.';
        } else {
            $st = $pdo->prepare('SELECT * FROM admins WHERE username=? LIMIT 1');
            $st->execute([$username]);
            $u = $st->fetch();
            if ($u && ($u['status'] ?? 'active') === 'active' && password_verify($password, (string)$u['password_hash'])) {
                admin_login_success($pdo, $username, $ip);
                if (!empty($u['two_factor_enabled']) && !empty($u['two_factor_secret'])) {
                    $_SESSION['pending_2fa_admin_id'] = (int)$u['id'];
                    $_SESSION['pending_2fa_at'] = time();
                    $_SESSION['pending_2fa_username'] = $u['username'];
                    $twoFactorPending = true;
                } else {
                    complete_admin_login($pdo, $u);
                }
            } else {
                admin_login_failure($pdo, $username, $ip);
                admin_login_event($pdo, $u?(int)$u['id']:null, $username, 'login', 'failed', $u && ($u['status'] ?? 'active') !== 'active'?'Account not active':'Invalid credentials');
                $error = ($u && ($u['status'] ?? 'active') === 'pending')?'This administrator account is waiting for approval.':(($u && ($u['status'] ?? 'active') !== 'active')?'This account is inactive. Contact the System Administrator.':'The username or password you entered is incorrect.');
                admin_log($pdo, 'auth', 'Failed sign-in', 'Failed sign-in attempt for '.($username !== ''?$username:'unknown user'));
            }
        }
    }
}
if (isset($_GET['inactive']) && $error === '') $error = 'Your account is inactive. Contact the System Administrator.';
if (isset($_GET['session_expired']) && $error === '') $error = 'Your administrator session ended or was signed out remotely. Sign in again.';
?>
<!doctype html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" href="../public/assets/images/favicon.png">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>USC Administration</title>
        <link rel="stylesheet" href="../public/assets/css/admin.css?v=<?=e((string)@filemtime(__DIR__.'/../public/assets/css/admin.css'))?>">
    </head>
    <body class="login-wrap">
        <section class="login-branding">
            <div class="login-branding__top">
                <img src="../public/assets/images/usc-logo.jpg" alt="USC seal">
                <div>
                    <strong>University Student Council</strong><span>Don Mariano Marcos Memorial State University</span>
                </div>
            </div>
            <div class="login-branding__hero">
                <span>Administration Portal</span>
                <h1>Manage student information with clarity.</h1>
                <p>Publish official updates, review E-Sumbong concerns, and manage student services from one connected administrative workspace.</p>
            </div>
            <div class="login-branding__foot">
                Don Mariano Marcos Memorial State University · University Student Council
            </div>
        </section>
        <section class="login-panel">
            <?php if ($twoFactorPending) : ?>
                <form class="login-card" method="post" autocomplete="off">
                    <div class="login-card__eyebrow">
                        Two-step verification
                    </div>
                    <h2>Enter your security code</h2>
                    <p>Enter the current 6-digit authenticator code for <?=e((string)($_SESSION['pending_2fa_username']??'this account'))?>, or use one of your saved recovery codes.</p>
                    <?php if ($error) : ?>
                        <div class="notice notice--error">
                            <?=e($error)?>
                        </div>
                    <?php endif ?>
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="action" value="verify_2fa">
                    <label>
                        Authenticator or recovery code
                        <input class="login-otp" name="code" autocomplete="one-time-code" maxlength="14" required autofocus placeholder="000000 or XXXX-XXXX-XXXX">
                    </label>
                    <button class="btn" type="submit">Verify and continue</button>
                    <a class="login-cancel-link" href="logout.php">Cancel sign in</a>
                </form>
            <?php else: ?>
                <form class="login-card" method="post">
                    <div class="login-card__eyebrow">
                        Secure access
                    </div>
                    <h2>Welcome back</h2>
                    <p>Sign in to continue to the USC administration console.</p>
                    <?php if ($error) : ?>
                        <div class="notice notice--error">
                            <?=e($error)?>
                        </div>
                    <?php endif ?>
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="action" value="login">
                    <label>
                        Username
                        <input name="username" autocomplete="username" required placeholder="Enter username">
                    </label>
                    <label>
                        Password
                        <input type="password" name="password" autocomplete="current-password" required placeholder="Enter password">
                    </label>
                    <button class="btn" type="submit">Sign in to administration</button>
                    <div class="login-hint">
                        Accounts use role-based access, database-backed lockout protection, revocable sessions, and optional authenticator verification.
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </body>
</html>
