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
require_once __DIR__.'/../config/helpers.php';
require_admin();

function admin_nav_items(): array {
    $items = [
    'dashboard.php' => ['Dashboard', 'Overview', '⌂'],
    'posts.php' => ['News & Updates', 'Publishing', '▤'],
    ];
    if (can_manage_homepage()) $items['hero.php'] = ['Homepage', 'Hero slider', '◇'];
    if (admin_can_permission('officers.manage') || admin_can_permission('academic.manage')) {
        $canOfficers = admin_can_permission('officers.manage');
        $canAcademic = admin_can_permission('academic.manage');
        $leadershipHref = $canOfficers?'officers.php':'academic-years.php';
        $leadershipSubtitle = $canOfficers && $canAcademic?'Roster & academic years':($canOfficers?'Officer roster':'Academic years');
        $items[$leadershipHref] = ['Leadership', $leadershipSubtitle, '♙'];
    }
    if (can_manage_media()) $items['media.php'] = ['Media Library', 'Images & files', '▧'];
    $items['concerns.php'] = ['E-Sumbong', 'Case management', '✦'];
    if (can_view_reports()) $items['reports.php'] = ['Reports', 'Analytics', '◫'];
    return $items;
}
function admin_system_nav(): array {
    $items = ['notifications.php' => ['Notifications', 'Updates', '●']];
    if (can_view_activity()) $items['activity.php'] = ['Activity Log', 'Audit trail', '≡'];
    if (can_delete_admin_records()) $items['trash.php'] = ['Trash', 'Restore records', '⌫'];
    if (can_manage_settings()) $items['settings.php'] = ['System Settings', 'Configuration', '⚙'];
    return $items;
}
function admin_header(string $title, string $eyebrow = 'Administration'): void {
    global $pdo;
    $current = basename($_SERVER['PHP_SELF'] ?? '');
    $adminName = $_SESSION['admin_name'] ?? 'USC Administrator';
    $adminRole = admin_role_label();
    $adminCampus = admin_campus();
    $adminProfile = $_SESSION['admin_profile_image'] ?? null;
    $publicPortal = admin_scope_code() ?: 'USC';
    $publicWebsite = '../'.hero_portal_home_path($publicPortal);
    $unread = admin_unread_notifications($pdo);
    $currentReturn = $current.(!empty($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'');
    $selectedPortal = admin_selected_portal();
?>
<!doctype html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" href="../public/assets/images/favicon.png">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="color-scheme" content="light">
        <title><?=e($title)?> | USC Administration</title>
        <link rel="stylesheet" href="../public/assets/css/admin.css?v=<?=e((string)@filemtime(__DIR__.'/../public/assets/css/admin.css'))?>">
        <?php if ($current === 'concerns.php') : ?>
            <link rel="stylesheet" href="../public/assets/css/esumbong-review.css?v=<?=e((string)@filemtime(__DIR__.'/../public/assets/css/esumbong-review.css'))?>">
        <?php endif; ?>
        <?php if ($current === 'handbook-ai.php') : ?>
            <link rel="stylesheet" href="../public/assets/css/handbook-admin.css?v=<?=e((string)@filemtime(__DIR__.'/../public/assets/css/handbook-admin.css'))?>">
        <?php endif; ?>
    </head>
    <body>
        <a class="skip-link admin-skip-link" href="#adminMain">Skip to main content</a>
        <div class="admin-app">
            <aside class="admin-sidebar" id="adminSidebar">
                <a class="admin-brand" href="dashboard.php">
                <img src="../public/assets/images/usc-logo.jpg" alt="USC seal" class="admin-brand__seal">
                <div>
                    <strong>USC Administration</strong><span>Student Council Portal</span>
                </div>
                </a>
                <nav class="admin-nav" aria-label="Administration">
                    <div class="admin-nav__label">
                        Workspace
                    </div>
                    <a class="admin-nav__item <?=$current==='dashboard.php'?'active':''?>" href="dashboard.php">
                    <span class="admin-nav__icon">⌂</span><span><b>Dashboard</b><small>Overview</small></span>
                    </a>
                    <a class="admin-nav__item <?=in_array($current,['posts.php','hero.php','announcements.php'],true)?'active':''?>" href="posts.php">
                    <span class="admin-nav__icon">▤</span><span><b>Content</b><small>News & homepage</small></span>
                    </a>
                    <?php if (admin_can_permission('officers.manage') || admin_can_permission('academic.manage')) : ?>
                        <?php
                                $canOfficers=admin_can_permission('officers.manage');
                                $canAcademic=admin_can_permission('academic.manage');
                                $leadershipHref=$canOfficers?'officers.php':'academic-years.php';
                                $leadershipSubtitle=$canOfficers&&$canAcademic?'Roster & academic years':($canOfficers?'Officer roster':'Academic years');
                              ?>
                        <a class="admin-nav__item <?=in_array($current,['officers.php','academic-years.php'],true)?'active':''?>" href="<?=e($leadershipHref)?>">
                        <span class="admin-nav__icon">♙</span><span><b>Leadership</b><small><?=e($leadershipSubtitle)?></small></span>
                        </a>
                    <?php endif; ?>
                    <?php if (can_manage_media()) : ?>
                        <a class="admin-nav__item <?=$current==='media.php'?'active':''?>" href="media.php">
                        <span class="admin-nav__icon">▧</span><span><b>Media Library</b><small>Images & files</small></span>
                        </a>
                    <?php endif; ?>
                    <a class="admin-nav__item <?=$current==='concerns.php'?'active':''?>" href="concerns.php">
                    <span class="admin-nav__icon">✦</span><span><b>E-Sumbong</b><small>Case management</small></span>
                    </a>
                </nav>
                <div class="admin-sidebar__footer">
                    <a class="admin-user admin-user--linked" href="profile.php">
                    <?=admin_avatar_html($adminName,$adminProfile)?>
                    <div>
                        <strong><?=e($adminName)?></strong><span><?=e($adminRole)?><?= $adminCampus?' · '.e($adminCampus):''?></span>
                    </div>
                    </a>
                    <a class="admin-logout" href="logout.php">Sign out</a>
                </div>
            </aside>
            <div class="admin-content-shell">
                <header class="admin-topbar admin-topbar--refined admin-topbar--clean">
                    <button class="admin-menu" type="button" aria-label="Open navigation" onclick="document.body.classList.toggle('sidebar-open')">☰</button>
                    <div class="admin-page-title">
                        <span><?=e($eyebrow)?></span>
                        <h1><?=e($title)?></h1>
                    </div>
                    <form class="admin-global-search admin-global-search--clean" action="search.php" method="get">
                        <span class="admin-search-icon" aria-hidden="true"></span>
                        <input name="q" type="search" placeholder="Search administration…" aria-label="Search administration">
                    </form>
                    <div class="admin-topbar__right admin-topbar__right--clean">
                        <?php if (admin_is_global()) : ?>
                            <form class="topbar-portal-switcher" method="post" action="portal-switch.php" title="Change the working portal for scoped modules">
                                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                <input type="hidden" name="return" value="<?=e($currentReturn)?>">
                                <select name="portal" aria-label="Working portal" onchange="this.form.submit()">
                                    <option value="" <?=$selectedPortal===null?'selected':''?>>All portals</option>
                                    <option value="USC" <?=$selectedPortal==='USC'?'selected':''?>>University Student Council</option>
                                    <?php foreach (admin_campuses() as $code => $name) : ?>
                                        <option value="<?=e($code)?>" <?=$selectedPortal===$code?'selected':''?>><?=e($name)?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php endif; ?>
                        <div class="admin-control-cluster admin-control-cluster--simple">
                            <a class="topbar-notification topbar-notification--clean <?=$unread?'has-unread':''?>" href="notifications.php" aria-label="Notifications" title="Notifications">
                            <span class="notification-symbol" aria-hidden="true"></span>
                            <?php if ($unread) : ?>
                                <b><?=$unread>99?'99+':$unread?></b>
                            <?php endif; ?>
                            </a>
                            <?php
                            $canSystemManagement = admin_can_permission('system.health') || admin_can_permission('storage.view') || admin_can_permission('disaster_recovery.view') || can_manage_backups() || can_manage_migrations() || can_delete_admin_records();
                            $canAccessSecurity = admin_can_permission('permissions.view') || admin_can_permission('security.login_history');
                            $hasTopbarTools = can_view_reports() || $canSystemManagement || $canAccessSecurity || can_view_activity() || is_system_admin();
                            $systemManagementPages = ['system-health.php', 'storage.php', 'disaster-recovery.php', 'maintenance.php', 'trash.php'];
                            $accessSecurityPages = ['permissions.php', 'account-security.php'];
                            $systemManagementHref = admin_can_permission('system.health')?'system-health.php':(admin_can_permission('storage.view')?'storage.php':(admin_can_permission('disaster_recovery.view')?'disaster-recovery.php':((can_manage_backups() || can_manage_migrations())?'maintenance.php':'trash.php')));
                            $accessSecurityHref = admin_can_permission('permissions.view')?'permissions.php':'account-security.php';
                            ?>
                            <?php if ($hasTopbarTools) : ?>
                                <div class="topbar-tools-menu">
                                    <button type="button" class="topbar-tools-trigger topbar-tools-trigger--clean" onclick="toggleTopbarMenu(event,this.parentElement,'tools')" aria-label="Open tools menu" aria-haspopup="menu" aria-expanded="false">
                                        <span>Tools</span><i>⌄</i>
                                    </button>
                                    <div class="topbar-tools-dropdown topbar-tools-dropdown--compact topbar-tools-dropdown--aligned topbar-tools-dropdown--grouped">
                                        <?php if (can_view_reports()) : ?>
                                            <a class="<?=$current==='reports.php'?'active':''?>" href="reports.php"><span class="tool-menu-icon">◫</span><strong>Reports</strong></a>
                                        <?php endif; ?>
                                        <?php if (is_system_admin()) : ?>
                                            <a class="<?=$current==='handbook-ai.php'?'active':''?>" href="handbook-ai.php"><span class="tool-menu-icon">✦</span><strong>Handbook AI</strong></a>
                                        <?php endif; ?>
                                        <?php if ($canSystemManagement) : ?>
                                            <a class="<?=in_array($current,$systemManagementPages,true)?'active':''?>" href="<?=e($systemManagementHref)?>"><span class="tool-menu-icon">◉</span><strong>System Management</strong></a>
                                        <?php endif; ?>
                                        <?php if ($canAccessSecurity) : ?>
                                            <a class="<?=in_array($current,$accessSecurityPages,true)?'active':''?>" href="<?=e($accessSecurityHref)?>"><span class="tool-menu-icon">✓</span><strong>Access &amp; Security</strong></a>
                                        <?php endif; ?>
                                        <?php if (can_view_activity()) : ?>
                                            <a class="<?=$current==='activity.php'?'active':''?>" href="activity.php"><span class="tool-menu-icon">≡</span><strong>Activity Log</strong></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (can_manage_accounts()) : ?>
                                <a class="topbar-account-link topbar-account-link--clean <?=$current==='accounts.php'?'active':''?>" href="accounts.php">
                                <span class="topbar-account-icon">A</span>
                                <strong>Accounts</strong>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="topbar-user-menu">
                            <button type="button" class="topbar-user-trigger topbar-user-trigger--clean" onclick="toggleTopbarMenu(event,this.parentElement,'user')" aria-label="Account menu" aria-haspopup="menu" aria-expanded="false">
                                <?=admin_avatar_html($adminName,$adminProfile)?>
                                <span class="topbar-user-copy">
                                <strong><?=e($adminName)?></strong>
                                </span>
                                <span class="topbar-chevron">⌄</span>
                            </button>
                            <div class="topbar-user-dropdown topbar-user-dropdown--focused">
                                <a class="<?= $current==='profile.php'?'active':'' ?>" href="profile.php">My Account</a>
                                <?php if (admin_can_permission('security.login_history')) : ?>
                                    <a class="<?= $current==='account-security.php'?'active':'' ?>" href="account-security.php">Account Security</a>
                                <?php endif; ?>
                                <?php if (can_manage_settings()) : ?>
                                    <a href="settings.php">System Settings</a>
                                <?php endif; ?>
                                <a href="<?=e($publicWebsite)?>" target="_blank" rel="noopener">View Public Website ↗</a>
                                <a href="logout.php">Sign out</a>
                            </div>
                        </div>
                    </div>
                </header>
                <main class="admin-main" id="adminMain" tabindex="-1">
                    <?php }

                    function admin_leadership_tabs(string $current): void {
                        $canOfficers=admin_can_permission('officers.manage');
                        $canAcademic=admin_can_permission('academic.manage');
                        // Do not render a one-item tab bar. Campus-scoped roles only manage the roster,
                        // so a lone "Officer Roster" tab adds visual noise and implies missing access.
                        if(!($canOfficers && $canAcademic)) return;
                    ?>
                    <div class="admin-section-tabs leadership-section-tabs" role="navigation" aria-label="Leadership management">
                        <a class="<?=$current==='officers.php'?'active':''?>" href="officers.php">Officer Roster</a>
                        <a class="<?=$current==='academic-years.php'?'active':''?>" href="academic-years.php">Academic Years</a>
                    </div>
                    <?php
                    }

                    function admin_content_tabs(string $current): void {
                      $tabPortal=strtoupper(trim((string)($_GET['portal']??$_POST['portal']??'')));
                      $heroHomeHref='hero.php'.($tabPortal!==''?'?portal='.rawurlencode($tabPortal):'');
                      $heroPromoHref='hero.php?view=promotions'.($tabPortal!==''?'&portal='.rawurlencode($tabPortal):'');
                    ?>
                    <div class="admin-section-tabs" role="navigation" aria-label="Content management">
                        <a class="<?=$current==='posts.php'?'active':''?>" href="posts.php">News &amp; Updates</a>
                        <?php if (admin_can_permission('announcements.manage')) : ?>
                            <a class="<?=$current==='announcements.php'?'active':''?>" href="announcements.php">Announcements</a>
                        <?php endif; ?>
                        <?php if (can_manage_homepage()) : ?>
                            <a class="<?=$current==='hero.php'?'active':''?>" href="<?=e($heroHomeHref)?>">Homepage</a>
                            <a class="<?=$current==='hero-promotions'?'active':''?>" href="<?=e($heroPromoHref)?>">Promotion Requests</a>
                        <?php endif; ?>
                    </div>
                    <?php }


                    function admin_system_management_tabs(string $current): void {?>
                    <div class="admin-section-tabs admin-section-tabs--tools" role="navigation" aria-label="System management">
                        <?php if (admin_can_permission('system.health')) : ?>
                            <a class="<?=$current==='system-health.php'?'active':''?>" href="system-health.php">System Health</a>
                        <?php endif; ?>
                        <?php if (admin_can_permission('storage.view')) : ?>
                            <a class="<?=$current==='storage.php'?'active':''?>" href="storage.php">Storage</a>
                        <?php endif; ?>
                        <?php if (admin_can_permission('disaster_recovery.view')) : ?>
                            <a class="<?=$current==='disaster-recovery.php'?'active':''?>" href="disaster-recovery.php">Disaster Recovery</a>
                        <?php endif; ?>
                        <?php if (can_manage_backups() || can_manage_migrations()) : ?>
                            <a class="<?=$current==='maintenance.php'?'active':''?>" href="maintenance.php">Maintenance</a>
                        <?php endif; ?>
                        <?php if (can_delete_admin_records()) : ?>
                            <a class="<?=$current==='trash.php'?'active':''?>" href="trash.php">Trash</a>
                        <?php endif; ?>
                    </div>
                    <?php }

                    function admin_access_security_tabs(string $current): void {?>
                    <div class="admin-section-tabs admin-section-tabs--tools" role="navigation" aria-label="Access and security">
                        <?php if (admin_can_permission('permissions.view')) : ?>
                            <a class="<?=$current==='permissions.php'?'active':''?>" href="permissions.php">Permissions</a>
                        <?php endif; ?>
                        <?php if (admin_can_permission('security.login_history')) : ?>
                            <a class="<?=$current==='account-security.php'?'active':''?>" href="account-security.php">Account Security</a>
                        <?php endif; ?>
                    </div>
                    <?php }

                    function admin_footer(): void {?>
                </main>
            </div>
        </div>
        <dialog id="adminConfirmDialog" class="admin-confirm-dialog" aria-labelledby="adminConfirmTitle">
        <div class="admin-confirm-dialog__icon">
            !
        </div>
        <h2 id="adminConfirmTitle">Confirm action</h2>
        <p id="adminConfirmMessage">Are you sure you want to continue?</p>
        <div class="admin-confirm-dialog__actions">
            <button type="button" class="btn btn--soft" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="button" class="btn btn--danger" id="adminConfirmAccept">Continue</button>
        </div>
        </dialog>
        <script>
function toggleTopbarMenu(event, menu) {
    event.stopPropagation();
    document.querySelectorAll('.topbar-tools-menu.open,.topbar-user-menu.open').forEach(item => {
        if (item !== menu) {
            item.classList.remove('open');
            const otherTrigger = item.querySelector('button[aria-expanded]');
            if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
        }
    });
    const isOpen = menu.classList.toggle('open');
    const trigger = menu.querySelector('button');
    if (trigger) trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}
document.addEventListener('click', function (e) {
    if (document.body.classList.contains('sidebar-open') && !e.target.closest('.admin-sidebar') && !e.target.closest('.admin-menu')) {
        document.body.classList.remove('sidebar-open');
    }
    if (!e.target.closest('.topbar-user-menu')) {
        document.querySelectorAll('.topbar-user-menu.open').forEach(x => x.classList.remove('open'));
    }
    if (!e.target.closest('.topbar-tools-menu')) {
        document.querySelectorAll('.topbar-tools-menu.open').forEach(x => x.classList.remove('open'));
    }
});
window.addEventListener('beforeunload', function (e) {
    const form = document.querySelector('form[data-unsaved-warning="1"]');
    if (form && form.dataset.dirty === "1") { e.preventDefault(); e.returnValue = ''; }
});
document.querySelectorAll('form[data-unsaved-warning="1"]').forEach(form => {
    form.addEventListener('input', () => form.dataset.dirty = "1");
    form.addEventListener('submit', () => form.dataset.dirty = "0");
});
const adminConfirmDialog = document.getElementById('adminConfirmDialog');
const adminConfirmMessage = document.getElementById('adminConfirmMessage');
const adminConfirmAccept = document.getElementById('adminConfirmAccept');
let pendingConfirmation = null;
function openAdminConfirmation(message, callback) {
    if (!adminConfirmDialog || typeof adminConfirmDialog.showModal !== 'function') { if (window.confirm(message)) callback(); return; }
    adminConfirmMessage.textContent = message || 'Are you sure you want to continue?';
    pendingConfirmation = callback; adminConfirmDialog.showModal();
}
adminConfirmAccept?.addEventListener('click', () => { const callback = pendingConfirmation; pendingConfirmation = null; adminConfirmDialog.close(); if (callback) callback(); });
adminConfirmDialog?.addEventListener('close', () => { pendingConfirmation = null; });
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return; }
        event.preventDefault(); const submitter = event.submitter;
        openAdminConfirmation(form.dataset.confirm || 'Are you sure you want to continue?', () => { form.dataset.confirmed = '1'; if (submitter) form.requestSubmit(submitter); else form.requestSubmit(); });
    });
});
document.querySelectorAll('button[data-confirm],a[data-confirm]').forEach(control => {
    control.addEventListener('click', event => {
        if (control.dataset.confirmed === '1') { delete control.dataset.confirmed; return; }
        event.preventDefault(); event.stopPropagation();
        openAdminConfirmation(control.dataset.confirm || 'Are you sure you want to continue?', () => { control.dataset.confirmed = '1'; control.click(); });
    });
});

