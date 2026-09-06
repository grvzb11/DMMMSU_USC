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

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

try {
    handbook_verify_public_request();
    if (!handbook_ai_enabled($pdo)) {
        http_response_code(503);
        echo json_encode(['error' => 'The Handbook Assistant is currently unavailable.']);
        exit;
    }
    if (!handbook_ai_public_ready($pdo)) {
        http_response_code(503);
        echo json_encode(['error' => 'The Handbook Assistant is being configured. Please try again later.']);
        exit;
    }

    $question = handbook_text_substr(trim((string)($_POST['question'] ?? '')), 0, 600);
    if ($question === '' || handbook_text_length($question) < 2) {
        http_response_code(422);
        echo json_encode(['error' => 'Enter a question about the Student Handbook.']);
        exit;
    }

    $rate = handbook_chat_rate_limit(admin_current_ip());
    if (!$rate['allowed']) {
        http_response_code(429);
        header('Retry-After: '.max(1, (int)$rate['retry_after']));
        echo json_encode(['error' => 'Too many questions were sent from this connection. Please wait a few minutes and try again.']);
        exit;
    }

    $history = [];
    $historyRaw = trim((string)($_POST['history'] ?? ''));
    if ($historyRaw !== '') {
        $decoded = json_decode($historyRaw, true);
        if (is_array($decoded)) $history = array_slice($decoded, -6);
    }

    $result = handbook_ask($pdo, $question, $history);
    $result['source_url'] = app_absolute_url('handbook/source.php');
    $result['disclaimer'] = 'This assistant summarizes approved Student Handbook content. The handbook and authorized university offices remain the official sources.';
    echo json_encode($result, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $ref = app_error_reference();
    app_log_error('Handbook Assistant request failed', ['reference' => $ref, 'error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'The Handbook Assistant could not answer right now. Please try again later.', 'reference' => $ref], JSON_UNESCAPED_UNICODE);
}
