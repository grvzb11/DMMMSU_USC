<?php
require_once '../config/database.php';
require_once '_layout.php';
if (!can_manage_homepage()) deny_access('You do not have permission to manage the homepage.');
ensure_hero_slides_table($pdo);
ensure_hero_promotion_requests_table($pdo);

$homepagePortals = hero_portal_codes();
$scope = admin_scope_code();
$requestedPortal = strtoupper(trim((string)($_GET['portal'] ?? $_POST['portal'] ?? '')));
$activePortal = $scope ?: (isset($homepagePortals[$requestedPortal])?$requestedPortal:'USC');
if (!isset($homepagePortals[$activePortal])) $activePortal = 'USC';
if ($scope !== null && $activePortal !== $scope) deny_access('You do not have permission to manage this homepage.');
$activePortalName = $homepagePortals[$activePortal];
$portalQuery = 'portal='.rawurlencode($activePortal);
$publicHomepage = '../'.hero_portal_home_path($activePortal);
$portalEyebrow = $activePortal === 'USC'?'USC DIGITAL STUDENT SERVICES':strtoupper($activePortalName).' • STUDENT SERVICES';
$pageView = in_array((string)($_GET['view'] ?? $_POST['view'] ?? 'slides'), ['slides', 'promotions'], true)?(string)($_GET['view'] ?? $_POST['view'] ?? 'slides'):'slides';

