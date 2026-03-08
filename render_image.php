<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();

require_once BASE_PATH . '/lib/ImageManagement.php';

$imageId = $_GET['id'] ?? null;

if (!$imageId || !is_numeric($imageId)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$image = ImageManagement::getImage($imageId);

if (!$image) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Cache the image
ImageManagement::cacheImage($imageId, $image['data'], $image['mime_type']);

// Output the image
header('Content-Type: ' . $image['mime_type']);
header('Content-Length: ' . strlen($image['data']));
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
echo $image['data'];
