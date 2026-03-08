<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();

require_once BASE_PATH . '/lib/CSRF.php';
require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/lib/EmailService.php';

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token. Please try again.');
    }
    
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Please provide an email address.');
    }
    
    // Check if user exists
    $user = UserManagement::getUserByEmail($email);
    
    if ($user) {
        // Create password reset token
        $token = UserManagement::createPasswordResetToken($user['id']);
        
        // Send email
        $resetUrl = Application::getConfig('site_url') . '/reset_password.php?token=' . $token;
        $subject = 'Password Reset Request';
        $body = "Hello,\n\n";
        $body .= "You requested a password reset. Click the link below to reset your password:\n\n";
        $body .= $resetUrl . "\n\n";
        $body .= "This link will expire in 1 hour.\n\n";
        $body .= "If you didn't request this, please ignore this email.\n\n";
        $body .= "Thanks,\n";
        $body .= "The Immersion Team";
        
        $success = EmailService::send($email, $subject, $body);
        
        if (!$success) {
            throw new Exception('Failed to send password reset email. Please try again later.');
        }
    }
    
    // Always show success message (security: don't reveal if email exists)
    Application::setFlashMessage('If an account exists with that email, you will receive a password reset link shortly.', 'success');
    Application::redirect('/login.php');
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    Application::redirect('/forgot_password.php');
}
