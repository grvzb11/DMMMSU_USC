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
require_once dirname(__DIR__, 2).'/config/database.php';
require_once dirname(__DIR__, 2).'/config/helpers.php';
ensure_post_images_table($pdo);
ensure_post_videos_table($pdo);
if (db_column_exists($pdo, 'posts', 'scheduled_at') && system_setting($pdo, 'auto_publish_scheduled', '1') === '1') publish_scheduled_posts($pdo);

if (!isset($campusCode, $campusName, $campusLogo, $campusAccent, $campusAccent2)) {
    http_response_code(500);
    exit('Campus configuration missing.');
}
$campusKey = strtolower($campusCode);
$campusPage = 'campus/' . $campusKey . '.php';
$campusParam = urlencode($campusCode);

$stmt = $pdo->prepare("SELECT * FROM posts WHERE status='published' AND LOWER(category)=? ORDER BY published_at DESC, id DESC LIMIT 30");
$stmt->execute([$campusKey]);
$posts = $stmt->fetchAll();
$campusImages = [];
$campusVideos = [];
if ($posts) {
    $ids = array_map(fn($p) => (int)$p['id'], $posts);
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $imageStmt = $pdo->prepare('SELECT * FROM post_images WHERE post_id IN ('.$marks.') ORDER BY post_id,sort_order,id');
    $imageStmt->execute($ids);
    foreach ($imageStmt->fetchAll() as $img) {
        $resolved = public_file_record($img);
        if ($resolved !== null) $campusImages[(int)$img['post_id']][] = $resolved;
    }
    $videoStmt = $pdo->prepare('SELECT * FROM post_videos WHERE post_id IN ('.$marks.') ORDER BY post_id,sort_order,id');
    $videoStmt->execute($ids);
    foreach ($videoStmt->fetchAll() as $video) {
        $resolved = public_file_record($video);
        if ($resolved !== null) $campusVideos[(int)$video['post_id']][] = $resolved;
    }
}
foreach ($posts as &$post) {
    $post['images'] = $campusImages[(int)$post['id']] ?? [];
    $post['videos'] = $campusVideos[(int)$post['id']] ?? [];
}
unset($post);

