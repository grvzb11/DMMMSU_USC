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
require_once dirname(__DIR__, 2).'/config/campus-context.php';
require_once dirname(__DIR__, 2).'/config/handbook-ai.php';
$footerYear = date('Y');
$defaultFacebook = 'https://www.facebook.com/usc.dmmmsu';
$defaultEmail = 'usc@dmmmsu.edu.ph';
$footerFacebook = ($ctxIsCampus && !empty($ctxConfig['facebook'])) ? $ctxConfig['facebook'] : (isset($pdo)?system_setting($pdo, 'usc_facebook', $defaultFacebook):$defaultFacebook);
$footerEmail = isset($pdo)?system_setting($pdo, 'usc_email', $defaultEmail):$defaultEmail;
$handbookReady = isset($pdo) && $pdo instanceof PDO ? handbook_ai_public_ready($pdo) : false;
$handbookTitle = isset($pdo) && $pdo instanceof PDO ? handbook_title($pdo) : 'DMMMSU Student Handbook';
$handbookEndpoint = app_absolute_url('api/handbook-chat.php');
$handbookSource = app_absolute_url('handbook/source.php');
$handbookEsumbong = app_absolute_url(ctx_link('esumbong/esumbong.php'));
$handbookCss = app_absolute_url('public/assets/css/handbook-assistant.css');
$handbookJs = app_absolute_url('public/assets/js/handbook-assistant.js');
?>
<footer class="global-footer">
    <div class="container global-footer__main">
        <div class="global-footer__brand">
            <a class="global-footer__identity" href="<?=e($ctxHome)?>" aria-label="<?=e($ctxBrandPrimary)?> home">
            <img src="<?=e($ctxBrandLogo)?>" alt="<?=e($ctxBrandPrimary)?> logo">
            <span class="global-footer__identity-copy">
            <strong><?=e($ctxBrandPrimary)?></strong>
            <small><?= $ctxIsCampus ? 'Campus Student Body Organization' : 'Don Mariano Marcos Memorial State University' ?></small>
            </span>
            </a>
            <p><?= $ctxIsCampus
        ? 'Student services, campus updates, E-Sumbong, concern tracking, and transparency resources in one connected USC platform.'
        : 'The official digital home for USC student services, campus updates, E-Sumbong, concern tracking, and transparency resources.' ?></p>
            <div class="global-footer__icon-links" aria-label="Official contact channels">
                <a class="global-footer__icon-link" href="<?=e($footerFacebook)?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5h1.7V4.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V11H7.3v3h2.8v8h3.4Z"/></svg>
                </a>
                <a class="global-footer__icon-link" href="mailto:<?=e($footerEmail)?>" aria-label="Email USC">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.8 5h16.4A1.8 1.8 0 0 1 22 6.8v10.4a1.8 1.8 0 0 1-1.8 1.8H3.8A1.8 1.8 0 0 1 2 17.2V6.8A1.8 1.8 0 0 1 3.8 5Zm.4 2 7.8 5.5L19.8 7H4.2Zm15.8 2.2-7.4 5.2a1 1 0 0 1-1.2 0L4 9.2v7.6c0 .1.1.2.2.2h15.6c.1 0 .2-.1.2-.2V9.2Z"/></svg>
                </a>
            </div>
        </div>
        <div class="global-footer__links-grid">
            <nav class="global-footer__column" aria-label="Footer explore links">
                <span class="global-footer__label">Explore</span>
                <a href="<?=e($ctxHome)?>">Home</a>
                <a href="<?=e(ctx_link('news/updates.php'))?>">News &amp; Updates</a>
                <a href="<?=e(ctx_link('about.php'))?>">About</a>
            </nav>
            <nav class="global-footer__column" aria-label="Footer student services links">
                <span class="global-footer__label">Student services</span>
                <a href="<?=e(ctx_link('esumbong/esumbong.php'))?>">E-Sumbong</a>
                <a href="<?=e(ctx_link('esumbong/track.php'))?>">Track Concern</a>
            </nav>
            <nav class="global-footer__column global-footer__campuses" aria-label="Footer campus links">
                <span class="global-footer__label">Campuses</span>
                <a href="campus/nluc.php">North La Union Campus</a>
                <a href="campus/mluc.php">Mid La Union Campus</a>
                <a href="campus/sluc.php">South La Union Campus</a>
                <a href="campus/ous.php">Open University System</a>
            </nav>
        </div>
    </div>
    <div class="container global-footer__bottom">
        <span>© <?=e((string)$footerYear)?> University Student Council • Don Mariano Marcos Memorial State University</span>
    </div>
