<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__).'/config/helpers.php';
$tests = 0;
$failed = 0;
function check(bool $ok, string $name): void {
    global $tests, $failed;
    $tests++;
    if ($ok) {
        echo "[PASS] $name\n";
    } else {
        $failed++;
        echo "[FAIL] $name\n";
    }
}

function sql_defines_table(string $sql, string $table): bool {
    return (bool)preg_match('/\bCREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?'.preg_quote($table, '/').'`?\s*\(/i', $sql);
}

function local_literal_references_exist(string $root, string $relativeFile): bool {
    $path = $root.'/'.ltrim($relativeFile, '/');
    $source = (string)@file_get_contents($path);
    if ($source === '') return false;
    $baseDir = dirname($path);
    if (preg_match('~<base\s+href=["\']([^"\']+)["\']~i', $source, $m)) $baseDir = realpath(dirname($path).'/'.$m[1])?:dirname($path).'/'.$m[1];
    if (!preg_match_all('~\b(?:href|src|action)\s*=\s*["\']([^"\']+)["\']~i', $source, $matches)) return true;
    foreach ($matches[1] as $value) {
        $value = html_entity_decode(trim((string)$value), ENT_QUOTES|ENT_HTML5, 'UTF-8');
        if ($value === '' || str_contains($value, '<?') || str_contains($value, '${') || preg_match('~^(?:#|https?:|mailto:|tel:|javascript:|data:|//)~i', $value)) continue;
        $value = preg_split('/[?#]/', $value, 2)[0] ?? '';
        if ($value === '') continue;
        $target = str_starts_with($value, '/')?$root.'/'.ltrim($value, '/'):$baseDir.'/'.$value;
        if (!file_exists($target)) return false;
    }
    return true;
}