// Connectivity and duplicate-submit protection.
(() => {
    let banner = document.getElementById('networkStatusBanner');
    if (!banner) { banner = document.createElement('div'); banner.id = 'networkStatusBanner'; banner.className = 'network-status-banner'; banner.setAttribute('role', 'status'); banner.setAttribute('aria-live', 'polite'); document.body.appendChild(banner); }
    const sync = () => { const offline = !navigator.onLine; banner.textContent = offline ? 'You are offline. Changes will be sent when your connection returns.' : ''; banner.classList.toggle('is-visible', offline); };
    window.addEventListener('online', sync); window.addEventListener('offline', sync); sync();
    document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
        if (event.defaultPrevented || form.dataset.allowRepeat === '1' || form.dataset.confirm === '1') return;
        const btn = event.submitter; if (btn && btn.dataset.directAction === '1') return;
        if (form.dataset.submitting === '1') { event.preventDefault(); return; }
        form.dataset.submitting = '1'; if (btn) { btn.dataset.originalText = btn.textContent; btn.setAttribute('aria-disabled', 'true'); btn.classList.add('is-submitting'); }
    }));
})();

// Auto-dismiss temporary success/info notices after 5 seconds.
// Errors and warnings remain until the user navigates away or resolves them.
(() => {
    const transientNotices = [...document.querySelectorAll('.admin-main .notice')].filter(notice =>
        !notice.classList.contains('notice--error') &&
        !notice.classList.contains('notice--warning') &&
        notice.dataset.persistent !== '1'
    );
    if (!transientNotices.length) return;

    transientNotices.forEach(notice => {
        notice.classList.add('notice--auto');
        window.setTimeout(() => {
            if (!notice.isConnected) return;
            notice.classList.add('is-auto-dismissing');
            window.setTimeout(() => notice.remove(), 320);
        }, 5000);
    });

    // Remove flash-only query flags so refreshing does not replay a dismissed notice.
    const flashKeys = [
        'saved', 'status_updated', 'sessions_revoked', 'disabled', 'deleted', 'created',
        'promotion_requested', 'promotion_withdrawn', 'promotion_approved', 'promotion_changes',
        'promotion_rejected', 'promotion_revoked', 'preferences_saved', 'two_factor_enabled',
        'two_factor_disabled', 'uploaded', 'verified', 'updated', 'image_deleted', 'cover_set'
    ];
    try {
        const url = new URL(window.location.href);
        let changed = false;
        flashKeys.forEach(key => { if (url.searchParams.has(key)) { url.searchParams.delete(key); changed = true; } });
        if (changed) {
            history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') + url.hash);
        }
    } catch (_e) { }
})();
</script>
    </body>
</html>
<?php
}
function status_badge(string $status): string {
$key = strtolower(str_replace([' ', '/'], ['-', '-'], $status));
return '<span class="status-badge status-badge--'.e($key).'">'.e($status).'</span>';
}
