<?php

class Application {
    private static $initialized = false;
    private static $config = null;

    public static function init() {
        if (self::$initialized) {
            return;
        }

        // Load config
        self::$config = require BASE_PATH . '/config.php';

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::$initialized = true;
    }

    public static function getConfig($key = null) {
        if ($key === null) {
            return self::$config;
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public static function requireAuth() {
        require_once BASE_PATH . '/lib/SessionManagement.php';
        
        if (!SessionManagement::isLoggedIn()) {
            self::redirect('/login.php');
        }
    }

    public static function requireAdmin() {
        require_once BASE_PATH . '/lib/SessionManagement.php';
        require_once BASE_PATH . '/lib/UserManagement.php';
        
        if (!SessionManagement::isLoggedIn()) {
            self::redirect('/login.php');
        }

        $user = UserManagement::getUserById(SessionManagement::getUserId());
        if (!$user || !$user['is_admin']) {
            self::setFlashMessage('You do not have permission to access this page.', 'error');
            self::redirect('/index.php');
        }
    }

    public static function redirect($url) {
        header('Location: ' . $url);
        exit;
    }

    public static function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    public static function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = [
                'message' => $_SESSION['flash_message'],
                'type' => $_SESSION['flash_type'] ?? 'info'
            ];
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            return $message;
        }
        return null;
    }

    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
}
