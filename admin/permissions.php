<?php
require_once '../config/database.php';
require_once '_layout.php';
require_admin();
if (!admin_can_permission('permissions.view')) deny_access('Your role cannot view access control.');
$roles = admin_roles();
$definitions = admin_permission_definitions();
$baseMatrix = admin_role_permission_matrix();
$editable = admin_can_permission('permissions.manage');
$ready = db_table_exists($pdo, 'role_permission_overrides') && db_table_exists($pdo, 'admin_permission_overrides');
$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$editable) deny_access('Only authorized System Administrators can change permissions.');
    if (!$ready) throw new RuntimeException('Run pending migrations before editing permissions.');
    try {
        $mode = (string)($_POST['mode'] ?? 'role');
        $keys = (array)($_POST['permission_keys'] ?? []);
        $values = (array)($_POST['permission_values'] ?? []);
        if (count($keys) !== count($values)) throw new RuntimeException('Invalid permission submission.');
        if ($mode === 'role') {
            $role = (string)($_POST['role'] ?? '');
            if (!isset($roles[$role])) throw new RuntimeException('Choose a valid role.');
            if ($role === 'admin') throw new RuntimeException('System Administrator always has full access and cannot be restricted.');
            $pdo->prepare('DELETE FROM role_permission_overrides WHERE role=?')->execute([$role]);
            foreach ($keys as $i => $permission) {
                $permission = (string)$permission;
                if (!isset($definitions[$permission])) continue;
                $value = (string)($values[$i] ?? 'inherit');
                if ($value === 'allow') permission_save_role_override($pdo, $role, $permission, true);
                elseif ($value === 'deny') permission_save_role_override($pdo, $role, $permission, false);
            }
            admin_log($pdo, 'security', 'Updated role permissions', $roles[$role].' permission policy changed', 'role_permission', null, null, ['role' => $role]);
            $notice = 'Role permission policy saved.';
        }
        else {
            $adminId = (int)($_POST['admin_id'] ?? 0);
            $st = $pdo->prepare('SELECT id,full_name,role FROM admins WHERE id=?');
            $st->execute([$adminId]);
            $account = $st->fetch();
            if (!$account) throw new RuntimeException('Administrator account not found.');
            $pdo->prepare('DELETE FROM admin_permission_overrides WHERE admin_id=?')->execute([$adminId]);
            foreach ($keys as $i => $permission) {
                $permission = (string)$permission;
                if (!isset($definitions[$permission])) continue;
                $value = (string)($values[$i] ?? 'inherit');
                if ($value === 'allow') permission_save_admin_override($pdo, $adminId, $permission, true);
                elseif ($value === 'deny') permission_save_admin_override($pdo, $adminId, $permission, false);
            }
            admin_log($pdo, 'security', 'Updated account permission overrides', $account['full_name'].' permission overrides changed', 'admin', $adminId, null, ['account_overrides' => true]);
            admin_notify($pdo, 'Access permissions updated', 'Your administrator permissions were updated by the System Administrator.', 'account-security.php', 'warning', null, null, $adminId, 'security');
            $notice = 'Account-specific permission overrides saved.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$effective = $ready?role_permission_effective_matrix($pdo):$baseMatrix;
$roleFocus = (string)($_GET['role'] ?? 'usc');
if (!isset($roles[$roleFocus])) $roleFocus = 'usc';
$adminFocus = (int)($_GET['admin'] ?? 0);
$admins = $pdo->query('SELECT id,full_name,username,role,campus,status FROM admins ORDER BY full_name')->fetchAll();
if (!$adminFocus && $admins) $adminFocus = (int)$admins[0]['id'];
$roleOverrides = [];
$accountOverrides = [];
if ($ready) {
    $st = $pdo->prepare('SELECT permission,allowed FROM role_permission_overrides WHERE role=?');
    $st->execute([$roleFocus]);
    foreach ($st->fetchAll() as $r) $roleOverrides[$r['permission']] = (bool)$r['allowed'];
    if ($adminFocus) {
        $st = $pdo->prepare('SELECT permission,allowed FROM admin_permission_overrides WHERE admin_id=?');
        $st->execute([$adminFocus]);
        foreach ($st->fetchAll() as $r) $accountOverrides[$r['permission']] = (bool)$r['allowed'];
    }
}
$groups = [];
foreach ($definitions as $key => $meta) $groups[$meta[0]][$key] = $meta[1];
$selectedAdmin = null;
foreach ($admins as $a) if ((int)$a['id'] === $adminFocus) $selectedAdmin = $a;
$roleOverrideCount = count($roleOverrides);
$accountOverrideCount = count($accountOverrides);
$roleDescriptions = admin_role_descriptions();
admin_header('Roles & Permissions', 'Access control');
admin_access_security_tabs('permissions.php');
?>
<div class="permissions-workspace">
    <div class="permission-top-actions" aria-label="Access control shortcuts">
        <a class="btn btn--soft" href="accounts.php">Accounts</a>
        <a class="btn btn--soft" href="account-security.php">Login history</a>
    </div>
    <?php if (!$ready) : ?>
        <div class="notice notice--warning">
            The configurable permission tables are not installed yet. The baseline matrix is shown below; run pending migrations to enable editing.
        </div>
    <?php endif; ?>
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
    <section class="role-structure-section">
        <div class="role-structure-heading">
            <span class="permission-eyebrow">ROLE STRUCTURE</span>
            <h2>Access hierarchy</h2>
            <p>University-level roles oversee all portals. Campus roles share the same full operational controls but remain limited to their assigned campus.</p>
        </div>
        <div class="role-structure-grid">
            <?php foreach($roles as $roleKey=>$roleLabel):
                    $permissionCount=count($baseMatrix[$roleKey]??[]);
                    $isCampusRole=in_array($roleKey,['sbo_adviser','campus_sbo'],true);
                  ?>
            <article class="role-structure-card role-structure-card--<?=e($roleKey)?> <?=$roleKey==='admin'?'is-primary':''?> <?=$isCampusRole?'is-campus':''?>">
                <div class="role-structure-card__top">
                    <span class="role-structure-scope"><?=e(admin_role_scope_label($roleKey))?></span>
                    <?php if ($roleKey === 'admin') : ?>
                        <span class="role-structure-fixed">Fixed full access</span>
                    <?php endif; ?>
                </div>
                <h3><?=e(admin_role_option_label($roleKey))?></h3>
                <p><?=e($roleDescriptions[$roleKey]??'Administrative role.')?></p>
                <div class="role-structure-card__footer">
                    <span><?=e((string)$permissionCount)?> default permissions</span>
                    <?php if ($roleKey === 'usc') : ?>
                        <strong>Operational peer: Student Affairs and Services</strong>
                    <?php elseif ($roleKey === 'sas_director') : ?>
                        <strong>Operational peer: USC</strong>
                    <?php elseif ($isCampusRole) : ?>
                        <strong>Shared campus baseline</strong>
                    <?php else: ?>
                        <strong>Overall control</strong>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<details class="panel permission-matrix-disclosure">
    <summary class="permission-matrix-summary">
        <div>
            <span class="permission-eyebrow">ROLE ACCESS</span>
            <strong>Effective permissions</strong>
            <small>View the complete role access matrix only when needed.</small>
        </div>
        <div class="permission-matrix-summary__meta">
            <span><?=count($definitions)?> permissions · <?=count($roles)?> roles</span>
            <i aria-hidden="true"></i>
        </div>
    </summary>
    <div class="permission-matrix-dropdown">
        <div class="permission-matrix-guide">
            <span><b class="is-allowed">✓</b> Allowed</span><span><b class="is-denied">—</b> Not allowed</span><small>Scroll horizontally only if needed.</small>
        </div>
        <div class="permission-matrix-scroll">
            <table class="permission-table permission-table--unified permission-table--clean">
                <thead>
                    <tr>
                        <th class="permission-col--permission">Permission</th>
                        <?php foreach ($roles as $role => $label) : ?>
                            <th class="permission-role-col"><strong><?=e(admin_role_option_label($role))?></strong></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group => $items) : ?>
                        <tr class="permission-module-row">
                            <th colspan="<?=count($roles)+1?>"><span><?=e($group)?></span><small><?=count($items)?> permission<?=count($items)===1?'':'s'?></small></th>
                        </tr>
                        <?php foreach ($items as $permission => $label) : ?>
                            <tr class="permission-data-row">
                                <td class="permission-col--permission"><strong><?=e($label)?></strong></td>
                                <?php foreach ($roles as $role => $roleLabel) :$allowed = in_array($permission, $effective[$role] ?? [], true); ?>
                                    <td class="permission-role-col"><span class="permission-state <?=$allowed?'is-allowed':'is-denied'?>" title="<?=e($roleLabel.' · '.$label)?>"><?=$allowed?'✓':'—'?></span></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</details>
<?php if ($editable && $ready) : ?>
    <section class="permission-editor-heading">
        <div>
            <span class="permission-eyebrow">EXCEPTIONS</span>
            <h2>Permission overrides</h2>
            <p>Use overrides sparingly. Inherited defaults remain the easiest policy to maintain.</p>
        </div>
    </section>
    <div class="permission-edit-grid permission-edit-grid--organized">
        <section class="panel permission-policy-card">
            <div class="panel__head permission-policy-head">
                <div>
                    <h2>Role policy</h2>
                    <p>Change access for every administrator using the selected role.</p>
                </div>
                <span class="permission-override-badge"><?=$roleOverrideCount?> override<?=$roleOverrideCount===1?'':'s'?></span>
            </div>
            <form method="get" class="permission-selector-bar">
                <input type="hidden" name="admin" value="<?=$adminFocus?>">
                <label>
                    <span>Role</span>
                    <select name="role" onchange="this.form.submit()">
                        <?php foreach ($roles as $key => $label) : ?>
                            <option value="<?=e($key)?>" <?=$roleFocus===$key?'selected':''?>><?=e(admin_role_option_label($key))?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
            <?php if ($roleFocus === 'admin') : ?>
                <div class="permission-fixed-role-note">
                    <strong>System Administrator is fixed to full access.</strong>
                    <span>This role cannot be denied permissions or reduced by overrides, preventing administrative lockout.</span>
                </div>
            <?php else: ?>
                <form method="post" class="permission-editor permission-editor--accordion">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="mode" value="role">
                    <input type="hidden" name="role" value="<?=e($roleFocus)?>">
                    <?php foreach ($groups as $group => $items) : ?>
                        <details class="permission-editor-group">
                            <summary><span><?=e($group)?></span><small><?=count($items)?> permission<?=count($items)===1?'':'s'?></small></summary>
                            <div class="permission-editor-group__body">
                                <?php foreach($items as $permission=>$label):$default=in_array($permission,$baseMatrix[$roleFocus]??[],true);$override=array_key_exists($permission,$roleOverrides)?($roleOverrides[$permission]?'allow':'deny'):'inherit';?>
                                    <div class="permission-editor-row">
                                        <div>
                                            <strong><?=e($label)?></strong><small>Default: <?=$default?'Allow':'Deny'?></small>
                                        </div>
                                        <input type="hidden" name="permission_keys[]" value="<?=e($permission)?>">
                                        <select name="permission_values[]">
                                            <option value="inherit" <?=$override==='inherit'?'selected':''?>>Use default (<?=$default?'Allow':'Deny'?>)</option>
                                            <option value="allow" <?=$override==='allow'?'selected':''?>>Allow</option>
                                            <option value="deny" <?=$override==='deny'?'selected':''?>>Deny</option>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                    <div class="form-actions permission-save-bar">
                        <span>Changes apply to the selected role.</span>
                        <button class="btn">Save role policy</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
        <section class="panel permission-policy-card">
            <div class="panel__head permission-policy-head">
                <div>
                    <h2>Account exceptions</h2>
                    <p>Override the role only for one administrator account.</p>
                </div>
                <span class="permission-override-badge"><?=$accountOverrideCount?> override<?=$accountOverrideCount===1?'':'s'?></span>
            </div>
            <form method="get" class="permission-selector-bar">
                <input type="hidden" name="role" value="<?=e($roleFocus)?>">
                <label>
                    <span>Administrator</span>
                    <select name="admin" onchange="this.form.submit()">
                        <?php foreach ($admins as $a) : ?>
                            <option value="<?=$a['id']?>" <?=$adminFocus===(int)$a['id']?'selected':''?>><?=e($a['full_name'].' · '.admin_role_option_label((string)$a['role']))?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
            <?php if ($selectedAdmin && $selectedAdmin['role'] === 'admin') : ?>
                <div class="permission-fixed-role-note">
                    <strong>System Administrator accounts always inherit full access.</strong>
                    <span>Account-specific restrictions are ignored for this protected role.</span>
                </div>
            <?php elseif ($selectedAdmin) : ?>
                <form method="post" class="permission-editor permission-editor--accordion">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                    <input type="hidden" name="mode" value="admin">
                    <input type="hidden" name="admin_id" value="<?=$adminFocus?>">
                    <?php foreach ($groups as $group => $items) : ?>
                        <details class="permission-editor-group">
                            <summary><span><?=e($group)?></span><small><?=count($items)?> permission<?=count($items)===1?'':'s'?></small></summary>
                            <div class="permission-editor-group__body">
                                <?php foreach($items as $permission=>$label):$roleDefault=in_array($permission,$effective[$selectedAdmin['role']]??[],true);$override=array_key_exists($permission,$accountOverrides)?($accountOverrides[$permission]?'allow':'deny'):'inherit';?>
                                    <div class="permission-editor-row">
                                        <div>
                                            <strong><?=e($label)?></strong><small>Role default: <?=$roleDefault?'Allow':'Deny'?></small>
                                        </div>
                                        <input type="hidden" name="permission_keys[]" value="<?=e($permission)?>">
                                        <select name="permission_values[]">
                                            <option value="inherit" <?=$override==='inherit'?'selected':''?>>Use role (<?=$roleDefault?'Allow':'Deny'?>)</option>
                                            <option value="allow" <?=$override==='allow'?'selected':''?>>Allow for account</option>
                                            <option value="deny" <?=$override==='deny'?'selected':''?>>Deny for account</option>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                    <div class="form-actions permission-save-bar">
                        <span>Account exceptions take priority over the role.</span>
                        <button class="btn">Save account overrides</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>
</div>
<?php
admin_footer();
?>