check(post_slugify('Hello, DMMMSU USC!') === 'hello-dmmmsu-usc', 'Publication slug normalization');
check(admin_password_strength_error('StrongPass!2026') === null, 'Strong password accepted');
check(admin_password_strength_error('aaaaaaaaaaaa') !== null, 'Weak repeated password rejected');
check(concern_valid_college('MLUC', 'College of Information Technology (CIT)') === true, 'Valid MLUC college accepted');
check(concern_valid_college('NLUC', 'College of Information Technology') === false, 'Cross-campus college rejected');
check(concern_type_default_priority('Concern') === 'Normal', 'Concern starts at Normal priority');
check(concern_type_default_priority('Complaint') === 'High', 'Complaint starts at High priority');
check(concern_type_default_priority('Suggestion') === 'Low' && concern_type_default_priority('Feedback') === 'Low', 'Suggestion and Feedback keep lightweight Low starting priority');
check(count(language_moderation_matches('Please do not write f.u.c.k in a suggestion.')) > 0, 'English profanity moderation detects punctuation obfuscation');
check(count(language_moderation_matches('Sinabihan niya akong g4g0 sa harap ng klase.')) > 0, 'Filipino profanity moderation detects a common leet substitution');
check(count(language_moderation_matches('Please review the student services process.')) === 0, 'Language moderation does not flag ordinary respectful text');
$secret = 'Sensitive test value '.bin2hex(random_bytes(4));
$cipher = app_encrypt_sensitive($secret);
check(is_string($cipher) && str_starts_with($cipher, 'enc:v1:'), 'Sensitive value encrypted');
check(app_decrypt_sensitive($cipher) === $secret, 'Sensitive value decrypts correctly');
$totpSecret = admin_totp_secret();
$encrypted = admin_encrypt_2fa_secret($totpSecret);
check(admin_decrypt_2fa_secret($encrypted) === $totpSecret, '2FA secret encrypted at rest and recoverable');
$root = dirname(__DIR__);
check(is_file($root.'/uploads/concerns/.htaccess'), 'Private concern upload protection present');
check(is_file($root.'/config/dev-router.php'), 'LAN development router present');
check(is_file($root.'/scripts/maintenance-cron.php'), 'Scheduled maintenance runner present');
$router = (string)file_get_contents($root.'/config/dev-router.php');
check(str_contains($router, 'isPrivateConcernUpload'), 'LAN router blocks private concern evidence');
$updates = (string)file_get_contents($root.'/news/updates.php');
check(str_contains($updates, 'SELECT COUNT(*) FROM posts') && str_contains($updates, ' LIMIT '), 'Newsroom uses server-side pagination');
check(str_contains($updates, 'p.is_university_featured=1') && str_contains($updates, "\$campus === 'ALL'"), 'Newsroom All Featured section uses the separate university-controlled flag');
check(str_contains($updates, "1=0") && str_contains($updates, 'local USC Featured content must not be mistaken for All Featured'), 'All Featured never falls back to the USC local Featured flag');
check(str_contains($updates, "\$campus !== 'ALL'") && str_contains($updates, 'temporary local lead'), 'Each individual campus keeps its own Featured presentation with a latest-story fallback');
$adminPosts = (string)file_get_contents($root.'/admin/posts.php');
check(str_contains($adminPosts, 'Only the University Student Council or System Administrator can choose publications for the university-wide All Featured section.') && str_contains($adminPosts, 'is_usc_role() || is_system_admin()') && str_contains($adminPosts, 'Feature in All'), 'USC and System Administrator receive direct controls for university-wide Featured placement');
check(str_contains($adminPosts, 'UPDATE posts SET is_university_featured=0 WHERE is_university_featured=1') && str_contains($adminPosts, 'UPDATE posts SET is_featured=0 WHERE category=? AND id<>? AND is_featured=1'), 'All Featured and per-portal Featured selections are independent single slots');
check(str_contains($adminPosts, '<span><strong>Featured</strong></span>') && !str_contains($adminPosts, 'publicationFeatureHelp'), 'Publication editor uses the simplified Featured control');
check(!str_contains($adminPosts, 'Display label') && str_contains($adminPosts, '$labelMap = [') && str_contains($adminPosts, "'sluc' => 'SLUC'"), 'Publication source label is generated automatically from the selected portal');
check(!str_contains($adminPosts, 'Featured sections are independent.') && !str_contains($adminPosts, 'publication-feature-separation-note'), 'Publication list no longer shows the Featured-separation notice');
check(str_contains($adminPosts, "header('Location: posts.php?saved=1');") && !str_contains($adminPosts, "header('Location: posts.php?edit='.\$id.'&saved=1');"), 'Saving a publication returns to the News & Updates management list');
check(str_contains($adminPosts, 'pendingMediaFiles') && str_contains($adminPosts, 'new DataTransfer()') && str_contains($adminPosts, 'data-remove-pending-media'), 'Computer media picker accumulates repeated selections until publication save');
check(strpos($adminPosts, '$uploadErrors = upload_post_images') < strpos($adminPosts, '$libraryErrors = attach_media_to_post'), 'Computer uploads are appended before Media Library selections for predictable new-post cover order');
check(str_contains($adminPosts, 'maxPublicationPhotos = 100') && str_contains($adminPosts, 'upload_post_images($pdo, $id, $imageUploads, 100)') && str_contains($adminPosts, '/ 100 photos'), 'Publication photo gallery is limited to 100 photos');
check(str_contains($adminPosts, 'publication-media-preview--inline') && str_contains($adminPosts, 'publicationMediaCount') && str_contains($adminPosts, '<h3>Media</h3>') && !str_contains($adminPosts, '<h3>Photos</h3>') && !str_contains($adminPosts, '<h3>Videos</h3>'), 'Photos and videos share one unified Media card and live count');
check(str_contains($adminPosts, 'maxPublicationVideoBytes = 250 * 1024 * 1024') && str_contains($adminPosts, 'upload_post_videos') && str_contains($adminPosts, 'MP4, WebM or MOV · 250 MB each'), 'Publication editor supports videos up to 250 MB');
check(str_contains($updates, 'public_file_record($img)') && str_contains($updates, "['_public_url']"), 'Newsroom validates stored publication images before rendering');
check(!is_file($root.'/admin/feature-requests.php') && !is_file($root.'/database/patch_20260831_news_feature_requests.sql'), 'Campus News feature-request workflow is absent');


