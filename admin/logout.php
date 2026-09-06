<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['admin_id'])) {
    admin_login_event($pdo, (int)$_SESSION['admin_id'], (string)($_SESSION['admin_name'] ?? ''), 'logout', 'success', 'Administrator signed out');
    admin_log($pdo, 'auth', 'Signed out', 'Administrator signed out', 'admin', (int)$_SESSION['admin_id']);
    admin_revoke_current_session($pdo);
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
