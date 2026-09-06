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
$campusContexts = [
'NLUC' => ['name' => 'North La Union Campus', 'logo' => 'public/assets/images/nluc-sbo-logo.jpg', 'page' => 'campus/nluc.php', 'facebook' => 'https://www.facebook.com/nluccsbo'],
'MLUC' => ['name' => 'Mid La Union Campus', 'logo' => 'public/assets/images/mluc-sbo-logo.jpg', 'page' => 'campus/mluc.php', 'facebook' => 'https://www.facebook.com/MLUC.CSBO'],
'SLUC' => ['name' => 'South La Union Campus', 'logo' => 'public/assets/images/sluc-sbo-logo.jpg', 'page' => 'campus/sluc.php', 'facebook' => 'https://www.facebook.com/profile.php?id=61566349724645'],
'OUS' => ['name' => 'Open University System', 'logo' => 'public/assets/images/ous-sbo-logo.jpg', 'page' => 'campus/ous.php'],
];
$ctxCampus = strtoupper(trim((string)($_GET['campus'] ?? ($campusCode ?? ''))));
$ctxIsCampus = isset($campusContexts[$ctxCampus]);
$ctxConfig = $ctxIsCampus ? $campusContexts[$ctxCampus] : null;
$ctxQuery = $ctxIsCampus ? '?campus='.rawurlencode($ctxCampus) : '';
$ctxHome = $ctxIsCampus ? $ctxConfig['page'] : 'index.php';
$ctxBrandLogo = $ctxIsCampus ? $ctxConfig['logo'] : 'public/assets/images/usc-seal.jpg';
$ctxBrandPrimary = $ctxIsCampus ? $ctxConfig['name'] : 'University Student Council';
$ctxBrandSecondary = $ctxIsCampus ? 'Campus Student Body Organization' : 'Don Mariano Marcos Memorial State University';
function ctx_link(string $path, bool $withCampus = true): string {
    global $ctxIsCampus, $ctxCampus;
    if (!$withCampus || !$ctxIsCampus) return $path;
    return $path.(str_contains($path, '?')?'&':'?').'campus='.rawurlencode($ctxCampus);
}
