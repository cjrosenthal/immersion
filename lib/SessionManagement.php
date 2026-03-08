<?php

class SessionManagement {
    
    public static function login($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_time'] = time();
        session_regenerate_id(true);
    }

    public static function logout() {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getLoginTime() {
        return $_SESSION['login_time'] ?? null;
    }
}
