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
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/helpers.php';
require_once '../config/handbook-ai.php';
require_once '_layout.php';
require_admin();
if (!is_system_admin()) deny_access('Only the System Administrator can manage the Handbook Assistant.');

// Seed display settings for the bundled handbook without changing an existing installation.
if (system_setting($pdo, 'handbook_ai_title', null) === null) save_system_setting($pdo, 'handbook_ai_title', 'DMMMSU Student Handbook 2023');
if (system_setting($pdo, 'handbook_ai_filename', null) === null) save_system_setting($pdo, 'handbook_ai_filename', handbook_default_filename());
if (system_setting($pdo, 'handbook_ai_enabled', null) === null) save_system_setting($pdo, 'handbook_ai_enabled', '1');
if (system_setting($pdo, 'handbook_ai_index_status', null) === null) save_system_setting($pdo, 'handbook_ai_index_status', 'not_indexed');

$flash = '';
$flashType = 'ok';

function handbook_activate_remote(PDO $pdo, string $filename, string $title, array $remote, bool $replaceFile = false): void {
    $oldVs = handbook_vector_store_id($pdo);
    $oldFile = handbook_openai_file_id($pdo);
    save_system_setting($pdo, 'handbook_ai_filename', $filename);
    save_system_setting($pdo, 'handbook_ai_title', $title);
    save_system_setting($pdo, 'handbook_ai_openai_file_id', (string)$remote['file_id']);
    save_system_setting($pdo, 'handbook_ai_vector_store_id', (string)$remote['vector_store_id']);
    save_system_setting($pdo, 'handbook_ai_index_status', (string)$remote['status']);
    if ((string)$remote['status'] === 'completed') save_system_setting($pdo, 'handbook_ai_indexed_at', date('Y-m-d H:i:s'));
    if ($oldVs !== '' && $oldVs !== (string)$remote['vector_store_id']) handbook_best_effort_delete_remote($oldVs, $oldFile);
}