</footer>
<link rel="stylesheet" href="<?=e($handbookCss)?>?v=<?=e((string)@filemtime(app_root('public/assets/css/handbook-assistant.css')))?>">
<div class="handbook-ai" id="handbookAi"
     data-ready="<?=$handbookReady?'1':'0'?>"
     data-endpoint="<?=e($handbookEndpoint)?>"
     data-source="<?=e($handbookSource)?>"
     data-esumbong="<?=e($handbookEsumbong)?>">
    <button class="handbook-ai__launcher" type="button" data-handbook-launcher aria-expanded="false" aria-controls="handbookAiPanel">
        <span class="handbook-ai__launcher-icon" aria-hidden="true">📖</span>
        <span class="handbook-ai__launcher-copy"><strong>Handbook Assistant</strong><small>Ask about student policies</small></span>
    </button>
    <section class="handbook-ai__panel" id="handbookAiPanel" data-handbook-panel aria-hidden="true" aria-label="Student Handbook Assistant">
        <header class="handbook-ai__head">
            <div class="handbook-ai__identity">
                <span class="handbook-ai__avatar" aria-hidden="true">📖</span>
                <span class="handbook-ai__identity-copy"><strong>Student Handbook Assistant</strong><small><?=e($handbookTitle)?></small></span>
            </div>
            <button class="handbook-ai__close" type="button" data-handbook-close aria-label="Close Handbook Assistant">×</button>
        </header>
        <div class="handbook-ai__body" role="log" aria-live="polite">
            <div class="handbook-ai__welcome">
                <span class="handbook-ai__welcome-label">Approved handbook source</span>
                <h3>How can I help?</h3>
                <p>Ask about student rights, responsibilities, attendance, dress code, organizations, grievances, discipline, and other policies covered by the handbook.</p>
                <?php if (!$handbookReady) : ?>
                    <div class="handbook-ai__status">The assistant is visible but its knowledge base is still being configured by the System Administrator.</div>
                <?php endif; ?>
                <div class="handbook-ai__suggestions" aria-label="Suggested handbook questions">
                    <button type="button" class="handbook-ai__suggestion" data-handbook-question="What are the attendance rules?">Attendance rules</button>
                    <button type="button" class="handbook-ai__suggestion" data-handbook-question="What is the student dress code?">Dress code</button>
                    <button type="button" class="handbook-ai__suggestion" data-handbook-question="How can a student file a grievance?">Student grievance</button>
                    <button type="button" class="handbook-ai__suggestion" data-handbook-question="What are the grounds for disciplinary action?">Disciplinary action</button>
                </div>
            </div>
            <div class="handbook-ai__messages" data-handbook-messages></div>
        </div>
        <footer class="handbook-ai__foot">
            <form class="handbook-ai__form" data-handbook-form autocomplete="off">
                <label class="handbook-ai__sr-only" for="handbookAiInput">Ask the Student Handbook Assistant</label>
                <textarea class="handbook-ai__input" id="handbookAiInput" data-handbook-input rows="1" maxlength="600" placeholder="Ask a question about the Student Handbook…"></textarea>
                <button class="handbook-ai__send" data-handbook-send type="submit" aria-label="Send question">→</button>
            </form>
            <p class="handbook-ai__disclaimer">Answers are summaries of approved handbook content. Do not enter confidential E-Sumbong details. The handbook and authorized university offices remain the official sources.</p>
        </footer>
    </section>
</div>
<script src="<?=e($handbookJs)?>?v=<?=e((string)@filemtime(app_root('public/assets/js/handbook-assistant.js')))?>" defer></script>
<div class="public-network-banner" id="publicNetworkBanner" role="status" aria-live="polite">
</div>
<script>
(() => {
    const banner = document.getElementById('publicNetworkBanner');
    const sync = () => { const off = !navigator.onLine; banner.textContent = off ? 'You are offline. Keep this page open; retry when your connection returns.' : ''; banner.classList.toggle('is-visible', off); };
    window.addEventListener('online', sync); window.addEventListener('offline', sync); sync();
    document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
        if (event.defaultPrevented || form.dataset.allowRepeat === '1') return;
        if (form.dataset.submitting === '1') { event.preventDefault(); return; }
        form.dataset.submitting = '1'; const btn = event.submitter; if (btn) { btn.dataset.originalText = btn.textContent; btn.setAttribute('aria-disabled', 'true'); btn.classList.add('is-submitting'); }
    }));
})();
</script>
