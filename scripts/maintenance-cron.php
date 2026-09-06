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
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/config/helpers.php';
require_once dirname(__DIR__).'/config/maintenance.php';
try {
    $result = maintenance_run_scheduled_tasks($pdo);
    fwrite(STDOUT, '['.date('c').'] Scheduled maintenance complete'.PHP_EOL);
    fwrite(STDOUT, 'Published scheduled posts: '.$result['published'].PHP_EOL);
    fwrite(STDOUT, 'Email notifications processed: '.$result['emails'].PHP_EOL);
    fwrite(STDOUT, 'Deadlines approaching: '.($result['due_soon_reminders'] ?? 0).PHP_EOL);
    fwrite(STDOUT, 'Overdue reminders/escalations: '.($result['overdue_reminders'] ?? 0).PHP_EOL);
    fwrite(STDOUT, 'Concern follow-up reminders: '.($result['followup_reminders'] ?? 0).PHP_EOL);
    fwrite(STDOUT, 'Concern records archived by retention: '.($result['concern_retention']['archived'] ?? 0).PHP_EOL);
    if ($result['backup']) fwrite(STDOUT, 'Backup created: '.$result['backup']['filename'].PHP_EOL);
    if ($result['verification']) fwrite(STDOUT, 'Backup verification: '.($result['verification']['ok']?'OK':'FAILED').' - '.$result['verification']['message'].PHP_EOL);
    fwrite(STDOUT, 'Retention target: '.$result['retention']['target'].'; preserved backups: '.$result['retention']['total'].PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    app_log_error('Scheduled maintenance failed', ['error' => $e->getMessage()]);
    fwrite(STDERR, 'Maintenance failed: '.$e->getMessage().PHP_EOL);
    exit(1);
}