$migrations = (string)file_get_contents($root.'/config/migrations.php');
check(str_contains($migrations, "'2026081912'") && str_contains($migrations, 'contact_email_cipher'), 'Advanced migrations include protected contact email and pending-account policy');
check(str_contains($migrations, "'2026090501'") && str_contains($migrations, 'student_urgency') && str_contains($migrations, 'esumbong_suggestion_feedback_cooldown_hours'), 'Type-aware E-Sumbong policy migration present');
check(str_contains($migrations, "'2026090502'") && str_contains($migrations, 'concern_student_updates') && str_contains($migrations, 'concern_satisfaction') && str_contains($migrations, 'privacy_level') && str_contains($migrations, 'claimed_by'), 'E-Sumbong operational hardening migration present');
check(str_contains($migrations, "'2026090101'") && str_contains($migrations, 'Normalize independent per-portal and university-wide News Featured slots'), 'Featured-slot normalization migration present');
check(str_contains($migrations, "'2026090102'") && str_contains($migrations, 'post_videos') && str_contains($migrations, '250 MB'), 'Publication-video migration present');
$article = (string)file_get_contents($root.'/news/article.php');
check(str_contains($article, 'post_videos($pdo') && str_contains($article, 'story-video-stream') && str_contains($article, '<video controls'), 'Public article renders publication videos');
check(str_contains($article, '$singlePhotoOnly = $imageCount === 1 && $videoCount === 0') && str_contains($article, '$photosOnlyGallery = $imageCount > 1 && $videoCount === 0'), 'Article keeps a single photo prominent but moves multi-photo publications into the photo stream');
check(str_contains($article, '$singleVideoOnly = $imageCount === 0 && $videoCount === 1') && str_contains($article, 'story-feature-video'), 'A single video is presented prominently near the top of the article');
check(str_contains($article, '$mixedMediaGallery = $imageCount > 0 && $videoCount > 0') && str_contains($article, 'story-photo-stream') && str_contains($article, 'story-video-stream'), 'Mixed photo and video publications use clean media streams below the article');
check(!str_contains($article, '<h2>Photos</h2>') && !str_contains($article, '>PHOTOS<') && !str_contains($article, 'story-media-count') && !str_contains($article, 'Related stories'), 'Article removes Photos labels/counts and Related Stories');
check(str_contains($article, 'story-photo-stream') && str_contains($article, 'story-photo-item') && !str_contains($article, 'data-gallery-expand') && !str_contains($article, 'View all <?=$imageCount?> photos'), 'Article shows every publication image in a full photo stream instead of an album-style thumbnail gallery');
check(!str_contains($article, '<p class="story-deck">') && !str_contains($article, "elseif (trim((string)\$post['excerpt']))"), 'Article Detail uses the full article content only; Summary remains preview-only');
$preview = (string)file_get_contents($root.'/admin/post-preview.php');
check(!str_contains($preview, '<p class="story-deck">') && !str_contains($preview, "elseif (trim((string)\$post['excerpt']))"), 'Admin Preview matches the summary-free Article Detail layout');
check(str_contains($preview, '$singlePhotoOnly') && str_contains($preview, '$singleVideoOnly') && str_contains($preview, '$mixedMediaGallery') && str_contains($preview, 'story-photo-stream'), 'Admin publication Preview follows the same article media hierarchy and photo stream');
$track = (string)file_get_contents($root.'/esumbong/track.php');
check(str_contains($track, "'Resolved', 'Closed'") && str_contains($track, 'resolution_summary'), 'Tracker distinguishes Resolved and Closed and exposes the resolution summary');
check(str_contains($track, 'tracker-reference-field') && !str_contains($track, "name=\"token\""), 'Tracker uses reference number only');
check(str_contains($track, 'verify_attachments') && str_contains($track, 'concern_private_identity($pdo, $c)') && str_contains($track, 'tracker-attachments__grid'), 'Tracker requires Student ID verification before viewing protected concern files');
check(str_contains($track, '$isAttachmentVerification') && str_contains($track, "'esumbong-track-files-v2'") && str_contains($track, 'if (!$isAttachmentVerification && !$isVerificationRedirect && !$isFollowupSubmission && !$isSatisfactionSubmission)') && !str_contains($track, 'platform_rate_limit_or_429($pdo, \'esumbong-track-files\''), 'Attachment verification is not double-counted by the normal tracker limiter and failed checks stay inline');
check(!str_contains($track, 'target="_blank"') && str_contains($track, 'tracker-attachment-card__actions'), 'Tracked attachment View links stay in the current tab');
check(str_contains($track, '$caseEvidenceAttachments') && str_contains($track, 'Files &amp; evidence') && str_contains($track, 'tracker-attachments__grid--compact') && !str_contains($track, 'Your submitted files'), 'Track Concern shows only compact administrator case evidence and hides submitted-file repetition');
check(str_contains($track, 'View submitted concern') && str_contains($track, 'tracker-submission-dialog') && str_contains($track, 'verify_submission') && str_contains($track, "'subject', 'message'"), 'Tracker provides a protected View submitted concern modal using the existing Student ID verification session');
check(str_contains($track, 'Your message') && str_contains($track, 'Concern type') && str_contains($track, 'College / Program') && str_contains($track, 'Submitted files'), 'Submitted-concern modal shows the original report details and original attachment list after verification');
check(str_contains($track, 'tracker_store_one_time_verification') && str_contains($track, 'tracker_consume_one_time_verification') && !str_contains($track, 'tracker_remember_file_access'), 'Tracker file verification is one-page-view only and resets after refresh');
check(str_contains($track, 'data-close-submission-modal') && str_contains($track, "submissionDialog.addEventListener('cancel'") && str_contains($track, 'closeSubmission'), 'Submitted-concern modal can close from X, backdrop, and Escape');
check(str_contains($track, 'trackerFileViewerDialog') && str_contains($track, 'data-tracker-file-view') && str_contains($track, 'openFileViewer'), 'Protected attachment View opens in an in-page tracker viewer');
check(substr_count($track, 'new URL(url, document.baseURI)') >= 2 && !str_contains($track, 'new URL(url, window.location.href)'), 'Protected PDF and viewer download URLs resolve against the document base path');
check(str_contains($track, 'data-student-id-format') && str_contains($track, "formatted += '-' + digits.slice(3, 7)") && str_contains($track, "formatted += '-' + digits.slice(7, 8)"), 'Tracker Student ID input auto-formats as 000-0000-0');
$trackAttachment = (string)file_get_contents($root.'/esumbong/track-attachment.php');
check(str_contains($trackAttachment, "IN ('student','student_followup','admin')") && str_contains($trackAttachment, 'concern_public_attachment_signature_is_valid') && str_contains($trackAttachment, 'Content-Disposition'), 'Public attachment endpoint serves student submissions and handler case evidence only through short-lived signed view/download links');
$esumbong = (string)file_get_contents($root.'/esumbong/esumbong.php');
check(str_contains($esumbong, 'public_csrf_token()') && str_contains($esumbong, 'verify_public_csrf()') && !str_contains($esumbong, 'verify_csrf();'), 'Public E-Sumbong submission uses CSRF protection independent of the administrator session');
check(str_contains($track, 'public_csrf_token()') && str_contains($track, 'verify_public_csrf()') && !str_contains($track, 'verify_csrf();'), 'Public concern tracking uses the independent public CSRF token');
$concernAdmin = (string)file_get_contents($root.'/admin/concerns.php');
$concernCss = (string)file_get_contents($root.'/public/assets/css/esumbong-review.css');
$concernLayout = (string)file_get_contents($root.'/admin/_layout.php');
check(str_contains($concernLayout, 'esumbong-review.css') && str_contains($concernAdmin, 'caseAttachmentFiles') && str_contains($concernAdmin, 'case-evidence-upload__remove') && str_contains($concernCss, '.case-evidence-upload__file'), 'E-Sumbong pending evidence uses an isolated styled file list with individual removal controls');
check(str_contains($concernAdmin, '$adminEvidence') && str_contains($concernAdmin, 'Saved case evidence') && str_contains($concernCss, '.case-evidence-saved__item'), 'Saved administrator case evidence remains visible beside the upload control after saving');
check(str_contains($concernAdmin, "format_file_size((int)\$a['file_size'])") && !str_contains($concernAdmin, 'human_bytes('), 'Saved case evidence uses the existing file-size helper and cannot crash the Review Concern renderer');
check(str_contains($concernAdmin, 'remove_case_evidence') && str_contains($concernAdmin, "LOWER(source)='admin'") && str_contains($concernAdmin, 'Removed case evidence') && str_contains($concernCss, '.case-evidence-saved__remove'), 'Saved administrator case evidence can be removed explicitly without exposing student-submitted evidence to deletion');
check(str_contains($concernAdmin, "validation=resolution") && str_contains($concernAdmin, 'caseResolutionSummary') && str_contains($concernAdmin, 'caseResolutionError') && !str_contains($concernAdmin, "app_render_error_page(422, 'Resolution summary required'"), 'Resolved/Closed validation stays inline on Review Concern instead of leaving the workflow with HTTP 422');
check(str_contains($concernAdmin, 'Concern Updates') && str_contains($concernAdmin, 'caseReferralSection') && str_contains($concernAdmin, 'caseResolutionSection') && str_contains($concernAdmin, "statusSelect.value === 'Referred'") && str_contains($concernAdmin, "['Resolved','Closed'].includes(statusSelect.value)") && !str_contains($concernAdmin, '<h3>Notes</h3>'), 'E-Sumbong review shows referral and resolution fields only for relevant statuses and labels notes as Concern Updates');
$trackCss = (string)file_get_contents($root.'/public/assets/css/track.css');
check(str_contains($trackCss, 'grid-template-columns: 108px minmax(0, 1fr)') && str_contains($trackCss, 'max-width: 760px'), 'Tracked student attachments use compact horizontal cards instead of oversized poster cards');
check(str_contains($esumbong, 'request_exceeds_php_post_limit()') && str_contains($esumbong, "app_render_error_page(413, 'Upload too large'"), 'Oversized concern evidence is reported as an upload-limit error instead of a false 419');
check(str_contains($esumbong, 'concern_submission_restriction($pdo, $studentId, $type)') && str_contains($esumbong, 'concern_type_default_priority($type)'), 'E-Sumbong applies type-aware duplicate/cooldown protection and internal starting priority');
check(str_contains($esumbong, 'concern_language_moderation($pdo, $type, $subject, $message)') && str_contains($esumbong, "'language_review'") && is_file($root.'/config/profanity.php'), 'E-Sumbong applies centralized English/Filipino language moderation before saving');
check(str_contains($concernAdmin, 'language-review-badge') && str_contains($concernAdmin, 'Review wording in context'), 'Flagged formal reports show contextual language-review guidance to case handlers');
check(str_contains($concernAdmin, "view=completed") && str_contains($concernAdmin, "c.status NOT IN ('Resolved','Closed')") && str_contains($concernAdmin, "c.status IN ('Resolved','Closed')") && str_contains($concernAdmin, 'Completed Cases'), 'E-Sumbong separates active work from Resolved/Closed completed cases');
check(str_contains($concernAdmin, 'was reopened and returned to the Active Queue') && str_contains($concernAdmin, 'was completed and moved out of the Active Queue') && str_contains($concernAdmin, 'cannot reopen completed concerns'), 'Completed E-Sumbong cases move automatically and reopening is permission-protected');
check(str_contains($concernAdmin, 'Awaiting Student Information') && str_contains($concernAdmin, 'Assigned to me') && str_contains($concernAdmin, 'Needs attention') && str_contains($concernAdmin, 'Privacy classification') && str_contains($concernAdmin, 'Archived'), 'E-Sumbong review queue includes request-info, assignment inbox, privacy, and archive controls');
check(str_contains($concernAdmin, 'concern_acquire_case_lock') && str_contains($concernAdmin, 'Take over editing'), 'Concurrent E-Sumbong case editing is protected by an ownership lock');
check(!str_contains($esumbong, 'Impact / Urgency') && !str_contains($esumbong, 'submissionPolicyCard') && !str_contains($esumbong, 'urgencyLevel'), 'Public E-Sumbong keeps policy and urgency controls out of the student form');
$tracker = (string)file_get_contents($root.'/esumbong/track.php');
check(str_contains($tracker, 'submit_followup') && str_contains($tracker, 'Awaiting Student Information') && str_contains($tracker, 'submit_satisfaction'), 'Track Concern supports verified student follow-ups, requested information, and post-resolution satisfaction');
$helpersSource = (string)file_get_contents($root.'/config/helpers.php');
check(str_contains($helpersSource, 'concern_find_similar_recent') && str_contains($helpersSource, 'concern_run_due_soon_reminders') && str_contains($helpersSource, 'concern_run_retention_maintenance'), 'E-Sumbong helpers cover similarity warnings, SLA reminders, and retention maintenance');
check(str_contains($helpersSource, 'DMMMSU_USC_PUBLIC_CSRF') && str_contains($helpersSource, "'samesite' => 'Strict'") && str_contains($helpersSource, 'double-submit CSRF cookie'), 'Independent public CSRF cookie is same-site protected');
check(str_contains($helpersSource, 'concern_public_attachment_signature') && str_contains($helpersSource, 'esumbong-public-attachment-v1') && str_contains($helpersSource, 'app_data_key()'), 'Student attachment links are signed with the protected application data key and expire quickly');
$reports = (string)file_get_contents($root.'/admin/reports.php');
check((bool)preg_match('/concern_private_identity\s*\(\s*\$pdo\s*,\s*\$r\s*\)/', $reports) && str_contains($reports, "'Protected'"), 'Authorized reports decrypt protected identity while privacy-safe reports stay masked');
$identityEndpoint = (string)file_get_contents($root.'/admin/concern-identity.php');
check((bool)preg_match("/REQUEST_METHOD'\]\s*!==\s*'POST'/", $identityEndpoint) && str_contains($identityEndpoint, 'verify_csrf()'), 'Protected identity reveal requires POST and CSRF');
$style = (string)file_get_contents($root.'/public/assets/css/style.css');
$shared = (string)file_get_contents($root.'/public/assets/css/shared-nav.css');
check(!str_contains($style, '\\n') && !str_contains($shared, '\\n'), 'Stylesheets contain real line breaks rather than literal escape text');
$accounts = (string)file_get_contents($root.'/admin/accounts.php');
check((bool)preg_match("/\[\s*'active'\s*,\s*'inactive'\s*,\s*'pending'\s*,\s*'suspended'\s*,\s*'archived'\s*\]/", $accounts) && str_contains($accounts, 'New accounts require a temporary password'), 'Full account lifecycle and server-side temporary-password validation present');

