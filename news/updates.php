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

$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
$campus = strtoupper(trim((string)($_GET['campus'] ?? 'ALL')));
if (!in_array($campus, ['ALL', 'USC', 'NLUC', 'MLUC', 'SLUC', 'OUS'], true)) $campus = 'ALL';
$where = ["p.status='published'", "p.deleted_at IS NULL"];
$params = [];
if ($campus !== 'ALL') {
    $where[] = 'UPPER(p.category)=?';
    $params[] = $campus;
}
if ($q !== '') {
    $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ? OR p.label LIKE ?)';
    $like = '%'.$q.'%';
    array_push($params, $like, $like, $like, $like);
}
$whereSql = ' WHERE '.implode(' AND ', $where);
$countSt = $pdo->prepare('SELECT COUNT(*) FROM posts p'.$whereSql);
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$pages = max(1, (int)ceil($total/$perPage));
if ($page>$pages) $page = $pages;
$offset = ($page-1)*$perPage;
$sql = 'SELECT p.* FROM posts p'.$whereSql.' ORDER BY p.published_at DESC,p.id DESC LIMIT '.$perPage.' OFFSET '.$offset;
$st = $pdo->prepare($sql);
$st->execute($params);
$posts = $st->fetchAll();

// Every portal keeps its own Featured story through is_featured. The
// university-wide All Featured placement is a separate flag controlled only
// by the USC or System Administrator and may point to any published story.
$lead = null;
if ($page === 1) {
    // Build the Featured query independently from the ordinary publication
    // listing so USC's local Featured choice and the university-wide All
    // Featured choice can never leak into one another.
    $featuredWhere = ["p.status='published'", 'p.deleted_at IS NULL'];
    $featuredParams = [];
    if ($q !== '') {
        $featuredWhere[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ? OR p.label LIKE ?)';
        $featuredLike = '%'.$q.'%';
        array_push($featuredParams, $featuredLike, $featuredLike, $featuredLike, $featuredLike);
    }
    if ($campus === 'ALL') {
        if (db_column_exists($pdo, 'posts', 'is_university_featured')) {
            $featuredWhere[] = 'p.is_university_featured=1';
        } else {
            // Before migration there is deliberately no All Featured fallback:
            // local USC Featured content must not be mistaken for All Featured.
            $featuredWhere[] = '1=0';
        }
    } else {
        $featuredWhere[] = 'UPPER(p.category)=?';
        $featuredParams[] = $campus;
        $featuredWhere[] = 'p.is_featured=1';
    }
    $featuredSt = $pdo->prepare('SELECT p.* FROM posts p WHERE '.implode(' AND ', $featuredWhere).' ORDER BY p.published_at DESC,p.id DESC LIMIT 1');
    $featuredSt->execute($featuredParams);
    $lead = $featuredSt->fetch() ?: null;

    // Keep a Featured presentation on every individual portal. If that portal
    // has not explicitly selected a Featured story yet, use its latest matching
    // publication as a temporary local lead. The All view never uses this
    // fallback because university-wide Featured placement must be intentional.
    if (!$lead && $campus !== 'ALL') {
        $fallbackSt = $pdo->prepare('SELECT p.* FROM posts p'.$whereSql.' ORDER BY p.published_at DESC,p.id DESC LIMIT 1');
        $fallbackSt->execute($params);
        $lead = $fallbackSt->fetch() ?: null;
    }
}

$imagesByPost = [];
$ids = array_map(fn($r) => (int)$r['id'], $posts);
if ($lead) $ids[] = (int)$lead['id'];
$ids = array_values(array_unique($ids));
if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $im = $pdo->prepare('SELECT * FROM post_images WHERE post_id IN ('.$ph.') ORDER BY post_id,sort_order,id');
    $im->execute($ids);
    foreach ($im->fetchAll() as $img) {
        $resolved = public_file_record($img);
        if ($resolved !== null) $imagesByPost[(int)$img['post_id']][] = $resolved;
    }
    foreach ($posts as &$post) $post['images'] = $imagesByPost[(int)$post['id']] ?? [];
    unset($post);
    if ($lead) $lead['images'] = $imagesByPost[(int)$lead['id']] ?? [];
}

