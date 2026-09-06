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
ensure_post_images_table($pdo);

if (db_column_exists($pdo, 'posts', 'scheduled_at') && system_setting($pdo, 'auto_publish_scheduled', '1') === '1') publish_scheduled_posts($pdo);
$id = (int)($_GET['id'] ?? 0);
$slug = trim((string)($_GET['slug'] ?? ''));
$post = null;
if ($slug !== '' && db_column_exists($pdo, 'posts', 'slug')) {
    $st = $pdo->prepare("SELECT * FROM posts WHERE slug=? AND deleted_at IS NULL AND status='published' LIMIT 1");
    $st->execute([$slug]);
    $post = $st->fetch()?:null;
}
elseif ($id>0) {
    $st = $pdo->prepare("SELECT * FROM posts WHERE id=? AND deleted_at IS NULL AND status='published' LIMIT 1");
    $st->execute([$id]);
    $post = $st->fetch()?:null;
}
if (!$post) {
    http_response_code(404);
    $pageTitle = 'Story not found';
    $images = [];
    $videos = [];
}
else {
    $images = public_file_records(post_images($pdo, (int)$post['id']));
    $videos = public_file_records(post_videos($pdo, (int)$post['id']));
    $pageTitle = (string)$post['title'];
}
function article_campus_name(string $campus): string {
    return match(strtoupper($campus)) {
        'NLUC' => 'North La Union Campus', 'MLUC' => 'Mid La Union Campus', 'SLUC' => 'South La Union Campus', 'OUS' => 'Open University System', default => 'University Student Council',
    };
}
function article_campus_logo(string $campus): string {
    return match(strtoupper($campus)) {
        'NLUC' => 'public/assets/images/nluc-sbo-logo.jpg', 'MLUC' => 'public/assets/images/mluc-sbo-logo.jpg', 'SLUC' => 'public/assets/images/sluc-sbo-logo.jpg', 'OUS' => 'public/assets/images/ous-sbo-logo.jpg', default => 'public/assets/images/usc-seal.jpg',
    };
}
$campus = $post ? strtoupper((string)$post['category']) : 'USC';
$published = $post && !empty($post['published_at']) ? date('F j, Y', strtotime($post['published_at'])) : '';
$back = 'news/updates.php';
if (!empty($_GET['campus']) && in_array(strtoupper((string)$_GET['campus']), ['NLUC', 'MLUC', 'SLUC', 'OUS'], true)) {
    $back.='?campus='.rawurlencode(strtoupper((string)$_GET['campus']));
}
$cover = $images[0] ?? null;
$imageCount = count($images);
$videoCount = count($videos);
$totalMediaCount = $imageCount + $videoCount;
$singlePhotoOnly = $imageCount === 1 && $videoCount === 0;
$singleVideoOnly = $imageCount === 0 && $videoCount === 1;
$photosOnlyGallery = $imageCount > 1 && $videoCount === 0;
$videosOnlyGallery = $videoCount > 1 && $imageCount === 0;
$mixedMediaGallery = $imageCount > 0 && $videoCount > 0;
$showHeroMedia = false;
$singleTopImage = $imageCount === 1 ? $images[0] : null;
$galleryImages = $imageCount > 1 ? $images : [];
$galleryVideos = $videos;
$galleryAspectRatio = post_gallery_display_ratio($images);
$canonical = '';
if ($post) {
    $canonical = app_absolute_url('news/article.php?'.(!empty($post['slug'])?'slug='.rawurlencode((string)$post['slug']):'id='.(int)$post['id']));
}
?>
<!doctype html>
<html lang="en">
    <head>
        <base href="../">
        <link rel="icon" type="image/png" href="public/assets/images/favicon.png">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?=e($pageTitle)?> | USC DMMMSU</title>
        <?php if ($post) :$metaDescription = trim((string)($post['excerpt']?:mb_substr(strip_tags((string)$post['content']), 0, 180))); ?>
            <meta name="description" content="<?=e($metaDescription)?>">
            <link rel="canonical" href="<?=e($canonical)?>">
            <meta property="og:type" content="article">
            <meta property="og:title" content="<?=e($post['title'])?>">
            <meta property="og:description" content="<?=e($metaDescription)?>">
            <meta property="og:url" content="<?=e($canonical)?>">
            <?php if ($cover) : ?>
                <meta property="og:image" content="<?=e((string)$cover['_public_url'])?>">
            <?php endif; ?>
            <meta property="article:published_time" content="<?=e(date(DATE_ATOM,strtotime((string)$post['published_at'])))?>">
        <?php endif; ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="public/assets/css/style.css">
        <link rel="stylesheet" href="public/assets/css/shared-nav.css">
        <link rel="stylesheet" href="public/assets/css/article.css">
        <style>
            /* Keep the existing article design; only tighten the gap between the hero/meta and article body. */
            .article-page .story-hero { padding-bottom: 20px; }
            .article-page .story-main { padding-top: 20px; }
            .article-page .story-gallery-section { margin-top: 20px !important; }
            .article-page .story-video-section--top { margin-top: 0 !important; }
            .article-page .story-photo-section--after-article { margin-top: 24px !important; }
            @media (max-width: 700px) {
                .article-page .story-hero { padding-bottom: 14px; }
                .article-page .story-main { padding-top: 14px; }
            }
        </style>
    </head>
    <body class="article-page">
        <?php
        $activeNav = 'updates';
        $activeService = '';
        include dirname(__DIR__).'/src/includes/public-header.php';
        ?>
        <main id="main-content" tabindex="-1">
            <?php if (!$post) : ?>
                <section class="article-not-found">
                    <div class="article-shell">
                        <span>UNIVERSITY STUDENT COUNCIL NEWSROOM</span>
                        <h1>Story not found</h1>
                        <p>The publication may have been removed, unpublished, or the link may be incorrect.</p>
                        <a href="<?=e($back)?>">← Back to News &amp; Updates</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="story-hero">
                    <div class="article-shell">
                        <a class="article-back" href="<?=e($back)?>">← Back to News &amp; Updates</a>
                        <div class="story-hero__grid<?=$showHeroMedia?'':' story-hero__grid--no-media'?>">
                            <div class="story-hero__copy">
                                <div class="story-kicker">
                                    <img src="<?=e(article_campus_logo($campus))?>" alt="">
                                    <span><?=e(article_campus_name($campus))?></span>
                                </div>
                                <h1><?=e($post['title'])?></h1>
                                <div class="story-meta">
                                    <span><?=e(article_campus_name($campus))?></span>
                                    <span aria-hidden="true">•</span>
                                    <time datetime="<?=e(date('Y-m-d',strtotime($post['published_at'])))?>"><?=e($published)?></time>
                                    <?php if (count($images)) : ?>
                                        <span aria-hidden="true">•</span><span><?=count($images)?> <?=count($images)===1?'photo':'photos'?></span>
                                    <?php endif; ?>
                                    <?php if (count($videos)) : ?>
                                        <span aria-hidden="true">•</span><span><?=count($videos)?> <?=count($videos)===1?'video':'videos'?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>
                <article class="story-main article-shell">
                    <div class="story-content-column">
                        <?php if ($galleryVideos) : ?>
                            <section class="story-gallery-section story-gallery-section--breakout story-video-section story-video-section--top">
                                <div class="story-video-grid<?=$videoCount===1?' story-video-grid--single':''?>">
                                    <?php foreach ($galleryVideos as $video) : ?>
                                        <figure class="story-video-card<?=$videoCount===1?' story-feature-video':''?>" data-video-frame>
                                            <div class="story-video-stage">
                                                <video controls preload="metadata" playsinline data-video-poster src="<?=e((string)$video['_public_url'])?>"></video>
                                            </div>
                                        </figure>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ($singleTopImage) : ?>
                            <section class="story-single-photo-section story-single-photo-section--top">
                                <button class="story-single-photo" type="button" style="--story-gallery-ratio: <?=e($galleryAspectRatio)?>" data-lightbox-index="0" aria-label="Open publication photo">
                                    <span class="story-single-photo__blur" style="background-image:url('<?=e((string)$singleTopImage['_public_url'])?>')"></span>
                                    <img src="<?=e((string)$singleTopImage['_public_url'])?>" alt="Publication photo">
                                </button>
                            </section>
                        <?php endif; ?>

                        <div class="article-prose">
                            <?php if (trim((string)$post['content'])) : ?>
                                <?=nl2br(e($post['content']))?>
                            <?php endif; ?>
                        </div>

                        <?php if ($galleryImages) : ?>
                            <section class="story-gallery-section story-gallery-section--breakout story-photo-section--after-article">
                                <div class="story-gallery-grid" data-article-gallery style="--story-gallery-ratio: <?=e($galleryAspectRatio)?>">
                                    <?php foreach ($galleryImages as $i => $img) : ?>
                                        <button class="story-gallery-tile<?=$i>=8?' story-gallery-tile--extra':''?>" type="button" data-lightbox-index="<?=$i?>" aria-label="Open publication photo <?=($i+1)?>" <?=$i>=8?'hidden':''?>>
                                            <span class="story-gallery-tile__blur" style="background-image:url('<?=e((string)$img['_public_url'])?>')"></span>
                                            <img loading="lazy" src="<?=e((string)$img['_public_url'])?>" alt="Publication photo <?=($i+1)?>">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($imageCount > 8) : ?>
                                    <button class="story-view-all" type="button" data-gallery-expand>View all <?=$imageCount?> photos <span>→</span></button>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endif; ?>
        </main>
        <?php
        include dirname(__DIR__).'/src/includes/public-footer.php';
        ?>
        <?php if ($post && $images) : ?>
            <div class="story-lightbox" id="storyLightbox" hidden aria-modal="true" role="dialog" aria-label="Publication gallery">
                <button class="story-lightbox__close" type="button" aria-label="Close">×</button>
                <button class="story-lightbox__nav story-lightbox__nav--prev" type="button" aria-label="Previous photo">‹</button>
                <figure>
                    <img alt="Expanded publication photo">
                    <figcaption></figcaption>
                </figure>
                <button class="story-lightbox__nav story-lightbox__nav--next" type="button" aria-label="Next photo">›</button>
            </div>
            <script>
