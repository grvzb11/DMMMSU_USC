<?php
$status = 404;
$title = $title ?? 'Page not found';
$message = $message ?? 'The page or record you requested could not be found.';
require __DIR__.'/_template.php';
