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
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($newPasswordConfirm)) {
        throw new Exception('All password fields are required.');
    }
    
    if ($newPassword !== $newPasswordConfirm) {
        throw new Exception('New passwords do not match.');
    }
    
    // Verify current password
    $user = UserManagement::getUserById($userId);
    $authenticatedUser = UserManagement::authenticate($user['email'], $currentPassword);
    
    if (!$authenticatedUser) {
        throw new Exception('Current password is incorrect.');
    }
    
    UserManagement::updatePassword($userId, $newPassword);
    ActivityLogManagement::log('password_change', 'Changed password');
    
    Application::setFlashMessage('Password changed successfully.', 'success');
    Application::redirect('/profile.php');
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    Application::redirect('/profile.php');
}
