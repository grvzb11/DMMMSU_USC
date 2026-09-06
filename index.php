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
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/helpers.php';

// The homepage is data-driven. Prevent a browser/proxy from serving an older
// hero after an administrator has just saved a slide.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

ensure_post_images_table($pdo);
ensure_post_videos_table($pdo);
if (db_column_exists($pdo, 'posts', 'scheduled_at') && system_setting($pdo, 'auto_publish_scheduled', '1') === '1') publish_scheduled_posts($pdo);
// Load every published post first, then normalize its campus/source code for
// the homepage filter. This keeps older/legacy labels from disappearing when
// the campus buttons are used.
$posts = $pdo->query("SELECT * FROM posts WHERE status='published' AND deleted_at IS NULL ORDER BY published_at DESC, id DESC LIMIT 100")->fetchAll();

function home_post_campus_code(array $post): string {
    $category = strtolower(trim((string)($post['category'] ?? '')));
    $label = strtolower(trim((string)($post['label'] ?? '')));
    $source = $category.' '.$label;

    if ($category === 'nluc' || str_contains($source, 'north la union') || preg_match('/\bnluc\b/', $source)) return 'nluc';
    if ($category === 'mluc' || str_contains($source, 'mid la union') || preg_match('/\bmluc\b/', $source)) return 'mluc';
    if ($category === 'sluc' || str_contains($source, 'south la union') || preg_match('/\bsluc\b/', $source)) return 'sluc';
    if ($category === 'ous' || str_contains($source, 'open university') || preg_match('/\bous\b/', $source)) return 'ous';
    return 'usc';
}

$imageStmt = $pdo->prepare('SELECT * FROM post_images WHERE post_id=? ORDER BY sort_order ASC,id ASC');
$videoStmt = $pdo->prepare('SELECT * FROM post_videos WHERE post_id=? ORDER BY sort_order ASC,id ASC');
foreach ($posts as &$post) {
    $imageStmt->execute([(int)$post['id']]);
    $post['images'] = public_file_records($imageStmt->fetchAll());
    $videoStmt->execute([(int)$post['id']]);
    $post['videos'] = public_file_records($videoStmt->fetchAll());
    $post['_home_campus'] = home_post_campus_code($post);
}
unset($post);

