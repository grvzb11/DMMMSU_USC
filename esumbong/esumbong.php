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
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/config/helpers.php';
$requestedCampus = strtoupper(trim($_GET['campus'] ?? ''));
$campusPresetMap = [
'NLUC' => 'North La Union Campus',
'MLUC' => 'Mid La Union Campus',
'SLUC' => 'South La Union Campus',
'OUS' => 'Open University System',
];
$campusPreset = $campusPresetMap[$requestedCampus] ?? '';
$sourcePortal = $campusPreset !== '' ? $requestedCampus : 'USC';
$isCampusPortal = $sourcePortal !== 'USC';
$portalNames = ['USC' => 'University Student Council', 'NLUC' => 'North La Union Campus', 'MLUC' => 'Mid La Union Campus', 'SLUC' => 'South La Union Campus', 'OUS' => 'Open University System'];
$sourcePortalName = $portalNames[$sourcePortal] ?? 'University Student Council';
$ref = '';
$error = '';
$submissionWarning = '';
$submissionRestriction = null;
$publicCsrf = public_csrf_token();
$publicTextLength = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
};
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (request_exceeds_php_post_limit()) {
        $limit = trim((string)ini_get('post_max_size'));
        app_render_error_page(413, 'Upload too large', 'The concern form exceeded the server upload limit'.($limit !== ''?' of '.$limit:'').'. Reduce the evidence file size or ask the System Administrator to increase PHP post_max_size.');
    }
    verify_public_csrf();
    if (!empty($_POST['website'])) {
        http_response_code(202);
        exit;
    }
    $rate = concern_submission_rate_limit($pdo, admin_current_ip());
    if (!$rate['allowed']) $error = 'Too many submissions were attempted from this connection. Please wait '.max(1, (int)ceil($rate['retry_after']/60)).' minute(s) and try again.';
    $campus = $isCampusPortal ? $campusPreset : trim($_POST['campus'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $type = trim($_POST['concernType'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $privacyConsent = !empty($_POST['privacy_consent']);
    $studentName = trim((string)($_POST['studentName'] ?? ''));
    $studentId = trim((string)($_POST['studentId'] ?? ''));
    $contactEmail = trim((string)($_POST['contactEmail'] ?? ''));
    $studentCampusCode = concern_campus_code($campus);
    $allowedTypes = ['Concern', 'Complaint', 'Suggestion', 'Feedback'];
    $languageModeration = concern_language_moderation($pdo, $type, $subject, $message);
    if ($error !== '') {
        // Keep the rate-limit error.
    } elseif ($campus === '' || $college === '' || $type === '' || $subject === '' || $message === '' || $studentName === '' || $studentId === '') {
        $error = 'Please complete all required fields, including your Student Name and Student ID.';
    } elseif (!isset(admin_campuses()[$studentCampusCode])) {
        $error = 'Please select a valid campus.';
    } elseif (!concern_valid_college($studentCampusCode, $college)) {
        $error = 'The selected College / Program does not belong to the selected campus. Please select it again.';
    } elseif (!in_array($type, $allowedTypes, true)) {
        $error = 'Please choose a valid concern type.';
    } elseif ($publicTextLength($subject)>140 || $publicTextLength($message)>1000 || $publicTextLength($studentName)>150 || $publicTextLength($contactEmail)>190) {
        $error = 'One or more fields are longer than the allowed limit.';
    } elseif ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid contact email address or leave it blank.';
    } elseif (!$privacyConsent) {
        $error = 'Please confirm the privacy notice before submitting.';
    } elseif (!preg_match('/^\d{3}-\d{4}-\d$/', $studentId)) {
        $error = 'Student ID must use the format 000-0000-0.';
    } elseif (($languageModeration['action'] ?? 'allow') === 'block') {
        $error = 'Please revise your Subject or Message. The submission contains inappropriate or offensive language. Use respectful wording before submitting.';
    } elseif (($submissionRestriction = concern_submission_restriction($pdo, $studentId, $type)) !== null) {
        if (($submissionRestriction['restriction_kind'] ?? '') === 'cooldown') {
            $retryAt = !empty($submissionRestriction['retry_at']) ? date('M j, Y \a\t g:i A', strtotime((string)$submissionRestriction['retry_at'])) : 'the cooldown reset time';
            $error = 'A recent '.strtolower($type).' was already submitted. To reduce duplicate or excessive submissions, another '.strtolower($type).' can be sent after '.$retryAt.'.';
        } else {
            $error = 'You already have an active '.strtolower($type).' case. Please track the existing case and wait until it is resolved or closed before submitting another '.$type.'.';
        }
    } else {
        $ref = ref_code();
        $priority = concern_type_default_priority($type);
        $hasWorkflow = db_column_exists($pdo, 'concerns', 'due_at');
        $hasPriority = db_column_exists($pdo, 'concerns', 'priority');
        $hasPrivateIdentity = db_table_exists($pdo, 'concern_private_identity');
        $dueAt = $hasWorkflow?concern_default_due_at($pdo, $priority):null;
        $retentionDays = max(30, min(3650, (int)(system_setting($pdo, 'privacy_retention_days', '730') ?? 730)));
        $retentionUntil = date('Y-m-d', strtotime('+'.$retentionDays.' days'));
        $sensitivity = (($languageModeration['action'] ?? 'allow') === 'flag') ? 'language_review' : 'standard';
        try {
            $pdo->beginTransaction();
            $plainName = $hasPrivateIdentity?null:$studentName;
            $plainId = $hasPrivateIdentity?null:$studentId;
            // Build the INSERT from columns that actually exist. This keeps the
            // public submission form compatible with older/local databases while
            // pending migrations are being applied.
            $insertColumns = [
                'reference_code', 'campus', 'college', 'source_portal', 'assigned_scope',
                'concern_type', 'student_name', 'student_id', 'subject', 'message'
            ];
            $insertValues = [
                $ref, $campus, $college, $sourcePortal, $sourcePortal,
                $type, $plainName, $plainId, $subject, $message
            ];

            $optionalInsert = [
                'sensitivity' => $sensitivity,
                'priority' => $priority,
                'due_at' => $dueAt,
                'retention_until' => $retentionUntil,
            ];
            foreach ($optionalInsert as $column => $value) {
                if (!db_column_exists($pdo, 'concerns', $column)) continue;
                $insertColumns[] = $column;
                $insertValues[] = $value;
            }

            $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
            $quotedColumns = implode(',', array_map(static fn(string $column): string => '`'.$column.'`', $insertColumns));
            $st = $pdo->prepare('INSERT INTO concerns('.$quotedColumns.') VALUES('.$placeholders.')');
            $st->execute($insertValues);
            $concernId = (int)$pdo->lastInsertId();
            if (db_column_exists($pdo, 'concerns', 'academic_year_id')) {
                $ay = active_academic_year_id($pdo);
                if ($ay) $pdo->prepare('UPDATE concerns SET academic_year_id=? WHERE id=?')->execute([$ay, $concernId]);
            }
            if ($hasPrivateIdentity) concern_store_private_identity($pdo, $concernId, $studentName, $studentId, $contactEmail);
            $pdo->commit();
            $attachmentErrors = concern_store_attachments($pdo, $concernId, $_FILES['attachments'] ?? [], null, 'student');
            if ($attachmentErrors) $submissionWarning = 'The concern was submitted successfully, but some evidence files were not attached: '.implode(' ', $attachmentErrors);
            admin_notify($pdo, 'New E-Sumbong '.$type, $ref.' · '.$priority.' · '.$subject, 'concerns.php?id='.$concernId, 'warning', null, $sourcePortal, null, 'concerns');
            admin_notify($pdo, 'New E-Sumbong '.$type, $ref.' · '.$priority.' · '.$subject.' · '.$sourcePortal.' portal', 'concerns.php?id='.$concernId, 'warning', 'admin', null, null, 'concerns');
            if ($contactEmail !== '' && system_setting($pdo, 'esumbong_email_updates_enabled', '1') === '1' && system_setting($pdo, 'notification_email_enabled', '0') === '1') {
                admin_queue_email($pdo, $contactEmail, 'E-Sumbong concern received', "Your E-Sumbong concern has been received.\n\nReference: {$ref}\nType: {$type}\nStatus: Submitted\n\nKeep your reference number so you can check your case status anytime.", 'concerns', null, $concernId);
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $ref = '';
            $error = 'The concern could not be recorded. Please try again.';
            app_log_error('E-Sumbong submission failed', ['error' => $e->getMessage()]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <base href="../">
        <link rel="icon" type="image/png" href="public/assets/images/favicon.png">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>E-Sumbong | <?=e($sourcePortalName)?> | DMMMSU</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="public/assets/css/home.css">
        <link rel="stylesheet" href="public/assets/css/esumbong.css">
        <link rel="stylesheet" href="public/assets/css/shared-nav.css">
    </head>
    <body class="esumbong-page">
        <?php
        $activeNav = 'services';
        $activeService = 'esumbong';
        include dirname(__DIR__).'/src/includes/public-header.php';
        ?>
        <main id="main-content" tabindex="-1">
            <section class="esumbong-hero">
                <div class="esumbong-hero-inner">
                    <div class="hero-copy">
                        <span class="page-kicker">E-SUMBONG</span>
                        <h1>Submit a concern</h1>
                        <p>Send a concern, complaint, suggestion, or feedback to <?=e($sourcePortalName)?> through one clear and organized form.</p>
                    </div>
                    <a class="esumbong-hero__link" href="<?=e(ctx_link('esumbong/track.php'))?>">
                    <span>#</span>
                    <div>
                        <strong>Track concern</strong><small>Check your E-Sumbong status</small>
                    </div>
                    <b>→</b>
                    </a>
                </div>
            </section>
            <section class="esumbong-content">
                <div class="container esumbong-shell">
                    <section class="concern-card one-page-card">
                        <div class="concern-card-head">
                            <div>
                                <span>E-SUMBONG FORM</span>
                                <h2>Tell us what happened.</h2>
                                <p>
                                    <?php if ($isCampusPortal) : ?>
                                        Complete the required fields below. This concern will be submitted directly to <?=e($sourcePortalName)?> for review.
                                    <?php else: ?>
                                        Complete the required fields below. This concern will be submitted to the University Student Council for review; your campus is collected only for identification.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($error) : ?>
                            <div class="form-alert" role="alert" aria-live="polite">
                                <span class="form-alert-icon">!</span>
                                <div>
                                    <strong>Check your submission</strong>
                                    <p><?=e($error)?></p>
                                    <?php if ($submissionRestriction) : ?>
                                        <div class="active-concern-actions">
                                            <?php if (($submissionRestriction['restriction_kind'] ?? '') === 'cooldown') : ?>
                                                <span>Previous reference: <strong><?=e((string)$submissionRestriction['reference_code'])?></strong> · Cooldown resets: <strong><?=e(date('M j, Y · g:i A', strtotime((string)$submissionRestriction['retry_at'])))?></strong></span>
                                                <a href="<?=e(ctx_link('esumbong/track.php?code='.rawurlencode((string)$submissionRestriction['reference_code'])))?>">Track Previous Submission →</a>
                                            <?php else : ?>
                                                <span>Existing reference: <strong><?=e((string)$submissionRestriction['reference_code'])?></strong> · Status: <?=e((string)$submissionRestriction['status'])?></span>
                                                <a href="<?=e(ctx_link('esumbong/track.php?code='.rawurlencode((string)$submissionRestriction['reference_code'])))?>">Track Existing Concern →</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <form id="concernForm" class="concern-form compact-form" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf" value="<?=e($publicCsrf)?>">
                            <label class="form-honeypot" aria-hidden="true">
                                Website
                                <input name="website" tabindex="-1" autocomplete="off">
                            </label>
                            <div class="form-workspace">
                                <section class="form-section concern-details">
                                    <div class="section-heading">
                                        <span>01</span>
                                        <div>
                                            <strong>Concern details</strong><small>Where it belongs and what happened.</small>
                                        </div>
                                    </div>
                                    <div class="form-three">
                                        <label>
                                            <span class="field-label-row"><span><?= $isCampusPortal ? 'Campus' : 'Your Campus' ?></span><em class="required-badge">Required</em></span>
                                            <?php if ($isCampusPortal) : ?>
                                                <div class="portal-fixed-field portal-fixed-field--name-only">
                                                    <strong><?=e($sourcePortalName)?></strong>
                                                </div>
                                                <input id="campus" name="campus" type="hidden" value="<?=e($campusPreset)?>">
                                                <small class="field-hint">Fixed to the website where this concern is being submitted.</small>
                                            <?php else: ?>
                                                <select id="campus" name="campus" required>
                                                    <option value="">Select your campus</option>
                                                    <option value="North La Union Campus">North La Union Campus</option>
                                                    <option value="Mid La Union Campus">Mid La Union Campus</option>
                                                    <option value="South La Union Campus">South La Union Campus</option>
                                                    <option value="Open University System">Open University System</option>
                                                </select>
                                                <small class="field-hint">Used to identify your campus and available college/program.</small>
                                            <?php endif; ?>
                                        </label>
                                        <label>
                                            <span class="field-label-row"><span>College / Program</span><em class="required-badge">Required</em></span>
                                            <select id="college" name="college" required disabled>
                                                <option value="">Select campus first</option>
                                            </select>
                                            <small class="field-hint field-hint-spacer" aria-hidden="true">Alignment spacer</small>
                                        </label>
                                        <label>
                                            <span class="field-label-row"><span>Concern Type</span><em class="required-badge">Required</em></span>
                                            <select id="concernType" name="concernType" required>
                                                <option value="">Choose type</option>
                                                <?php foreach (['Concern','Complaint','Suggestion','Feedback'] as $optionType) : ?>
                                                    <option value="<?=e($optionType)?>" <?=($_POST['concernType'] ?? '')===$optionType?'selected':''?>><?=e($optionType)?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                    <label>
                                        <span class="field-label-row"><span>Subject</span><em class="required-badge">Required</em></span>
                                        <input id="subject" name="subject" type="text" required maxlength="140" placeholder="Give your concern a short, clear title">
                                    </label>
                                    <label>
                                        <span class="field-label-row"><span>Message</span><em class="required-badge">Required</em></span>
                                        <textarea id="message" name="message" required maxlength="1000" placeholder="Describe the concern clearly. Include only the details needed for <?=e($sourcePortal)?> to understand and respond."></textarea>
                                        <span class="character-count"><b id="charCount">0</b>/1000</span>
                                    </label>
                                    <label class="evidence-upload-field">
                                        <span class="field-label-row"><span>Supporting evidence</span><em>Optional</em></span>
                                        <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf">
                                        <small class="field-hint">Up to 3 JPG, PNG, WebP, or PDF files. Evidence is private and available only to authorized case handlers.</small>
                                    </label>
                                </section>
                                <section class="form-section identity-section">
                                    <div class="section-heading">
                                        <span>02</span>
                                        <div>
                                            <strong>Your information</strong><small>Required for verification, follow-up, and accurate case handling.</small>
                                        </div>
                                    </div>
                                    <div class="identity-content">
                                        <div class="identity-assurance" role="note">
                                            <span class="identity-assurance-icon">i</span>
                                            <p><strong>Your identity is protected.</strong> Student Name and Student ID are required for E-Sumbong submissions and are available only to authorized administrators when needed to process the case.</p>
                                        </div>
                                        <div class="identity-grid identity-grid-balanced" id="identityFields">
                                            <div class="identity-field">
                                                <label class="identity-field-label" for="studentName">
                                                    Student Name <span>(LN, FN, MI)</span> <em class="required-badge">Required</em>
                                                </label>
                                                <input id="studentName" name="studentName" type="text" required maxlength="150" placeholder="Enter student name" autocomplete="name">
                                                <small class="field-hint">Example: Dela Cruz, Juan P.</small>
                                            </div>
                                            <div class="identity-field">
                                                <label class="identity-field-label" for="studentId">
                                                    Student ID <em class="required-badge">Required</em>
                                                </label>
                                                <input id="studentId" name="studentId" type="text" required placeholder="000-0000-0" inputmode="numeric" maxlength="10" pattern="\d{3}-\d{4}-\d" title="Use the format 000-0000-0" autocomplete="off">
                                                <small class="field-hint">Format: 000-0000-0</small>
                                            </div>
                                            <div class="identity-field identity-field--wide">
                                                <label class="identity-field-label" for="contactEmail">
                                                    Contact Email <span>(optional)</span>
                                                </label>
                                                <input id="contactEmail" name="contactEmail" type="email" maxlength="190" placeholder="you@example.com" autocomplete="email">
                                                <small class="field-hint">Optional. If email delivery is enabled, you can receive the tracking details and student-visible case updates. This address is stored with your protected identity information.</small>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                                <section class="form-section confirmation-section">
                                    <div class="section-heading">
                                        <span>03</span>
                                        <div>
                                            <strong>Review & consent</strong><small>Confirm how the information will be used before you submit.</small>
                                        </div>
                                    </div>
                                    <div class="confirmation-grid">
                                        <label class="privacy-consent">
                                            <input type="checkbox" name="privacy_consent" value="1" required>
                                            <span><strong>Privacy consent</strong><small>I understand that information submitted here will be used only to review, route, document, and respond to this concern according to the system retention policy.</small></span>
                                        </label>
                                        <div class="privacy-note">
                                            <span class="privacy-note-icon">i</span>
                                            <p><strong>Share only what is needed.</strong> Avoid including passwords, financial details, or unrelated personal information.</p>
                                        </div>
                                    </div>
                                </section>
                            </div>
                            <div class="form-footer">
                                <div class="submit-note">
                                    <span>✓</span>
                                    <p>Review your details, then keep the reference number shown after submission so you can track the concern.</p>
                                </div>
                                <button class="submit-concern-btn" type="submit">Submit Concern <span>→</span></button>
                            </div>
                        </form>
                    </section>
                    <section class="after-submit">
                        <div class="after-submit-copy">
                            <span>WHAT HAPPENS NEXT</span><strong>From submission to resolution.</strong>
                        </div>
                        <div class="process-row" aria-label="E-Sumbong process">
                            <div>
                                <b>1</b><span><strong>Submit</strong><small>Concern recorded</small></span>
                            </div>
                            <i>→</i>
                            <div>
                                <b>2</b><span><strong>Review</strong><small>Details checked</small></span>
                            </div>
                            <i>→</i>
                            <div>
                                <b>3</b><span><strong>Action</strong><small>Handled or referred</small></span>
                            </div>
                            <i>→</i>
                            <div>
                                <b>4</b><span><strong>Track</strong><small>Follow the status</small></span>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </main>
        <?php
        include dirname(__DIR__).'/src/includes/public-footer.php';
        ?>
        <div class="submission-modal" id="submissionModal" role="dialog" aria-modal="true" aria-labelledby="submissionModalTitle" aria-hidden="true">
            <div class="modal-backdrop" data-close-modal>
            </div>
            <div class="submission-modal-card">
                <button class="modal-close" type="button" data-close-modal>×</button>
                <span class="modal-check">✓</span>
                <span class="modal-kicker">SUBMISSION RECEIVED</span>
                <h2 id="submissionModalTitle">Your concern has been recorded.</h2>
                <p>Save your reference number. You can use it anytime to check the status of your concern.</p>
                <strong class="reference-number" id="referenceNumber">ES-2026-00000</strong>
                <div class="modal-actions">
                    <a href="<?=e(ctx_link('esumbong/track.php'))?>">Track Concern</a>
                    <button type="button" data-close-modal>Done</button>
                </div>
            </div>
        </div>
        <?php if ($ref) : ?>
            <script>
window.submittedReference = <?=json_encode($ref)?>; window.submissionWarning = <?=json_encode($submissionWarning)?>;
</script>
        <?php endif; ?>
        <script src="public/assets/js/esumbong.js"></script>
    </body>
</html>
