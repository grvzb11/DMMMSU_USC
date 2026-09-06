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
$activeNav = $activeNav ?? '';
$activeService = $activeService ?? '';
?>
<a class="skip-link public-skip-link" href="#main-content">Skip to main content</a>
<header class="site-header <?= $ctxIsCampus ? 'campus-context-header' : '' ?>">
    <div class="container nav-wrap">
        <a class="brand <?= $ctxIsCampus ? 'campus-context-brand' : '' ?>" href="<?=e($ctxHome)?>">
        <img src="<?=e($ctxBrandLogo)?>" alt="<?=e($ctxBrandPrimary)?> logo">
        <span><strong><?=e($ctxBrandPrimary)?></strong><small><?=e($ctxBrandSecondary)?></small></span>
        </a>
        <button class="nav-toggle" id="navToggle" type="button" aria-label="Open menu" aria-expanded="false"><span></span><span></span><span></span></button>
        <nav class="main-nav" id="mainNav">
            <a href="<?=e($ctxHome)?>" <?= $activeNav==='home'?'aria-current="page"':'' ?>>Home</a>
            <a href="<?=e(ctx_link('news/updates.php'))?>" <?= $activeNav==='updates'?'aria-current="page"':'' ?>>News &amp; Updates</a>
            <div class="nav-dropdown <?= $activeNav==='services'?'active-dropdown':'' ?>">
                <button class="nav-drop-btn" type="button">Services <span class="chevron">▾</span></button>
                <div class="nav-drop-menu">
                    <a href="<?=e(ctx_link('esumbong/esumbong.php'))?>" <?= $activeService==='esumbong'?'class="active-link"':'' ?>>E-Sumbong</a>
                    <a href="<?=e(ctx_link('esumbong/track.php'))?>" <?= $activeService==='track'?'class="active-link"':'' ?>>Track Concern</a>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="nav-drop-btn" type="button">Campuses <span class="chevron">▾</span></button>
                <div class="nav-drop-menu">
                    <a href="campus/nluc.php" <?= $ctxCampus==='NLUC'?'aria-current="page"':'' ?>>North La Union Campus</a>
                    <a href="campus/mluc.php" <?= $ctxCampus==='MLUC'?'aria-current="page"':'' ?>>Mid La Union Campus</a>
                    <a href="campus/sluc.php" <?= $ctxCampus==='SLUC'?'aria-current="page"':'' ?>>South La Union Campus</a>
                    <a href="campus/ous.php" <?= $ctxCampus==='OUS'?'aria-current="page"':'' ?>>Open University System</a>
                </div>
            </div>
            <a href="<?=e(ctx_link('about.php'))?>" <?= $activeNav==='about'?'aria-current="page"':'' ?>>About</a>
        </nav>
        <a class="header-cta submit-cta" href="<?= $ctxIsCampus ? 'index.php' : 'esumbong/esumbong.php' ?>"><?= $ctxIsCampus ? 'USC Main Page' : 'Submit Concern' ?></a>
    </div>
</header>
<?php if (isset($pdo) && $pdo instanceof PDO) echo public_alerts_html($pdo, $ctxCampus ?: 'USC'); ?>
