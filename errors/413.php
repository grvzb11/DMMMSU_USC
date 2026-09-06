<?php
$status = 413;
$title = $title ?? 'Upload too large';
$message = $message ?? 'The selected upload is larger than this server currently allows. Reduce the file size or increase the server upload limit, then try again.';
require __DIR__.'/_template.php';
