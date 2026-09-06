<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$base = rtrim((string)($argv[1] ?? ''), '/');
$securityOnly = in_array('--security-only', $argv, true);
if ($base === '' || !preg_match('~^https?://~i', $base)) {
    fwrite(STDERR, "Usage: php tests/http-smoke.php http://127.0.0.1:8000 [--security-only]\n");
    exit(2);
}
$publicChecks = [
['/', [200]],
['/news/updates.php', [200]],
['/esumbong/esumbong.php', [200]],
['/campus/mluc.php', [200]],
];
$securityChecks = [
['/public/assets/images/favicon.png', [200]],
['/database/dmmmsu_usc.production.sql', [403, 404]],
['/config/database.php', [403, 404]],
['/README.md', [403, 404]],
['/storage/logs/app.log', [403, 404]],
['/uploads/concerns/example.pdf', [403, 404]],
];
$checks = $securityOnly?$securityChecks:array_merge($publicChecks, $securityChecks);
$fail = 0;
$blocked = 0;
$total = 0;
foreach ($checks as [$path, $expected]) {
    $total++;
    $url = $base.$path;
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'ignore_errors' => true, 'timeout' => 8, 'follow_location' => 0, 'header' => "User-Agent: DMMMSU-USC-SmokeTest/2.0\r\n"]]);
    $body = @file_get_contents($url, false, $ctx);
    $headers = $http_response_header ?? [];
    $status = 0;
    foreach ($headers as $h) {
        if (preg_match('~^HTTP/\\S+\\s+(\\d{3})~i', $h, $m)) {
            $status = (int)$m[1];
            break;
        }
    }
    $ok = in_array($status, $expected, true);
    $environmentBlocked = !$securityOnly && $status === 500 && is_string($body) && (str_contains($body, 'Server configuration incomplete') || str_contains($body, 'Database unavailable'));
    if ($ok) {
        echo '[PASS] '.$path.' -> '.$status.' (expected '.implode('/', $expected).")\n";
        continue;
    }
    if ($environmentBlocked) {
        $blocked++;
        echo '[BLOCKED] '.$path.' -> 500 (database/PHP environment is not ready for full application smoke testing)'."\n";
        continue;
    }
    $fail++;
    echo '[FAIL] '.$path.' -> '.$status.' (expected '.implode('/', $expected).")\n";
}
echo "\n$total HTTP smoke check(s), $fail failure(s), $blocked environment-blocked check(s).\n";
if ($fail>0) exit(1);
if ($blocked>0) exit(2);
exit(0);