$heroThemeGroups = [
'Base colors' => [
'none' => 'None', 'green' => 'Green', 'blue' => 'Green / Blue', 'forest' => 'Forest Green',
'emerald' => 'Emerald', 'teal' => 'Teal', 'navy' => 'Navy Blue', 'sky' => 'Sky Blue', 'purple' => 'Purple', 'maroon' => 'Maroon',
'red' => 'Red', 'orange' => 'Orange', 'gold' => 'Gold', 'slate' => 'Slate / Charcoal',
],
];
$heroThemeLabels = [];
foreach ($heroThemeGroups as $groupThemes) {
    $heroThemeLabels = array_merge($heroThemeLabels, $groupThemes);
}
$heroButtonModes = ['both' => 'Primary + secondary', 'primary' => 'Primary only', 'secondary' => 'Secondary only', 'none' => 'None — no buttons'];

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'request_promotion') {
        if ($activePortal === 'USC') {
            http_response_code(422);
            exit('USC slides are already on the USC homepage.');
        }
        $st = $pdo->prepare("SELECT id,title,eyebrow,is_active FROM hero_slides WHERE id=? AND UPPER(portal_code)=? LIMIT 1");
        $st->execute([$id, $activePortal]);
        $slide = $st->fetch();
        if (!$slide) not_found('The requested homepage slide was not found.');
        if (empty($slide['is_active'])) {
            header('Location: hero.php?'.$portalQuery.'&view=promotions&promotion_error=visible');
            exit;
        }
        $check = $pdo->prepare("SELECT id,status FROM hero_promotion_requests WHERE source_slide_id=? AND UPPER(source_portal)=? AND UPPER(target_portal)='USC' AND status IN ('pending','approved') ORDER BY id DESC LIMIT 1");
        $check->execute([$id, $activePortal]);
        if (!$check->fetch()) {
            $reuse = $pdo->prepare("SELECT id,status FROM hero_promotion_requests WHERE source_slide_id=? AND UPPER(source_portal)=? AND UPPER(target_portal)='USC' AND status IN ('needs_reapproval','changes_requested') ORDER BY id DESC LIMIT 1");
            $reuse->execute([$id, $activePortal]);
            $reuseRow = $reuse->fetch();
            if ($reuseRow) {
                $requestId = (int)$reuseRow['id'];
                $pdo->prepare("UPDATE hero_promotion_requests SET status='pending',request_note=?,requested_by=?,requested_by_name=?,requested_at=NOW(),reviewed_by=NULL,reviewed_by_name=NULL,reviewed_at=NULL WHERE id=?")
                ->execute([trim((string)($_POST['request_note'] ?? ''))?:null, (int)($_SESSION['admin_id'] ?? 0)?:null, $_SESSION['admin_name'] ?? null, $requestId]);
            } else {
                $ins = $pdo->prepare("INSERT INTO hero_promotion_requests(source_slide_id,source_portal,target_portal,status,request_note,requested_by,requested_by_name) VALUES(?,?,'USC','pending',?,?,?)");
                $ins->execute([$id, $activePortal, trim((string)($_POST['request_note'] ?? ''))?:null, (int)($_SESSION['admin_id'] ?? 0)?:null, $_SESSION['admin_name'] ?? null]);
                $requestId = (int)$pdo->lastInsertId();
            }
            admin_notify($pdo, 'Homepage promotion request', $activePortalName.' requested USC homepage display for “'.($slide['title']?:$slide['eyebrow']).'”.', 'hero.php?portal=USC&view=promotions#promotion-'.$requestId, 'warning', 'usc', null, null, 'promotions');
            admin_notify($pdo, 'Homepage promotion request', $activePortalName.' requested USC homepage display for “'.($slide['title']?:$slide['eyebrow']).'”.', 'hero.php?portal=USC&view=promotions#promotion-'.$requestId, 'warning', 'admin', null, null, 'promotions');
            admin_log($pdo, 'homepage', 'Requested USC promotion', $activePortalName.' · '.($slide['title']?:'Hero slide'), 'hero_promotion', $requestId);
        }
        header('Location: hero.php?'.$portalQuery.'&view=promotions&promotion_requested=1');
        exit;
    }

    if ($action === 'withdraw_promotion') {
        if ($activePortal === 'USC') deny_access('USC slides cannot be submitted as campus promotion requests.');
        $requestId = (int)($_POST['request_id'] ?? 0);
        $st = $pdo->prepare("UPDATE hero_promotion_requests SET status='withdrawn' WHERE id=? AND source_slide_id=? AND UPPER(source_portal)=? AND status='pending'");
        $st->execute([$requestId, $id, $activePortal]);
        if ($st->rowCount()) {
            admin_notify($pdo, 'Homepage request withdrawn', $activePortalName.' withdrew a pending USC homepage promotion request.', 'hero.php?portal=USC&view=promotions', 'info', 'usc', null, null, 'promotions');
            admin_log($pdo, 'homepage', 'Withdrew USC promotion', $activePortalName.' · Request #'.$requestId, 'hero_promotion', $requestId);
        }
        header('Location: hero.php?'.$portalQuery.'&view=promotions&promotion_withdrawn=1');
        exit;
    }

    if ($action === 'review_promotion' || $action === 'revoke_promotion') {
        if ($activePortal !== 'USC' || !can_approve_homepage_promotions()) deny_access('You do not have permission to review USC homepage promotion requests.');
        $requestId = (int)($_POST['request_id'] ?? 0);
        $st = $pdo->prepare("SELECT pr.*,h.title,h.eyebrow,h.is_active FROM hero_promotion_requests pr INNER JOIN hero_slides h ON h.id=pr.source_slide_id AND UPPER(h.portal_code)=UPPER(pr.source_portal) WHERE pr.id=? AND UPPER(pr.target_portal)='USC' LIMIT 1");
        $st->execute([$requestId]);
        $request = $st->fetch();
        if (!$request) not_found('The requested homepage promotion request was not found.');
        $sourcePortal = strtoupper((string)$request['source_portal']);
        $sourceName = portal_display_name($sourcePortal);
        $slideName = $request['title']?:($request['eyebrow']?:'Hero slide');
        if ($action === 'revoke_promotion') {
            $pdo->prepare("UPDATE hero_promotion_requests SET status='revoked',reviewed_by=?,reviewed_by_name=?,reviewed_at=NOW(),review_note=? WHERE id=? AND status='approved'")
            ->execute([(int)($_SESSION['admin_id'] ?? 0)?:null, $_SESSION['admin_name'] ?? null, trim((string)($_POST['review_note'] ?? ''))?:null, $requestId]);
            admin_notify($pdo, 'USC homepage promotion revoked', 'USC removed “'.$slideName.'” from the main homepage.', 'hero.php?portal='.$sourcePortal.'&view=promotions', 'warning', null, $sourcePortal, null, 'promotions');
            admin_log($pdo, 'homepage', 'Revoked promoted slide', $sourceName.' · '.$slideName, 'hero_promotion', $requestId);
            header('Location: hero.php?portal=USC&view=promotions&promotion_revoked=1');
            exit;
        }

        $decision = $_POST['decision'] ?? '';
        $reviewNote = trim((string)($_POST['review_note'] ?? ''));
        if ($decision === 'approve') {
            if (empty($request['is_active'])) {
                header('Location: hero.php?portal=USC&view=promotions&promotion_error=source_hidden');
                exit;
            }
            $fromRaw = trim((string)($_POST['display_from'] ?? ''));
            $untilRaw = trim((string)($_POST['display_until'] ?? ''));
            $fromTs = $fromRaw !== ''?strtotime($fromRaw):time();
            $untilTs = $untilRaw !== ''?strtotime($untilRaw):false;
            if ($fromTs === false || ($untilRaw !== '' && $untilTs === false)) {
                header('Location: hero.php?portal=USC&view=promotions&promotion_error=date');
                exit;
            }
            if ($untilTs !== false && $untilTs<$fromTs) {
                header('Location: hero.php?portal=USC&view=promotions&promotion_error=date_order');
                exit;
            }
            $displayOrder = max(1, (int)($_POST['display_order'] ?? 0));
            if (empty($_POST['display_order'])) {
                $local = (int)$pdo->query("SELECT COUNT(*) FROM hero_slides WHERE UPPER(portal_code)='USC' AND is_active=1")->fetchColumn();
                $approved = (int)$pdo->query("SELECT COUNT(*) FROM hero_promotion_requests WHERE UPPER(target_portal)='USC' AND status='approved'")->fetchColumn();
                $displayOrder = $local+$approved+1;
            }
            $pdo->prepare("UPDATE hero_promotion_requests SET status='approved',review_note=?,reviewed_by=?,reviewed_by_name=?,reviewed_at=NOW(),display_from=?,display_until=?,display_order=? WHERE id=?")
            ->execute([$reviewNote?:null, (int)($_SESSION['admin_id'] ?? 0)?:null, $_SESSION['admin_name'] ?? null, date('Y-m-d H:i:s', $fromTs), $untilTs !== false?date('Y-m-d H:i:s', $untilTs):null, $displayOrder, $requestId]);
            admin_notify($pdo, 'USC homepage request approved', '“'.$slideName.'” was approved for display on the USC homepage.', 'hero.php?portal='.$sourcePortal.'&view=promotions', 'success', null, $sourcePortal, null, 'promotions');
            admin_log($pdo, 'homepage', 'Approved promoted slide', $sourceName.' · '.$slideName, 'hero_promotion', $requestId);
            header('Location: hero.php?portal=USC&view=promotions&promotion_approved=1');
            exit;
        }
        if ($decision === 'changes') {
            $pdo->prepare("UPDATE hero_promotion_requests SET status='changes_requested',review_note=?,reviewed_by=?,reviewed_by_name=?,reviewed_at=NOW() WHERE id=?")
            ->execute([$reviewNote?:'Please revise the slide and submit it again.', (int)($_SESSION['admin_id'] ?? 0)?:null, $_SESSION['admin_name'] ?? null, $requestId]);
            admin_notify($pdo, 'Changes requested for homepage promotion', 'USC requested changes to “'.$slideName.'”.', 'hero.php?portal='.$sourcePortal.'&view=promotions', 'warning', null, $sourcePortal, null, 'promotions');
            admin_log($pdo, 'homepage', 'Requested promotion changes', $sourceName.' · '.$slideName, 'hero_promotion', $requestId);
            header('Location: hero.php?portal=USC&view=promotions&promotion_changes=1');
            exit;
        }
        if ($decision === 'reject') {
            $pdo->prepare("UPDATE hero_promotion_requests SET status='rejected',review_note=?,reviewed_by=?,reviewed_by_name=?,reviewed_at=NOW() WHERE id=?")
            ->execute([$reviewNote?:null, (int)($_SESSION['admin_id'] ?? 0)?:null, $_SESSION['admin_name'] ?? null, $requestId]);
            admin_notify($pdo, 'USC homepage request declined', 'USC declined the homepage promotion request for “'.$slideName.'”.', 'hero.php?portal='.$sourcePortal.'&view=promotions', 'danger', null, $sourcePortal, null, 'promotions');
            admin_log($pdo, 'homepage', 'Rejected promoted slide', $sourceName.' · '.$slideName, 'hero_promotion', $requestId);
            header('Location: hero.php?portal=USC&view=promotions&promotion_rejected=1');
            exit;
        }
        header('Location: hero.php?portal=USC&view=promotions');
        exit;
    }

    if ($action === 'reorder') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $requested = array_values(array_unique(array_filter(array_map('intval', $_POST['order'] ?? []), fn($value) => $value>0)));
            $st = $pdo->prepare('SELECT id FROM hero_slides WHERE UPPER(portal_code)=? ORDER BY sort_order,id');
            $st->execute([$activePortal]);
            $currentIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            $requestedCheck = $requested;
            $currentCheck = $currentIds;
            sort($requestedCheck, SORT_NUMERIC);
            sort($currentCheck, SORT_NUMERIC);
            if (!$requested || $requestedCheck !== $currentCheck) throw new RuntimeException('The slide list changed. Refresh the page and try again.');

            $pdo->beginTransaction();
            $up = $pdo->prepare('UPDATE hero_slides SET sort_order=? WHERE id=? AND UPPER(portal_code)=?');
            foreach ($requested as $index => $slideId) $up->execute([$index+1, $slideId, $activePortal]);
            $pdo->commit();
            try {
                admin_log($pdo, 'homepage', 'Reordered hero slides', $activePortalName.' homepage slide order updated.', 'hero_slide');
            } catch (Throwable $ignored) {
            }
            echo json_encode(['ok' => true, 'order' => $requested], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    if ($action === 'delete') {
        $st = $pdo->prepare('SELECT panel_image,panel_media_id,background_image,background_media_id,sort_order FROM hero_slides WHERE id=? AND UPPER(portal_code)=?');
        $st->execute([$id, $activePortal]);
        $old = $st->fetch();
        if ($old) {
            if (empty($old['panel_media_id'])) delete_hero_image($old['panel_image']);
            if (empty($old['background_media_id'])) delete_hero_image($old['background_image']);
            $pdo->prepare("UPDATE hero_promotion_requests SET status=CASE WHEN status='pending' THEN 'withdrawn' ELSE 'revoked' END WHERE source_slide_id=? AND UPPER(source_portal)=? AND status IN ('pending','approved')")->execute([$id, $activePortal]);
            $pdo->prepare('DELETE FROM hero_slides WHERE id=? AND UPPER(portal_code)=?')->execute([$id, $activePortal]);
            normalize_hero_slide_order($pdo, $activePortal);
            admin_log($pdo, 'homepage', 'Removed hero slide', $activePortalName.' · Hero slide #'.$id, 'hero_slide', $id);
        }
        header('Location: hero.php?'.$portalQuery.'&deleted=1');
        exit;
    }

    $current = ['panel_image' => null, 'panel_media_id' => null, 'background_image' => null, 'background_media_id' => null, 'sort_order' => null];
    if ($id) {
        $st = $pdo->prepare('SELECT panel_image,panel_media_id,background_image,background_media_id,sort_order FROM hero_slides WHERE id=? AND UPPER(portal_code)=?');
        $st->execute([$id, $activePortal]);
        $current = $st->fetch()?:$current;
    }

    try {
        $panelImage = $current['panel_image'];
        $panelMediaId = (int)($current['panel_media_id'] ?? 0);
        $backgroundImage = $current['background_image'];
        $backgroundMediaId = (int)($current['background_media_id'] ?? 0);

        if (isset($_POST['remove_panel_image']) && $panelImage) {
            if (!$panelMediaId) delete_hero_image($panelImage);
            $panelImage = null;
            $panelMediaId = 0;
        }
        if (isset($_POST['remove_background_image']) && $backgroundImage) {
            if (!$backgroundMediaId) delete_hero_image($backgroundImage);
            $backgroundImage = null;
            $backgroundMediaId = 0;
        }

        if (!empty($_FILES['panel_image']['name']) || !empty($_FILES['background_image']['name'])) {
            $rl = max(5, (int)(system_setting($pdo, 'security_rate_limit_uploads_per_hour', '40') ?? 40));
            platform_rate_limit_or_429($pdo, 'admin-upload', ((string)($_SESSION['admin_id'] ?? 0)).'|'.admin_current_ip(), $rl, 3600, 900);
        }
        $newPanel = upload_hero_image($_FILES['panel_image'] ?? [], strtolower($activePortal).'_panel');
        if ($newPanel) {
            if (!$panelMediaId) delete_hero_image($panelImage);
            $panelImage = $newPanel;
            $panelMediaId = 0;
        }
        $newBg = upload_hero_image($_FILES['background_image'] ?? [], strtolower($activePortal).'_background');
        if ($newBg) {
            if (!$backgroundMediaId) delete_hero_image($backgroundImage);
            $backgroundImage = $newBg;
            $backgroundMediaId = 0;
        }

        $selectedPanelMediaId = (int)($_POST['panel_media_id'] ?? 0);
        if ($selectedPanelMediaId>0) {
            $asset = media_asset($pdo, $selectedPanelMediaId);
            $assetPortal = strtoupper(trim((string)(($asset['campus'] ?? '')?:'USC')));
            if (!$asset || !media_asset_is_image($asset) || $assetPortal !== $activePortal) throw new RuntimeException('The selected right-side Media Library image is not available for this homepage.');
            if (!$panelMediaId) delete_hero_image($panelImage);
            $panelImage = $asset['file_path'];
            $panelMediaId = $selectedPanelMediaId;
        }
        $selectedBackgroundMediaId = (int)($_POST['background_media_id'] ?? 0);
        if ($selectedBackgroundMediaId>0) {
            $asset = media_asset($pdo, $selectedBackgroundMediaId);
            $assetPortal = strtoupper(trim((string)(($asset['campus'] ?? '')?:'USC')));
            if (!$asset || !media_asset_is_image($asset) || $assetPortal !== $activePortal) throw new RuntimeException('The selected background Media Library image is not available for this homepage.');
            if (!$backgroundMediaId) delete_hero_image($backgroundImage);
            $backgroundImage = $asset['file_path'];
            $backgroundMediaId = $selectedBackgroundMediaId;
        }

        $theme = array_key_exists($_POST['theme'] ?? '', $heroThemeLabels)?$_POST['theme']:'green';
        $buttonMode = array_key_exists($_POST['button_mode'] ?? '', $heroButtonModes)?$_POST['button_mode']:'both';
        $panel = in_array($_POST['panel_type'] ?? '', ['card', 'none', 'image'], true)?$_POST['panel_type']:'card';
        $displayMode = in_array($_POST['display_mode'] ?? '', ['standard', 'image_only'], true)?$_POST['display_mode']:'standard';
        $backgroundPositionX = max(0, min(100, (int)($_POST['background_position_x'] ?? 50)));
        $backgroundPositionY = max(0, min(100, (int)($_POST['background_position_y'] ?? 50)));
        if ($displayMode === 'image_only' && !$backgroundImage) throw new RuntimeException('Image Only mode requires a background image.');
        if ($displayMode === 'standard' && $panel === 'image' && !$panelImage) throw new RuntimeException('Image right-side content requires an uploaded image.');

        $eyebrow = trim($_POST['eyebrow'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($id) {
            $preservedSortOrder = max(1, (int)($current['sort_order'] ?? 1));
        }
        else {
            $countSt = $pdo->prepare('SELECT COUNT(*) FROM hero_slides WHERE UPPER(portal_code)=?');
            $countSt->execute([$activePortal]);
            $preservedSortOrder = (int)$countSt->fetchColumn()+1;
        }
        if ($displayMode === 'standard' && ($eyebrow === '' || $title === '')) throw new RuntimeException('Eyebrow and headline are required for a standard slide.');

        $data = [
        $eyebrow, $title, $description, trim($_POST['primary_label'] ?? ''), trim($_POST['primary_url'] ?? ''),
        trim($_POST['secondary_label'] ?? ''), trim($_POST['secondary_url'] ?? ''), $buttonMode, $theme, $panel,
        trim($_POST['panel_kicker'] ?? ''), trim($_POST['panel_status'] ?? ''), trim($_POST['panel_title'] ?? ''), trim($_POST['panel_description'] ?? ''),
        $panelImage, $panelMediaId?:null, $backgroundImage, $backgroundMediaId?:null, $backgroundPositionX, $backgroundPositionY, $displayMode,
        $preservedSortOrder, isset($_POST['is_active'])?1:0
        ];

        if ($id) {
            $data[] = $id;
            $data[] = $activePortal;
            $pdo->prepare('UPDATE hero_slides SET eyebrow=?,title=?,description=?,primary_label=?,primary_url=?,secondary_label=?,secondary_url=?,button_mode=?,theme=?,panel_type=?,panel_kicker=?,panel_status=?,panel_title=?,panel_description=?,panel_image=?,panel_media_id=?,background_image=?,background_media_id=?,background_position_x=?,background_position_y=?,display_mode=?,sort_order=?,is_active=? WHERE id=? AND UPPER(portal_code)=?')->execute($data);
            normalize_hero_slide_order($pdo, $activePortal);
            if ($activePortal !== 'USC') {
                $promoUp = $pdo->prepare("UPDATE hero_promotion_requests SET status='needs_reapproval' WHERE source_slide_id=? AND UPPER(source_portal)=? AND status='approved'");
                $promoUp->execute([$id, $activePortal]);
                if ($promoUp->rowCount()) {
                    admin_notify($pdo, 'Promoted campus slide changed', $activePortalName.' changed a slide that was approved for the USC homepage. Reapproval is required.', 'hero.php?portal=USC&view=promotions', 'warning', 'usc', null, null, 'promotions');
                    admin_notify($pdo, 'Promoted campus slide changed', $activePortalName.' changed a slide that was approved for the USC homepage. Reapproval is required.', 'hero.php?portal=USC&view=promotions', 'warning', 'admin', null, null, 'promotions');
                }
            }
            admin_log($pdo, 'homepage', 'Updated hero slide', $activePortalName.' · '.($title?:'Image-only slide'), 'hero_slide', $id);
            header('Location: hero.php?'.$portalQuery.'&edit='.$id.'&saved=1');
            exit;
        } else {
            array_unshift($data, $activePortal);
            $pdo->prepare('INSERT INTO hero_slides(portal_code,eyebrow,title,description,primary_label,primary_url,secondary_label,secondary_url,button_mode,theme,panel_type,panel_kicker,panel_status,panel_title,panel_description,panel_image,panel_media_id,background_image,background_media_id,background_position_x,background_position_y,display_mode,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($data);
            $newId = (int)$pdo->lastInsertId();
            normalize_hero_slide_order($pdo, $activePortal);
            admin_log($pdo, 'homepage', 'Created hero slide', $activePortalName.' · '.($title?:'Image-only slide'), 'hero_slide', $newId);
            header('Location: hero.php?'.$portalQuery.'&edit='.$newId.'&created=1');
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$slidesSt = $pdo->prepare("SELECT * FROM hero_slides WHERE UPPER(portal_code)=? AND LOWER(COALESCE(theme,''))<>'tala' AND LOWER(COALESCE(primary_url,'')) NOT LIKE '%tala.php%' AND LOWER(COALESCE(secondary_url,'')) NOT LIKE '%tala.php%' ORDER BY sort_order,id");
$slidesSt->execute([$activePortal]);
$slides = $slidesSt->fetchAll();
$promotionBySlide = [];
$promotionRequests = [];
if ($activePortal !== 'USC') {
    $promoSt = $pdo->prepare("SELECT pr.*,h.title,h.eyebrow,h.description,h.background_image,h.display_mode,h.is_active FROM hero_promotion_requests pr LEFT JOIN hero_slides h ON h.id=pr.source_slide_id AND UPPER(h.portal_code)=UPPER(pr.source_portal) WHERE UPPER(pr.source_portal)=? AND UPPER(pr.target_portal)='USC' ORDER BY pr.requested_at DESC,pr.id DESC LIMIT 50");
    $promoSt->execute([$activePortal]);
    $promotionRequests = $promoSt->fetchAll();
    foreach ($promotionRequests as $promo) {
        $sid = (int)$promo['source_slide_id'];
        if (!isset($promotionBySlide[$sid])) $promotionBySlide[$sid] = $promo;
    }
} else {
    $promotionRequests = $pdo->query("SELECT pr.*,h.title,h.eyebrow,h.description,h.background_image,h.display_mode,h.is_active FROM hero_promotion_requests pr LEFT JOIN hero_slides h ON h.id=pr.source_slide_id AND UPPER(h.portal_code)=UPPER(pr.source_portal) WHERE UPPER(pr.target_portal)='USC' ORDER BY FIELD(pr.status,'pending','needs_reapproval','approved','changes_requested','rejected','expired','revoked','withdrawn'),pr.requested_at DESC,pr.id DESC LIMIT 50")->fetchAll();
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM hero_slides WHERE id=? AND UPPER(portal_code)=?');
    $st->execute([(int)$_GET['edit'], $activePortal]);
    $edit = $st->fetch()?:null;
}
$newMode = isset($_GET['new']) && !$edit;
$showEditor = $edit || $newMode || $error !== '';
$libraryHeroImages = $showEditor?media_assets($pdo, $activePortal, 'image'):[];
$v = $edit?:[];
if ($error !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = array_merge($v, $_POST);
    $v['id'] = $id;
    $v['is_active'] = isset($_POST['is_active'])?1:0;
}
$storedPanelType = $v['panel_type'] ?? 'card';
$panelTypeValue = in_array($storedPanelType, ['esumbong', 'metrics', 'card'], true)?'card':(in_array($storedPanelType, ['none', 'image'], true)?$storedPanelType:'card');
$selectedPosition = 0;
if ($edit) {
    foreach ($slides as $index => $slideRow) {
        if ((int)$slideRow['id'] === (int)$edit['id']) {
            $selectedPosition = $index+1;
            break;
        }
    }
}
$bannerPositionX = max(0, min(100, (int)($v['background_position_x'] ?? 50)));
$bannerPositionY = max(0, min(100, (int)($v['background_position_y'] ?? 50)));
$heroSummary = [
'total' => count($slides), 'visible' => count(array_filter($slides, fn($s) => !empty($s['is_active']))),
'hidden' => count(array_filter($slides, fn($s) => empty($s['is_active']))),
'image_only' => count(array_filter($slides, fn($s) => ($s['display_mode'] ?? 'standard') === 'image_only')),
];

admin_header('Content Management', $activePortal === 'USC'?'USC digital content':$activePortalName.' content');
admin_content_tabs($pageView === 'promotions'?'hero-promotions':'hero.php');
?>
<div class="hero-workspace-page hero-reference-page">
    <?php if (!$showEditor && $pageView === 'slides') : ?>
        <div class="content-page-head hero-reference-head">
            <div>
                <span class="page-kicker">HOMEPAGE</span>
                <h2>Hero slider</h2>
                <p>Manage <?=e($activePortalName)?> homepage slides, messaging, media, calls to action, and visibility.</p>
            </div>
            <div class="hero-list-actions hero-list-actions--portal">
                <?php if (!$scope) : ?>
                    <form method="get" class="media-portal-switch hero-portal-switch">
                        <select name="portal" aria-label="Select homepage portal" onchange="this.form.submit()">
                            <?php foreach ($homepagePortals as $code => $name) : ?>
                                <option value="<?=e($code)?>" <?=$activePortal===$code?'selected':''?>><?=e($name)?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <span class="media-portal-chip"><?=e($activePortalName)?></span>
                <?php endif; ?>
                <a class="btn btn--soft hero-preview-button" href="<?=e($publicHomepage)?>?preview=<?=time()?>#home" target="_blank" rel="noopener">Preview website ↗</a>
                <a class="btn content-new-button" href="hero.php?<?=$portalQuery?>&new=1"><span>＋</span> New slide</a>
            </div>
        </div>
        <?php if (isset($_GET['saved'])) : ?>
            <div class="notice notice--success">
                Slide updated successfully.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['created'])) : ?>
            <div class="notice notice--success">
                New slide created successfully.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice--success">
                Slide removed.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['promotion_requested'])) : ?>
            <div class="notice notice--success">
                Promotion request sent to USC for review.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['promotion_withdrawn'])) : ?>
            <div class="notice notice--success">
                Promotion request withdrawn.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['promotion_approved'])) : ?>
            <div class="notice notice--success">
                Campus slide approved for the USC homepage.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['promotion_changes'])) : ?>
            <div class="notice notice--success">
                Revision request sent to the campus.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['promotion_rejected'])) : ?>
            <div class="notice notice--success">
                Promotion request declined.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['promotion_revoked'])) : ?>
            <div class="notice notice--success">
                Promoted slide removed from the USC homepage.
            </div>
        <?php endif; ?>
        <?php if (($_GET['promotion_error'] ?? '') === 'visible') : ?>
            <div class="notice notice--error">
                Make the slide visible before requesting USC homepage display.
            </div>
        <?php endif; ?>
        <?php if (($_GET['promotion_error'] ?? '') === 'source_hidden') : ?>
            <div class="notice notice--error">
                The campus slide is currently hidden and cannot be approved.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['promotion_error']) && in_array($_GET['promotion_error'], ['date', 'date_order'], true)) : ?>
            <div class="notice notice--error">
                Check the promotion start and end dates.
            </div>
        <?php endif; ?>
        <section class="panel publication-workspace hero-reference-workspace hero-overview-panel">
            <div class="publication-overview hero-overview" aria-label="Hero slider summary">
                <div class="publication-overview__stat">
                    <span>Total slides</span>
                    <strong><?=$heroSummary['total']?></strong>
                </div>
                <div class="publication-overview__stat">
                    <span>Visible</span>
                    <strong><?=$heroSummary['visible']?></strong>
                </div>
                <div class="publication-overview__stat">
                    <span>Hidden</span>
                    <strong><?=$heroSummary['hidden']?></strong>
                </div>
                <div class="publication-overview__stat">
                    <span>Image slides</span>
                    <strong><?=$heroSummary['image_only']?></strong>
                </div>
            </div>
        </section>
        <section class="panel publication-workspace hero-reference-workspace hero-list-panel">
            <div class="hero-order-feedback" id="heroOrderFeedback" aria-live="polite">
            </div>
            <div class="table-wrap publication-table-wrap publication-table-wrap--simple hero-table-wrap">
                <table class="publication-table publication-table--simple hero-management-table">
                    <colgroup>
                    <col class="hero-col-slide">
                    <col class="hero-col-mode">
                    <col class="hero-col-theme">
                    <col class="hero-col-status">
                    <col class="hero-col-order">
                    <col class="hero-col-actions">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Slide</th>
                            <th>Display</th>
                            <th>Theme</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th class="actions-heading">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="heroSortableBody" data-csrf="<?=e(csrf_token())?>">
                        <?php foreach ($slides as $index => $s) : ?>
                            <?php
                                      $slideTitle=($s['display_mode']??'standard')==='image_only'?'Image-only slide':($s['title']?:'Untitled slide');
                                      $slideSubtitle=($s['display_mode']??'standard')==='image_only'?'Full-width homepage image':($s['eyebrow']?:'No eyebrow provided');
                                      $themeLabel=$heroThemeLabels[$s['theme']??'green']??ucfirst((string)($s['theme']??'green'));
                                    ?>
                            <tr class="hero-sortable-row" data-slide-id="<?=e((string)$s['id'])?>">
                                <td class="cell-title publication-title-cell hero-slide-title-cell">
                                    <div class="publication-title-row">
                                        <button type="button" class="hero-drag-handle" draggable="true" aria-label="Move <?=e($slideTitle)?>. Drag to reorder or use arrow keys." title="Drag to reorder">
                                            <span class="hero-drag-grip" aria-hidden="true">⋮⋮</span>
                                            <span class="hero-row-number"><?=e((string)($index+1))?></span>
                                        </button>
                                        <strong><?=e($slideTitle)?></strong>
                                    </div>
                                    <span><?=e($slideSubtitle)?></span>
                                </td>
                                <td><span class="hero-type-badge"><?=($s['display_mode']??'standard')==='image_only'?'Image only':'Standard'?></span></td>
                                <td><span class="hero-theme-label"><?=e($themeLabel)?></span></td>
                                <td><span class="status-badge <?=$s['is_active']?'status-badge--published':'status-badge--draft'?>"><?=$s['is_active']?'Visible':'Hidden'?></span></td>
                                <td><span class="hero-order-value"><?=e((string)($index+1))?></span></td>
                                <td>
                                    <div class="table-actions publication-actions hero-row-actions">
                                        <a class="publication-action-edit" href="hero.php?<?=$portalQuery?>&edit=<?=$s['id']?>">Edit</a>
                                        <?php
                                        if ($activePortal !== 'USC') :
                                        $latestPromo = $promotionBySlide[(int)$s['id']] ?? null;
                                        $latestStatus = $latestPromo['status'] ?? '';
                                        ?>
                                        <?php if ($latestPromo) : ?>
                                            <span class="hero-promotion-mini-status hero-promotion-mini-status--<?=e($latestStatus)?>" title="<?=e($latestPromo['review_note']??'')?>"><?=e(hero_promotion_status_label($latestStatus))?></span>
                                        <?php endif; ?>
                                        <?php if ($latestStatus === 'pending') : ?>
                                            <form method="post" class="hero-inline-form">
                                                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                                <input type="hidden" name="action" value="withdraw_promotion">
                                                <input type="hidden" name="id" value="<?=$s['id']?>">
                                                <input type="hidden" name="request_id" value="<?=$latestPromo['id']?>">
                                                <input type="hidden" name="portal" value="<?=e($activePortal)?>">
                                                <button type="submit" class="hero-promotion-link">Withdraw</button>
                                            </form>
                                        <?php elseif ($latestStatus !== 'approved') : ?>
                                            <form method="post" class="hero-inline-form">
                                                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                                <input type="hidden" name="action" value="request_promotion">
                                                <input type="hidden" name="id" value="<?=$s['id']?>">
                                                <input type="hidden" name="portal" value="<?=e($activePortal)?>">
                                                <button type="submit" class="hero-promotion-link">Request USC display</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$slides) : ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state content-empty-state hero-list-empty">
                                    <strong>No homepage slides yet</strong>
                                    <span>Create the first slide to start building the homepage hero.</span>
                                    <a class="btn" href="hero.php?<?=$portalQuery?>&new=1">Create first slide</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php elseif (!$showEditor && $pageView === 'promotions') : ?>
    <div class="content-page-head hero-reference-head hero-promotion-page-head">
        <div>
            <span class="page-kicker">HOMEPAGE</span>
            <h2><?=$activePortal==='USC'?'Promotion requests':'USC homepage promotion'?></h2>
            <p><?=$activePortal==='USC'?'Review campus hero slides requesting placement on the main USC homepage.':'Track requests from '.$activePortalName.' to feature campus hero slides on the main USC homepage.'?></p>
        </div>
        <div class="hero-list-actions hero-list-actions--portal">
            <?php if (!$scope) : ?>
                <form method="get" class="media-portal-switch hero-portal-switch">
                    <input type="hidden" name="view" value="promotions">
                    <select name="portal" aria-label="Select portal promotion requests" onchange="this.form.submit()">
                        <?php foreach ($homepagePortals as $code => $name) : ?>
                            <option value="<?=e($code)?>" <?=$activePortal===$code?'selected':''?>><?=e($name)?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <span class="media-portal-chip"><?=e($activePortalName)?></span>
            <?php endif; ?>
            <a class="btn btn--soft" href="hero.php?<?=$portalQuery?>">Homepage slides</a>
        </div>
    </div>
    <?php if (isset($_GET['promotion_requested'])) : ?>
        <div class="notice notice--success">
            Promotion request sent to USC for review.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['promotion_withdrawn'])) : ?>
        <div class="notice notice--success">
            Promotion request withdrawn.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['promotion_approved'])) : ?>
        <div class="notice notice--success">
            Campus slide approved for the USC homepage.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['promotion_changes'])) : ?>
        <div class="notice notice--success">
            Revision request sent to the campus.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['promotion_rejected'])) : ?>
        <div class="notice notice--success">
            Promotion request declined.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['promotion_revoked'])) : ?>
        <div class="notice notice--success">
            Promoted slide removed from the USC homepage.
        </div>
    <?php endif; ?>
    <?php if (($_GET['promotion_error'] ?? '') === 'visible') : ?>
        <div class="notice notice--error">
            Make the slide visible before requesting USC homepage display.
        </div>
    <?php endif; ?>
    <?php if (($_GET['promotion_error'] ?? '') === 'source_hidden') : ?>
        <div class="notice notice--error">
            The campus slide is currently hidden and cannot be approved.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['promotion_error']) && in_array($_GET['promotion_error'], ['date', 'date_order'], true)) : ?>
        <div class="notice notice--error">
            Check the promotion start and end dates.
        </div>
    <?php endif; ?>
    <section class="panel hero-promotion-panel hero-promotion-panel--standalone">
        <div class="hero-promotion-panel__head">
            <div>
                <span class="page-kicker"><?=$activePortal==='USC'?'CAMPUS REQUESTS':'REQUEST HISTORY'?></span>
                <h3><?=$activePortal==='USC'?'USC homepage promotion':'Requests to USC'?></h3>
                <p><?=$activePortal==='USC'?'Approve, request changes, reject, or revoke campus hero placements.':'Requests remain linked to the original campus slide. Edit the slide from Homepage when USC requests changes.'?></p>
            </div>
            <?php if ($activePortal === 'USC') : ?>
                <span class="hero-promotion-count"><?=count(array_filter($promotionRequests,fn($r)=>in_array($r['status'],['pending','needs_reapproval'],true)))?> awaiting review</span>
            <?php else: ?>
                <span class="hero-promotion-count"><?=count($promotionRequests)?> request<?=count($promotionRequests)===1?'':'s'?></span>
            <?php endif; ?>
        </div>
        <?php if ($promotionRequests) : ?>
            <div class="hero-promotion-list">
                <?php foreach($promotionRequests as $pr):
                      $prStatus=(string)$pr['status']; $isAwaiting=in_array($prStatus,['pending','needs_reapproval'],true); $isApproved=$prStatus==='approved';
                      $sourcePortal=strtoupper(trim((string)($pr['source_portal']??$activePortal)));
                      if(!isset($homepagePortals[$sourcePortal]) || $sourcePortal==='USC') $sourcePortal=$activePortal==='USC'?'USC':$activePortal;
                      $sourceName=portal_display_name($sourcePortal);
                      $prTitle=$pr['title']?:($pr['eyebrow']?:'Hero slide');
                    ?>
                <article class="hero-promotion-request <?=$isAwaiting?'is-awaiting':''?>" id="promotion-<?=$pr['id']?>">
                    <div class="hero-promotion-request__summary">
                        <div class="hero-promotion-request__source">
                            <span><?=e($sourceName)?></span><strong><?=e($prTitle)?></strong><small>Requested <?=e(date('M j, Y · g:i A',strtotime($pr['requested_at'])))?></small>
                        </div>
                        <div class="hero-promotion-request__top-actions">
                            <?php if (!empty($pr['source_slide_id'])) : ?>
                                <a href="../<?=e(hero_portal_home_path($sourcePortal))?>?hero_slide=<?=$pr['source_slide_id']?>&preview=<?=time()?>#home" target="_blank" rel="noopener">Preview source ↗</a>
                            <?php endif; ?>
                            <span class="hero-promotion-status hero-promotion-status--<?=e($prStatus)?>"><?=e(hero_promotion_status_label($prStatus))?></span>
                        </div>
                    </div>
                    <?php if (!empty($pr['review_note'])) : ?>
                        <p class="hero-promotion-note"><strong>USC note:</strong> <?=e($pr['review_note'])?></p>
                    <?php endif; ?>
                    <?php if ($activePortal === 'USC' && $isAwaiting) : ?>
                        <form method="post" class="hero-promotion-review-form">
                            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                            <input type="hidden" name="view" value="promotions">
                            <input type="hidden" name="action" value="review_promotion">
                            <input type="hidden" name="request_id" value="<?=$pr['id']?>">
                            <input type="hidden" name="portal" value="USC">
                            <label>
                                Display from
                                <input type="datetime-local" name="display_from" value="<?=e(($prStatus==='needs_reapproval' && !empty($pr['display_from']))?date('Y-m-d\\TH:i',strtotime($pr['display_from'])):date('Y-m-d\\TH:i'))?>">
                            </label>
                            <label>
                                Display until <span>Optional</span>
                                <input type="datetime-local" name="display_until" value="<?=e(($prStatus==='needs_reapproval' && !empty($pr['display_until']))?date('Y-m-d\\TH:i',strtotime($pr['display_until'])):'')?>">
                            </label>
                            <label>
                                USC position
                                <input type="number" name="display_order" min="1" value="<?=$prStatus==='needs_reapproval'?max(1,(int)($pr['display_order']??1)):count($slides)+count(array_filter($promotionRequests,fn($x)=>$x['status']==='approved'))+1?>">
                            </label>
                            <label class="hero-promotion-review-note">
                                Review note <span>Optional</span>
                                <input type="text" name="review_note" maxlength="500" placeholder="Add a note for the campus">
                            </label>
                            <div class="hero-promotion-review-actions">
                                <button class="btn btn--small" type="submit" name="decision" value="approve">Approve</button>
                                <button class="btn btn--soft btn--small" type="submit" name="decision" value="changes">Request changes</button>
                                <button class="btn btn--danger-soft btn--small" type="submit" name="decision" value="reject">Reject</button>
                            </div>
                        </form>
                    <?php elseif ($activePortal === 'USC' && $isApproved) : ?>
                        <div class="hero-promotion-approved-meta">
                            <span>Position <strong><?=e((string)$pr['display_order'])?></strong></span><span>Starts <strong><?=e($pr['display_from']?date('M j, Y g:i A',strtotime($pr['display_from'])):'Immediately')?></strong></span><span>Ends <strong><?=e($pr['display_until']?date('M j, Y g:i A',strtotime($pr['display_until'])):'No end date')?></strong></span>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                <input type="hidden" name="view" value="promotions">
                                <input type="hidden" name="action" value="revoke_promotion">
                                <input type="hidden" name="request_id" value="<?=$pr['id']?>">
                                <input type="hidden" name="portal" value="USC">
                                <button class="btn btn--danger-soft btn--small" type="submit">Revoke</button>
                            </form>
                        </div>
                    <?php elseif ($activePortal !== 'USC') : ?>
                        <div class="hero-promotion-campus-actions">
                            <?php if ($prStatus === 'pending') : ?>
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                                    <input type="hidden" name="view" value="promotions">
                                    <input type="hidden" name="action" value="withdraw_promotion">
                                    <input type="hidden" name="request_id" value="<?=$pr['id']?>">
                                    <input type="hidden" name="id" value="<?=$pr['source_slide_id']?>">
                                    <input type="hidden" name="portal" value="<?=e($activePortal)?>">
                                    <button class="btn btn--soft btn--small" type="submit">Withdraw request</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($pr['source_slide_id'])) : ?>
                                <a class="btn btn--soft btn--small" href="hero.php?<?=$portalQuery?>&edit=<?=$pr['source_slide_id']?>">Edit source slide</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state content-empty-state hero-promotion-empty">
            <strong><?=$activePortal==='USC'?'No campus promotion requests':'No promotion requests yet'?></strong><span><?=$activePortal==='USC'?'Campus requests will appear here for USC review.':'Use “Request USC display” from a campus homepage slide when you want it considered for the main USC homepage.'?></span>
            <?php if ($activePortal !== 'USC') : ?>
                <a class="btn" href="hero.php?<?=$portalQuery?>">View homepage slides</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php else: ?>
