<?php
require_once '../config/database.php';
require_once '_layout.php';

$viewer = (int)($_SESSION['admin_id'] ?? 0);
$target = $viewer;
if (isset($_GET['admin']) && admin_can_permission('security.login_history')) {
    $target = max(1, (int)$_GET['admin']);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $sessionId = (int)($_POST['session_id'] ?? 0);
    $owner = (int)($_POST['admin_id'] ?? $viewer);
    if ($owner !== $viewer && !admin_can_permission('security.login_history')) deny_access();

    $st = $pdo->prepare('SELECT * FROM admin_sessions WHERE id=? AND admin_id=? LIMIT 1');
    $st->execute([$sessionId, $owner]);
    $session = $st->fetch();
    if ($session) {
        $currentHash = !empty($_SESSION['admin_session_key'])?hash('sha256', (string)$_SESSION['admin_session_key']):'';
        if ($owner === $viewer && $currentHash !== '' && hash_equals($currentHash, (string)$session['session_key_hash'])) {
            $error = 'Use Sign out to end the current session.';
        } else {
            $pdo->prepare('UPDATE admin_sessions SET revoked_at=NOW() WHERE id=?')->execute([$sessionId]);
            admin_log($pdo, 'security', 'Revoked device session', 'Session #'.$sessionId.' revoked', 'admin', $owner);
            $redirect = 'account-security.php';
            $query = [];
            if ($owner !== $viewer) $query['admin'] = $owner;
            $query['revoked'] = 1;
            header('Location: '.$redirect.'?'.http_build_query($query));
            exit;
        }
    }
}

$st = $pdo->prepare('SELECT id,full_name,username,email,role,campus,status,last_login,password_changed_at,two_factor_enabled FROM admins WHERE id=?');
$st->execute([$target]);
$account = $st->fetch();
if (!$account) deny_access('Administrator account not found.');

$history = admin_login_history($pdo, $target, 150);
$sessions = admin_active_sessions($pdo, $target);
$currentHash = !empty($_SESSION['admin_session_key'])?hash('sha256', (string)$_SESSION['admin_session_key']):'';
$accounts = [];
if (admin_can_permission('security.login_history')) {
    $accounts = $pdo->query('SELECT id,full_name,username FROM admins ORDER BY full_name')->fetchAll();
}

$accountName = trim((string)($account['full_name'] ?? 'Administrator'));
$initials = 'A';
if ($accountName !== '') {
    $parts = preg_split('/\s+/', $accountName) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials.=strtoupper(substr($part, 0, 1));
    }
    $initials = $initials !== ''?$initials:'A';
}

$roleLabel = admin_role_label($account['role']);
$campusLabel = $account['campus']?strtoupper((string)$account['campus']):'University-wide';
$statusLabel = ucfirst((string)$account['status']);
$lastLoginLabel = $account['last_login']?date('M j, Y · g:i A', strtotime((string)$account['last_login'])):'Never';
$passwordChangedLabel = $account['password_changed_at']?date('M j, Y', strtotime((string)$account['password_changed_at'])):'Not recorded';

admin_header('Account Security', 'Security');
admin_access_security_tabs('account-security.php');
?>
<div class="page-intro access-page-intro access-page-intro--compact">
    <div>
        <span class="page-kicker">LOGIN &amp; DEVICE HISTORY</span>
        <h2>Security activity</h2>
        <p>Review active sessions, recent sign-ins, and authentication protection for the selected administrator account.</p>
    </div>
    <?php if ($accounts) : ?>
        <form method="get" class="access-account-select-form">
            <label class="access-account-picker access-account-picker--compact">
                <span>Switch account</span>
                <select name="admin" onchange="this.form.submit()" aria-label="Administrator account">
                    <?php foreach ($accounts as $a) : ?>
                        <option value="<?=$a['id']?>" <?=$target===(int)$a['id']?'selected':''?>><?=e($a['full_name'].' (@'.$a['username'].')')?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
    <?php endif; ?>
</div>
<?php if ($error) : ?>
    <div class="notice notice--error">
        <?=e($error)?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['revoked'])) : ?>
    <div class="notice">
        The selected device session was signed out.
    </div>
