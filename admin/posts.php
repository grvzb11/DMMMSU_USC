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
require_once '../config/database.php';
require_once '_layout.php';
require_once '../config/helpers.php';
if (!can_manage_content()) deny_access('Your role cannot manage publications.');
$scope = admin_scope_code();
$deleteAllowed = can_delete_admin_records();
ensure_post_images_table($pdo);
ensure_post_videos_table($pdo);

/**
 * Split the unified publication media input into the image/video upload-array
 * shapes expected by the existing validated upload helpers.
 */
function split_publication_media_uploads(array $files): array {
    $empty = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
    $result = ['images' => $empty, 'videos' => $empty, 'errors' => []];
    if (empty($files['name']) || !is_array($files['name'])) return $result;

    $imageExt = ['jpg', 'jpeg', 'png', 'webp'];
    $videoExt = ['mp4', 'webm', 'mov'];
    foreach ($files['name'] as $i => $name) {
        $name = (string)$name;
        $type = strtolower((string)($files['type'][$i] ?? ''));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $bucket = null;
        if (str_starts_with($type, 'image/') || in_array($ext, $imageExt, true)) $bucket = 'images';
        elseif (str_starts_with($type, 'video/') || in_array($ext, $videoExt, true)) $bucket = 'videos';

        if ($bucket === null) {
            if ((int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $result['errors'][] = basename($name).' is not a supported photo or video.';
            }
            continue;
        }
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
            $result[$bucket][$key][] = $files[$key][$i] ?? ($key === 'error' ? UPLOAD_ERR_NO_FILE : '');
        }
    }
    return $result;
}

/**
 * Build one sortable sequence for publication photos and videos.
 *
 * Older records stored photo and video sort_order values independently, so
 * duplicate order values mean "legacy order": photos first, videos last.
 * Once a mixed order is saved, every media item receives a unique global
 * sort_order and can be placed anywhere in the sequence.
 */
function publication_media_sequence(array $images, array $videos): array {
    $media = [];
    foreach ($images as $row) {
        $row['_media_type'] = 'image';
        $media[] = $row;
    }
    foreach ($videos as $row) {
        $row['_media_type'] = 'video';
        $media[] = $row;
    }
    if (count($media) < 2) return $media;

    $orders = array_map(static fn($row) => (int)($row['sort_order'] ?? 0), $media);
    $hasGlobalOrder = count(array_unique($orders, SORT_REGULAR)) === count($orders);
    if (!$hasGlobalOrder) return $media; // preserve legacy photos-then-videos order

    usort($media, static function ($a, $b): int {
        $cmp = ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0));
        if ($cmp !== 0) return $cmp;
        $typeCmp = strcmp((string)($a['_media_type'] ?? ''), (string)($b['_media_type'] ?? ''));
        return $typeCmp !== 0 ? $typeCmp : ((int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0));
    });
    return $media;
}

function publication_save_media_sequence(PDO $pdo, int $postId, array $sequence): void {
    $updateImage = $pdo->prepare('UPDATE post_images SET sort_order=? WHERE id=? AND post_id=?');
    $updateVideo = $pdo->prepare('UPDATE post_videos SET sort_order=? WHERE id=? AND post_id=?');
    foreach (array_values($sequence) as $order => $item) {
        $type = (string)($item['_media_type'] ?? $item['type'] ?? '');
        $id = (int)($item['id'] ?? 0);
        if ($id <= 0) continue;
        if ($type === 'image') $updateImage->execute([$order, $id, $postId]);
        elseif ($type === 'video') $updateVideo->execute([$order, $id, $postId]);
    }
}

$flashError = '';
$publishingAdvanced = db_column_exists($pdo, 'posts', 'slug');
$universityFeatureAvailable = db_column_exists($pdo, 'posts', 'is_university_featured');
$canManageUniversityFeature = is_usc_role() || is_system_admin();

$publicationPostMaxBytes = app_ini_size_bytes((string)ini_get('post_max_size'));
$publicationUploadMaxBytes = app_ini_size_bytes((string)ini_get('upload_max_filesize'));
$publicationServerFileMaxBytes = $publicationUploadMaxBytes > 0 ? $publicationUploadMaxBytes : 0;
$publicationServerPostMaxBytes = $publicationPostMaxBytes > 0 ? $publicationPostMaxBytes : 0;
$publicationServerFileMaxMb = $publicationServerFileMaxBytes > 0 ? max(1, (int)floor($publicationServerFileMaxBytes/(1024*1024))) : 0;
$publicationServerPostMaxMb = $publicationServerPostMaxBytes > 0 ? max(1, (int)floor($publicationServerPostMaxBytes/(1024*1024))) : 0;