<div class="content-page-head hero-reference-head hero-editor-page-head hero-editor-page-head--redesigned">
    <div>
        <span class="page-kicker"><?=e(strtoupper($activePortalName))?> · HOMEPAGE</span>
        <h2><?=$edit?'Edit slide':'New slide'?></h2>
        <p><?=$edit?'Refine this '.$activePortalName.' homepage slide, review its presentation, and save when ready.':'Build a homepage slide for '.$activePortalName.' with a clear message and purposeful actions.'?></p>
    </div>
    <div class="hero-list-actions hero-editor-top-actions">
        <span class="media-portal-chip"><?=e($activePortalName)?></span>
        <a class="btn btn--soft hero-preview-button" href="<?=e($publicHomepage)?>?<?= $edit ? 'hero_slide='.(int)$edit['id'].'&' : '' ?>preview=<?=time()?>#home" target="_blank" rel="noopener">Preview website ↗</a>
        <a class="btn btn--soft" href="hero.php?<?=$portalQuery?>">Back to slides</a>
    </div>
</div>
<?php if (isset($_GET['saved'])) : ?>
    <div class="notice notice--success">
        Slide updated successfully.
    </div>
<?php endif; ?>
<?php if (isset($_GET['created'])) : ?>
    <div class="notice notice--success">
        New slide created successfully.
    </div>