// Governance and long-term operations.
$advanced = (string)file_get_contents($root.'/config/advanced.php');
check(str_contains($advanced, 'function effective_permission_overrides') || str_contains($advanced, 'role_permission_overrides'), 'Granular permission override helpers present');
check(str_contains($advanced, 'dashboard_widget_preferences'), 'Per-account dashboard customization helper present');
check(str_contains($advanced, 'function platform_rate_limit'), 'Database-backed request rate limiting present');
check(str_contains($advanced, 'function admin_login_event'), 'Login security event recorder present');
check(str_contains($advanced, 'function audit_verify_chain'), 'Tamper-evident audit verifier present');
check(str_contains($advanced, 'function media_optimize_file'), 'Media optimization helper present');
check(str_contains($advanced, 'function offsite_backup_copy'), 'Secondary/off-site backup copy helper present');

check(is_file($root.'/admin/academic-years.php'), 'Academic-year administration page present');
check(is_file($root.'/admin/officers.php'), 'Officer-term history page present');
check(is_file($root.'/admin/announcements.php'), 'Announcements and emergency banner administration present');
check(is_file($root.'/admin/storage.php'), 'Storage management dashboard present');
check(is_file($root.'/admin/disaster-recovery.php'), 'Disaster recovery dashboard present');
check(is_file($root.'/admin/account-security.php'), 'Login/session security history page present');
check(is_file($root.'/admin/permissions.php'), 'Roles and permissions editor present');

