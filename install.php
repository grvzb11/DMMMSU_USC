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
require_once __DIR__.'/config/app.php';
if (!app_env_bool('APP_INSTALLER_ENABLED', false)) app_render_error_page(403, 'Installer disabled', 'The one-time installer is disabled. Set APP_INSTALLER_ENABLED=true only during initial production setup.');
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/helpers.php';
if (!db_table_exists($pdo, 'admins')) app_render_error_page(500, 'Schema not installed', 'Import database/dmmmsu_usc.production.sql before using the installer.');
$count = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($count>0) app_render_error_page(403, 'Installation locked', 'An administrator account already exists. Disable APP_INSTALLER_ENABLED again.');
$error = '';
$created = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['full_name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || $username === '' || $password === '') $error = 'Full name, username, and password are required.';
    elseif (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) $error = 'Username may use letters, numbers, period, underscore, and hyphen.';
    elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';
    elseif (($strength = admin_password_strength_error($password)) !== null) $error = $strength;
    else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (db_column_exists($pdo, 'admins', 'approved_at')) {
            $st = $pdo->prepare("INSERT INTO admins(username,email,password_hash,full_name,role,status,force_password_change,password_changed_at,approved_at) VALUES(?,?,?,?,'admin','active',0,NOW(),NOW())");
        } else {
            $st = $pdo->prepare("INSERT INTO admins(username,email,password_hash,full_name,role,status,force_password_change,password_changed_at) VALUES(?,?,?,?,'admin','active',0,NOW())");
        }
        $st->execute([$username, $email?:null, $hash, $name]);
        $created = true;
        app_log_error('Initial System Administrator created', ['username' => $username]);
    }
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="noindex">
        <title>DMMMSU USC Initial Setup</title>
        <style>
body {
    font-family: Inter,Arial,sans-serif;
    background: #f4f8f5;
    margin: 0;
    min-height: 100vh;
    display: grid;
    place-items: center;
    color: #17352a;
}

.card {
    width: min(560px,calc(100% - 40px));
    background: #fff;
    border: 1px solid #dae8e0;
    border-radius: 22px;
    padding: 32px;
    box-shadow: 0 18px 55px rgba(20,70,45,.08);
}

h1 {
    margin: 8px 0 6px;
}

p {
    color: #66776f;
    line-height: 1.6;
}

.notice {
    padding: 12px;
    border-radius: 10px;
    background: #fff0f0;
    color: #8c2f2f;
}

.ok {
    background: #eaf6ef;
    color: #176b48;
}

form {
    display: grid;
    gap: 14px;
    margin-top: 22px;
}

label {
    display: grid;
    gap: 6px;
    font-size: 13px;
    font-weight: 700;
}

input {
    padding: 12px;
    border: 1px solid #cfded6;
    border-radius: 9px;
    font: inherit;
}

button,a {
    border: 0;
    border-radius: 10px;
    padding: 12px 16px;
    background: #146c48;
    color: #fff;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
}
</style>
    </head>
    <body>
        <main class="card">
            <small>ONE-TIME PRODUCTION SETUP</small>
            <h1>Create the first System Administrator</h1>
            <?php if ($created) : ?>
                <div class="notice ok">
                    Administrator created. Sign in, then immediately set <code>APP_INSTALLER_ENABLED=false</code>.
                </div>
                <p><a href="admin/login.php">Open administration →</a></p>
            <?php else: ?>
                <p>This installer works only when the production schema has no administrator records. It never reveals or generates a reusable default password.</p>
                <?php if ($error) : ?>
                    <div class="notice">
                        <?=e($error)?>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <label>
                        Full name
                        <input name="full_name" required>
                    </label>
                    <label>
                        Username
                        <input name="username" required autocomplete="username">
                    </label>
                    <label>
                        Email
                        <input type="email" name="email">
                    </label>
                    <label>
                        Password
                        <input type="password" name="password" minlength="12" required autocomplete="new-password">
                        <small>Use at least 12 characters with upper- and lowercase letters, a number, and a symbol.</small>
                    </label>
                    <button>Create System Administrator</button>
                </form>
            <?php endif; ?>
        </main>
    </body>
</html>
