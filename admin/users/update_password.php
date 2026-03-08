<?php
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/lib/CSRF.php';
require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

try {
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token.');
    }
    
    $userId = $_POST['id'] ?? null;
    $newPassword = $_POST['new_password'] ?? '';
    
    if (!$userId || !is_numeric($userId)) {
        throw new Exception('Invalid user ID.');
    }
    
    if (empty($newPassword)) {
        throw new Exception('Password is required.');
    }
    
    UserManagement::updatePassword($userId, $newPassword);
    ActivityLogManagement::log('user_password_update', 'Updated password for user ID: ' . $userId);
    
    Application::setFlashMessage('Password updated successfully.', 'success');
    Application::redirect('/admin/users/edit.php?id=' . $userId);
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    $userId = $_POST['id'] ?? null;
    if ($userId) {
        Application::redirect('/admin/users/edit.php?id=' . $userId);
    } else {
        Application::redirect('/admin/users/list.php');
    }
}