$layout = (string)file_get_contents($root.'/admin/_layout.php');
check(str_contains($layout, 'Academic Years') && str_contains($layout, 'Disaster Recovery') && str_contains($layout, 'Account Security'), 'Administration navigation exposes governance and recovery tools');
$dashboard = (string)file_get_contents($root.'/admin/dashboard.php');
check(str_contains($dashboard, 'dashboard-summary-strip') && str_contains($dashboard, 'Needs attention') && !str_contains($dashboard, 'save_widgets'), 'Dashboard uses the current streamlined role-aware layout');
$search = (string)file_get_contents($root.'/admin/search.php');
check(str_contains($search, 'announcements') && str_contains($search, 'officers') && str_contains($search, 'academic_years') && str_contains($search, 'admin_activity_logs'), 'Global search spans governance and audit resources');

$reports = (string)file_get_contents($root.'/admin/reports.php');
check(str_contains($reports, 'summary_excel') && str_contains($reports, "name=\"academic_year\"") && str_contains($reports, "name=\"case_status\"") && str_contains($reports, "name=\"priority\"") && str_contains($reports, "name=\"concern_type\"") && str_contains($reports, "name=\"office\""), 'Reports provide Excel export and advanced operational filters');
check(str_contains($reports, 'Student satisfaction') && str_contains($reports, 'Awaiting Student Information'), 'Reports include satisfaction and the request-more-information workflow status');

