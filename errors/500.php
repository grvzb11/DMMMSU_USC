<?php
$status = 500;
$title = $title ?? 'System error';
$message = $message ?? 'The system could not complete this request.';
require __DIR__.'/_template.php';
