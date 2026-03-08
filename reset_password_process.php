<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();

require_once BASE_PATH . '/lib/CSRF.php';
require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token. Please try again.');
    }
    
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (empty($password) || empty($passwordConfirm)) {
        throw new Exception('Please provide both password fields.');
    }
    
    if ($password !== $passwordConfirm) {
        throw new Exception('Passwords do not match.');
    }
    
    // Validate token
    $tokenData = UserManagement::validatePasswordResetToken($token);
    if (!$tokenData) {
        throw new Exception('Invalid or expired password reset link.');
    }
    
    // Update password
    UserManagement::updatePassword($tokenData['user_id'], $password);
    
    // Mark token as used
    UserManagement::markPasswordResetTokenUsed($token);
    
    // Log the activity
    ActivityLogManagement::log('password_reset', 'Password reset via token', $tokenData['user_id']);
    
    Application::setFlashMessage('Your password has been reset successfully. Please log in.', 'success');
    Application::redirect('/login.php');
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    
    // Redirect back with token if available
    if (!empty($_POST['token'])) {
        Application::redirect('/reset_password.php?token=' . urlencode($_POST['token']));
    } else {
        Application::redirect('/login.php');
    }
}
