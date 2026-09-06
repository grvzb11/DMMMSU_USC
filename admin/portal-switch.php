<?php
require_once '../config/database.php';
require_once '../config/helpers.php';
require_admin();
if (!admin_is_global()) deny_access('Only university-wide administrators can change the working portal.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
verify_csrf();
$portal = strtoupper(trim((string)($_POST['portal'] ?? '')));
$before = admin_selected_portal()?:'ALL';
if (in_array($portal, ['USC', 'NLUC', 'MLUC', 'SLUC', 'OUS'], true)) $_SESSION['admin_selected_portal'] = $portal;
else unset($_SESSION['admin_selected_portal']);
$after = admin_selected_portal()?:'ALL';
if ($before !== $after) admin_log($pdo, 'system', 'Changed portal context', $before.' → '.$after, null, null, ['portal' => $before], ['portal' => $after]);
$return = (string)($_POST['return'] ?? 'dashboard.php');
if (!preg_match('/^[a-zA-Z0-9_-]+\.php(?:\?[a-zA-Z0-9_=&%.-]*)?$/', $return)) $return = 'dashboard.php';
header('Location: '.$return);
exit;
