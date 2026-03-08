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
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required.');
    }
    
    $userId = UserManagement::createUser($email, $password, $firstName, $lastName, $isAdmin);
    ActivityLogManagement::log('user_create', 'Created user ID: ' . $userId);
    
    Application::setFlashMessage('User created successfully.', 'success');
    Application::redirect('/admin/users/edit.php?id=' . $userId);
    
} catch (Exception $e) {
    // Store form data in session to repopulate form
    $_SESSION['form_data'] = [
        'email' => $_POST['email'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'is_admin' => isset($_POST['is_admin']) ? 1 : 0
    ];
    
    Application::setFlashMessage($e->getMessage(), 'error');
    Application::redirect('/admin/users/create.php');
}