$publicHeader = (string)file_get_contents($root.'/src/includes/public-header.php');
$aboutPage = (string)file_get_contents($root.'/about.php');
check(str_contains($publicHeader, 'public_alerts_html') && str_contains($aboutPage, 'current_officers') && str_contains($aboutPage, 'officer-group'), 'Public portals display scoped announcements and officer history on About');

$migrations = (string)file_get_contents($root.'/config/migrations.php');
foreach (['2026081913', '2026081914', '2026081915', '2026081916'] as $ver) {
    check(str_contains($migrations, "'$ver'"), "Migration $ver present");
}
foreach (['academic_years', 'officers', 'announcements', 'role_permission_overrides', 'admin_permission_overrides', 'admin_dashboard_widgets', 'admin_login_events'] as $table) {
    check(str_contains($migrations, "CREATE TABLE IF NOT EXISTS $table"), "Migration defines $table");
}

$prodSql = (string)file_get_contents($root.'/database/dmmmsu_usc.production.sql');
$demoSql = (string)file_get_contents($root.'/database/dmmmsu_usc.sql');
foreach (['academic_years', 'officers', 'announcements', 'role_permission_overrides', 'admin_permission_overrides', 'admin_dashboard_widgets', 'admin_login_events'] as $table) {
    check(sql_defines_table($prodSql, $table) && sql_defines_table($demoSql, $table), "Fresh-install SQL includes $table");
}
check(str_contains($prodSql, 'academic_year_id INT NULL') && str_contains($prodSql, 'record_hash CHAR(64)') && str_contains($prodSql, 'offsite_path VARCHAR(500)'), 'Fresh-install SQL includes governance, audit and recovery columns');