<?php endif; ?>
<?php if ($error) : ?>
    <div class="notice notice--error">
        <?=e($error)?>
    </div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="admin-form hero-editor-form hero-editor-form--redesigned <?=$edit?'is-editing':'is-creating'?>" id="heroEditor" data-unsaved-warning="1">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="id" value="<?=e((string)($v['id']??0))?>">
    <input type="hidden" name="portal" value="<?=e($activePortal)?>">
    <div class="hero-editor-layout">
        <div class="hero-editor-main" role="region" aria-label="Slide editor">
            <section class="hero-editor-section hero-editor-section--redesigned hero-section-presentation">
                <div class="hero-editor-section__head hero-editor-section__head--numbered">
                    <span class="hero-section-number">1</span>
                    <div>
                        <strong>Slide presentation</strong>
                        <span>Set the layout, visual theme, and display order.</span>
                    </div>
                </div>
                <div class="hero-form-grid hero-form-grid--presentation">
                    <label>
                        Display mode
                        <select name="display_mode" id="displayMode">
                            <option value="standard" <?=($v['display_mode']??'standard')==='standard'?'selected':''?>>Standard</option>
                            <option value="image_only" <?=($v['display_mode']??'')==='image_only'?'selected':''?>>Image Only</option>
                        </select>
                        <small class="hero-field-help">Standard can combine text, a background photo, and right-side content. Image Only uses one full banner image.</small>
                    </label>
                    <label>
                        Color theme
                        <select name="theme" id="themeSelect">
                            <?php foreach ($heroThemeGroups as $groupLabel => $groupThemes) : ?>
                                <optgroup label="<?=e($groupLabel)?>">
                                <?php foreach ($groupThemes as $themeValue => $themeName) : ?>
                                    <option value="<?=e($themeValue)?>" <?=($v['theme']??'green')===$themeValue?'selected':''?>><?=e($themeName)?></option>
                                <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <small class="hero-field-help">Choose a base color, or select None to remove color tinting. With a photo, only a neutral contrast shade is kept for readable text.</small>
                    </label>
                    <input type="hidden" name="sort_order" id="sortOrder" value="<?=e((string)($v['sort_order']??(count($slides)+1)))?>">
                    <div class="hero-position-readonly" aria-label="Slide position">
                        <span>Position</span>
                        <strong id="editorPositionLabel"><?=$edit?e((string)$selectedPosition):e((string)(count($slides)+1))?></strong>
                        <small>Reorder slides by dragging them on the Hero slider list.</small>
                    </div>
                </div>
            </section>
            <section class="hero-editor-section hero-editor-section--redesigned hero-section-background">
                <div class="hero-editor-section__head hero-editor-section__head--numbered hero-background-head">
                    <span class="hero-section-number">2</span>
                    <div class="hero-background-head__copy">
                        <strong id="backgroundSectionTitle">Hero background</strong>
                        <span id="backgroundSectionHelp">Optional for Standard mode. Use a wide photo behind the text and right-side card, or use it as the complete slide in Image Only mode. Drag the photo itself to adjust the crop.</span>
                    </div>
                    <button
                    type="button"
                    class="hero-background-remove <?=empty($v['background_image'])?'is-hidden':''?>"
                    id="removeBackgroundButton"
                    aria-label="Remove hero background">Remove background</button>
            </div>
            <div class="hero-banner-positioner hero-banner-positioner--clean <?=empty($v['background_image'])?'is-empty':''?>" id="bannerPositioner">
                <div class="hero-banner-position-stage" id="bannerPositionStage" aria-label="Drag banner image to reposition it">
                    <img
                    id="bannerPositionImage"
                    src="<?=!empty($v['background_image'])?e('../'.$v['background_image']):''?>"
                    alt="Banner positioning preview"
                    style="object-position:<?=$bannerPositionX?>% <?=$bannerPositionY?>%;"
                    <?=empty($v['background_image'])?'hidden':''?>
                    draggable="false">
                    <div class="hero-banner-position-empty" id="bannerPositionEmpty" <?=!empty($v['background_image'])?'hidden':''?>>
                        <strong>Choose a banner image</strong>
                        <span>Your selected image will appear here so you can drag it into position before saving.</span>
                    </div>
                    <div class="hero-banner-drag-hint" id="bannerDragHint" <?=empty($v['background_image'])?'hidden':''?>>
                        <span aria-hidden="true">↕</span> Drag to reposition
                    </div>
                </div>
                <input type="hidden" name="background_position_x" id="backgroundPositionX" value="<?=$bannerPositionX?>">
                <input type="hidden" name="background_position_y" id="backgroundPositionY" value="<?=$bannerPositionY?>">
                <input type="hidden" name="remove_background_image" id="removeBackgroundImage" value="1" disabled>
                <input type="hidden" name="background_media_id" id="backgroundMediaId" value="">
            </div>
            <label class="upload-field hero-upload-field hero-upload-field--large hero-background-upload-bar">
                <span class="hero-upload-title"><?=!empty($v['background_image'])?'Replace background':'Choose background'?></span>
                <span class="hero-upload-copy">JPG, PNG or WebP · max 8 MB</span>
                <input type="file" name="background_image" id="backgroundImageInput" accept="image/jpeg,image/png,image/webp">
                <small class="hero-upload-live-status" id="backgroundUploadStatus" aria-live="polite"></small>
            </label>
            <div class="library-picker library-picker--hero" data-hero-library="background">
                <div class="library-picker__head">
                    <div>
                        <strong>Or choose from Media Library</strong><span>Reuse an image already uploaded for <?=e($activePortalName)?>.</span>
                    </div>
                    <a href="media.php?portal=<?=e($activePortal)?>" target="_blank" rel="noopener">Manage library ↗</a>
                </div>
                <?php if ($libraryHeroImages) : ?>
                    <div class="library-picker__grid">
                        <?php foreach ($libraryHeroImages as $asset) : ?>
                            <button type="button" class="library-asset library-asset--button <?=((int)($v['background_media_id']??0)===(int)$asset['id'])?'is-selected':''?>" data-hero-media-target="background" data-media-id="<?=$asset['id']?>" data-media-src="../<?=e($asset['file_path'])?>"><img src="../<?=e($asset['file_path'])?>" alt="<?=e($asset['alt_text']?:$asset['original_name'])?>"><span><?=e($asset['original_name']?:'Library image')?></span></button>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <div class="library-picker__empty">
                        No reusable images for <?=e($activePortalName)?> yet. <a href="media.php?portal=<?=e($activePortal)?>">Upload to Media Library</a>.
                    </div>
                <?php endif ?>
            </div>
        </section>
        <section class="hero-editor-section hero-editor-section--redesigned standard-fields hero-section-content <?=($v['display_mode']??'standard')==='image_only'?'is-hidden':''?>">
            <div class="hero-editor-section__head hero-editor-section__head--numbered">
                <span class="hero-section-number">3</span>
                <div>
                    <strong>Main content</strong>
                    <span>Write the main message students should see first.</span>
                </div>
            </div>
            <div class="hero-form-grid hero-form-grid--main">
                <label>
                    Eyebrow
                    <input name="eyebrow" id="heroEyebrow" maxlength="160" value="<?=e($v['eyebrow']??'')?>" placeholder="Student Services">
                    <small class="hero-field-help">Optional short label shown above the headline.</small>
                </label>
                <label>
                    Headline
                    <input name="title" id="heroTitle" maxlength="220" value="<?=e($v['title']??'')?>" placeholder="Add a clear and engaging headline">
                    <small class="hero-field-help">Keep the headline short, clear, and easy to scan.</small>
                </label>
                <label class="hero-field-description">
                    Description
                    <textarea name="description" id="heroDescription" rows="4" maxlength="420" placeholder="Add a brief message that supports the headline"><?=e($v['description']??'')?></textarea>
                    <span class="hero-textarea-meta"><small>Use one or two short sentences to add context or important details.</small><small><span id="descriptionCount">0</span> characters</small></span>
                </label>
            </div>
        </section>
        <section class="hero-editor-section hero-editor-section--redesigned standard-fields hero-section-buttons <?=($v['display_mode']??'standard')==='image_only'?'is-hidden':''?>">
            <div class="hero-editor-section__head hero-editor-section__head--numbered">
                <span class="hero-section-number">4</span>
                <div>
                    <strong>Calls to action</strong>
                    <span>Add up to two buttons that guide visitors to the next step.</span>
                </div>
            </div>
            <label class="hero-full-field hero-button-mode-field">
                Button display
                <select name="button_mode" id="buttonMode">
                    <?php foreach ($heroButtonModes as $modeValue => $modeLabel) : ?>
                        <option value="<?=e($modeValue)?>" <?=($v['button_mode']??'both')===$modeValue?'selected':''?>><?=e($modeLabel)?></option>
                    <?php endforeach; ?>
                </select>
                <small class="hero-field-help">Choose whether the slide shows two buttons, one button, or no buttons.</small>
            </label>
            <div class="hero-button-grid hero-button-grid--pairs" id="heroButtonGrid">
                <div class="hero-action-pair" id="primaryActionFields">
                    <div class="hero-action-pair__title">
                        <strong>Primary action</strong><span>Main button</span>
                    </div>
                    <label>
                        Button label
                        <input name="primary_label" id="primaryLabel" maxlength="80" value="<?=e($v['primary_label']??'')?>" placeholder="Learn more">
                    </label>
                    <label>
                        Link
                        <input name="primary_url" id="primaryUrl" maxlength="500" value="<?=e($v['primary_url']??'')?>" placeholder="Enter page or website link">
                    </label>
                </div>
                <div class="hero-action-pair" id="secondaryActionFields">
                    <div class="hero-action-pair__title">
                        <strong>Secondary action</strong><span>Optional second button</span>
                    </div>
                    <label>
                        Button label
                        <input name="secondary_label" id="secondaryLabel" maxlength="80" value="<?=e($v['secondary_label']??'')?>" placeholder="View details">
                    </label>
                    <label>
                        Link
                        <input name="secondary_url" id="secondaryUrl" maxlength="500" value="<?=e($v['secondary_url']??'')?>" placeholder="Enter page or website link">
                    </label>
                </div>
            </div>
            <div class="hero-no-buttons-note is-hidden" id="noButtonsNote">
                No call-to-action buttons will be displayed on this slide.
            </div>
        </section>
        <section class="hero-editor-section hero-editor-section--redesigned standard-fields hero-section-rightside <?=($v['display_mode']??'standard')==='image_only'?'is-hidden':''?>">
            <div class="hero-editor-section__head hero-editor-section__head--numbered">
                <span class="hero-section-number">5</span>
                <div>
                    <strong>Right-side content</strong>
                    <span>Choose what should appear on the right side of the slide.</span>
                </div>
            </div>
            <label class="hero-full-field">
                Content style
                <select name="panel_type" id="panelType">
                    <option value="card" <?=$panelTypeValue==='card'?'selected':''?>>Card</option>
                    <option value="none" <?=$panelTypeValue==='none'?'selected':''?>>None</option>
                    <option value="image" <?=$panelTypeValue==='image'?'selected':''?>>Image</option>
                </select>
            </label>
            <div id="panelImageFields" class="hero-media-manager hero-panel-media-manager <?=!empty($v['panel_image'])?'has-current-image':'no-current-image'?> <?=$panelTypeValue==='image'?'':'is-hidden'?>">
                <?php if (!empty($v['panel_image'])) : ?>
                    <div class="hero-image-preview hero-image-preview--panel">
                        <img src="../<?=e($v['panel_image'])?>" alt="Current right-side image" onerror="this.closest('.hero-image-preview').classList.add('preview-error')">
                    </div>
                <?php endif; ?>
                <label class="upload-field hero-upload-field hero-upload-field--large hero-panel-upload-bar">
                    <span class="hero-upload-title"><?=!empty($v['panel_image'])?'Replace image':'Choose image'?></span>
                    <span class="hero-upload-copy">JPG, PNG or WebP · max 8 MB</span>
                    <input type="file" name="panel_image" id="panelImageInput" accept="image/jpeg,image/png,image/webp">
                </label>
                <input type="hidden" name="panel_media_id" id="panelMediaId" value="">
                <div class="library-picker library-picker--hero" data-hero-library="panel">
                    <div class="library-picker__head">
                        <div>
                            <strong>Or choose from Media Library</strong><span>Reuse an <?=e($activePortalName)?> image for the right-side visual.</span>
                        </div>
                    </div>
                    <?php if ($libraryHeroImages) : ?>
                        <div class="library-picker__grid">
                            <?php foreach ($libraryHeroImages as $asset) : ?>
                                <button type="button" class="library-asset library-asset--button <?=((int)($v['panel_media_id']??0)===(int)$asset['id'])?'is-selected':''?>" data-hero-media-target="panel" data-media-id="<?=$asset['id']?>" data-media-src="../<?=e($asset['file_path'])?>"><img src="../<?=e($asset['file_path'])?>" alt="<?=e($asset['alt_text']?:$asset['original_name'])?>"><span><?=e($asset['original_name']?:'Library image')?></span></button>
                            <?php endforeach ?>
                        </div>
                    <?php else: ?>
                        <div class="library-picker__empty">
                            No reusable images for <?=e($activePortalName)?> yet.
                        </div>
                    <?php endif ?>
                </div>
            </div>
            <div id="panelTextFields" class="hero-panel-fields hero-panel-fields--card <?=$panelTypeValue==='card'?'':'is-hidden'?>">
                <label>
                    Card label
                    <input name="panel_kicker" id="panelKicker" maxlength="120" value="<?=e($v['panel_kicker']??'')?>" placeholder="Student Update">
                </label>
                <label>
                    Card status
                    <input name="panel_status" id="panelStatus" maxlength="120" value="<?=e($v['panel_status']??'')?>" placeholder="Now available">
                </label>
                <label>
                    Card heading
                    <input name="panel_title" id="panelTitle" maxlength="160" value="<?=e($v['panel_title']??'')?>" placeholder="Important information">
                </label>
                <label class="hero-panel-description">
                    Card description
                    <textarea name="panel_description" id="panelDescription" rows="3" maxlength="260" placeholder="Add a short supporting detail for this slide."><?=e($v['panel_description']??'')?></textarea>
                </label>
            </div>
        </section>
    </div>
    <aside class="hero-editor-sidebar" aria-label="Slide settings and preview">
        <section class="hero-side-card hero-side-card--settings">
            <div class="hero-side-card__head">
                <div>
                    <span class="hero-side-eyebrow">Publishing</span>
                    <h3>Slide settings</h3>
                </div>
                <?php if ($edit) : ?>
                    <span class="hero-editor-position">Slide <?=$selectedPosition?> of <?=count($slides)?></span>
                <?php endif; ?>
            </div>
            <label class="hero-visibility hero-visibility--card">
                <input type="checkbox" name="is_active" id="heroVisibility" value="1" <?=!isset($v['is_active'])||$v['is_active']?'checked':''?>>
                <span class="hero-toggle" aria-hidden="true"></span>
                <span class="hero-visibility-copy"><strong>Visible on homepage</strong><small>Turn this off to keep the slide hidden from visitors.</small></span>
            </label>
            <div class="hero-publish-meta">
                <div>
                    <span>Status</span>
                    <strong id="summaryStatus"><?=(!isset($v['is_active'])||$v['is_active'])?'Visible':'Hidden'?></strong>
                </div>
                <div>
                    <span>Position</span>
                    <strong id="summaryOrder"><?=$edit?e((string)$selectedPosition):e((string)(count($slides)+1))?></strong>
                </div>
            </div>
        </section>
        <section class="hero-side-card hero-side-card--preview">
            <div class="hero-side-card__head hero-side-card__head--simple">
                <div>
                    <span class="hero-side-eyebrow">Live preview</span>
                    <h3>Slide snapshot</h3>
                </div>
            </div>
            <div class="hero-mini-preview hero-mini-preview--green" id="heroMiniPreview" aria-live="polite" data-current-bg-src="<?=!empty($v['background_image'])?e('../'.$v['background_image']):''?>">
                <div class="hero-mini-preview__content" id="miniStandardContent">
                    <span class="hero-mini-preview__eyebrow" id="previewEyebrow"><?=e($v['eyebrow']??$portalEyebrow)?></span>
                    <strong class="hero-mini-preview__title" id="previewTitle"><?=e($v['title']??'Your slide headline')?></strong>
                    <p id="previewDescription"><?=e($v['description']??'Add a short message to support the headline.')?></p>
                    <div class="hero-mini-preview__buttons" id="previewButtons">
                        <span id="previewPrimary"><?=e($v['primary_label']??'Learn more')?></span>
                        <span id="previewSecondary" class="is-secondary"><?=e($v['secondary_label']??'View details')?></span>
                    </div>
                </div>
                <div class="hero-mini-preview__panel" id="previewPanel" data-current-panel-src="<?=!empty($v['panel_image'])?e('../'.$v['panel_image']):''?>">
                    <img class="hero-mini-preview__panel-image" id="miniPanelImage" src="<?=!empty($v['panel_image'])?e('../'.$v['panel_image']):''?>" alt="Right-side image preview" <?=empty($v['panel_image'])?'hidden':''?>>
                    <span id="previewPanelKicker"><?=e($v['panel_kicker']??'STUDENT UPDATE')?></span>
                    <strong id="previewPanelTitle"><?=e($v['panel_title']??'Important information')?></strong>
                    <small id="previewPanelDescription"><?=e($v['panel_description']??'Add a short supporting detail for this slide.')?></small>
                </div>
                <div class="hero-mini-preview__image-only" id="miniImageOnlyContent" data-current-src="<?=!empty($v['background_image'])?e('../'.$v['background_image']):''?>">
                    <img id="miniBannerImage" src="<?=!empty($v['background_image'])?e('../'.$v['background_image']):''?>" alt="Banner preview" style="object-position:<?=$bannerPositionX?>% <?=$bannerPositionY?>%;" <?=empty($v['background_image'])?'hidden':''?>>
                    <div class="hero-mini-preview__image-empty" id="miniBannerEmpty" <?=!empty($v['background_image'])?'hidden':''?>>
                        <span>IMAGE ONLY</span>
                        <strong>Full banner slide</strong>
                        <small>Choose a banner image to preview the public slide.</small>
                    </div>
                </div>
            </div>
            <p class="hero-preview-note">This compact preview reflects the saved or newly selected banner, content, visibility, and display mode. Preview website opens this slide directly after it has been saved.</p>
        </section>
        <section class="hero-side-card hero-side-card--actions">
            <div class="hero-save-state" id="heroSaveState">
                <span class="hero-save-state__dot" aria-hidden="true"></span>
                <div>
                    <strong id="heroSaveStateTitle"><?=$edit?'Ready to update':'Ready to create'?></strong><small id="heroSaveStateCopy">Review the preview and settings before saving.</small>
                </div>
            </div>
            <div class="hero-sidebar-actions">
                <a class="btn btn--soft" href="hero.php?<?=$portalQuery?>">Cancel</a>
                <button class="btn" type="submit" name="action" value="save"><?=$edit?'Save changes':'Create slide'?></button>
            </div>
            <?php if ($edit) : ?>
                <div class="hero-danger-row">
                    <span>Need to remove this slide?</span>
                    <button class="hero-delete-button hero-delete-button--inline" type="submit" name="action" value="delete" data-confirm="Remove this slide permanently? This cannot be undone.">Delete slide</button>
                </div>
            <?php endif; ?>
        </section>
    </aside>
