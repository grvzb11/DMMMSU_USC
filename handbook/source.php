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
declare(strict_types=1);
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/config/helpers.php';
require_once dirname(__DIR__).'/config/handbook-ai.php';

$path = handbook_active_path($pdo);
if (!is_file($path)) not_found('Student Handbook not found.');
$filename = basename(handbook_active_filename($pdo));
header('Content-Type: application/pdf');
header('Content-Length: '.filesize($path));
header('Content-Disposition: inline; filename="'.str_replace(['"', "\r", "\n"], '_', $filename).'"');
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