check(is_file($root.'/README.md'), 'Consolidated technical documentation present');
check(is_file($root.'/tests/http-smoke.php') && is_file($root.'/scripts/windows/RUN_HTTP_SMOKE_TESTS.bat'), 'Optional HTTP end-to-end smoke test runner present');
check(str_contains($router, "'docs'") && str_contains($router, "'md'"), 'Markdown documentation is blocked from public LAN access');
check(is_file($root.'/uploads/thumbnails/.htaccess'), 'Generated thumbnails directory blocks executable uploads');

$app = (string)file_get_contents($root.'/config/app.php');
check(str_contains($app, 'Content-Security-Policy') && str_contains($app, 'X-Content-Type-Options') && str_contains($app, 'Permissions-Policy'), 'Browser security headers are configured');
$env = (string)file_get_contents($root.'/.env.example');
check(str_contains($env, 'APP_DATA_KEY') && str_contains($env, 'APP_CSP_ENFORCE'), 'Sensitive key and CSP controls are environment-configurable');

$maintenance = (string)file_get_contents($root.'/config/maintenance.php');
check(str_contains($maintenance, 'offsite_backup_copy') && str_contains($maintenance, 'notification_cleanup_expired'), 'Scheduled maintenance covers secondary backup and notification retention');
$adminMaintenance = (string)file_get_contents($root.'/admin/maintenance.php');
check(str_contains($adminMaintenance, 'safety_backup_filename'), 'Migration workflow records its safety backup');