</div>
</form>
<?php endif; ?>
</div>
<script>
(function setupHeroMediaLibrary() {
    const bgInput = document.getElementById('backgroundMediaId');
    const panelInput = document.getElementById('panelMediaId');
    document.querySelectorAll('[data-hero-media-target]').forEach(button => button.addEventListener('click', () => {
        const target = button.dataset.heroMediaTarget, src = button.dataset.mediaSrc, id = button.dataset.mediaId;
        if (target === 'background') {
            if (bgInput) bgInput.value = id;
            const image = document.getElementById('bannerPositionImage'); const empty = document.getElementById('bannerPositionEmpty'); const hint = document.getElementById('bannerDragHint');
            if (image) { image.src = src; image.hidden = false; } if (empty) empty.hidden = true; if (hint) hint.hidden = false;
            document.getElementById('bannerPositioner')?.classList.remove('is-empty'); document.getElementById('removeBackgroundButton')?.classList.remove('is-hidden');
            const mini = document.getElementById('miniBannerImage'); if (mini) { mini.src = src; mini.hidden = false; } document.getElementById('miniBannerEmpty')?.setAttribute('hidden', '');
            const preview = document.getElementById('heroMiniPreview'); if (preview) preview.dataset.currentBgSrc = src;
            const file = document.getElementById('backgroundImageInput'); if (file) file.value = '';
            const remove = document.getElementById('removeBackgroundImage'); if (remove) remove.disabled = true;
        } else {
            if (panelInput) panelInput.value = id;
            const mini = document.getElementById('miniPanelImage'); if (mini) { mini.src = src; mini.hidden = false; }
            const panel = document.getElementById('previewPanel'); if (panel) panel.dataset.currentPanelSrc = src;
            const file = document.getElementById('panelImageInput'); if (file) file.value = '';
            const holder = document.getElementById('panelImageFields'); if (holder) { holder.classList.add('has-current-image'); holder.classList.remove('no-current-image'); }
        }
        button.closest('.library-picker')?.querySelectorAll('.library-asset--button').forEach(x => x.classList.toggle('is-selected', x === button));
        if (typeof syncHeroEditor === 'function') syncHeroEditor();
        document.querySelector('form[data-unsaved-warning="1"]')?.setAttribute('data-dirty', '1');
    }));
})();