$latestPosts = $posts;
ensure_hero_slides_table($pdo);
ensure_hero_promotion_requests_table($pdo);
$heroSlides = hero_public_slides($pdo, 'USC');
$requestedHeroId = max(0, (int)($_GET['hero_slide'] ?? 0));
$initialHeroIndex = 0;
if ($requestedHeroId>0) {
    foreach ($heroSlides as $heroIndex => $heroSlide) {
        if (empty($heroSlide['_promotion_request_id']) && (int)$heroSlide['id'] === $requestedHeroId) {
            $initialHeroIndex = $heroIndex;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="icon" type="image/png" href="public/assets/images/favicon.png">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>University Student Council</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="public/assets/css/home.css?v=<?=e((string)@filemtime(__DIR__.'/public/assets/css/home.css'))?>">
        <link rel="stylesheet" href="public/assets/css/shared-nav.css">
    </head>
    <body>
        <?php
        $activeNav = 'home';
        $activeService = '';
        include __DIR__.'/src/includes/public-header.php';
        ?>
        <main id="main-content" tabindex="-1">
            <section class="hero" id="home">
                <?php foreach ($heroSlides as $i => $slide) : ?>
                    <?php
                          $allowedHeroThemes=['none','green','blue','forest','emerald','teal','navy','sky','purple','maroon','red','orange','gold','slate'];
                          $themeValue=in_array($slide['theme']??'green',$allowedHeroThemes,true)?($slide['theme']??'green'):'green';
                          $themeClass='hero-'.$themeValue;
                          $hasBg=!empty($slide['background_image']);
                          $imageOnly=($slide['display_mode']??'standard')==='image_only';
                          $storedPanelType=$slide['panel_type']??'card';
                          $panelType=in_array($storedPanelType,['esumbong','metrics','card'],true)
                            ? 'card'
                            : (in_array($storedPanelType,['none','image'],true)?$storedPanelType:'card');
                          $noPanel=!$imageOnly && $panelType==='none';
                          $buttonMode=in_array($slide['button_mode']??'both',['both','primary','secondary','none'],true)?($slide['button_mode']??'both'):'both';
                          $showPrimaryButton=!empty($slide['primary_label']) && in_array($buttonMode,['both','primary'],true);
                          $showSecondaryButton=!empty($slide['secondary_label']) && in_array($buttonMode,['both','secondary'],true);
                          $bannerPositionX=max(0,min(100,(int)($slide['background_position_x']??50)));
                          $bannerPositionY=max(0,min(100,(int)($slide['background_position_y']??50)));
                          $imageVersion=!empty($slide['updated_at']) ? (string)strtotime((string)$slide['updated_at']) : (string)$slide['id'];
                          $bannerUrl=$hasBg ? $slide['background_image'].'?v='.$imageVersion : '';
                          // Keep the focal point on the slide itself so both Standard and Image Only
                          // use the exact same positioning data. Standard photo-backed slides now
                          // render a real <img> backdrop instead of a CSS background. This makes
                          // the crop shown in the editor and the crop on the public homepage use
                          // the same object-fit/object-position rendering model.
                          $positionStyle=$hasBg
                            ? ' style="--hero-bg-x:'.$bannerPositionX.'%;--hero-bg-y:'.$bannerPositionY.'%;"'
                            : '';
                        ?>
                    <article class="hero-slide <?=$i===$initialHeroIndex?'active ':''?><?=$themeClass?> <?=(!$imageOnly && $hasBg)?'hero-has-image ':''?><?=$imageOnly?'hero-image-only ':''?><?=$noPanel?'hero-no-panel':''?>"<?=$positionStyle?> data-slide-id="<?=e((string)$slide['id'])?>" data-display-mode="<?=e($imageOnly?'image_only':'standard')?>">
                        <?php if (!$imageOnly && $hasBg) : ?>
                            <img class="hero-backdrop-media" src="<?=e($bannerUrl)?>" alt="" aria-hidden="true" <?=$i===$initialHeroIndex?'fetchpriority="high"':'loading="lazy"'?> decoding="async">
                        <?php endif; ?>
                        <?php if ($imageOnly) : ?>
                            <?php if ($hasBg) : ?>
                                <img class="hero-image-only__media" src="<?=e($bannerUrl)?>" alt="<?=e($slide['title']?:'Homepage banner')?>" style="object-position:<?=$bannerPositionX?>% <?=$bannerPositionY?>%;" <?=$i===$initialHeroIndex?'fetchpriority="high"':'loading="lazy"'?> decoding="async">
                            <?php else: ?>
                                <div class="hero-image-only__missing" role="status">
                                    Banner image unavailable.
                                </div>
                            <?php endif; ?>
                            <div class="hero-image-only__accessibility">
                                <?php if (!empty($slide['title'])) : ?>
                                    <h1><?=e($slide['title'])?></h1>
                                <?php endif; ?>
                                <?php if (!empty($slide['description'])) : ?>
                                    <p><?=e($slide['description'])?></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="container hero-grid">
                                <div class="hero-copy">
                                    <?php if (!empty($slide['_promotion_request_id'])) : ?>
                                        <span class="hero-promotion-source"><?=e($slide['_promotion_source_name'])?></span>
                                    <?php endif; ?>
                                    <span class="eyebrow <?=in_array($themeValue,['gold','orange'],true)?'gold':''?>"><?=e($slide['eyebrow'])?></span>
                                    <h1><?=e($slide['title'])?></h1>
                                    <p><?=e($slide['description'])?></p>
                                    <?php if ($showPrimaryButton || $showSecondaryButton) : ?>
                                        <div class="hero-actions">
                                            <?php if ($showPrimaryButton) : ?>
                                                <a class="btn btn-light" href="<?=e(hero_promotion_url($slide['primary_url']?:'#',(string)($slide['_promotion_source_portal']??'USC')))?>"><?=e($slide['primary_label'])?> <span>→</span></a>
                                            <?php endif; ?>
                                            <?php if ($showSecondaryButton) : ?>
                                                <a class="btn btn-outline" href="<?=e(hero_promotion_url($slide['secondary_url']?:'#',(string)($slide['_promotion_source_portal']??'USC')))?>"><?=e($slide['secondary_label'])?></a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($panelType === 'none') : ?>
                                <?php elseif ($panelType === 'image' && !empty($slide['panel_image'])) : ?>
                                    <div class="hero-panel hero-media-panel" style="--hero-media-image:url('<?=e($slide['panel_image'])?>')">
                                        <img src="<?=e($slide['panel_image'])?>" alt="<?=e($slide['panel_title']?:$slide['title'])?>">
                                    </div>
                                <?php elseif ($panelType === 'card') : ?>
                                    <div class="hero-panel hero-generic-card">
                                        <?php if (!empty($slide['panel_kicker']) || !empty($slide['panel_status'])) : ?>
                                            <div class="panel-head">
                                                <span><?=e($slide['panel_kicker'])?></span>
                                                <b><?=e($slide['panel_status'])?></b>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($slide['panel_title'])) : ?>
                                            <h3><?=e($slide['panel_title'])?></h3>
                                        <?php endif; ?>
                                        <?php if (!empty($slide['panel_description'])) : ?>
                                            <p><?=e($slide['panel_description'])?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (count($heroSlides)>1) : ?>
                    <button class="hero-arrow prev" id="prevSlide" type="button" aria-label="Previous slide">‹</button>
                    <button class="hero-arrow next" id="nextSlide" type="button" aria-label="Next slide">›</button>
                    <div class="hero-dots" id="heroDots">
                        <?php foreach ($heroSlides as $i => $slide) : ?>
                            <button class="<?=$i===$initialHeroIndex?'active':''?>" data-slide="<?=$i?>" type="button" aria-label="Slide <?=$i+1?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            <section class="shortcuts">
                <div class="container shortcuts-grid">
                    <a href="esumbong/esumbong.php"><span class="shortcut-icon green">E</span><span><strong>E-Sumbong</strong><small>Submit a concern</small></span><b>→</b></a>
                    <a href="esumbong/track.php"><span class="shortcut-icon blue">#</span><span><strong>Track Concern</strong><small>Check your status</small></span><b>→</b></a>
                </div>
            </section>
            <section class="latest" id="latest">
                <div class="container">
                    <div class="latest-header">
                        <div>
                            <span class="section-kicker">RECENT PUBLICATIONS</span>
                            <h2>Latest updates</h2>
                            <p class="latest-subtitle">The newest matching post is highlighted automatically for every campus filter.</p>
                        </div>
                        <div class="latest-tools">
                            <label class="search-box">
                                <span>⌕</span>
                                <input id="newsSearch" type="search" placeholder="Search updates..." aria-label="Search updates">
                            </label>
                            <div class="filters" id="newsFilters">
                                <button class="active" data-filter="all" type="button">All</button>
                                <button data-filter="usc" type="button">USC</button>
                                <button data-filter="nluc" type="button">NLUC</button>
                                <button data-filter="mluc" type="button">MLUC</button>
                                <button data-filter="sluc" type="button">SLUC</button>
                                <button data-filter="ous" type="button">OUS</button>
                            </div>
                        </div>
                    </div>
                    <div class="latest-lead-slot" id="latestLeadSlot">
                    </div>
                    <div class="news-feed latest-list" id="newsFeed">
                        <?php foreach ($latestPosts as $post) : ?>
                            <?php
                            $postImages=$post['images']??[];
                            $postVideos=$post['videos']??[];
                            $cover=$postImages[0]??null;
                            $featuredMedia=post_featured_media_record($postImages,$postVideos);
                            $featuredType=(string)($featuredMedia['_media_type']??'');
                            $featuredImage=$featuredType==='image'?$featuredMedia:null;
                            $previewVideo=$featuredType==='video'?$featuredMedia:null;
                            $postUrl = 'news/article.php?'.(!empty($post['slug'])?'slug='.rawurlencode((string)$post['slug']):'id='.rawurlencode((string)$post['id']));
                            ?>
                            <article class="news-item row"
                                data-category="<?=e($post['_home_campus']??home_post_campus_code($post))?>"
                                data-post-id="<?=e((string)$post['id'])?>"
                                data-post-url="<?=e($postUrl)?>"
                                data-post-title="<?=e((string)$post['title'])?>"
                                data-cover-url="<?=e((string)($featuredImage['_public_url']??''))?>"
                                data-video-url="<?=e((string)($previewVideo['_public_url']??''))?>"
                                data-photo-count="<?=e((string)count($postImages))?>">
                                <div class="news-thumb <?=e($post['category'])?> <?= $featuredImage ? 'has-cover' : ($previewVideo ? 'has-video' : '') ?>">
                                    <?php if ($featuredImage) : ?>
                                        <img src="<?=e((string)$featuredImage['_public_url'])?>" alt="<?=e($post['title'])?> featured photo" loading="lazy">
                                    <?php elseif ($previewVideo) : ?>
                                        <a class="home-video-preview" href="<?=e($postUrl)?>" data-video-frame aria-label="Open video story: <?=e($post['title'])?>">
                                            <video muted playsinline preload="metadata" data-video-preview src="<?=e((string)$previewVideo['_public_url'])?>"></video>
                                            <span class="home-video-play" aria-hidden="true">▶</span>
                                            <span class="home-video-chip">Video</span>
                                        </a>
                                    <?php else: ?>
                                        <span><?=e(portal_display_name((string)$post['category']))?></span>
                                    <?php endif; ?>
                                    <?php if (count($postImages)>1) : ?>
                                        <b class="home-photo-count small">◫ <?=count($postImages)?> photos</b>
                                    <?php endif; ?>
                                </div>
                                <div class="news-copy">
                                    <div class="news-meta">
                                        <time><?=e(date('M j, Y',strtotime($post['published_at'])))?></time><span><?=e(portal_display_name((string)$post['category']))?></span>
                                    </div>
                                    <h3><a class="news-title-link" href="<?=e($postUrl)?>"><?=e($post['title'])?></a></h3>
                                    <p><?=e($post['excerpt'])?></p>
                                    <a href="<?=e($postUrl)?>">Read More →</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state" id="newsEmpty" hidden>
                        <strong>No updates found.</strong>
                        <span>Try another search or campus filter.</span>
                    </div>
                    <nav class="pagination" id="newsPagination" aria-label="News pages">
                        <button class="page-nav" id="newsPrev" type="button">← Previous</button>
                        <div class="page-numbers" id="newsPageNumbers">
                        </div>
                        <button class="page-nav" id="newsNext" type="button">Next →</button>
                    </nav>
                </div>
            </section>
        </main>
        <?php
        include __DIR__.'/src/includes/public-footer.php';
        ?>
        <script src="public/assets/js/home.js?v=<?=e((string)@filemtime(__DIR__.'/public/assets/js/home.js'))?>"></script>
        <script src="public/assets/js/video-preview.js?v=<?=e((string)@filemtime(__DIR__.'/public/assets/js/video-preview.js'))?>"></script>
    </body>
</html>