if ($publishingAdvanced && system_setting($pdo, 'auto_publish_scheduled', '1') === '1') publish_scheduled_posts($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (request_exceeds_php_post_limit()) {
        $limit = trim((string)ini_get('post_max_size'));
        app_render_error_page(
            413,
            'Upload too large',
            'The selected publication media is larger than the server can receive in one request'.($limit !== ''?' (current POST limit: '.$limit.')':'.').' Reduce the files or restart the USC LAN server using scripts/windows/START_USC_LAN_SERVER.bat, which enables large News & Updates video uploads.'
        );
    }
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'save');
    if ($action === 'set_university_feature') {
        if (!$canManageUniversityFeature) deny_access('Only the University Student Council or System Administrator can choose publications for the university-wide All Featured section.');
        if (!$universityFeatureAvailable) deny_access('Run the latest database migration before using university-wide Featured placement.');
        $postId = (int)($_POST['id'] ?? 0);
        $enable = (int)($_POST['enabled'] ?? 0) === 1;
        $st = $pdo->prepare('SELECT id,title,category,status FROM posts WHERE id=? AND deleted_at IS NULL LIMIT 1');
        $st->execute([$postId]);
        $target = $st->fetch();
        if (!$target) not_found('The requested publication was not found.');
        if ($enable && (string)$target['status'] !== 'published') deny_access('Only published stories can be featured in the All view.');
        if ($enable) {
            // The university-wide All Featured slot is independent from every
            // portal's local is_featured flag and intentionally has one active
            // selection at a time.
            $pdo->beginTransaction();
            try {
                $pdo->exec('UPDATE posts SET is_university_featured=0 WHERE is_university_featured=1');
                $pdo->prepare('UPDATE posts SET is_university_featured=1 WHERE id=?')->execute([$postId]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        } else {
            $pdo->prepare('UPDATE posts SET is_university_featured=0 WHERE id=?')->execute([$postId]);
        }
        admin_log($pdo, 'news', $enable?'Featured publication in All':'Removed publication from All Featured', (string)$target['title'].' · '.strtoupper((string)$target['category']), 'post', $postId);
        header('Location: posts.php?'.http_build_query(array_filter([
            'campus' => $_POST['return_campus'] ?? null,
            'status' => $_POST['return_status'] ?? null,
            'q' => $_POST['return_q'] ?? null,
            $enable?'all_featured':'all_unfeatured' => 1,
        ], static fn($v) => $v !== null && $v !== '')));
        exit;
    }
    if ($action === 'reorder_media') {
        $postId = (int)($_POST['id'] ?? 0);
        $check = $pdo->prepare('SELECT id,category,title FROM posts WHERE id=? AND deleted_at IS NULL');
        $check->execute([$postId]);
        $target = $check->fetch();
        if (!$target) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Publication not found.']);
            exit;
        }
        require_admin_code(strtoupper((string)$target['category']));

        $decoded = json_decode((string)($_POST['media_order'] ?? '[]'), true);
        $submitted = [];
        $seen = [];
        foreach (is_array($decoded) ? $decoded : [] as $token) {
            if (!is_string($token) || !preg_match('/^(image|video):(\d+)$/', $token, $m)) continue;
            $type = $m[1];
            $id = (int)$m[2];
            $key = $type.':'.$id;
            if ($id <= 0 || isset($seen[$key])) continue;
            $seen[$key] = true;
            $submitted[] = ['_media_type' => $type, 'id' => $id];
        }

        $currentImageStmt = $pdo->prepare('SELECT id FROM post_images WHERE post_id=?');
        $currentImageStmt->execute([$postId]);
        $currentImages = array_map('intval', $currentImageStmt->fetchAll(PDO::FETCH_COLUMN));
        $currentVideoStmt = $pdo->prepare('SELECT id FROM post_videos WHERE post_id=?');
        $currentVideoStmt->execute([$postId]);
        $currentVideos = array_map('intval', $currentVideoStmt->fetchAll(PDO::FETCH_COLUMN));
        $expected = array_merge(
            array_map(static fn($id) => 'image:'.$id, $currentImages),
            array_map(static fn($id) => 'video:'.$id, $currentVideos)
        );
        $received = array_map(static fn($item) => $item['_media_type'].':'.$item['id'], $submitted);
        sort($expected);
        $receivedCheck = $received;
        sort($receivedCheck);
        if ($expected !== $receivedCheck || count($received) !== count($expected)) {
            http_response_code(409);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Media changed while reordering. Refresh the editor and try again.']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            publication_save_media_sequence($pdo, $postId, $submitted);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        admin_log($pdo, 'news', 'Reordered publication media', (string)$target['title'], 'post', $postId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'bulk_remove_media') {
        $postId = (int)($_POST['id'] ?? 0);
        $check = $pdo->prepare('SELECT id,category FROM posts WHERE id=? AND deleted_at IS NULL');
        $check->execute([$postId]);
        $target = $check->fetch();
        if (!$target) not_found('The requested publication was not found.');
        require_admin_code(strtoupper((string)$target['category']));

        $bulkAction = (string)($_POST['bulk_media_action'] ?? 'remove_selected');
        $imageIds = [];
        $videoIds = [];
        if ($bulkAction === 'remove_all_photos') {
            $st = $pdo->prepare('SELECT id FROM post_images WHERE post_id=? ORDER BY sort_order ASC,id ASC');
            $st->execute([$postId]);
            $imageIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } else {
            $imageIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['selected_image_ids'] ?? [])), static fn($id) => $id > 0)));
            $videoIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['selected_video_ids'] ?? [])), static fn($id) => $id > 0)));
        }

        foreach ($imageIds as $imageId) delete_post_image($pdo, $imageId, $postId);
        foreach ($videoIds as $videoId) delete_post_video($pdo, $videoId, $postId);
        $removedCount = count($imageIds) + count($videoIds);
        header('Location: posts.php?edit='.$postId.'&media_removed='.$removedCount);
        exit;
    }
    if (isset($_POST['set_feature_media']) || isset($_POST['set_cover_id']) || isset($_POST['delete_image_id']) || isset($_POST['delete_video_id'])) {
        $postId = (int)($_POST['id'] ?? 0);
        $check = $pdo->prepare('SELECT id,category FROM posts WHERE id=? AND deleted_at IS NULL');
        $check->execute([$postId]);
        $target = $check->fetch();
        if (!$target) not_found('The requested publication was not found.');
        require_admin_code(strtoupper((string)$target['category']));
        if (isset($_POST['set_feature_media'])) {
            $token = (string)$_POST['set_feature_media'];
            if (!preg_match('/^(image|video):(\d+)$/', $token, $m)) {
                header('Location: posts.php?edit='.$postId.'&media_feature_error=1');
                exit;
            }
            $featureType = $m[1];
            $featureId = (int)$m[2];
            $sequence = publication_media_sequence(post_images($pdo, $postId), post_videos($pdo, $postId));
            $selected = null;
            $remaining = [];
            foreach ($sequence as $item) {
                if (($item['_media_type'] ?? '') === $featureType && (int)($item['id'] ?? 0) === $featureId) $selected = $item;
                else $remaining[] = $item;
            }
            if ($selected !== null) {
                array_unshift($remaining, $selected);
                $pdo->beginTransaction();
                try {
                    publication_save_media_sequence($pdo, $postId, $remaining);
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $e;
                }
                admin_log($pdo, 'news', 'Changed featured publication media', (string)$target['category'].' '.$featureType.' #'.$featureId, 'post', $postId);
            }
            header('Location: posts.php?edit='.$postId.'&media_featured=1');
            exit;
        }
        if (isset($_POST['set_cover_id'])) {
            $imageId = (int)$_POST['set_cover_id'];
            $sequence = publication_media_sequence(post_images($pdo, $postId), post_videos($pdo, $postId));
            $selected = null;
            $remaining = [];
            foreach ($sequence as $item) {
                if (($item['_media_type'] ?? '') === 'image' && (int)($item['id'] ?? 0) === $imageId) $selected = $item;
                else $remaining[] = $item;
            }
            if ($selected !== null) {
                $insertAt = count($remaining);
                foreach ($remaining as $i => $item) {
                    if (($item['_media_type'] ?? '') === 'image') { $insertAt = $i; break; }
                }
                array_splice($remaining, $insertAt, 0, [$selected]);
                $pdo->beginTransaction();
                try {
                    publication_save_media_sequence($pdo, $postId, $remaining);
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $e;
                }
            }
            header('Location: posts.php?edit='.$postId.'&cover_set=1');
            exit;
        }
        if (isset($_POST['delete_video_id'])) {
            $videoId = (int)$_POST['delete_video_id'];
            delete_post_video($pdo, $videoId, $postId);
            header('Location: posts.php?edit='.$postId.'&video_deleted=1');
            exit;
        }
        $imageId = (int)$_POST['delete_image_id'];
        delete_post_image($pdo, $imageId, $postId);
        header('Location: posts.php?edit='.$postId.'&image_deleted=1');
        exit;
    }
    if ($action === 'delete') {
        if (!$deleteAllowed) deny_access('Your role cannot move publications to Trash.');
        $postId = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT title,category FROM posts WHERE id=?');
        $st->execute([$postId]);
        $row = $st->fetch();
        if ($row) {
            require_admin_code(strtoupper($row['category']));
            $pdo->prepare('UPDATE posts SET deleted_at=NOW(),deleted_by=? WHERE id=?')->execute([$_SESSION['admin_id'] ?? null, $postId]);
            admin_log($pdo, 'news', 'Moved publication to trash', $row['title'], 'post', $postId);
        }
        header('Location: posts.php?deleted=1');
        exit;
    }
    if ($action === 'restore_revision' && $publishingAdvanced) {
        $postId = (int)($_POST['id'] ?? 0);
        $revisionId = (int)($_POST['revision_id'] ?? 0);
        $st = $pdo->prepare('SELECT p.category,p.title,r.snapshot_json,r.revision_no FROM post_revisions r INNER JOIN posts p ON p.id=r.post_id WHERE r.id=? AND r.post_id=? LIMIT 1');
        $st->execute([$revisionId, $postId]);
        $r = $st->fetch();
        if (!$r) not_found('Revision not found.');
        require_admin_code(strtoupper((string)$r['category']));
        $snap = json_decode((string)$r['snapshot_json'], true);
        $old = $snap['post'] ?? null;
        if (!is_array($old)) not_found('Revision data is unavailable.');
        post_save_revision($pdo, $postId, (int)($_SESSION['admin_id'] ?? 0), 'Automatic snapshot before restoring revision '.$r['revision_no']);
        $slug = post_unique_slug($pdo, (string)($old['title'] ?? $r['title']), $postId);
        $restoredCategory = (string)($old['category'] ?? 'usc');
        $restoredFeatured = (int)($old['is_featured'] ?? 0);
        $pdo->prepare('UPDATE posts SET title=?,slug=?,excerpt=?,content=?,category=?,label=?,is_featured=?,status=?,published_at=?,scheduled_at=?,review_note=?,published_by=?,scheduled_by=? WHERE id=?')->execute([(string)($old['title'] ?? ''), $slug, (string)($old['excerpt'] ?? ''), (string)($old['content'] ?? ''), $restoredCategory, (string)($old['label'] ?? 'USC'), $restoredFeatured, (string)($old['status'] ?? 'draft'), $old['published_at'] ?? null, $old['scheduled_at'] ?? null, $old['review_note'] ?? null, $old['published_by'] ?? null, $old['scheduled_by'] ?? null, $postId]);
        if ($restoredFeatured === 1) {
            $pdo->prepare('UPDATE posts SET is_featured=0 WHERE category=? AND id<>? AND is_featured=1')->execute([$restoredCategory, $postId]);
        }
        $restoredImages = post_restore_revision_images($pdo, $postId, is_array($snap['images'] ?? null)?$snap['images']:[]);
        $restoredVideos = post_restore_revision_videos($pdo, $postId, is_array($snap['videos'] ?? null)?$snap['videos']:[]);
        post_save_revision($pdo, $postId, (int)($_SESSION['admin_id'] ?? 0), 'Restored from revision '.$r['revision_no']);
        admin_log($pdo, 'news', 'Restored publication revision', $r['title'].' · revision '.$r['revision_no'].' · '.$restoredImages.' image'.($restoredImages === 1?'':'s').' · '.$restoredVideos.' video'.($restoredVideos === 1?'':'s').' available', 'post', $postId);
        header('Location: posts.php?edit='.$postId.'&revision_restored=1');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $existingPost = null;
    if ($id) {
        $check = $pdo->prepare('SELECT * FROM posts WHERE id=? AND deleted_at IS NULL');
        $check->execute([$id]);
        $existingPost = $check->fetch();
        if (!$existingPost) not_found('The requested publication was not found.');
        require_admin_code(strtoupper((string)$existingPost['category']));
    }
    $category = in_array($_POST['category'] ?? '', ['usc', 'nluc', 'mluc', 'sluc', 'ous'], true)?$_POST['category']:'usc';
    if ($scope) $category = strtolower($scope);
    $requestedStatus = (string)($_POST['status'] ?? 'draft');
    $allowedStatuses = can_publish_content()?['draft', 'review', 'published', 'scheduled', 'archived']:['draft', 'review'];
    if (!in_array($requestedStatus, $allowedStatuses, true)) $requestedStatus = 'draft';
    if (!can_publish_content() && $existingPost && in_array((string)$existingPost['status'], ['published', 'archived'], true)) $requestedStatus = 'review';
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
        $flashError = 'A publication title is required.';
    }
    else {
        $publishedAt = trim((string)($_POST['published_at'] ?? ''));
        $publishedAt = $publishedAt !== ''?date('Y-m-d H:i:s', strtotime($publishedAt)?:time()):date('Y-m-d H:i:s');
        $scheduledAt = null;
        $status = $requestedStatus;
        $publishedBy = $existingPost['published_by'] ?? null;
        $scheduledBy = null;
        if ($publishingAdvanced && $requestedStatus === 'scheduled') {
            $scheduleInput = trim((string)($_POST['scheduled_at'] ?? ''));
            $ts = $scheduleInput !== ''?strtotime($scheduleInput):false;
            if (!$ts || $ts <= time()) {
                $flashError = 'Choose a future date and time for scheduled publishing.';
            } else {
                $scheduledAt = date('Y-m-d H:i:s', $ts);
                $status = 'draft';
                $scheduledBy = (int)($_SESSION['admin_id'] ?? 0);
            }
        }
        elseif ($requestedStatus === 'published') {
            $publishedBy = (int)($_SESSION['admin_id'] ?? 0);
            $scheduledAt = null;
        }
        if ($flashError === '') {
            $slug = $publishingAdvanced?post_unique_slug($pdo, $title, $id?:null):null;
            $excerpt = trim((string)($_POST['excerpt'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            $labelMap = ['usc' => 'USC', 'nluc' => 'NLUC', 'mluc' => 'MLUC', 'sluc' => 'SLUC', 'ous' => 'OUS'];
            $label = $labelMap[$category] ?? strtoupper($category);
            $featured = isset($_POST['is_featured'])?1:0;
            $reviewNote = trim((string)($_POST['review_note'] ?? ''));
            if ($id && $publishingAdvanced) post_save_revision($pdo, $id, (int)($_SESSION['admin_id'] ?? 0), 'Snapshot before edit');
            if ($publishingAdvanced) {
                if ($id) {
                    $st = $pdo->prepare('UPDATE posts SET title=?,slug=?,excerpt=?,content=?,category=?,label=?,is_featured=?,status=?,published_at=?,scheduled_at=?,review_note=?,published_by=?,scheduled_by=? WHERE id=?');
                    $st->execute([$title, $slug, $excerpt, $content, $category, $label, $featured, $status, $publishedAt, $scheduledAt, $reviewNote, $publishedBy, $scheduledBy, $id]);
                }
                else {
                    $st = $pdo->prepare('INSERT INTO posts(title,slug,excerpt,content,category,label,is_featured,status,published_at,scheduled_at,review_note,published_by,scheduled_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $st->execute([$title, $slug, $excerpt, $content, $category, $label, $featured, $status, $publishedAt, $scheduledAt, $reviewNote, $publishedBy, $scheduledBy]);
                    $id = (int)$pdo->lastInsertId();
                }
            } else {
                $data = [$title, $excerpt, $content, $category, $label, $featured, $status, $publishedAt];
                if ($id) {
                    $st = $pdo->prepare('UPDATE posts SET title=?,excerpt=?,content=?,category=?,label=?,is_featured=?,status=?,published_at=? WHERE id=?');
                    $st->execute([...$data, $id]);
                } else {
                    $st = $pdo->prepare('INSERT INTO posts(title,excerpt,content,category,label,is_featured,status,published_at) VALUES (?,?,?,?,?,?,?,?)');
                    $st->execute($data);
                    $id = (int)$pdo->lastInsertId();
                }
            }
            // Local Featured is scoped to the selected portal only. Keep one
            // explicit local Featured selection per portal without touching
            // the separate university-wide is_university_featured flag.
            if ($featured === 1) {
                $pdo->prepare('UPDATE posts SET is_featured=0 WHERE category=? AND id<>? AND is_featured=1')->execute([$category, $id]);
            }
            if (db_column_exists($pdo, 'posts', 'academic_year_id')) {
                $ay = active_academic_year_id($pdo);
                if ($ay) $pdo->prepare('UPDATE posts SET academic_year_id=COALESCE(academic_year_id,?) WHERE id=?')->execute([$ay, $id]);
            }
            $portalCode = strtoupper($category);
            $mediaUploads = split_publication_media_uploads($_FILES['media_uploads'] ?? []);
            // Backward-compatible fallback for an older cached editor form.
            $imageUploads = !empty($mediaUploads['images']['name']) ? $mediaUploads['images'] : ($_FILES['images'] ?? []);
            $videoUploads = !empty($mediaUploads['videos']['name']) ? $mediaUploads['videos'] : ($_FILES['videos'] ?? []);
            $hasComputerMedia = !empty($imageUploads['name'][0]) || !empty($videoUploads['name'][0]);
            if ($hasComputerMedia) {
                $rl = max(5, (int)(system_setting($pdo, 'security_rate_limit_uploads_per_hour', '40') ?? 40));
                platform_rate_limit_or_429($pdo, 'admin-upload', ((string)($_SESSION['admin_id'] ?? 0)).'|'.admin_current_ip(), $rl, 3600, 900);
            }
            // Keep computer selections in their visible order within each media type.
            // Existing gallery images remain first; for a brand-new publication the
            // first selected photo becomes the cover. Library selections append after.
            $uploadErrors = upload_post_images($pdo, $id, $imageUploads, 100);
            $libraryErrors = attach_media_to_post($pdo, $id, (array)($_POST['media_image_ids'] ?? []), 100, $portalCode);
            $videoUploadErrors = upload_post_videos($pdo, $id, $videoUploads, 250);
            $videoLibraryErrors = attach_media_videos_to_post($pdo, $id, (array)($_POST['media_video_ids'] ?? []), $portalCode);
            $allErrors = array_merge($mediaUploads['errors'], $uploadErrors, $libraryErrors, $videoUploadErrors, $videoLibraryErrors);
            if ($allErrors) {
                $flashError = implode(' ', array_map('e', $allErrors));
            }
            else {
                if ($publishingAdvanced) post_save_revision($pdo, $id, (int)($_SESSION['admin_id'] ?? 0), $requestedStatus === 'scheduled'?'Scheduled publication':'Saved publication');
                $displayStatus = $requestedStatus === 'scheduled'?'scheduled for '.date('M j, Y · g:i A', strtotime((string)$scheduledAt)):$status;
                admin_log($pdo, 'news', 'Saved publication', $title.' · '.$displayStatus, 'post', $id, $existingPost?['title' => $existingPost['title'], 'status' => $existingPost['status'], 'portal' => strtoupper($existingPost['category'])]:null, ['title' => $title, 'status' => $requestedStatus, 'portal' => $portalCode, 'scheduled_at' => $scheduledAt]);
                if ($requestedStatus === 'review') {
                    $targetRole = $portalCode === 'USC'?'usc':'sas_director';
                    admin_notify($pdo, 'Publication ready for review', $title.' is waiting for publishing review.', 'posts.php?edit='.$id, 'info', $targetRole, $portalCode, null, 'content');
                }
                header('Location: posts.php?saved=1');
                exit;
            }
        }
    }
}

$edit = null;
$editImages = [];
$editVideos = [];
$editMedia = [];
$revisions = [];
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM posts WHERE id=? AND deleted_at IS NULL');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
    if ($edit) require_admin_code(strtoupper($edit['category']));
    if ($edit) {
        $editImages = post_images($pdo, (int)$edit['id']);
        $editVideos = post_videos($pdo, (int)$edit['id']);
        $editMedia = publication_media_sequence($editImages, $editVideos);
        if ($publishingAdvanced && db_table_exists($pdo, 'post_revisions')) {
            $rs = $pdo->prepare('SELECT r.*,a.full_name created_by_name FROM post_revisions r LEFT JOIN admins a ON a.id=r.created_by WHERE r.post_id=? ORDER BY r.revision_no DESC LIMIT 8');
            $rs->execute([(int)$edit['id']]);
            $revisions = $rs->fetchAll();
        }
    }
}
$usedPostMediaIds = array_map('intval', array_filter(array_column($editImages, 'media_id')));
$usedPostVideoMediaIds = array_map('intval', array_filter(array_column($editVideos, 'media_id')));
$editStatus = ($publishingAdvanced && !empty($edit['scheduled_at']) && ($edit['status'] ?? 'draft') === 'draft')?'scheduled':(string)($edit['status'] ?? 'draft');
if (!can_publish_content() && in_array($editStatus, ['published', 'archived'], true)) $editStatus = 'review';
$showForm = isset($_GET['action']) || $edit || $flashError !== '';
$libraryImages = $showForm?media_assets($pdo, $scope, 'image'):[];
$libraryVideos = $showForm?media_assets($pdo, $scope, 'video'):[];
$q = trim($_GET['q'] ?? '');
$campus = $_GET['campus'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$where = ['p.deleted_at IS NULL'];
$params = [];
if ($q !== '') {
    $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.label LIKE ?)';
    $like = '%'.$q.'%';
    array_push($params, $like, $like, $like);
}
if ($scope) {
    $where[] = 'p.category=?';
    $params[] = strtolower($scope);
} elseif (in_array($campus, ['usc', 'nluc', 'mluc', 'sluc', 'ous'], true)) {
    $where[] = 'p.category=?';
    $params[] = $campus;
}
if ($statusFilter === 'scheduled' && $publishingAdvanced) {
    $where[] = "p.status='draft' AND p.scheduled_at IS NOT NULL AND p.scheduled_at>NOW()";
}
elseif (in_array($statusFilter, ['draft', 'review', 'published', 'archived'], true)) {
    $where[] = 'p.status=?';
    $params[] = $statusFilter;
    if ($statusFilter === 'draft' && $publishingAdvanced) $where[] = 'p.scheduled_at IS NULL';
}
$from = ' FROM posts p'.($where?' WHERE '.implode(' AND ', $where):'');
$st = $pdo->prepare('SELECT COUNT(*)'.$from);
$st->execute($params);
$publicationTotal = (int)$st->fetchColumn();
$pg = pagination_state($publicationTotal, 25);
$page = $pg['page'];
$perPage = $pg['per_page'];
$sql = 'SELECT p.*,(SELECT COUNT(*) FROM post_images pi WHERE pi.post_id=p.id) AS image_count'.$from.' ORDER BY p.published_at DESC,p.id DESC LIMIT '.$perPage.' OFFSET '.$pg['offset'];
$st = $pdo->prepare($sql);
$st->execute($params);
$posts = $st->fetchAll();
$sumSql = "SELECT COUNT(*) total,SUM(p.status='published') published,SUM(p.status='draft'".($publishingAdvanced?" AND p.scheduled_at IS NULL":"").") drafts,SUM(p.status='review') review,".($publishingAdvanced?"SUM(p.status='draft' AND p.scheduled_at IS NOT NULL AND p.scheduled_at>NOW()) scheduled,SUM(p.status='archived') archived,":"0 scheduled,0 archived,")."SUM(p.is_featured=1) featured,SUM((SELECT COUNT(*) FROM post_images pi WHERE pi.post_id=p.id)) photos".$from;
$st = $pdo->prepare($sumSql);
$st->execute($params);
$row = $st->fetch()?:[];
$publicationSummary = ['total' => (int)($row['total'] ?? 0), 'published' => (int)($row['published'] ?? 0), 'drafts' => (int)($row['drafts'] ?? 0), 'review' => (int)($row['review'] ?? 0), 'scheduled' => (int)($row['scheduled'] ?? 0), 'archived' => (int)($row['archived'] ?? 0), 'featured' => (int)($row['featured'] ?? 0), 'photos' => (int)($row['photos'] ?? 0)];

admin_header('Content Management', 'USC digital content');
admin_content_tabs('posts.php');
if (isset($_GET['saved'])) echo '<div class="notice">Publication saved. Changes are reflected on the public website automatically.</div>';
if (isset($_GET['image_deleted'])) echo '<div class="notice">Image removed from this publication.</div>';
if (isset($_GET['media_removed'])) {
    $removedMediaCount = max(0, (int)$_GET['media_removed']);
    echo '<div class="notice">'.($removedMediaCount === 1 ? '1 media item removed from this publication.' : e((string)$removedMediaCount).' media items removed from this publication.').'</div>';
}
if (isset($_GET['cover_set'])) echo '<div class="notice">Cover photo updated.</div>';
if (isset($_GET['media_featured'])) echo '<div class="notice">Featured media updated.</div>';
if (isset($_GET['media_feature_error'])) echo '<div class="notice error">Could not feature that media item.</div>';
if (isset($_GET['deleted'])) echo '<div class="notice">Publication moved to Trash. It can be restored later.</div>';
if (isset($_GET['all_featured'])) echo '<div class="notice">Publication added to the university-wide All Featured section.</div>';
if (isset($_GET['all_unfeatured'])) echo '<div class="notice">Publication removed from the university-wide All Featured section.</div>';
if ($flashError) echo '<div class="notice notice--error">'.$flashError.'</div>';
if ($showForm) :
?>
<div class="page-intro publication-editor-pagehead">
    <div>
        <span class="page-kicker">NEWS &amp; UPDATES</span>
        <h2><?=$edit?'Edit publication':'Create publication'?></h2>
        <p>Write the update, manage its media, and choose how it should be published.</p>
    </div>
    <div class="publication-editor-pagehead__actions">
        <a class="btn btn--soft" href="posts.php">Back</a>
        <?php if ($edit && $publishingAdvanced) : ?>
            <a class="btn btn--soft" href="post-preview.php?id=<?=(int)$edit['id']?>" target="_blank" rel="noopener">Preview ↗</a>
        <?php endif; ?>
        <button class="btn publication-top-save" type="submit" form="publicationEditorForm">Save publication</button>
    </div>
</div>
<form id="publicationEditorForm" method="post" enctype="multipart/form-data" class="publication-editor-clean" data-unsaved-warning="1">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="id" value="<?=e((string)($edit['id']??''))?>">
    <div class="publication-editor-clean__grid">
        <main class="publication-editor-clean__main">
            <section class="publication-card publication-card--details">
                <div class="publication-card__head">
                    <div>
                        <span>01</span>
                        <div>
                            <h3>Publication details</h3>
                            <p>Basic information for the update.</p>
                        </div>
                    </div>
                    <?php if ($edit) : ?>
                        <span class="publication-state-chip">Editing</span>
                    <?php endif; ?>
                </div>
                <div class="publication-card__body">
                    <label class="publication-title-field">
                        Title
                        <input name="title" required value="<?=e($edit['title']??($_POST['title']??''))?>" placeholder="Enter publication title">
                    </label>
                    <div class="publication-detail-grid">
                        <label>
                            Campus / source
                            <?php
                            $publicationPortalLabels = [
                                'usc' => 'University Student Council',
                                'nluc' => 'North La Union Campus',
                                'mluc' => 'Mid La Union Campus',
                                'sluc' => 'South La Union Campus',
                                'ous' => 'Open University System',
                            ];
                            ?>
                            <?php if ($scope) : ?>
                                <input value="<?=e(portal_display_name($scope))?>" disabled>
                                <input type="hidden" name="category" value="<?=e(strtolower($scope))?>">
                            <?php else: ?>
                                <select name="category" id="publicationPortal">
                                    <?php foreach ($publicationPortalLabels as $c => $name) : ?>
                                        <option value="<?=$c?>" <?=($edit['category']??($_POST['category']??'usc'))===$c?'selected':''?>><?=e($name)?></option>
                                    <?php endforeach ?>
                                </select>
                            <?php endif ?>
                        </label>
                        <label>
                            Publish date
                            <input type="datetime-local" name="published_at" value="<?=!empty($edit['published_at'])?date('Y-m-d\TH:i',strtotime($edit['published_at'])):date('Y-m-d\TH:i')?>">
                        </label>
                    </div>
                </div>
            </section>
            <section class="publication-card publication-card--story">
                <div class="publication-card__head">
                    <div>
                        <span>02</span>
                        <div>
                            <h3>Story content</h3>
                            <p>Write the short preview and the complete update.</p>
                        </div>
                    </div>
                </div>
                <div class="publication-card__body">
                    <label>
                        Summary / excerpt
                        <textarea name="excerpt" class="publication-summary-clean" placeholder="Short description shown in cards and previews"><?=e($edit['excerpt']??($_POST['excerpt']??''))?></textarea>
                    </label>
                    <label>
                        Full update
                        <textarea name="content" class="publication-content-clean" placeholder="Write the complete publication here"><?=e($edit['content']??($_POST['content']??''))?></textarea>
                    </label>
                </div>
            </section>
        </main>
        <aside class="publication-editor-clean__side">
            <section class="publication-card publication-card--publishing publication-card--sticky">
                <div class="publication-card__head">
                    <div>
                        <span>03</span>
                        <div>
                            <h3>Publishing</h3>
                            <p>Choose what happens after saving.</p>
                        </div>
                    </div>
                </div>
                <div class="publication-card__body publication-publishing-clean">
                    <label>
                        Status
                        <select name="status" id="publicationStatus">
                            <option value="draft" <?=$editStatus==='draft'?'selected':''?>>Draft</option>
                            <option value="review" <?=$editStatus==='review'?'selected':''?>>For review</option>
                            <?php if (can_publish_content()) : ?>
                                <option value="published" <?=$editStatus==='published'?'selected':''?>>Publish now</option>
                                <?php if ($publishingAdvanced) : ?>
                                    <option value="scheduled" <?=$editStatus==='scheduled'?'selected':''?>>Schedule</option>
                                    <option value="archived" <?=$editStatus==='archived'?'selected':''?>>Archive</option>
                                <?php endif; ?>
                            <?php endif; ?>
                        </select>
                    </label>
                    <?php if ($publishingAdvanced && can_publish_content()) : ?>
                        <label id="scheduleField" <?=$editStatus==='scheduled'?'':'hidden'?>>
                            Scheduled date &amp; time
                            <input type="datetime-local" name="scheduled_at" id="scheduledAt" value="<?=!empty($edit['scheduled_at'])?date('Y-m-d\TH:i',strtotime($edit['scheduled_at'])):''?>">
                        </label>
                    <?php endif; ?>
                    <label class="publication-feature-toggle">
                        <input type="checkbox" name="is_featured" <?=!empty($edit['is_featured'])?'checked':''?>>
                        <span><strong>Featured</strong></span>
                    </label>
                    <?php if ($publishingAdvanced) : ?>
                        <label id="reviewNoteField" <?=$editStatus==='review'?'':'hidden'?>>
                            Editorial / review note
                            <textarea name="review_note" id="reviewNote" rows="3" placeholder="Optional internal note for reviewers"><?=e($edit['review_note']??($_POST['review_note']??''))?></textarea>
                            <small>Internal only. Never shown publicly.</small>
                        </label>
                    <?php endif; ?>
                </div>
            </section>
            <section class="publication-card publication-card--media publication-card--unified-media">
                <div class="publication-card__head">
                    <div>
                        <span>04</span>
                        <div>
                            <h3>Media</h3>
                            <p>Photos, cover image, gallery, and videos.</p>
                        </div>
                    </div>
                    <span class="publication-media-count" id="publicationMediaCount"><?=count($editImages)?> / 100 photos · <?=count($editVideos)?> video<?=count($editVideos)===1?'':'s'?></span>
                </div>
                <div class="publication-card__body">
                    <div class="publication-current-gallery">
                        <div class="publication-current-gallery__head publication-current-gallery__head--bulk">
                            <div class="publication-current-gallery__title">
                                <strong>Gallery</strong><small>Cover photo sets all gallery images to the nearest 4:3, 1:1, or 16:9 ratio</small>
                            </div>
                            <?php if ($edit && ($editImages || $editVideos)) : ?>
                                <div class="publication-media-bulk-controls publication-media-manage-controls">
                                    <div class="publication-media-order-note">
                                        <span class="publication-media-order-icon" aria-hidden="true">↕</span>
                                        <span id="publicationMediaOrderStatus">Drag any media · first item is featured · first photo is the cover</span>
                                    </div>
                                    <button type="button" class="publication-media-select-toggle" id="publicationMediaSelectionToggle" aria-pressed="false">Select</button>
                                </div>
                                <div class="publication-media-bulk-actions" id="publicationMediaBulkActions" hidden>
                                    <div class="publication-media-selection-summary">
                                        <strong id="publicationMediaSelectedCount">0 selected</strong>
                                        <span>Tap photos or videos to select them.</span>
                                    </div>
                                    <div class="publication-media-selection-actions">
                                        <button type="button" class="publication-media-bulk-link" id="publicationMediaSelectAll">Select all</button>
                                        <button type="submit" form="publicationMediaBulkForm" name="bulk_media_action" value="remove_selected" class="publication-media-bulk-remove" id="publicationMediaRemoveSelected" data-confirm="Remove the selected media from this publication?" disabled>Remove selected</button>
                                        <?php if ($editImages) : ?>
                                            <button type="submit" form="publicationMediaBulkForm" name="bulk_media_action" value="remove_all_photos" class="publication-media-bulk-remove publication-media-bulk-remove--all" data-confirm="Remove all photos from this publication? Videos will be kept.">Remove all photos</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="publication-current-gallery__grid publication-unified-media-grid" id="publicationMediaGrid">
                            <?php $photoPosition = 0; $videoPosition = 0; $featuredMediaKey = $editMedia ? (($editMedia[0]['_media_type'] ?? '').':'.(int)($editMedia[0]['id'] ?? 0)) : ''; ?>
                            <?php foreach ($editMedia as $mediaItem) : ?>
                                <?php if (($mediaItem['_media_type'] ?? '') === 'image') : $img = $mediaItem; $i = $photoPosition++; ?>
                                    <?php $mediaKey = 'image:'.(int)$img['id']; $isFeaturedMedia = $mediaKey === $featuredMediaKey; ?>
                                    <div class="existing-media-item publication-selectable-media <?=$i===0?'is-cover':''?> <?=$isFeaturedMedia?'is-featured-media':''?>" data-media-selectable data-media-type="image" data-media-id="<?=e((string)$img['id'])?>">
                                        <button type="button" class="publication-media-drag-handle" draggable="true" data-media-drag-handle aria-label="Move <?=$i===0?'cover photo':'photo '.($i+1)?>. Drag to reorder, or use the arrow keys." title="Drag to reorder"><span aria-hidden="true">⋮⋮</span></button>
                                        <label class="publication-media-select-check" title="Select <?=$i===0?'cover photo':'photo '.($i+1)?>">
                                            <input type="checkbox" name="selected_image_ids[]" value="<?=e((string)$img['id'])?>" form="publicationMediaBulkForm" aria-label="Select <?=$i===0?'cover photo':'photo '.($i+1)?>">
                                            <span aria-hidden="true">✓</span>
                                        </label>
                                        <img src="../<?=e($img['file_path'])?>" alt="Publication image <?=($i+1)?>"><span data-media-label><?=$i===0?'Cover':'Photo '.($i+1)?></span>
                                        <button type="submit" name="set_feature_media" value="<?=e($mediaKey)?>" class="media-feature <?=$isFeaturedMedia?'is-active':''?>" formnovalidate title="<?=$isFeaturedMedia?'Featured media':'Feature this photo'?>" aria-label="<?=$isFeaturedMedia?'Featured media':'Feature this photo'?>" <?=$isFeaturedMedia?'disabled':''?>><?=$isFeaturedMedia?'★':'☆'?></button>
                                    </div>
                                <?php else : $video = $mediaItem; $i = $videoPosition++; ?>
                                    <?php $mediaKey = 'video:'.(int)$video['id']; $isFeaturedMedia = $mediaKey === $featuredMediaKey; ?>
                                    <div class="existing-video-item publication-unified-video-item publication-selectable-media <?=$isFeaturedMedia?'is-featured-media':''?>" data-video-frame data-media-selectable data-media-type="video" data-media-id="<?=e((string)$video['id'])?>">
                                        <button type="button" class="publication-media-drag-handle" draggable="true" data-media-drag-handle aria-label="Move video <?=($i+1)?>. Drag to reorder, or use the arrow keys." title="Drag to reorder"><span aria-hidden="true">⋮⋮</span></button>
                                        <label class="publication-media-select-check" title="Select video <?=($i+1)?>">
                                            <input type="checkbox" name="selected_video_ids[]" value="<?=e((string)$video['id'])?>" form="publicationMediaBulkForm" aria-label="Select video <?=($i+1)?>">
                                            <span aria-hidden="true">✓</span>
                                        </label>
                                        <video preload="metadata" muted playsinline data-video-preview src="../<?=e($video['file_path'])?>"></video>
                                        <span class="publication-video-play" aria-hidden="true">▶</span>
                                        <span class="publication-video-label" data-media-label>Video <?=($i+1)?></span>
                                        <button type="submit" name="set_feature_media" value="<?=e($mediaKey)?>" class="media-feature <?=$isFeaturedMedia?'is-active':''?>" formnovalidate title="<?=$isFeaturedMedia?'Featured media':'Feature this video'?>" aria-label="<?=$isFeaturedMedia?'Featured media':'Feature this video'?>" <?=$isFeaturedMedia?'disabled':''?>><?=$isFeaturedMedia?'★':'☆'?></button>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div id="mediaPreview" class="publication-media-preview--inline" aria-live="polite"></div>
                        </div>
                    </div>
                    <label class="publication-upload-box publication-upload-box--media" for="postMedia">
                        <span>＋</span><strong>Add media</strong>
                        <small>Photos: JPG, PNG or WebP · 8 MB each · Up to 100 &nbsp;|&nbsp; Videos: MP4, WebM or MOV · 250 MB each</small>
                        <input id="postMedia" name="media_uploads[]" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime,.jpg,.jpeg,.png,.webp,.mp4,.webm,.mov" multiple hidden>
                    </label>
                    <details class="publication-library-drawer">
                        <summary>Choose from Media Library</summary>
                        <div class="library-picker library-picker--compact" data-library-picker="publication-media">
                            <div class="library-picker__head">
                                <div>
                                    <strong>Media Library</strong><span>Reuse photos or videos already uploaded to this portal.</span>
                                </div>
                                <a href="media.php" target="_blank" rel="noopener">Manage ↗</a>
                            </div>
                            <?php if ($libraryImages || $libraryVideos) : ?>
                                <div class="library-picker__grid">
                                    <?php foreach ($libraryImages as $asset) :$alreadyUsed = in_array((int)$asset['id'], $usedPostMediaIds, true); ?>
                                        <label class="library-asset library-asset--image <?=$alreadyUsed?'is-used':''?>" data-portal="<?=e(strtoupper((string)$asset['campus']))?>">
                                            <input type="checkbox" name="media_image_ids[]" value="<?=$asset['id']?>" <?=$alreadyUsed?'disabled':''?>>
                                            <img src="../<?=e($asset['file_path'])?>" alt="<?=e($asset['alt_text']?:$asset['original_name'])?>"><span>Photo · <?=e($asset['original_name']?:'Library image')?><?=$alreadyUsed?' · Already used':''?></span>
                                        </label>
                                    <?php endforeach ?>
                                    <?php foreach ($libraryVideos as $asset) :$alreadyUsed = in_array((int)$asset['id'], $usedPostVideoMediaIds, true); ?>
                                        <label class="library-asset library-asset--video <?=$alreadyUsed?'is-used':''?>" data-portal="<?=e(strtoupper((string)$asset['campus']))?>" data-video-frame>
                                            <input type="checkbox" name="media_video_ids[]" value="<?=$asset['id']?>" <?=$alreadyUsed?'disabled':''?>>
                                            <video preload="metadata" muted playsinline data-video-preview src="../<?=e($asset['file_path'])?>"></video><span>Video · <?=e($asset['original_name']?:'Library video')?><?=$alreadyUsed?' · Already used':''?></span>
                                        </label>
                                    <?php endforeach ?>
                                </div>
                                <div class="library-picker__empty-filter" hidden>No Media Library items are available for the selected portal.</div>
                            <?php else: ?>
                                <div class="library-picker__empty">No reusable media yet. <a href="media.php">Upload to Media Library</a>.</div>
                            <?php endif ?>
                        </div>
                    </details>
                    <p class="publication-video-server-note">
                        Videos may be up to <strong>250 MB each</strong>.
                        <?php if ($publicationServerFileMaxMb > 0 || $publicationServerPostMaxMb > 0) : ?>
                            Current server: <?=($publicationServerFileMaxMb > 0 ? e((string)$publicationServerFileMaxMb).' MB per file' : 'file limit unavailable')?><?=($publicationServerPostMaxMb > 0 ? ' · '.e((string)$publicationServerPostMaxMb).' MB per request' : '')?>.
                        <?php else: ?>
                            Server upload limits could not be detected.
                        <?php endif; ?>
                    </p>
                </div>
            </section>
        </aside>
    </div>
</form>
<?php if ($edit && ($editImages || $editVideos)) : ?>
<form id="publicationMediaBulkForm" method="post" class="publication-media-bulk-form">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="id" value="<?=e((string)$edit['id'])?>">
    <input type="hidden" name="action" value="bulk_remove_media">
</form>
<?php endif; ?>
<script>
const mediaInput = document.getElementById('postMedia');
const mediaPreview = document.getElementById('mediaPreview');
const mediaCount = document.getElementById('publicationMediaCount');
const existingPhotoCount = <?=count($editImages)?>;
const existingVideoCount = <?=count($editVideos)?>;
const maxPublicationPhotos = 100;
const maxPublicationImageBytes = 8 * 1024 * 1024;
const maxPublicationVideoBytes = 250 * 1024 * 1024;
const publicationServerFileMaxBytes = <?=json_encode($publicationServerFileMaxBytes)?>;
const publicationServerPostMaxBytes = <?=json_encode($publicationServerPostMaxBytes)?>;
const publicationRequestSafetyBytes = 2 * 1024 * 1024;
let pendingMediaFiles = [];

function publicationFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

function publicationMediaType(file) {
    const type = (file.type || '').toLowerCase();
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp'].includes(ext)) return 'photo';
    if (type.startsWith('video/') || ['mp4', 'webm', 'mov'].includes(ext)) return 'video';
    return '';
}

function syncMediaInput() {
    if (!mediaInput || typeof DataTransfer === 'undefined') return;
    const transfer = new DataTransfer();
    pendingMediaFiles.forEach(entry => transfer.items.add(entry.file));
    mediaInput.files = transfer.files;
}

function selectedLibraryPhotoCount() {
    return document.querySelectorAll('input[name="media_image_ids[]"]:checked').length;
}

function selectedLibraryVideoCount() {
    return document.querySelectorAll('input[name="media_video_ids[]"]:checked').length;
}

function pendingPhotoCount() {
    return pendingMediaFiles.filter(entry => entry.kind === 'photo').length;
}

function pendingVideoCount() {
    return pendingMediaFiles.filter(entry => entry.kind === 'video').length;
}

function updatePublicationMediaCount() {
    if (!mediaCount) return;
    const photos = existingPhotoCount + pendingPhotoCount() + selectedLibraryPhotoCount();
    const videos = existingVideoCount + pendingVideoCount() + selectedLibraryVideoCount();
    mediaCount.textContent = `${photos} / ${maxPublicationPhotos} photos · ${videos} video${videos === 1 ? '' : 's'}`;
}

function renderPublicationMediaPreview() {
    if (!mediaPreview) return;
    mediaPreview.innerHTML = '';
    let photoIndex = 0;
    let videoIndex = 0;
    pendingMediaFiles.forEach((entry, index) => {
        const file = entry.file;
        const item = document.createElement('div');
        const url = URL.createObjectURL(file);
        if (entry.kind === 'photo') {
            const currentPhotoIndex = photoIndex++;
            const isNewCover = existingPhotoCount === 0 && currentPhotoIndex === 0;
            item.className = 'image-preview-item';
            item.innerHTML = `<img src="${url}" alt="Selected image ${currentPhotoIndex + 1}"><span>${isNewCover ? 'New cover' : 'New photo ' + (existingPhotoCount + currentPhotoIndex + 1)}</span><button type="button" class="media-remove" data-remove-pending-media="${index}" aria-label="Remove selected photo">×</button>`;
            item.querySelector('img')?.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
        } else {
            const currentVideoIndex = videoIndex++;
            item.className = 'video-preview-item publication-unified-video-item';
            item.setAttribute('data-video-frame', '');
            item.innerHTML = `<video preload="metadata" muted playsinline data-video-preview src="${url}"></video><span class="publication-video-play" aria-hidden="true">▶</span><span class="publication-video-label">New video ${existingVideoCount + currentVideoIndex + 1}</span><button type="button" class="media-remove" data-remove-pending-media="${index}" aria-label="Remove selected video">×</button>`;
        }
        mediaPreview.appendChild(item);
    });
    updatePublicationMediaCount();
}

mediaInput?.addEventListener('change', () => {
    const incomingFiles = Array.from(mediaInput.files || []);
    const known = new Set(pendingMediaFiles.map(entry => publicationFileKey(entry.file)));
    let availablePhotos = Math.max(0, maxPublicationPhotos - existingPhotoCount - selectedLibraryPhotoCount() - pendingPhotoCount());

    for (const file of incomingFiles) {
        const key = publicationFileKey(file);
        if (known.has(key)) continue;
        const kind = publicationMediaType(file);
        if (!kind) {
            alert(`${file.name} is not a supported photo or video.`);
            continue;
        }
        if (kind === 'photo') {
            if (file.size > maxPublicationImageBytes) {
                alert(`${file.name} is larger than the 8 MB photo limit.`);
                continue;
            }
            if (availablePhotos <= 0) {
                alert(`A publication can contain up to ${maxPublicationPhotos} photos.`);
                continue;
            }
            availablePhotos--;
        } else {
            const effectiveVideoLimit = publicationServerFileMaxBytes > 0
                ? Math.min(maxPublicationVideoBytes, publicationServerFileMaxBytes)
                : maxPublicationVideoBytes;
            if (file.size > effectiveVideoLimit) {
                const limitMb = Math.floor(effectiveVideoLimit / (1024 * 1024));
                const serverLimited = publicationServerFileMaxBytes > 0 && publicationServerFileMaxBytes < maxPublicationVideoBytes;
                alert(`${file.name} is larger than the ${limitMb} MB ${serverLimited ? 'current server' : 'video'} limit.`);
                continue;
            }
        }
        pendingMediaFiles.push({ file, kind });
        known.add(key);
    }
    syncMediaInput();
    renderPublicationMediaPreview();
});

mediaPreview?.addEventListener('click', event => {
    const button = event.target.closest('[data-remove-pending-media]');
    if (!button) return;
    const index = Number(button.dataset.removePendingMedia);
    if (!Number.isInteger(index) || index < 0 || index >= pendingMediaFiles.length) return;
    pendingMediaFiles.splice(index, 1);
    syncMediaInput();
    renderPublicationMediaPreview();
});

document.getElementById('publicationEditorForm')?.addEventListener('submit', event => {
    if (!publicationServerPostMaxBytes || pendingMediaFiles.length === 0) return;
    const pendingBytes = pendingMediaFiles.reduce((total, entry) => total + Number(entry.file?.size || 0), 0);
    const safePostBytes = Math.max(0, publicationServerPostMaxBytes - publicationRequestSafetyBytes);
    if (pendingBytes <= safePostBytes) return;
    event.preventDefault();
    const requestMb = Math.ceil(pendingBytes / (1024 * 1024));
    const limitMb = Math.floor(publicationServerPostMaxBytes / (1024 * 1024));
    alert(`The selected files total about ${requestMb} MB, but this server currently accepts about ${limitMb} MB per request. Remove some files or restart the USC LAN server using scripts/windows/START_USC_LAN_SERVER.bat.`);
});

document.querySelectorAll('input[name="media_image_ids[]"]').forEach(box => box.addEventListener('change', event => {
    const totalPhotos = existingPhotoCount + pendingPhotoCount() + selectedLibraryPhotoCount();
    if (event.currentTarget.checked && totalPhotos > maxPublicationPhotos) {
        event.currentTarget.checked = false;
        alert(`A publication can contain up to ${maxPublicationPhotos} photos.`);
    }
    updatePublicationMediaCount();
}));

document.querySelectorAll('input[name="media_video_ids[]"]').forEach(box => box.addEventListener('change', updatePublicationMediaCount));

const publicationMediaGrid = document.getElementById('publicationMediaGrid');
const publicationMediaBulkForm = document.getElementById('publicationMediaBulkForm');
const publicationMediaBulkActions = document.getElementById('publicationMediaBulkActions');
const publicationMediaSelectionToggle = document.getElementById('publicationMediaSelectionToggle');
const publicationMediaSelectAll = document.getElementById('publicationMediaSelectAll');
const publicationMediaSelectedCount = document.getElementById('publicationMediaSelectedCount');
const publicationMediaRemoveSelected = document.getElementById('publicationMediaRemoveSelected');
const publicationMediaOrderStatus = document.getElementById('publicationMediaOrderStatus');
const publicationMediaSelectionBoxes = () => Array.from(document.querySelectorAll('.publication-media-select-check input[type="checkbox"]'));

function updatePublicationMediaSelection() {
    const boxes = publicationMediaSelectionBoxes();
    const selected = boxes.filter(box => box.checked).length;
    if (publicationMediaSelectedCount) publicationMediaSelectedCount.textContent = `${selected} selected`;
    if (publicationMediaRemoveSelected) publicationMediaRemoveSelected.disabled = selected === 0;
    if (publicationMediaSelectAll) publicationMediaSelectAll.textContent = boxes.length > 0 && selected === boxes.length ? 'Clear all' : 'Select all';
    boxes.forEach(box => box.closest('[data-media-selectable]')?.classList.toggle('is-selected', box.checked));
}

function setPublicationMediaSelectionMode(enabled) {
    if (!publicationMediaGrid) return;
    publicationMediaGrid.classList.toggle('is-selecting', enabled);
    if (publicationMediaBulkActions) publicationMediaBulkActions.hidden = !enabled;
    if (publicationMediaSelectionToggle) {
        publicationMediaSelectionToggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        publicationMediaSelectionToggle.textContent = enabled ? 'Done' : 'Select';
    }
    if (!enabled) {
        publicationMediaSelectionBoxes().forEach(box => { box.checked = false; });
        updatePublicationMediaSelection();
    }
}

publicationMediaSelectionToggle?.addEventListener('click', () => {
    setPublicationMediaSelectionMode(!publicationMediaGrid?.classList.contains('is-selecting'));
});

publicationMediaSelectAll?.addEventListener('click', () => {
    const boxes = publicationMediaSelectionBoxes();
    const shouldSelect = boxes.some(box => !box.checked);
    boxes.forEach(box => { box.checked = shouldSelect; });
    updatePublicationMediaSelection();
});

publicationMediaSelectionBoxes().forEach(box => box.addEventListener('change', updatePublicationMediaSelection));
publicationMediaGrid?.addEventListener('click', event => {
    if (!publicationMediaGrid.classList.contains('is-selecting')) return;
    if (event.target.closest('button, input, label')) return;
    const item = event.target.closest('[data-media-selectable]');
    const box = item?.querySelector('.publication-media-select-check input[type="checkbox"]');
    if (!box) return;
    box.checked = !box.checked;
    updatePublicationMediaSelection();
});

function mediaItems(type = null) {
    const selector = type
        ? `[data-media-selectable][data-media-type="${type}"]`
        : '[data-media-selectable]';
    return Array.from(publicationMediaGrid?.querySelectorAll(selector) || []);
}

function refreshPublicationMediaOrderLabels() {
    let photoIndex = 0;
    let videoIndex = 0;
    mediaItems().forEach((item, globalIndex) => {
        const type = item.dataset.mediaType;
        const label = item.querySelector('[data-media-label]');
        const handle = item.querySelector('[data-media-drag-handle]');
        const feature = item.querySelector('.media-feature');
        const isFeatured = globalIndex === 0;
        item.classList.toggle('is-featured-media', isFeatured);
        if (feature) {
            feature.classList.toggle('is-active', isFeatured);
            feature.disabled = isFeatured;
            feature.textContent = isFeatured ? '★' : '☆';
            feature.title = isFeatured ? 'Featured media' : (type === 'video' ? 'Feature this video' : 'Feature this photo');
            feature.setAttribute('aria-label', feature.title);
        }
        if (type === 'image') {
            const isCover = photoIndex === 0;
            item.classList.toggle('is-cover', isCover);
            if (label) label.textContent = isCover ? 'Cover' : `Photo ${photoIndex + 1}`;
            if (handle) handle.setAttribute('aria-label', `Move ${isCover ? 'cover photo' : `photo ${photoIndex + 1}`}. Drag anywhere in the media order, or use the arrow keys.`);
            photoIndex++;
        } else if (type === 'video') {
            item.classList.remove('is-cover');
            if (label) label.textContent = `Video ${videoIndex + 1}`;
            if (handle) handle.setAttribute('aria-label', `Move video ${videoIndex + 1}. Drag anywhere in the media order, or use the arrow keys.`);
            videoIndex++;
        }
    });
}

let mediaOrderSaveTimer = 0;
async function savePublicationMediaOrder() {
    if (!publicationMediaBulkForm || !publicationMediaGrid) return;
    window.clearTimeout(mediaOrderSaveTimer);
    const token = publicationMediaBulkForm.querySelector('input[name="csrf"]')?.value || '';
    const postId = publicationMediaBulkForm.querySelector('input[name="id"]')?.value || '';
    const body = new FormData();
    body.append('csrf', token);
    body.append('action', 'reorder_media');
    body.append('id', postId);
    body.append('media_order', JSON.stringify(mediaItems().map(item => `${item.dataset.mediaType}:${item.dataset.mediaId}`)));
    if (publicationMediaOrderStatus) publicationMediaOrderStatus.textContent = 'Saving media order…';
    try {
        const response = await fetch('posts.php', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json().catch(() => null);
        if (!response.ok || !data?.ok) throw new Error(data?.message || 'Could not save media order.');
        refreshPublicationMediaOrderLabels();
        if (publicationMediaOrderStatus) publicationMediaOrderStatus.textContent = 'Order saved. The first item is featured; the first photo remains the cover.';
        mediaOrderSaveTimer = window.setTimeout(() => {
            if (publicationMediaOrderStatus) publicationMediaOrderStatus.textContent = 'Drag any photo or video to reorder. The first item is featured; the first photo is the cover.';
        }, 2200);
    } catch (error) {
        if (publicationMediaOrderStatus) publicationMediaOrderStatus.textContent = error?.message || 'Could not save media order. Refresh and try again.';
    }
}

let draggedMediaItem = null;
publicationMediaGrid?.querySelectorAll('[data-media-drag-handle]').forEach(handle => {
    handle.addEventListener('dragstart', event => {
        draggedMediaItem = handle.closest('[data-media-selectable]');
        if (!draggedMediaItem) return;
        draggedMediaItem.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', draggedMediaItem.dataset.mediaId || 'media');
    });
    handle.addEventListener('dragend', () => {
        draggedMediaItem?.classList.remove('is-dragging');
        publicationMediaGrid?.querySelectorAll('.is-drag-target').forEach(el => el.classList.remove('is-drag-target'));
        draggedMediaItem = null;
    });
    handle.addEventListener('keydown', event => {
        if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key)) return;
        const item = handle.closest('[data-media-selectable]');
        if (!item) return;
        const items = mediaItems();
        const index = items.indexOf(item);
        if (index < 0) return;
        const backward = event.key === 'ArrowLeft' || event.key === 'ArrowUp';
        const nextIndex = backward ? index - 1 : index + 1;
        if (nextIndex < 0 || nextIndex >= items.length) return;
        event.preventDefault();
        const target = items[nextIndex];
        if (backward) publicationMediaGrid.insertBefore(item, target);
        else publicationMediaGrid.insertBefore(target, item);
        refreshPublicationMediaOrderLabels();
        savePublicationMediaOrder();
        handle.focus();
    });
});

publicationMediaGrid?.querySelectorAll('[data-media-selectable]').forEach(item => {
    item.addEventListener('dragover', event => {
        if (!draggedMediaItem || draggedMediaItem === item) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        publicationMediaGrid.querySelectorAll('.is-drag-target').forEach(el => el.classList.remove('is-drag-target'));
        item.classList.add('is-drag-target');
    });
    item.addEventListener('dragleave', () => item.classList.remove('is-drag-target'));
    item.addEventListener('drop', event => {
        if (!draggedMediaItem || draggedMediaItem === item) return;
        event.preventDefault();
        item.classList.remove('is-drag-target');
        const items = mediaItems();
        const from = items.indexOf(draggedMediaItem);
        const to = items.indexOf(item);
        if (from < 0 || to < 0) return;
        if (from < to) publicationMediaGrid.insertBefore(draggedMediaItem, item.nextSibling);
        else publicationMediaGrid.insertBefore(draggedMediaItem, item);
        refreshPublicationMediaOrderLabels();
        savePublicationMediaOrder();
    });
});

refreshPublicationMediaOrderLabels();
updatePublicationMediaSelection();

updatePublicationMediaCount();
renderPublicationMediaPreview();
const publicationPortal = document.getElementById('publicationPortal');
function filterPublicationLibrary() {
    const portal = (publicationPortal?.value || '<?=e(strtolower($scope??($edit['category']??'usc')))?>').toUpperCase();
    document.querySelectorAll('[data-library-picker^="publication-"]').forEach(picker => {
        const items = [...picker.querySelectorAll('.library-asset')];
        let shown = 0;
        items.forEach(item => {
            const show = item.dataset.portal === portal;
            item.hidden = !show;
            if (show) shown++;
            else item.querySelector('input').checked = false;
        });
        const empty = picker.querySelector('.library-picker__empty-filter');
        if (empty) empty.hidden = shown > 0;
    });
    updatePublicationMediaCount();
    renderPublicationMediaPreview();
}
publicationPortal?.addEventListener('change', filterPublicationLibrary); filterPublicationLibrary();
const publicationStatus = document.getElementById('publicationStatus'), scheduleField = document.getElementById('scheduleField'), scheduledAt = document.getElementById('scheduledAt'), reviewNoteField = document.getElementById('reviewNoteField');
function syncPublishingFields() {
    const status = publicationStatus?.value || 'draft';
    const showSchedule = status === 'scheduled';
    const showReviewNote = status === 'review';
    if (scheduleField) scheduleField.hidden = !showSchedule;
    if (scheduledAt) scheduledAt.required = showSchedule;
    if (reviewNoteField) reviewNoteField.hidden = !showReviewNote;
}
publicationStatus?.addEventListener('change', syncPublishingFields); syncPublishingFields();
</script>
<script src="../public/assets/js/video-preview.js"></script>
<?php else: ?>
<div class="content-page-head">
    <div>
        <span class="page-kicker">NEWS &amp; UPDATES</span>
        <h2>Publications</h2>
        <p>Manage official announcements and campus updates from one simple workspace.</p>
    </div>
    <a class="btn content-new-button" href="?action=new"><span>＋</span> New publication</a>
</div>
<section class="panel publication-workspace">
    <div class="publication-overview" aria-label="Publication summary">
        <div class="publication-overview__stat">
            <span>Total publications</span>
            <strong><?=$publicationSummary['total']?></strong>
        </div>
        <div class="publication-overview__stat">
            <span>Published</span>
            <strong><?=$publicationSummary['published']?></strong>
        </div>
        <div class="publication-overview__stat">
            <span>For review</span>
            <strong><?=$publicationSummary['review']?></strong>
        </div>
        <?php if ($publishingAdvanced) : ?>
            <div class="publication-overview__stat">
                <span>Scheduled</span>
                <strong><?=$publicationSummary['scheduled']?></strong>
            </div>
        <?php endif; ?>
        <div class="publication-overview__stat">
            <span>Drafts</span>
            <strong><?=$publicationSummary['drafts']?></strong>
        </div>
    </div>
    <form class="publication-filters" method="get">
        <div class="searchbox publication-search">
            <input name="q" value="<?=e($q)?>" placeholder="Search title, summary, or label">
        </div>
        <select class="filter-select" name="campus" aria-label="Filter by campus" onchange="this.form.requestSubmit()" <?= $scope?'disabled':'' ?>>
            <option value="all">All campuses</option>
            <?php foreach (['usc' => 'University Student Council', 'nluc' => 'North La Union Campus', 'mluc' => 'Mid La Union Campus', 'sluc' => 'South La Union Campus', 'ous' => 'Open University System'] as $k => $v) : ?>
                <option value="<?=$k?>" <?=$campus===$k?'selected':''?>><?=$v?></option>
            <?php endforeach ?>
        </select>
        <select class="filter-select" name="status" aria-label="Filter by status" onchange="this.form.requestSubmit()">
            <option value="all">All statuses</option>
            <option value="published" <?=$statusFilter==='published'?'selected':''?>>Published</option>
            <option value="review" <?=$statusFilter==='review'?'selected':''?>>For review</option>
            <option value="draft" <?=$statusFilter==='draft'?'selected':''?>>Draft</option>
            <?php if ($publishingAdvanced) : ?>
                <option value="scheduled" <?=$statusFilter==='scheduled'?'selected':''?>>Scheduled</option>
                <option value="archived" <?=$statusFilter==='archived'?'selected':''?>>Archived</option>
            <?php endif; ?>
        </select>
        <?php if ($q !== '' || $campus !== 'all' || $statusFilter !== 'all') : ?>
            <a class="publication-filter-reset" href="posts.php">Reset</a>
        <?php endif; ?>
    </form>
    <div class="table-wrap publication-table-wrap publication-table-wrap--simple">
        <table class="publication-table publication-table--simple">
            <colgroup>
            <col class="pub-col-title">
            <col class="pub-col-campus">
            <col class="pub-col-media">
            <col class="pub-col-status">
            <col class="pub-col-date">
            <col class="pub-col-actions">
            </colgroup>
            <thead>
                <tr>
                    <th>Publication</th>
                    <th>Campus</th>
                    <th>Media</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th class="actions-heading">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p) : ?>
                    <tr>
                        <td class="cell-title publication-title-cell">
                            <div class="publication-title-row">
                                <strong><?=e($p['title'])?></strong>
                                <?php if (!empty($p['is_featured'])) : ?>
                                    <span class="featured-mark" title="Featured in this portal">★ Portal Featured</span>
                                <?php endif; ?>
                                <?php if ($universityFeatureAvailable && !empty($p['is_university_featured'])) : ?>
                                    <span class="featured-mark" title="Chosen by USC/System Administrator for the university-wide All view">★ Featured in All</span>
                                <?php endif; ?>
                            </div>
                            <span><?=e($p['excerpt']?:'No summary provided.')?></span>
                        </td>
                        <td>
                            <span class="campus-badge campus-badge--<?=e(strtolower($p['category']))?>"><?=e(portal_display_name((string)$p['category']))?></span>
                        </td>
                        <td>
                            <span class="media-count media-count--clean"><i>▧</i><?=e((string)$p['image_count'])?> photo<?=$p['image_count']==1?'':'s'?></span>
                        </td>
                        <?php $rowStatus = ($publishingAdvanced && !empty($p['scheduled_at']) && $p['status'] === 'draft' && strtotime((string)$p['scheduled_at'])>time())?'scheduled':$p['status']; ?>
                        <td><?=status_badge(ucfirst($rowStatus))?></td>
                        <td><time class="publication-date">
                            <?php if ($rowStatus === 'scheduled') : ?>
                                Scheduled <?=e(date('M j, Y · g:i A',strtotime($p['scheduled_at'])))?>
                            <?php else: ?>
                                <?=e(date('M j, Y',strtotime($p['published_at'])))?>
                            <?php endif; ?>
                            </time></td>
                        <td>
                            <div class="table-actions publication-actions">
                                <a class="publication-action-edit" href="?edit=<?=$p['id']?>">Edit</a>
                                <?php if ($canManageUniversityFeature && $universityFeatureAvailable && $p['status'] === 'published') : ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="set_university_feature">
                                        <input type="hidden" name="id" value="<?=$p['id']?>">
                                        <input type="hidden" name="enabled" value="<?=!empty($p['is_university_featured'])?0:1?>">
                                        <input type="hidden" name="return_campus" value="<?=e((string)$campus)?>">
                                        <input type="hidden" name="return_status" value="<?=e((string)$statusFilter)?>">
                                        <input type="hidden" name="return_q" value="<?=e((string)$q)?>">
                                        <button type="submit" class="publication-action-edit"><?=!empty($p['is_university_featured'])?'Remove from All':'Feature in All'?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($deleteAllowed) : ?>
                                    <form method="post" data-confirm="Move this publication to Trash?">
                                        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?=$p['id']?>">
                                        <button type="submit" class="publication-trash publication-trash--simple" title="Move to Trash" aria-label="Move <?=e($p['title'])?> to Trash"><svg class="publication-trash-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 3h6l1 2h4v2h-1l-1 14H6L5 7H4V5h4l1-2Zm1.2 2h3.6l-.4-.8h-3l-.2.8ZM7 7l.9 12h8.2L17 7H7Zm3 2h2v8h-2V9Zm4 0h2v8h-2V9Z"/></svg></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (!$posts) : ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state content-empty-state">
                                <strong>No publications found</strong>
                                <span>Try changing your search or filters.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>
    <?=render_pagination($pg)?>
</section>
<?php
endif;
admin_footer();
?>