$videosByPost = [];
if ($ids && db_table_exists($pdo, 'post_videos')) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $vm = $pdo->prepare('SELECT * FROM post_videos WHERE post_id IN ('.$ph.') ORDER BY post_id,sort_order,id');
    $vm->execute($ids);
    foreach ($vm->fetchAll() as $video) {
        $resolved = public_file_record($video);
        if ($resolved !== null) $videosByPost[(int)$video['post_id']][] = $resolved;
    }
}
foreach ($posts as &$post) $post['videos'] = $videosByPost[(int)$post['id']] ?? [];
unset($post);
if ($lead) $lead['videos'] = $videosByPost[(int)$lead['id']] ?? [];

$stories = $posts;

function postCampus(array $p): string {
    return strtoupper(trim((string)($p['category'] ?? 'USC')) ?: 'USC');
}
function postDate(array $p): string {
    return !empty($p['published_at']) ? date('M j, Y', strtotime($p['published_at'])) : '';
}
function imgUrl(array $img): string {
    return (string)($img['_public_url'] ?? public_file_url((string)($img['file_path'] ?? '')) ?? '');
}
function videoUrl(array $video): string {
    return (string)($video['_public_url'] ?? public_file_url((string)($video['file_path'] ?? '')) ?? '');
}
function campusLogo(string $campus): string {
    return match($campus) {
        'NLUC' => 'public/assets/images/nluc-sbo-logo.jpg', 'MLUC' => 'public/assets/images/mluc-sbo-logo.jpg', 'SLUC' => 'public/assets/images/sluc-sbo-logo.jpg', 'OUS' => 'public/assets/images/ous-sbo-logo.jpg', default => 'public/assets/images/usc-seal.jpg'
    };
}
function campusName(string $campus): string {
    return match($campus) {
        'NLUC' => 'North La Union Campus', 'MLUC' => 'Mid La Union Campus', 'SLUC' => 'South La Union Campus', 'OUS' => 'Open University System', default => 'University Student Council'
    };
}
function articleUrl(array $post): string {
    $key = !empty($post['slug'])?'slug='.rawurlencode((string)$post['slug']):'id='.(int)$post['id'];
    $url = 'news/article.php?'.$key;
    $ctx = strtoupper(trim((string)($_GET['campus'] ?? '')));
    if (in_array($ctx, ['NLUC', 'MLUC', 'SLUC', 'OUS'], true)) $url.='&campus='.rawurlencode($ctx);
    return $url;
}
function news_query_url(int $targetPage = 1, ?string $targetCampus = null): string {
    global $q, $campus;
    $params = [];
    $c = $targetCampus ?? $campus;
    if ($q !== '') $params['q'] = $q;
    if ($c !== 'ALL') $params['campus'] = $c;
    if ($targetPage>1) $params['page'] = $targetPage;
    return 'news/updates.php'.($params?'?'.http_build_query($params):'');
}
?>
<!doctype html>
<html lang="en">
    <head>
        <base href="../">
        <link rel="icon" type="image/png" href="public/assets/images/favicon.png">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="description" content="Official DMMMSU University Student Council news, announcements, campus stories, and student-service updates.">
        <title>News &amp; Updates | USC DMMMSU</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="public/assets/css/style.css?v=<?=e((string)@filemtime(dirname(__DIR__).'/public/assets/css/style.css'))?>">
        <link rel="stylesheet" href="public/assets/css/shared-nav.css">
    </head>
    <body class="newsroom-v3-page">
        <?php $activeNav='updates';$activeService='';include dirname(__DIR__).'/src/includes/public-header.php';?>
        <main id="main-content" tabindex="-1" class="newsroom-page-main">
            <section class="newsroom-hero">
                <div class="container newsroom-hero__inner">
                    <div class="newsroom-hero__copy">
                        <span class="newsroom-kicker">UNIVERSITY STUDENT COUNCIL NEWSROOM</span>
                        <h1>News &amp; Updates</h1>
                        <p>Official announcements, campus stories, student-service notices, and council activities from the University Student Council and DMMMSU campuses.</p>
                    </div>
                    <div class="newsroom-hero__stat" aria-label="Matching published updates">
                        <strong><?=$total?></strong><span><?=$q!==''||$campus!=='ALL'?'Matching stories':'Published stories'?></span>
                    </div>
                </div>
            </section>
            <section class="newsroom-content">
                <div class="container">
                    <div class="newsroom-toolbar">
                        <form class="newsroom-search" method="get" action="news/updates.php">
                            <span class="newsroom-search__icon" aria-hidden="true">⌕</span>
                            <input name="q" type="search" value="<?=e($q)?>" placeholder="Search news, titles, or keywords" aria-label="Search news and announcements">
                            <?php if ($campus !== 'ALL') : ?>
                                <input type="hidden" name="campus" value="<?=e($campus)?>">
                            <?php endif; ?>
                            <button type="submit" aria-label="Search">Search</button>
                            <?php if ($q !== '') : ?>
                                <a id="newsSearchClear" href="<?=e('news/updates.php'.($campus!=='ALL'?'?campus='.rawurlencode($campus):''))?>" aria-label="Clear search">×</a>
                            <?php endif; ?>
                        </form>
                        <div class="newsroom-filter-group">
                            <span>Campus</span>
                            <div class="newsroom-filters" aria-label="Filter by campus">
                                <?php foreach (['ALL' => 'All', 'USC' => 'USC', 'NLUC' => 'NLUC', 'MLUC' => 'MLUC', 'SLUC' => 'SLUC', 'OUS' => 'OUS'] as $key => $label) : ?>
                                    <a class="news-filter <?=$campus===$key?'active':''?>" href="<?=e(news_query_url(1,$key))?>" <?=$campus===$key?'aria-current="page"':''?>><?=e($label)?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!$posts) : ?>
                        <div class="newsroom-empty">
                            <strong>No updates found.</strong><br>Try a different keyword or campus filter.
                        </div>
                    <?php else: ?>
                        <?php if($lead):$lc=postCampus($lead);$li=$lead['images']??[];$lv=$lead['videos']??[];$cover=$li[0]??null;$featuredMedia=post_featured_media_record($li,$lv);$featuredType=(string)($featuredMedia['_media_type']??'');$featuredImage=$featuredType==='image'?$featuredMedia:null;$leadVideo=$featuredType==='video'?$featuredMedia:null;$leadMediaClass=$featuredImage?'has-cover':($leadVideo?'has-video':'is-brand');?>
                            <section class="newsroom-featured-wrap">
                                <div class="newsroom-section-heading">
                                    <div>
                                        <span>FEATURED</span>
                                    </div>
                                </div>
                                <article class="newsroom-featured">
                                    <div class="newsroom-featured__media <?=e($leadMediaClass)?>">
                                        <div class="newsroom-media-fallback">
                                            <img src="<?=e(campusLogo($lc))?>" alt=""><strong><?=e(campusName($lc))?></strong>
                                        </div>
                                        <?php if ($featuredImage) : ?>
                                            <img class="newsroom-cover" src="<?=e(imgUrl($featuredImage))?>" alt="<?=e($lead['title'])?> featured photo" onerror="this.parentElement.classList.add('image-failed')">
                                        <?php elseif ($leadVideo) : ?>
                                            <a class="newsroom-video-preview" href="<?=e(articleUrl($lead))?>" data-video-frame aria-label="Open video story: <?=e($lead['title'])?>">
                                                <video muted playsinline preload="metadata" data-video-preview src="<?=e(videoUrl($leadVideo))?>"></video>
                                                <span class="newsroom-video-play" aria-hidden="true">▶</span>
                                                <span class="newsroom-video-chip">Video</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (count($li)>1) : ?>
                                            <span class="newsroom-photo-badge">+<?=count($li)-1?> photos</span>
                                        <?php elseif ($cover && count($lv)>0) : ?>
                                            <span class="newsroom-video-badge">▶ <?=count($lv)?> <?=count($lv)===1?'video':'videos'?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="newsroom-featured__body">
                                        <div class="newsroom-meta">
                                            <span><?=e(campusName($lc))?></span><time><?=e(postDate($lead))?></time>
                                        </div>
                                        <h2><a class="newsroom-title-link" href="<?=e(articleUrl($lead))?>"><?=e($lead['title'])?></a></h2>
                                        <?php if (trim((string)$lead['excerpt'])) : ?>
                                            <p><?=e($lead['excerpt'])?></p>
                                        <?php endif; ?>
                                        <a class="newsroom-read newsroom-read-link" href="<?=e(articleUrl($lead))?>">Read More <span>→</span></a>
                                    </div>
                                </article>
                            </section>
                        <?php endif; ?>
                        <section class="newsroom-stories">
                            <div class="newsroom-section-heading newsroom-section-heading--stories">
                                <div>
                                    <span>ALL PUBLICATIONS</span>
                                    <h2><?=$q!==''||$campus!=='ALL'?'Search results':'Latest stories'?></h2>
                                </div>
                                <strong><?=$total?> <?=$total===1?'story':'stories'?></strong>
                            </div>
                            <div class="newsroom-grid" id="updatesGrid">
                                <?php foreach($stories as $p):$c=postCampus($p);$imgs=$p['images']??[];$vids=$p['videos']??[];$cover=$imgs[0]??null;$featuredMedia=post_featured_media_record($imgs,$vids);$featuredType=(string)($featuredMedia['_media_type']??'');$featuredImage=$featuredType==='image'?$featuredMedia:null;$cardVideo=$featuredType==='video'?$featuredMedia:null;$cardMediaClass=$featuredImage?'has-cover':($cardVideo?'has-video':'is-brand');?>
                                    <article class="newsroom-card">
                                        <div class="newsroom-card__media <?=e($cardMediaClass)?>">
                                            <div class="newsroom-media-fallback newsroom-media-fallback--card">
                                                <img src="<?=e(campusLogo($c))?>" alt=""><strong><?=e(campusName($c))?></strong>
                                            </div>
                                            <?php if ($featuredImage) : ?>
                                                <img class="newsroom-cover" loading="lazy" src="<?=e(imgUrl($featuredImage))?>" alt="<?=e($p['title'])?> featured photo" onerror="this.parentElement.classList.add('image-failed')">
                                            <?php elseif ($cardVideo) : ?>
                                                <a class="newsroom-video-preview" href="<?=e(articleUrl($p))?>" data-video-frame aria-label="Open video story: <?=e($p['title'])?>">
                                                    <video muted playsinline preload="metadata" data-video-preview src="<?=e(videoUrl($cardVideo))?>"></video>
                                                    <span class="newsroom-video-play" aria-hidden="true">▶</span>
                                                    <span class="newsroom-video-chip">Video</span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (count($imgs)>1) : ?>
                                                <span class="newsroom-photo-badge">+<?=count($imgs)-1?> photos</span>
                                            <?php elseif ($cover && count($vids)>0) : ?>
                                                <span class="newsroom-video-badge">▶ <?=count($vids)?> <?=count($vids)===1?'video':'videos'?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="newsroom-card__body">
                                            <div class="newsroom-meta">
                                                <span><?=e(campusName($c))?></span><time><?=e(postDate($p))?></time>
                                            </div>
                                            <h3><a class="newsroom-title-link" href="<?=e(articleUrl($p))?>"><?=e($p['title'])?></a></h3>
                                            <?php if (trim((string)$p['excerpt'])) : ?>
                                                <p><?=e($p['excerpt'])?></p>
                                            <?php endif; ?>
                                            <a class="newsroom-read newsroom-read-link" href="<?=e(articleUrl($p))?>">Read More <span>→</span></a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($pages>1) : ?>
                                <nav class="newsroom-pagination" aria-label="News pagination">
                                    <?php if ($page>1) : ?>
                                        <a class="newsroom-page-btn newsroom-page-nav" href="<?=e(news_query_url($page-1))?>">← Previous</a>
                                    <?php endif; ?>
                                    <div class="newsroom-page-numbers">
                                        <?php $start=max(1,$page-2);$end=min($pages,$page+2);for($i=$start;$i<=$end;$i++):?>
                                        <a class="newsroom-page-btn <?=$i===$page?'active':''?>" href="<?=e(news_query_url($i))?>" <?=$i===$page?'aria-current="page"':''?>><?=$i?></a>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($page<$pages) : ?>
                                    <a class="newsroom-page-btn newsroom-page-nav" href="<?=e(news_query_url($page+1))?>">Next →</a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php include dirname(__DIR__).'/src/includes/public-footer.php'; ?>
    <script src="public/assets/js/script.js"></script>
    <script src="public/assets/js/video-preview.js"></script>
</body>
</html>
