<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/lib/CSRF.php';
require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/lib/ImageManagement.php';
require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

try {
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token.');
    }
    
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please select a photo to upload.');
    }
    
    $file = $_FILES['photo'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.');
    }
    
    // Validate file size (max 25MB)
    if ($file['size'] > 25 * 1024 * 1024) {
        throw new Exception('File is too large. Maximum size is 25MB.');
    }
    
    // Read file data
    $imageData = file_get_contents($file['tmp_name']);
    
    // Create image in database
    $imageId = ImageManagement::createImage($imageData, $file['type']);
    
    // Update user profile
    $userId = SessionManagement::getUserId();
    UserManagement::updateProfileImage($userId, $imageId);
    
    ActivityLogManagement::log('profile_photo_update', 'Updated profile photo');
    
    Application::setFlashMessage('Profile photo updated successfully.', 'success');
    Application::redirect('/profile.php');
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    Application::redirect('/profile.php');
}
