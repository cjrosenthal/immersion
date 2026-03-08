<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();

require_once BASE_PATH . '/lib/CSRF.php';
require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token. Please try again.');
    }
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('Please provide both email and password.');
    }
    
    // Authenticate user
    $user = UserManagement::authenticate($email, $password);
    
    if (!$user) {
        throw new Exception('Invalid email or password.');
    }
    
    // Log in the user
    SessionManagement::login($user['id']);
    
    // Log the activity
    ActivityLogManagement::log('login', 'User logged in', $user['id']);
    
    // Redirect to homepage
    Application::redirect('/index.php');
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    Application::redirect('/login.php');
}
