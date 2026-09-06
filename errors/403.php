<?php
$status = 403;
$title = $title ?? 'Access denied';
$message = $message ?? 'Your account does not have permission to access this resource.';
require __DIR__.'/_template.php';
