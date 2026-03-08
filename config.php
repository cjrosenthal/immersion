<?php
/**
 * Base Configuration File
 * This file contains default configuration values and is checked into git.
 * Local overrides should be placed in config.local.php
 */

// Base configuration (only define if not already defined)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', BASE_PATH . '/cache');
}

// Default timezone
date_default_timezone_set('America/New_York');

// Error reporting (override in config.local.php for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration (only set if session not started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
}

// Default values (should be overridden in config.local.php)
$config = [
    'db' => [
        'host' => 'localhost',
        'name' => 'immersion',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'from_email' => 'noreply@example.com',
        'from_name' => 'Immersion'
    ],
    'api' => [
        'claude_key' => '',
        'openai_key' => ''
    ],
    'super_password' => 'super',
    'site_url' => 'http://localhost'
];

// Load local configuration if it exists and merge
if (file_exists(__DIR__ . '/config.local.php')) {
    $localConfig = require __DIR__ . '/config.local.php';
    $config = array_replace_recursive($config, $localConfig);
}

return $config;
