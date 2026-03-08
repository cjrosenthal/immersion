<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();

require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

// Log the activity before destroying session
if (SessionManagement::isLoggedIn()) {
    ActivityLogManagement::log('logout', 'User logged out');
}

// Logout
SessionManagement::logout();

// Redirect to login
Application::setFlashMessage('You have been logged out successfully.', 'success');
Application::redirect('/login.php');