// Automatically finish a replacement that was still processing on the previous request.
$pendingVs = trim((string)system_setting($pdo, 'handbook_ai_pending_vector_store_id', ''));
$pendingFile = trim((string)system_setting($pdo, 'handbook_ai_pending_openai_file_id', ''));
$pendingFilename = trim((string)system_setting($pdo, 'handbook_ai_pending_filename', ''));
$pendingTitle = trim((string)system_setting($pdo, 'handbook_ai_pending_title', ''));
if ($pendingVs !== '' && $pendingFile !== '' && handbook_openai_ready()) {
    try {
        $pendingStatus = handbook_vector_file_status($pendingVs, $pendingFile);
        if ($pendingStatus === 'completed') {
            handbook_activate_remote($pdo, $pendingFilename, $pendingTitle ?: pathinfo($pendingFilename, PATHINFO_FILENAME), ['file_id'=>$pendingFile,'vector_store_id'=>$pendingVs,'status'=>'completed']);
            $newPath = handbook_storage_dir().'/'.basename($pendingFilename);
            if (!handbook_build_page_index($newPath, $pendingFilename)) @unlink(handbook_storage_dir().'/handbook-pages.json');
            foreach (['handbook_ai_pending_vector_store_id','handbook_ai_pending_openai_file_id','handbook_ai_pending_filename','handbook_ai_pending_title'] as $key) save_system_setting($pdo, $key, '');
            $flash = 'The replacement handbook finished indexing and is now active.';
        } elseif (in_array($pendingStatus, ['failed','cancelled'], true)) {
            handbook_best_effort_delete_remote($pendingVs, $pendingFile);
            if ($pendingFilename !== '') @unlink(handbook_storage_dir().'/'.basename($pendingFilename));
            foreach (['handbook_ai_pending_vector_store_id','handbook_ai_pending_openai_file_id','handbook_ai_pending_filename','handbook_ai_pending_title'] as $key) save_system_setting($pdo, $key, '');
            $flash = 'The pending handbook could not be indexed. The previous handbook remains active.';
            $flashType = 'error';
        }
    } catch (Throwable $ignored) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    try {
        if ($action === 'toggle') {
            $enabled = handbook_ai_enabled($pdo) ? '0' : '1';
            save_system_setting($pdo, 'handbook_ai_enabled', $enabled);
            admin_log($pdo, 'settings', $enabled === '1' ? 'Enabled Handbook Assistant' : 'Disabled Handbook Assistant', 'Handbook Assistant', 'system');
            $flash = $enabled === '1' ? 'Handbook Assistant enabled on the public website.' : 'Handbook Assistant disabled on the public website.';
        } elseif ($action === 'build') {
            if (!handbook_openai_ready()) throw new RuntimeException('Configure OPENAI_API_KEY and enable PHP cURL first.');
            $path = handbook_active_path($pdo);
            if (!is_file($path)) throw new RuntimeException('The current handbook PDF is missing.');
            $oldVs = handbook_vector_store_id($pdo); $oldFile = handbook_openai_file_id($pdo);
            $remote = handbook_prepare_knowledge_base($pdo, $path, handbook_active_filename($pdo), handbook_title($pdo));
            handbook_activate_remote($pdo, handbook_active_filename($pdo), handbook_title($pdo), $remote);
            $flash = $remote['status'] === 'completed' ? 'Knowledge base built successfully. The public assistant is ready.' : 'The handbook was uploaded and is still indexing. Use Refresh status shortly.';
        } elseif ($action === 'refresh') {
            if (!handbook_openai_ready()) throw new RuntimeException('OPENAI_API_KEY is not configured.');
            $status = handbook_refresh_remote_status($pdo);
            $flash = 'Knowledge base status: '.ucwords(str_replace('_',' ',$status)).'.';
            $flashType = $status === 'completed' ? 'ok' : (in_array($status,['failed','cancelled'],true)?'error':'ok');
        } elseif ($action === 'replace') {
            if (!handbook_openai_ready()) throw new RuntimeException('Configure OPENAI_API_KEY and enable PHP cURL before replacing the handbook.');
            if ($pendingVs !== '' || $pendingFile !== '') throw new RuntimeException('A replacement handbook is already being indexed. Wait for it to finish before uploading another file.');
            if (!isset($_FILES['handbook']) || !is_array($_FILES['handbook']) || (int)$_FILES['handbook']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Choose a PDF handbook to upload.');
            if ((int)$_FILES['handbook']['size'] > 25*1024*1024) throw new RuntimeException('The handbook PDF must be 25 MB or smaller.');
            $tmp = (string)$_FILES['handbook']['tmp_name'];
            $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if ($mime !== 'application/pdf') throw new RuntimeException('Only PDF files are accepted.');
            $title = trim((string)($_POST['title'] ?? '')) ?: 'DMMMSU Student Handbook';
            $title = handbook_text_substr($title, 0, 150);
            $safeBase = preg_replace('/[^A-Za-z0-9 _.-]+/', '', pathinfo((string)$_FILES['handbook']['name'], PATHINFO_FILENAME)) ?: 'Student Handbook';
            $filename = trim($safeBase).'-'.date('Ymd-His').'.pdf';
            $destination = handbook_storage_dir().'/'.$filename;
            if (!move_uploaded_file($tmp, $destination)) throw new RuntimeException('Unable to save the uploaded handbook.');
            try {
                $remote = handbook_prepare_knowledge_base($pdo, $destination, $filename, $title);
                if ($remote['status'] === 'completed') {
                    handbook_activate_remote($pdo, $filename, $title, $remote);
                    if (!handbook_build_page_index($destination, $filename)) @unlink(handbook_storage_dir().'/handbook-pages.json');
                    $flash = 'The new handbook is indexed and is now the active source.';
                } else {
                    save_system_setting($pdo, 'handbook_ai_pending_vector_store_id', (string)$remote['vector_store_id']);
                    save_system_setting($pdo, 'handbook_ai_pending_openai_file_id', (string)$remote['file_id']);
                    save_system_setting($pdo, 'handbook_ai_pending_filename', $filename);
                    save_system_setting($pdo, 'handbook_ai_pending_title', $title);
                    $flash = 'The replacement handbook is still indexing. The current handbook remains active until processing completes.';
                }
                admin_log($pdo, 'settings', 'Uploaded replacement Student Handbook', $filename, 'system');
            } catch (Throwable $e) {
                @unlink($destination);
                throw $e;
            }
        }
    } catch (Throwable $e) {
        $flash = $e->getMessage();
        $flashType = 'error';
        app_log_error('Handbook AI administration failed', ['action'=>$action,'error'=>$e->getMessage()]);
    }
}

$status = handbook_index_status($pdo);
$filename = handbook_active_filename($pdo);
$title = handbook_title($pdo);
$activePath = handbook_active_path($pdo);
$indexedAt = trim((string)system_setting($pdo, 'handbook_ai_indexed_at', ''));
$pendingFilename = trim((string)system_setting($pdo, 'handbook_ai_pending_filename', ''));
$pagesAvailable = is_file(handbook_storage_dir().'/handbook-pages.json');

admin_header('Handbook AI', 'Student Services');
?>
<section class="handbook-admin">
    <?php if ($flash !== '') : ?><div class="handbook-admin__flash handbook-admin__flash--<?=$flashType==='error'?'error':'ok'?>"><?=e($flash)?></div><?php endif; ?>
    <div class="handbook-admin__hero">
        <div class="handbook-admin__hero-copy">
            <span class="handbook-admin__eyebrow">Student Handbook Assistant</span>
            <h2>Approved handbook knowledge base</h2>
            <p>The public chatbot answers only from the active Student Handbook. Replacing the PDF rebuilds its searchable OpenAI File Search knowledge base before the new document becomes active.</p>
        </div>
        <div class="handbook-admin__hero-actions">
            <?php if (is_file($activePath)) : ?><a class="handbook-admin__button" href="../handbook/source.php" target="_blank" rel="noopener">View PDF ↗</a><?php endif; ?>
            <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle"><button class="handbook-admin__button handbook-admin__button--soft" type="submit"><?=handbook_ai_enabled($pdo)?'Disable assistant':'Enable assistant'?></button></form>
        </div>
    </div>

    <div class="handbook-admin__grid">
        <div class="handbook-admin__card">
            <div class="handbook-admin__card-head"><div><h3>Current Handbook</h3><p>Official document used by the public Handbook Assistant.</p></div><span class="handbook-status handbook-status--<?=e($status)?>"><?=e(ucwords(str_replace('_',' ',$status)))?></span></div>
            <div class="handbook-admin__card-body">
                <div class="handbook-doc">
                    <div class="handbook-doc__icon">PDF</div>
                    <div class="handbook-doc__meta"><strong><?=e($title)?></strong><span><?=e($filename)?><?=is_file($activePath)?' · '.e(format_file_size((int)filesize($activePath))):' · File missing'?></span></div>
                    <span class="handbook-status handbook-status--<?=handbook_ai_public_ready($pdo)?'completed':'not_indexed'?>"><?=handbook_ai_public_ready($pdo)?'Public ready':'Setup needed'?></span>
                </div>
                <div class="handbook-admin__facts">
                    <div class="handbook-admin__fact"><span>AI model</span><strong><?=e(handbook_openai_model())?></strong></div>
                    <div class="handbook-admin__fact"><span>Page citations</span><strong><?=$pagesAvailable?'Page index available':'Document-only citation'?></strong></div>
                    <div class="handbook-admin__fact"><span>Last indexed</span><strong><?=e($indexedAt!==''?date('M j, Y · g:i A',strtotime($indexedAt)):'Not indexed yet')?></strong></div>
                </div>
                <?php if (!handbook_openai_ready()) : ?>
                    <div class="handbook-admin__notice" style="margin-top:13px"><strong>API setup required.</strong> Add <code>OPENAI_API_KEY</code> to the project <code>.env</code> file and make sure PHP cURL is enabled. The key is never exposed to the public browser.</div>
                <?php endif; ?>
                <?php if ($pendingFilename !== '') : ?><div class="handbook-admin__pending">Replacement pending: <strong><?=e($pendingFilename)?></strong>. Refresh this page or use Refresh status after OpenAI finishes processing it.</div><?php endif; ?>
                <div class="handbook-admin__actions">
                    <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="build"><button class="handbook-admin__button handbook-admin__button--primary" type="submit" <?=handbook_openai_ready()?'':'disabled'?>><?=handbook_vector_store_id($pdo)!==''?'Rebuild knowledge base':'Build knowledge base'?></button></form>
                    <?php if (handbook_vector_store_id($pdo)!=='') : ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="refresh"><button class="handbook-admin__button" type="submit">Refresh status</button></form><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="handbook-admin__card">
            <div class="handbook-admin__card-head"><div><h3>Replace Handbook</h3><p>The previous handbook stays active until the replacement finishes indexing.</p></div></div>
            <div class="handbook-admin__card-body">
                <form class="handbook-admin__form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="replace">
                    <label>Handbook title<input type="text" name="title" maxlength="150" value="<?=e($title)?>" required></label>
                    <label>New handbook PDF<input type="file" name="handbook" accept="application/pdf,.pdf" required><small>PDF only, up to 25 MB. The new document is not activated until indexing succeeds.</small></label>
                    <button class="handbook-admin__button handbook-admin__button--primary" type="submit" <?=(handbook_openai_ready() && $pendingFilename==='')?'':'disabled'?>>Replace &amp; rebuild</button>
                </form>
            </div>
        </div>
    </div>

    <div class="handbook-admin__card">
        <div class="handbook-admin__card-head"><div><h3>Assistant safeguards</h3><p>Rules applied to every student question.</p></div></div>
        <div class="handbook-admin__card-body handbook-admin__principles">
            <div class="handbook-admin__principle"><i>✓</i><div><strong>Handbook-only answers</strong><span>The model is instructed to answer only from retrieved approved handbook content and to refuse unsupported policy claims.</span></div></div>
            <div class="handbook-admin__principle"><i>📖</i><div><strong>Source verification</strong><span>Responses display the active document and, when the local page index can verify the retrieved excerpt, the matching PDF page.</span></div></div>
            <div class="handbook-admin__principle"><i>!</i><div><strong>No official adjudication</strong><span>The assistant summarizes policy; it does not make final disciplinary decisions or replace Student Affairs and Services.</span></div></div>
            <div class="handbook-admin__principle"><i>↗</i><div><strong>E-Sumbong stays separate</strong><span>Chat messages are not formal concern records. Students can choose to open E-Sumbong when they need further assistance.</span></div></div>
        </div>
    </div>
</section>
<?php admin_footer(); ?>