<?php endif; ?>
<section class="panel access-account-banner access-account-banner--compact">
    <div class="access-account-banner__body access-account-banner__body--compact">
        <div class="access-account-identity">
            <div class="access-account-avatar">
                <?=e($initials)?>
            </div>
            <div class="access-account-copy">
                <strong><?=e($accountName)?></strong>
                <span>@<?=e((string)$account['username'])?> · <?=e($roleLabel)?></span>
                <?php if (!empty($account['email'])) : ?>
                    <small><?=e((string)$account['email'])?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="access-account-facts" aria-label="Selected account security summary">
            <div>
                <span>Status</span><strong><?=e($statusLabel)?></strong>
            </div>
            <div>
                <span>Scope</span><strong><?=e($campusLabel)?></strong>
            </div>
            <div>
                <span>Last sign-in</span><strong><?=e($lastLoginLabel)?></strong>
            </div>
            <div>
                <span>2-step</span><strong><?=$account['two_factor_enabled']?'Enabled':'Off'?></strong>
            </div>
            <div>
                <span>Password updated</span><strong><?=e($passwordChangedLabel)?></strong>
            </div>
        </div>
    </div>
</section>
<section class="panel access-section-panel">
    <div class="panel__head access-section-head access-section-head--plain">
        <div>
            <h2>Active sessions</h2>
            <p><?=count($sessions)?> active device<?=count($sessions)===1?'':'s'?>. Device labels are estimated from the browser user agent; only non-current sessions can be revoked here.</p>
        </div>
    </div>
    <div class="panel__body access-session-stack access-session-stack--compact">
        <?php foreach($sessions as $s):
              $isCurrent=$currentHash!=='' && hash_equals($currentHash,(string)$s['session_key_hash']);
            ?>
        <article class="access-session-card access-session-card--compact <?=$isCurrent?'is-current':''?>">
            <div class="access-session-card__main">
                <div class="access-session-icon">
                    ◇
                </div>
                <div class="access-session-copy">
                    <div class="access-session-title-row">
                        <strong><?=e(admin_device_label($s['user_agent']??''))?></strong>
                        <?php if ($isCurrent) : ?>
                            <span class="status-badge status-badge--resolved">Current</span>
                        <?php endif; ?>
                    </div>
                    <p><?=e($s['ip_address']?:'Unknown IP')?> · Started <?=e(date('M j, Y · g:i A',strtotime((string)$s['created_at'])))?></p>
                </div>
            </div>
            <div class="access-session-card__meta">
                <div>
                    <span>Last active</span>
                    <strong><?=e(date('M j, g:i A',strtotime((string)$s['last_seen_at'])))?></strong>
                </div>
                <?php if (!$isCurrent) : ?>
                    <form method="post" data-confirm="Sign out this device session?">
                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                        <input type="hidden" name="session_id" value="<?=$s['id']?>">
                        <input type="hidden" name="admin_id" value="<?=$target?>">
                        <button class="btn btn--soft btn--small">Sign out</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$sessions) : ?>
        <div class="empty-state access-empty-state access-empty-state--compact">
            <strong>No active sessions</strong>
            <span>No recorded device sessions are active for this administrator.</span>
        </div>
    <?php endif; ?>
</div>
</section>
<section class="panel access-section-panel access-login-history-panel">
    <div class="panel__head access-section-head access-section-head--plain">
        <div>
            <h2>Login history</h2>
            <p>Recent sign-in events and authentication results for this account.</p>
        </div>
    </div>
    <?php if ($history) : ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Result</th>
                        <th>Device</th>
                        <th>IP / reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h) : ?>
                        <tr>
                            <td><?=e(date('M j, Y · g:i A',strtotime((string)$h['created_at'])))?></td>
                            <td><?=e(ucwords(str_replace('_',' ',$h['event_type'])))?></td>
                            <td><?=status_badge(ucfirst((string)$h['result']))?></td>
                            <td><?=e($h['device_label']?:admin_device_label($h['user_agent']??''))?></td>
                            <td>
                                <div class="cell-title">
                                    <strong><?=e($h['ip_address']?:'Unknown')?></strong>
                                    <span><?=e($h['reason']?:'No additional reason recorded')?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state access-empty-state access-empty-state--compact access-empty-state--history-compact">
            <strong>No login events recorded</strong>
            <span>New security events will appear here automatically when they are captured.</span>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