(function setupHeroReorder() {
    const body = document.getElementById('heroSortableBody');
    if (!body) return;

    const feedback = document.getElementById('heroOrderFeedback');
    let draggingRow = null;
    let snapshot = [];
    let saveTimer = null;

    const rows = () => Array.from(body.querySelectorAll('.hero-sortable-row'));
    const setFeedback = (message, state = '') => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.dataset.state = state;
    };
    const refreshPositions = () => {
        rows().forEach((row, index) => {
            row.querySelector('.hero-row-number')?.replaceChildren(document.createTextNode(String(index + 1)));
            row.querySelector('.hero-order-value')?.replaceChildren(document.createTextNode(String(index + 1)));
        });
    };
    const restoreSnapshot = () => {
        snapshot.forEach(id => {
            const row = rows().find(item => item.dataset.slideId === String(id));
            if (row) body.appendChild(row);
        });
        refreshPositions();
    };
    const saveOrder = async () => {
        clearTimeout(saveTimer);
        const orderedIds = rows().map(row => row.dataset.slideId);
        setFeedback('Saving order…', 'saving');
        const data = new FormData();
        data.append('csrf', body.dataset.csrf || '');
        data.append('action', 'reorder');
        data.append('portal', <?=json_encode($activePortal)?>);
        orderedIds.forEach(id => data.append('order[]', id));
        try {
            const response = await fetch('hero.php', { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json().catch(() => ({ ok: false, message: 'Unable to read the server response.' }));
            if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to save slide order.');
            snapshot = orderedIds.slice();
            setFeedback('Order saved', 'saved');
            saveTimer = setTimeout(() => setFeedback('', ''), 1800);
        } catch (error) {
            restoreSnapshot();
            setFeedback(error.message || 'Order could not be saved.', 'error');
        }
    };
    const moveRow = (row, target, before) => {
        if (!row || !target || row === target) return false;
        body.insertBefore(row, before ? target : target.nextSibling);
        refreshPositions();
        return true;
    };

    snapshot = rows().map(row => row.dataset.slideId);

    body.addEventListener('dragstart', event => {
        const handle = event.target.closest('.hero-drag-handle');
        if (!handle) return;
        draggingRow = handle.closest('.hero-sortable-row');
        if (!draggingRow) return;
        snapshot = rows().map(row => row.dataset.slideId);
        draggingRow.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', draggingRow.dataset.slideId || '');
    });

    body.addEventListener('dragover', event => {
        if (!draggingRow) return;
        const target = event.target.closest('.hero-sortable-row');
        if (!target || target === draggingRow) return;
        event.preventDefault();
        const rect = target.getBoundingClientRect();
        const before = event.clientY < rect.top + rect.height / 2;
        rows().forEach(row => row.classList.remove('is-drop-before', 'is-drop-after'));
        target.classList.add(before ? 'is-drop-before' : 'is-drop-after');
    });

    body.addEventListener('drop', event => {
        if (!draggingRow) return;
        const target = event.target.closest('.hero-sortable-row');
        if (!target || target === draggingRow) return;
        event.preventDefault();
        const rect = target.getBoundingClientRect();
        const before = event.clientY < rect.top + rect.height / 2;
        if (moveRow(draggingRow, target, before)) saveOrder();
    });

    body.addEventListener('dragend', () => {
        rows().forEach(row => row.classList.remove('is-dragging', 'is-drop-before', 'is-drop-after'));
        draggingRow = null;
    });

    body.addEventListener('keydown', event => {
        const handle = event.target.closest('.hero-drag-handle');
        if (!handle || !['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
        const row = handle.closest('.hero-sortable-row');
        if (!row) return;
        event.preventDefault();
        snapshot = rows().map(item => item.dataset.slideId);
        let changed = false;
        if (event.key === 'ArrowUp' && row.previousElementSibling) {
            body.insertBefore(row, row.previousElementSibling); changed = true;
        } else if (event.key === 'ArrowDown' && row.nextElementSibling) {
            body.insertBefore(row.nextElementSibling, row); changed = true;
        } else if (event.key === 'Home' && row !== body.firstElementChild) {
            body.insertBefore(row, body.firstElementChild); changed = true;
        } else if (event.key === 'End' && row !== body.lastElementChild) {
            body.appendChild(row); changed = true;
        }
        if (changed) { refreshPositions(); handle.focus(); saveOrder(); }
    });
})();

const displayMode = document.getElementById('displayMode');
const panelType = document.getElementById('panelType');
const themeSelect = document.getElementById('themeSelect');
const buttonMode = document.getElementById('buttonMode');
const sortOrder = document.getElementById('sortOrder');
const visibility = document.getElementById('heroVisibility');

const backgroundImageInput = document.getElementById('backgroundImageInput');
const backgroundUploadStatus = document.getElementById('backgroundUploadStatus');
const panelImageInput = document.getElementById('panelImageInput');

const bannerPositioner = document.getElementById('bannerPositioner');
const bannerPositionStage = document.getElementById('bannerPositionStage');
const bannerPositionImage = document.getElementById('bannerPositionImage');
const bannerPositionEmpty = document.getElementById('bannerPositionEmpty');
const bannerDragHint = document.getElementById('bannerDragHint');
const backgroundPositionX = document.getElementById('backgroundPositionX');
const backgroundPositionY = document.getElementById('backgroundPositionY');
const removeBackgroundButton = document.getElementById('removeBackgroundButton');
const removeBackgroundImage = document.getElementById('removeBackgroundImage');

const miniImageOnlyContent = document.getElementById('miniImageOnlyContent');
const miniBannerImage = document.getElementById('miniBannerImage');
const miniBannerEmpty = document.getElementById('miniBannerEmpty');
const miniPanelImage = document.getElementById('miniPanelImage');

let bannerPreviewObjectUrl = null;
let panelPreviewObjectUrl = null;
let bannerDragState = null;

function clampBannerPosition(value) {
    return Math.max(0, Math.min(100, Number.isFinite(value) ? value : 50));
}

function releaseBannerObjectUrl() {
    if (bannerPreviewObjectUrl) {
        URL.revokeObjectURL(bannerPreviewObjectUrl);
        bannerPreviewObjectUrl = null;
    }
}

function releasePanelObjectUrl() {
    if (panelPreviewObjectUrl) {
        URL.revokeObjectURL(panelPreviewObjectUrl);
        panelPreviewObjectUrl = null;
    }
}

function applyBannerPosition() {
    const x = clampBannerPosition(parseFloat(backgroundPositionX?.value ?? '50'));
    const y = clampBannerPosition(parseFloat(backgroundPositionY?.value ?? '50'));
    const value = x + '% ' + y + '%';

    if (bannerPositionImage) {
        bannerPositionImage.style.objectPosition = value;
        bannerPositionImage.style.setProperty('--hero-editor-x', x + '%');
        bannerPositionImage.style.setProperty('--hero-editor-y', y + '%');
    }
    if (miniBannerImage) {
        miniBannerImage.style.objectPosition = value;
        miniBannerImage.style.setProperty('--hero-editor-x', x + '%');
        miniBannerImage.style.setProperty('--hero-editor-y', y + '%');
    }
    if (bannerPositionStage) {
        bannerPositionStage.style.setProperty('--hero-editor-x', x + '%');
        bannerPositionStage.style.setProperty('--hero-editor-y', y + '%');
    }
    const preview = document.getElementById('heroMiniPreview');
    if (preview) {
        preview.style.setProperty('--hero-preview-x', x + '%');
        preview.style.setProperty('--hero-preview-y', y + '%');
        preview.style.setProperty('--hero-editor-x', x + '%');
        preview.style.setProperty('--hero-editor-y', y + '%');
    }
}

function setBannerSource(src, status = '') {
    const hasSource = Boolean(src);

    if (removeBackgroundButton) removeBackgroundButton.classList.toggle('is-hidden', !hasSource);

    if (bannerPositionImage) {
        if (hasSource) {
            bannerPositionImage.src = src;
            bannerPositionImage.hidden = false;
        } else {
            bannerPositionImage.removeAttribute('src');
            bannerPositionImage.hidden = true;
        }
    }
    if (miniBannerImage) {
        if (hasSource) {
            miniBannerImage.src = src;
            miniBannerImage.hidden = false;
        } else {
            miniBannerImage.removeAttribute('src');
            miniBannerImage.hidden = true;
        }
    }

    if (bannerPositionEmpty) bannerPositionEmpty.hidden = hasSource;
    if (miniBannerEmpty) miniBannerEmpty.hidden = hasSource;
    if (bannerDragHint) bannerDragHint.hidden = !hasSource;
    bannerPositioner?.classList.toggle('is-empty', !hasSource);

    const preview = document.getElementById('heroMiniPreview');
    if (preview) {
        preview.classList.toggle('has-background', hasSource);
        if (hasSource) {
            preview.style.setProperty('--hero-preview-bg', 'url("' + src.replace(/"/g, '\\"') + '")');
        } else {
            preview.style.removeProperty('--hero-preview-bg');
        }
    }

    if (backgroundUploadStatus) backgroundUploadStatus.textContent = status;
    applyBannerPosition();
}

function restoreSavedBannerPreview() {
    releaseBannerObjectUrl();
    if (removeBackgroundImage && !removeBackgroundImage.disabled) {
        setBannerSource('', '');
        return;
    }
    const preview = document.getElementById('heroMiniPreview');
    const saved = preview?.dataset.currentBgSrc || miniImageOnlyContent?.dataset.currentSrc || '';
    setBannerSource(saved, '');
}

function fieldValue(id, fallback = '') {
    const el = document.getElementById(id);
    return (el?.value || '').trim() || fallback;
}

function syncHeroEditor() {
    const imageOnly = displayMode?.value === 'image_only';
    document.querySelectorAll('.standard-fields').forEach(el => {
        el.classList.toggle('is-hidden', imageOnly);
        el.classList.remove('field-group-disabled');
    });
    const backgroundTitle = document.getElementById('backgroundSectionTitle');
    const backgroundHelp = document.getElementById('backgroundSectionHelp');
    if (backgroundTitle) backgroundTitle.textContent = imageOnly ? 'Full banner image' : 'Hero background';
    if (backgroundHelp) backgroundHelp.textContent = imageOnly
        ? 'This image becomes the entire public hero slide. Drag the photo itself to choose the visible crop.'
        : 'Optional backdrop behind the slide message and right-side card. Drag the photo itself to choose the visible crop.';

    const panelImage = panelType?.value === 'image';
    const panelEmpty = panelType?.value === 'none';
    document.getElementById('panelImageFields')?.classList.toggle('is-hidden', !panelImage);
    document.getElementById('panelTextFields')?.classList.toggle('is-hidden', panelImage || panelEmpty);

    const preview = document.getElementById('heroMiniPreview');
    if (preview) {
        preview.classList.toggle('is-image-only', imageOnly);
        [...preview.classList].filter(className => className.startsWith('hero-mini-preview--')).forEach(className => preview.classList.remove(className));
        preview.classList.add('hero-mini-preview--' + (themeSelect?.value || 'green'));
    }
    document.getElementById('miniStandardContent')?.classList.toggle('is-hidden', imageOnly);
    const previewPanel = document.getElementById('previewPanel');
    previewPanel?.classList.toggle('is-hidden', imageOnly || panelEmpty);
    previewPanel?.classList.toggle('is-image-panel', panelImage && !imageOnly && !panelEmpty);
    preview?.classList.toggle('has-no-panel', panelEmpty && !imageOnly);
    if (miniPanelImage) miniPanelImage.hidden = !(panelImage && miniPanelImage.getAttribute('src'));
    document.getElementById('miniImageOnlyContent')?.classList.toggle('is-active', imageOnly);

    const selectedButtonMode = buttonMode?.value || 'both';
    const showPrimary = !imageOnly && (selectedButtonMode === 'both' || selectedButtonMode === 'primary');
    const showSecondary = !imageOnly && (selectedButtonMode === 'both' || selectedButtonMode === 'secondary');
    const primaryFields = document.getElementById('primaryActionFields');
    const secondaryFields = document.getElementById('secondaryActionFields');
    const buttonGrid = document.getElementById('heroButtonGrid');
    const noButtonsNote = document.getElementById('noButtonsNote');
    primaryFields?.classList.toggle('is-hidden', !showPrimary);
    secondaryFields?.classList.toggle('is-hidden', !showSecondary);
    buttonGrid?.classList.toggle('is-single-action', showPrimary !== showSecondary);
    buttonGrid?.classList.toggle('is-hidden', !showPrimary && !showSecondary);
    noButtonsNote?.classList.toggle('is-hidden', showPrimary || showSecondary);
    document.getElementById('previewPrimary')?.classList.toggle('is-hidden', !showPrimary);
    document.getElementById('previewSecondary')?.classList.toggle('is-hidden', !showSecondary);
    document.getElementById('previewButtons')?.classList.toggle('is-hidden', !showPrimary && !showSecondary);

    const displayText = imageOnly ? 'Image only' : 'Standard';
    const themeText = themeSelect?.options[themeSelect.selectedIndex]?.text || 'Green';
    if (document.getElementById('summaryDisplay')) document.getElementById('summaryDisplay').textContent = displayText;
    if (document.getElementById('summaryTheme')) document.getElementById('summaryTheme').textContent = themeText;
    if (document.getElementById('summaryOrder')) document.getElementById('summaryOrder').textContent = sortOrder?.value || '0';
    if (document.getElementById('summaryStatus')) document.getElementById('summaryStatus').textContent = visibility?.checked ? 'Visible' : 'Hidden';

    if (document.getElementById('previewEyebrow')) document.getElementById('previewEyebrow').textContent = fieldValue('heroEyebrow', <?=json_encode($portalEyebrow)?>);
    if (document.getElementById('previewTitle')) document.getElementById('previewTitle').textContent = fieldValue('heroTitle', 'Your slide headline');
    if (document.getElementById('previewDescription')) document.getElementById('previewDescription').textContent = fieldValue('heroDescription', 'Add a short description to explain what visitors can do next.');
    if (document.getElementById('previewPrimary')) document.getElementById('previewPrimary').textContent = fieldValue('primaryLabel', 'Primary action');
    if (document.getElementById('previewSecondary')) document.getElementById('previewSecondary').textContent = fieldValue('secondaryLabel', 'Secondary');

    const panelLabels = { none: '', card: 'Card', image: 'Image' };
    if (document.getElementById('previewPanelKicker')) document.getElementById('previewPanelKicker').textContent = fieldValue('panelKicker', panelLabels[panelType?.value] || 'Card');
    if (document.getElementById('previewPanelTitle')) document.getElementById('previewPanelTitle').textContent = fieldValue('panelTitle', panelType?.value === 'image' ? 'Image' : 'Card');
    if (document.getElementById('previewPanelDescription')) document.getElementById('previewPanelDescription').textContent = fieldValue('panelDescription', panelType?.value === 'image' ? 'Uploaded visual appears on the right side of the hero.' : 'Add supporting content for this card.');

    const description = document.getElementById('heroDescription');
    if (document.getElementById('descriptionCount')) document.getElementById('descriptionCount').textContent = description?.value.length || 0;
    applyBannerPosition();
}

displayMode?.addEventListener('change', syncHeroEditor);
panelType?.addEventListener('change', syncHeroEditor);
themeSelect?.addEventListener('change', syncHeroEditor);
buttonMode?.addEventListener('change', syncHeroEditor);
sortOrder?.addEventListener('input', syncHeroEditor);
visibility?.addEventListener('change', syncHeroEditor);

backgroundPositionX?.addEventListener('input', applyBannerPosition);
backgroundPositionY?.addEventListener('input', applyBannerPosition);


backgroundImageInput?.addEventListener('change', () => {
    const libraryField = document.getElementById('backgroundMediaId'); if (libraryField) libraryField.value = '';
    const file = backgroundImageInput.files?.[0];
    if (!file) {
        restoreSavedBannerPreview();
        return;
    }

    releaseBannerObjectUrl();
    bannerPreviewObjectUrl = URL.createObjectURL(file);

    if (backgroundPositionX) backgroundPositionX.value = '50';
    if (backgroundPositionY) backgroundPositionY.value = '50';

    setBannerSource(bannerPreviewObjectUrl, '');
    syncHeroEditor();
});


removeBackgroundButton?.addEventListener('click', () => {
    releaseBannerObjectUrl();
    if (backgroundImageInput) backgroundImageInput.value = '';
    const libraryField = document.getElementById('backgroundMediaId'); if (libraryField) libraryField.value = '';
    if (removeBackgroundImage) removeBackgroundImage.disabled = false;
    if (backgroundPositionX) backgroundPositionX.value = '50';
    if (backgroundPositionY) backgroundPositionY.value = '50';
    setBannerSource('', '');
    syncHeroEditor();
    markHeroDirty();
});

panelImageInput?.addEventListener('change', () => {
    const libraryField = document.getElementById('panelMediaId'); if (libraryField) libraryField.value = '';
    const file = panelImageInput.files?.[0];
    releasePanelObjectUrl();
    if (!file) {
        const saved = document.getElementById('previewPanel')?.dataset.currentPanelSrc || '';
        if (miniPanelImage) {
            if (saved) { miniPanelImage.src = saved; miniPanelImage.hidden = false; }
            else { miniPanelImage.removeAttribute('src'); miniPanelImage.hidden = true; }
        }
        syncHeroEditor();
        return;
    }
    panelPreviewObjectUrl = URL.createObjectURL(file);
    if (miniPanelImage) {
        miniPanelImage.src = panelPreviewObjectUrl;
        miniPanelImage.hidden = false;
    }
    syncHeroEditor();
});


function handleBannerLoadError() {
    if (backgroundUploadStatus) backgroundUploadStatus.textContent = 'The banner file could not be loaded. Choose a replacement image and save again.';
}
bannerPositionImage?.addEventListener('error', handleBannerLoadError);
miniBannerImage?.addEventListener('error', handleBannerLoadError);

bannerPositionStage?.addEventListener('pointerdown', event => {
    if (!bannerPositionImage || bannerPositionImage.hidden || !bannerPositionImage.naturalWidth) return;

    event.preventDefault();
    bannerPositionStage.setPointerCapture?.(event.pointerId);
    bannerPositionStage.classList.add('is-dragging');

    bannerDragState = {
        pointerId: event.pointerId,
        startClientX: event.clientX,
        startClientY: event.clientY,
        startPositionX: clampBannerPosition(parseFloat(backgroundPositionX?.value ?? '50')),
        startPositionY: clampBannerPosition(parseFloat(backgroundPositionY?.value ?? '50'))
    };
});

bannerPositionStage?.addEventListener('pointermove', event => {
    if (!bannerDragState || event.pointerId !== bannerDragState.pointerId || !bannerPositionImage) return;

    const rect = bannerPositionStage.getBoundingClientRect();
    const naturalWidth = bannerPositionImage.naturalWidth || rect.width;
    const naturalHeight = bannerPositionImage.naturalHeight || rect.height;
    const scale = Math.max(rect.width / naturalWidth, rect.height / naturalHeight);
    const renderedWidth = naturalWidth * scale;
    const renderedHeight = naturalHeight * scale;
    const overflowX = Math.max(0, renderedWidth - rect.width);
    const overflowY = Math.max(0, renderedHeight - rect.height);
    const deltaX = event.clientX - bannerDragState.startClientX;
    const deltaY = event.clientY - bannerDragState.startClientY;

    let nextX = bannerDragState.startPositionX;
    let nextY = bannerDragState.startPositionY;

    // Dragging the photo left/up moves the crop focus right/down, matching
    // the direct manipulation users expect from an image-position editor.
    if (overflowX > 1) nextX = clampBannerPosition(bannerDragState.startPositionX - (deltaX / overflowX) * 100);
    if (overflowY > 1) nextY = clampBannerPosition(bannerDragState.startPositionY - (deltaY / overflowY) * 100);

    if (backgroundPositionX) backgroundPositionX.value = String(Math.round(nextX));
    if (backgroundPositionY) backgroundPositionY.value = String(Math.round(nextY));
    applyBannerPosition();
    markHeroDirty();
});

function endBannerDrag(event) {
    if (!bannerDragState || (event?.pointerId !== undefined && event.pointerId !== bannerDragState.pointerId)) return;
    bannerPositionStage?.classList.remove('is-dragging');
    if (event?.pointerId !== undefined) bannerPositionStage?.releasePointerCapture?.(event.pointerId);
    bannerDragState = null;
}
bannerPositionStage?.addEventListener('pointerup', endBannerDrag);
bannerPositionStage?.addEventListener('pointercancel', endBannerDrag);
bannerPositionStage?.addEventListener('lostpointercapture', () => endBannerDrag());

const heroForm = document.getElementById('heroEditor');
const saveState = document.getElementById('heroSaveState');
const saveStateTitle = document.getElementById('heroSaveStateTitle');
const saveStateCopy = document.getElementById('heroSaveStateCopy');

function markHeroDirty() {
    saveState?.classList.add('is-dirty');
    if (saveStateTitle) saveStateTitle.textContent = 'Unsaved changes';
    if (saveStateCopy) saveStateCopy.textContent = 'Save the slide to keep your latest edits.';
}

heroForm?.addEventListener('input', markHeroDirty);
heroForm?.addEventListener('change', markHeroDirty);

['heroEyebrow', 'heroTitle', 'heroDescription', 'primaryLabel', 'secondaryLabel', 'panelKicker', 'panelTitle', 'panelDescription'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', syncHeroEditor);
});

restoreSavedBannerPreview();
syncHeroEditor();

window.addEventListener('beforeunload', () => { releaseBannerObjectUrl(); releasePanelObjectUrl(); });
</script>
<?php
admin_footer();
?>