// Each campus owns an independent homepage hero.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
ensure_hero_slides_table($pdo);
$heroSlides = hero_public_slides($pdo, $campusCode);
$requestedHeroId = max(0, (int)($_GET['hero_slide'] ?? 0));
$initialHeroIndex = 0;
if ($requestedHeroId>0) {
    foreach ($heroSlides as $heroIndex => $heroSlide) {
        if ((int)$heroSlide['id'] === $requestedHeroId) {
            $initialHeroIndex = $heroIndex;
            break;
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
        <title><?=e($campusName)?> Student Services | DMMMSU</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="public/assets/css/home.css">
        <link rel="stylesheet" href="public/assets/css/shared-nav.css">
        <link rel="stylesheet" href="public/assets/css/campus-home.css">
    </head>
    <body class="campus-home campus-<?=e($campusKey)?>" style="--campus-accent:<?=e($campusAccent)?>;--campus-accent-2:<?=e($campusAccent2)?>">
        <header class="site-header campus-site-header">
            <div class="container nav-wrap">
                <a class="brand campus-brand" href="<?=e($campusPage)?>">
                <img src="<?=e($campusLogo)?>" alt="<?=e($campusName)?> Campus Student Body Organization logo">
                <span><strong><?=e($campusName)?></strong><small>Campus Student Body Organization</small></span>
                </a>
                <button class="nav-toggle" id="navToggle" type="button" aria-label="Open menu" aria-expanded="false"><span></span><span></span><span></span></button>
                <nav class="main-nav" id="mainNav">
                    <a class="active" href="<?=e($campusPage)?>" aria-current="page">Home</a>
                    <a href="news/updates.php?campus=<?=e($campusParam)?>">News & Updates</a>
                    <div class="nav-dropdown">
                        <button class="nav-drop-btn" type="button">Services <span class="chevron">▾</span></button>
                        <div class="nav-drop-menu">
                            <a href="esumbong/esumbong.php?campus=<?=e($campusParam)?>">E-Sumbong</a>
                            <a href="esumbong/track.php?campus=<?=e($campusParam)?>">Track Concern</a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-drop-btn" type="button">Campuses <span class="chevron">▾</span></button>
                        <div class="nav-drop-menu">
                            <a href="campus/nluc.php" <?= $campusCode==='NLUC'?'aria-current="page"':'' ?>>North La Union Campus</a>
                            <a href="campus/mluc.php" <?= $campusCode==='MLUC'?'aria-current="page"':'' ?>>Mid La Union Campus</a>
                            <a href="campus/sluc.php" <?= $campusCode==='SLUC'?'aria-current="page"':'' ?>>South La Union Campus</a>
                            <a href="campus/ous.php" <?= $campusCode==='OUS'?'aria-current="page"':'' ?>>Open University System</a>
                        </div>
                    </div>
                    <a href="about.php?campus=<?=e($campusParam)?>">About</a>
                </nav>
                <a class="submit-cta" href="index.php">USC Main Page</a>
            </div>
        </header>
        <?=public_alerts_html($pdo,$campusCode)?>
        <main id="main-content" tabindex="-1">
            <section class="hero campus-hero" id="home">
                <?php foreach ($heroSlides as $i => $slide) : ?>
                    <?php
                          $allowedHeroThemes=['none','green','blue','forest','emerald','teal','navy','sky','purple','maroon','red','orange','gold','slate'];
                          $themeValue=in_array($slide['theme']??'green',$allowedHeroThemes,true)?($slide['theme']??'green'):'green';
                          $themeClass='hero-'.$themeValue;
                          $hasBg=!empty($slide['background_image']);
                          $imageOnly=($slide['display_mode']??'standard')==='image_only';
                          $storedPanelType=$slide['panel_type']??'card';
                          $panelType=in_array($storedPanelType,['esumbong','metrics','card'],true)?'card':(in_array($storedPanelType,['none','image'],true)?$storedPanelType:'card');
                          $noPanel=!$imageOnly && $panelType==='none';
                          $buttonMode=in_array($slide['button_mode']??'both',['both','primary','secondary','none'],true)?($slide['button_mode']??'both'):'both';
                          $showPrimaryButton=!empty($slide['primary_label']) && in_array($buttonMode,['both','primary'],true);
                          $showSecondaryButton=!empty($slide['secondary_label']) && in_array($buttonMode,['both','secondary'],true);
                          $bannerPositionX=max(0,min(100,(int)($slide['background_position_x']??50)));
                          $bannerPositionY=max(0,min(100,(int)($slide['background_position_y']??50)));
                          $imageVersion=!empty($slide['updated_at'])?(string)strtotime((string)$slide['updated_at']):(string)$slide['id'];
                          $bannerUrl=$hasBg?$slide['background_image'].'?v='.$imageVersion:'';
                          $positionStyle=$hasBg?' style="--hero-bg-x:'.$bannerPositionX.'%;--hero-bg-y:'.$bannerPositionY.'%;"':'';
                        ?>
                    <article class="hero-slide <?=$i===$initialHeroIndex?'active ':''?><?=$themeClass?> <?=(!$imageOnly && $hasBg)?'hero-has-image ':''?><?=$imageOnly?'hero-image-only ':''?><?=$noPanel?'hero-no-panel':''?>"<?=$positionStyle?> data-slide-id="<?=e((string)$slide['id'])?>" data-display-mode="<?=e($imageOnly?'image_only':'standard')?>">
                        <?php if (!$imageOnly && $hasBg) : ?>
                            <img class="hero-backdrop-media" src="<?=e($bannerUrl)?>" alt="" aria-hidden="true" <?=$i===$initialHeroIndex?'fetchpriority="high"':'loading="lazy"'?> decoding="async">
                        <?php endif; ?>
                        <?php if ($imageOnly) : ?>
                            <?php if ($hasBg) : ?>
                                <img class="hero-image-only__media" src="<?=e($bannerUrl)?>" alt="<?=e($slide['title']?:$campusName.' homepage banner')?>" style="object-position:<?=$bannerPositionX?>% <?=$bannerPositionY?>%;" <?=$i===$initialHeroIndex?'fetchpriority="high"':'loading="lazy"'?> decoding="async">
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
                                    <span class="eyebrow <?=in_array($themeValue,['gold','orange'],true)?'gold':''?>"><?=e($slide['eyebrow'])?></span>
                                    <h1><?=e($slide['title'])?></h1>
                                    <p><?=e($slide['description'])?></p>
                                    <?php if ($showPrimaryButton || $showSecondaryButton) : ?>
                                        <div class="hero-actions">
                                            <?php if ($showPrimaryButton) : ?>
                                                <a class="btn btn-light" href="<?=e(hero_promotion_url($slide['primary_url']?:'#',$campusCode))?>"><?=e($slide['primary_label'])?> <span>→</span></a>
                                            <?php endif; ?>
                                            <?php if ($showSecondaryButton) : ?>
                                                <a class="btn btn-outline" href="<?=e(hero_promotion_url($slide['secondary_url']?:'#',$campusCode))?>"><?=e($slide['secondary_label'])?></a>
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
                                                <span><?=e($slide['panel_kicker'])?></span><b><?=e($slide['panel_status'])?></b>
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
            <section class="shortcuts campus-shortcuts">
                <div class="container shortcuts-grid">
                    <a href="esumbong/esumbong.php?campus=<?=e($campusParam)?>"><span class="shortcut-icon green">E</span><span><strong>E-Sumbong</strong><small>Submit to <?=e($campusName)?></small></span><b>→</b></a>
                    <a href="esumbong/track.php?campus=<?=e($campusParam)?>"><span class="shortcut-icon blue">#</span><span><strong>Track Concern</strong><small>Check your status</small></span><b>→</b></a>
                </div>
            </section>
            <section class="latest" id="latest">
                <div class="container">
                    <div class="latest-header campus-latest-header">
                        <div>
                            <span class="section-kicker">RECENT CAMPUS PUBLICATIONS</span>
                            <h2>Latest updates</h2>
                            <p class="latest-subtitle">News and announcements published for <?=e($campusName)?>.</p>
                        </div>
                        <div class="latest-tools campus-latest-tools">
                            <label class="search-box">
                                <span>⌕</span>
                                <input id="newsSearch" type="search" placeholder="Search <?=e($campusName)?> updates..." aria-label="Search updates">
                            </label>
                            <a class="view-all-campus" href="news/updates.php?campus=<?=e($campusParam)?>">View all news →</a>
                        </div>
                    </div>
                    <div class="latest-lead-slot" id="latestLeadSlot">
                    </div>
                    <div class="news-feed latest-list" id="newsFeed">
                        <?php foreach($posts as $post): ?>
                            <?php
                            $postImages=$post['images']??[];
                            $postVideos=$post['videos']??[];
                            $featuredMedia=post_featured_media_record($postImages,$postVideos);
                            $featuredType=(string)($featuredMedia['_media_type']??'');
                            $featuredImage=$featuredType==='image'?$featuredMedia:null;
                            $previewVideo=$featuredType==='video'?$featuredMedia:null;
                            $postUrl = 'news/article.php?'.(!empty($post['slug'])?'slug='.rawurlencode((string)$post['slug']):'id='.rawurlencode((string)$post['id'])).'&campus='.rawurlencode((string)$campusParam);
                            ?>
                            <article class="news-item row"
                                data-category="<?=e($campusKey)?>"
                                data-post-id="<?=e((string)$post['id'])?>"
                                data-post-url="<?=e($postUrl)?>"
                                data-post-title="<?=e((string)$post['title'])?>"
                                data-cover-url="<?=e((string)($featuredImage['_public_url']??''))?>"
                                data-video-url="<?=e((string)($previewVideo['_public_url']??''))?>"
                                data-photo-count="<?=e((string)count($postImages))?>">
                                <div class="news-thumb <?=e($campusKey)?> <?= $featuredImage ? 'has-cover' : ($previewVideo ? 'has-video' : '') ?>">
                                    <?php if ($featuredImage) : ?>
                                        <img src="<?=e((string)$featuredImage['_public_url'])?>" alt="<?=e($post['title'])?> featured photo" loading="lazy">
                                    <?php elseif ($previewVideo) : ?>
                                        <a class="home-video-preview" href="<?=e($postUrl)?>" data-video-frame aria-label="Open video story: <?=e($post['title'])?>">
                                            <video muted playsinline preload="metadata" data-video-preview src="<?=e((string)$previewVideo['_public_url'])?>"></video>
                                            <span class="home-video-play" aria-hidden="true">▶</span>
                                            <span class="home-video-chip">Video</span>
                                        </a>
                                    <?php else: ?>
                                        <span><?=e($campusName)?></span>
                                    <?php endif; ?>
                                    <?php if (count($postImages)>1) : ?>
                                        <b class="home-photo-count small">◫ <?=count($postImages)?> photos</b>
                                    <?php endif; ?>
                                </div>
                                <div class="news-copy">
                                    <div class="news-meta">
                                        <time><?=e(date('M j, Y',strtotime($post['published_at'])))?></time><span><?=e($campusName)?></span>
                                    </div>
                                    <h3><a class="news-title-link" href="<?=e($postUrl)?>"><?=e($post['title'])?></a></h3>
                                    <p><?=e($post['excerpt'])?></p>
                                    <a href="<?=e($postUrl)?>">Read More →</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state" id="newsEmpty" <?=count($posts)?'hidden':''?>>
                        <strong>No campus updates yet.</strong><span>Published campus posts will appear here automatically.</span>
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
        <?php include __DIR__.'/public-footer.php'; ?>
        <script>
window.CAMPUS_HOME_FILTER = <?=json_encode($campusKey)?>;
</script>
        <script src="public/assets/js/home.js?v=<?=e((string)@filemtime(dirname(__DIR__, 2).'/public/assets/js/home.js'))?>"></script>
        <script src="public/assets/js/video-preview.js?v=<?=e((string)@filemtime(dirname(__DIR__, 2).'/public/assets/js/video-preview.js'))?>"></script>
    </body>
</html>
