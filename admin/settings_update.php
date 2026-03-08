<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/lib/CSRF.php';
require_once BASE_PATH . '/lib/SettingsManagement.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

try {
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token.');
    }
    
    $siteTitle = $_POST['site_title'] ?? '';
    
    if (empty($siteTitle)) {
        throw new Exception('Site title is required.');
    }
    
    SettingsManagement::set('site_title', $siteTitle);
    ActivityLogManagement::log('settings_update', 'Updated global settings');
    
    Application::setFlashMessage('Settings updated successfully.', 'success');
    Application::redirect('/admin/settings.php');
    
} catch (Exception $e) {
    Application::setFlashMessage($e->getMessage(), 'error');
    Application::redirect('/admin/settings.php');
}
