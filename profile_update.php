<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/lib/CSRF.php';
require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

try {
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token.');
    }
    
    $userId = SessionManagement::getUserId();
    $email = $_POST['email'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required.');
    }
    
    UserManagement::updateUser($userId, $email, $firstName, $lastName);
    ActivityLogManagement::log('profile_update', 'Updated profile information');
    
    Application::setFlashMessage('Profile updated successfully.', 'success');
    Application::redirect('/profile.php');
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    Application::redirect('/profile.php');
}