$footer = (string)file_get_contents($root.'/src/includes/public-footer.php');
check(str_contains($footer, 'offline') && str_contains($footer, 'navigator.onLine'), 'Public UI includes degraded-network feedback');
$adminCss = (string)file_get_contents($root.'/public/assets/css/admin.css');
check(str_contains($adminCss, ':focus-visible') && str_contains($adminCss, '@media print'), 'Admin UI includes keyboard focus and print rules');

// Router must keep sensitive operational paths private on the PHP LAN server.
foreach (["'config'", "'database'", "'storage'", "'backups'", "'scripts'", "'tests'", "'docs'"] as $dir) {
    check(str_contains($router, $dir), "LAN router protects $dir");
}

// Final QA/refinement checks.
foreach (['index.php', 'about.php', 'news/updates.php', 'news/article.php', 'esumbong/esumbong.php', 'esumbong/track.php', 'campus/nluc.php', 'campus/mluc.php', 'campus/sluc.php', 'campus/ous.php'] as $entry) {
    check(local_literal_references_exist($root, $entry), 'Local public references resolve: '.$entry);
}
check(is_file($root.'/tests/preflight.php') && is_file($root.'/scripts/windows/RUN_PREFLIGHT.bat'), 'Environment preflight runner present');
$dbConfig = (string)file_get_contents($root.'/config/database.php');
check(str_contains($dbConfig, "extension_loaded('pdo_mysql')") && str_contains($dbConfig, 'Server configuration incomplete'), 'Missing PDO MySQL driver produces a clear diagnostic');
$legacyCssComments = false;
foreach (glob($root.'/public/assets/css/*.css')?:[] as $css) {
    if (preg_match('/\/\*\s*V\d+\b/i', (string)file_get_contents($css))) {
        $legacyCssComments = true;
        break;
    }
}
check(!$legacyCssComments, 'Legacy CSS revision comments removed without changing rules');

echo "\n$tests test(s), $failed failure(s).\n";
exit($failed?1:0);