(() => {
    const files = <?=json_encode(array_values(array_map(fn($img)=>(string)$img['_public_url'],$images)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
    const lb = document.getElementById('storyLightbox'), img = lb?.querySelector('img'), cap = lb?.querySelector('figcaption'); let index = 0, lastFocus = null;
    const focusables = () => [...lb.querySelectorAll('button:not([disabled]),a[href],[tabindex]:not([tabindex="-1"])')];
    const show = i => { if (!lb || !img || !files.length) return; lastFocus = document.activeElement; index = (i + files.length) % files.length; img.src = files[index]; cap.textContent = `${index + 1} / ${files.length}`; lb.hidden = false; document.body.style.overflow = 'hidden'; lb.querySelector('.story-lightbox__close')?.focus() };
    const close = () => { if (!lb) return; lb.hidden = true; document.body.style.overflow = ''; if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus() };
    document.querySelectorAll('[data-lightbox-index]').forEach(el => el.addEventListener('click', () => show(Number(el.dataset.lightboxIndex || 0))));
    document.querySelectorAll('[data-gallery-expand]').forEach(button => button.addEventListener('click', () => {
        const section = button.closest('.story-gallery-section');
        section?.querySelectorAll('.story-gallery-tile--extra[hidden]').forEach(tile => tile.hidden = false);
        button.remove();
    }));
    lb?.querySelector('.story-lightbox__close')?.addEventListener('click', close); lb?.querySelector('.story-lightbox__nav--prev')?.addEventListener('click', () => show(index - 1)); lb?.querySelector('.story-lightbox__nav--next')?.addEventListener('click', () => show(index + 1)); lb?.addEventListener('click', e => { if (e.target === lb) close() });
    document.addEventListener('keydown', e => { if (lb?.hidden === false) { if (e.key === 'Escape') { e.preventDefault(); close() } if (e.key === 'ArrowLeft') show(index - 1); if (e.key === 'ArrowRight') show(index + 1); if (e.key === 'Tab') { const f = focusables(); if (!f.length) return; const first = f[0], last = f[f.length - 1]; if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() } else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() } } } });
})()
</script>
        <?php endif; ?>
        <script src="public/assets/js/script.js"></script>
        <script src="public/assets/js/video-preview.js"></script>
    </body>
</html>
