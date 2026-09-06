<?php
require_once '../config/database.php';
require_once '_layout.php';
require_once '../config/helpers.php';
if (!can_manage_content()) deny_access('Your role cannot preview publications.');
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM posts WHERE id=? AND deleted_at IS NULL LIMIT 1');
$st->execute([$id]);
$post = $st->fetch();
if (!$post) not_found('The requested publication was not found.');
require_admin_code(strtoupper((string)$post['category']));
$images = post_images($pdo, $id);
$videos = post_videos($pdo, $id);
$cover = $images[0] ?? null;
$imageCount = count($images);
$videoCount = count($videos);
$singlePhotoOnly = $imageCount === 1 && $videoCount === 0;
$singleVideoOnly = $imageCount === 0 && $videoCount === 1;
$photosOnlyGallery = $imageCount > 1 && $videoCount === 0;
$videosOnlyGallery = $videoCount > 1 && $imageCount === 0;
$mixedMediaGallery = $imageCount > 0 && $videoCount > 0;
$showHeroMedia = $singlePhotoOnly || $singleVideoOnly;
$galleryImages = ($photosOnlyGallery || $mixedMediaGallery) ? $images : [];
$galleryVideos = ($videosOnlyGallery || $mixedMediaGallery) ? $videos : [];
$galleryAspectRatio = post_gallery_display_ratio($images);
$campus = strtoupper((string)$post['category']);
$campusName = match($campus) {
    'NLUC' => 'North La Union Campus', 'MLUC' => 'Mid La Union Campus', 'SLUC' => 'South La Union Campus', 'OUS' => 'Open University System', default => 'University Student Council'
};
$campusLogo = match($campus) {
    'NLUC' => '../public/assets/images/nluc-sbo-logo.jpg', 'MLUC' => '../public/assets/images/mluc-sbo-logo.jpg', 'SLUC' => '../public/assets/images/sluc-sbo-logo.jpg', 'OUS' => '../public/assets/images/ous-sbo-logo.jpg', default => '../public/assets/images/usc-seal.jpg'
};
$status = (!empty($post['scheduled_at']) && $post['status'] === 'draft')?'Scheduled':ucfirst((string)$post['status']);
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title>Preview: <?=e($post['title'])?></title>
        <link rel="icon" type="image/png" href="../public/assets/images/favicon.png">
        <link rel="stylesheet" href="../public/assets/css/style.css">
        <link rel="stylesheet" href="../public/assets/css/article.css">
        <style>
.preview-banner {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: #111827;
    color: #fff;
    padding: 10px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    font: 600 14px/1.4 Inter,Arial,sans-serif;
}

.preview-banner a {
    color: #fff;
    text-decoration: underline;
}

.preview-banner strong {
    margin-right: 8px;
}

.preview-banner span {
    opacity: .8;
}

.preview-main {
    padding-top: 24px;
}
</style>
    </head>
    <body class="article-page">
        <div class="preview-banner">
            <div>
                <strong>Admin preview</strong><span><?=e($status)?> · not a public URL</span>
            </div>
            <a href="posts.php?edit=<?=$id?>">← Back to editor</a>
        </div>
        <main class="preview-main">
            <section class="story-hero">
                <div class="article-shell">
                    <div class="story-hero__grid<?=$showHeroMedia?'':' story-hero__grid--no-media'?>">
                        <div class="story-hero__copy">
                            <div class="story-kicker">
                                <img src="<?=e($campusLogo)?>" alt=""><span><?=e($campus)?></span><b><?=e($campusName)?></b>
                            </div>
                            <h1><?=e($post['title'])?></h1>
                            <div class="story-meta">
                                <span><?=e($post['label']?:$campusName)?></span><span aria-hidden="true">•</span><time><?=e(!empty($post['published_at'])?date('F j, Y',strtotime($post['published_at'])):'Unpublished')?></time>
                            </div>
                        </div>
                        <?php if ($singlePhotoOnly && $cover) : ?>
                            <div class="story-cover" style="cursor:default">
                                <span class="story-cover__blur" style="background-image:url('../<?=e($cover['file_path'])?>')"></span><img src="../<?=e($cover['file_path'])?>" alt="<?=e($post['title'])?>">
                            </div>
                        <?php elseif ($singleVideoOnly && !empty($videos[0])) : ?>
                            <figure class="story-feature-video" data-video-frame>
                                <div class="story-video-stage">
                                    <video controls preload="metadata" playsinline data-video-poster src="../<?=e($videos[0]['file_path'])?>"></video>
                                </div>
                            </figure>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <article class="story-main article-shell">
                <div class="story-content-column">
                    <div class="article-prose">
                        <?php if (trim((string)$post['content'])) : ?>
                            <?=nl2br(e($post['content']))?>
                        <?php endif; ?>
                    </div>
                    <?php if ($photosOnlyGallery) : ?>
                        <section class="story-photo-stream story-photo-stream--breakout" data-article-gallery style="--story-gallery-ratio: <?=e($galleryAspectRatio)?>">
                            <?php foreach ($galleryImages as $i => $img) : ?>
                                <div class="story-photo-item story-photo-item--preview">
                                    <img loading="lazy" src="../<?=e($img['file_path'])?>" alt="Publication image <?=($i+1)?>">
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php elseif ($videosOnlyGallery) : ?>
                        <section class="story-video-stream story-photo-stream--breakout">
                            <?php foreach ($galleryVideos as $video) : ?>
                                <figure class="story-video-card" data-video-frame>
                                    <div class="story-video-stage">
                                        <video controls preload="metadata" playsinline data-video-poster src="../<?=e($video['file_path'])?>"></video>
                                    </div>
                                </figure>
                            <?php endforeach; ?>
                        </section>
                    <?php elseif ($mixedMediaGallery) : ?>
                        <?php if ($galleryImages) : ?>
                            <section class="story-photo-stream story-photo-stream--breakout" data-article-gallery style="--story-gallery-ratio: <?=e($galleryAspectRatio)?>">
                                <?php foreach ($galleryImages as $i => $img) : ?>
                                    <div class="story-photo-item story-photo-item--preview">
                                        <img loading="lazy" src="../<?=e($img['file_path'])?>" alt="Publication image <?=($i+1)?>">
                                    </div>
                                <?php endforeach; ?>
                            </section>
                        <?php endif; ?>
                        <?php if ($galleryVideos) : ?>
                            <section class="story-video-stream story-photo-stream--breakout">
                                <?php foreach ($galleryVideos as $video) : ?>
                                    <figure class="story-video-card" data-video-frame>
                                        <div class="story-video-stage">
                                            <video controls preload="metadata" playsinline data-video-poster src="../<?=e($video['file_path'])?>"></video>
                                        </div>
                                    </figure>
                                <?php endforeach; ?>
                            </section>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </article>
        </main>
        <script src="../public/assets/js/video-preview.js"></script>
    </body>
</html>
