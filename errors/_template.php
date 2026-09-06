<?php
$status = $status ?? 500;
$title = $title ?? 'System notice';
$message = $message ?? 'The requested action could not be completed.';
$reference = $reference ?? null;
http_response_code((int)$status);
$projectFs = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$documentFs = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '';
$projectNorm = rtrim(str_replace('\\', '/', $projectFs), '/');
$documentNorm = rtrim(str_replace('\\', '/', $documentFs), '/');
$webRoot = '';
if ($documentNorm !== '' && str_starts_with(strtolower($projectNorm), strtolower($documentNorm))) {
    $relative = substr($projectNorm, strlen($documentNorm));
    $webRoot = '/'.trim(str_replace('\\', '/', $relative), '/');
    if ($webRoot === '/') $webRoot = '';
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="noindex">
        <title><?=htmlspecialchars($title)?> | DMMMSU USC</title>
        <style>
body {
    margin: 0;
    font-family: Inter,Arial,sans-serif;
    background: #f5f8f6;
    color: #183128;
    display: grid;
    min-height: 100vh;
    place-items: center;
}

.card {
    width: min(620px,calc(100% - 40px));
    background: #fff;
    border: 1px solid #dae7df;
    border-radius: 22px;
    padding: 36px;
    box-shadow: 0 18px 50px rgba(26,70,49,.09);
}

.code {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .16em;
    color: #278158;
}

.card h1 {
    font-size: 34px;
    margin: 12px 0;
}

.card p {
    line-height: 1.7;
    color: #5d6d65;
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 24px;
}

.actions a {
    padding: 11px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    background: #146c48;
    color: #fff;
}

.actions a.alt {
    background: #edf4f0;
    color: #214c39;
}

.ref {
    display: block;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #e3ebe6;
    font: 12px ui-monospace,monospace;
    color: #7b8982;
}
</style>
    </head>
    <body>
        <main class="card">
            <span class="code">HTTP <?=intval($status)?></span>
            <h1><?=htmlspecialchars($title)?></h1>
            <p><?=htmlspecialchars($message)?></p>
            <div class="actions">
                <a href="<?=htmlspecialchars($webRoot)?>/index.php">Public website</a><a class="alt" href="<?=htmlspecialchars($webRoot)?>/admin/login.php">Administration</a>
            </div>
            <?php if ($reference) : ?>
                <span class="ref">Reference: <?=htmlspecialchars($reference)?></span>
            <?php endif; ?>
        </main>
    </body>
</html>
