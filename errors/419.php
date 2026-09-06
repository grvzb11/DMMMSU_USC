<?php
$status = 419;
$title = $title ?? 'Request expired';
$message = $message ?? 'The security token for this request is invalid or expired. Return to the previous page and try again.';
require __DIR__.'/_template.php';
