<?php

require_once BASE_PATH . '/lib/Database.php';

class ActivityLogManagement {
    
    public static function log($action, $details = null, $userId = null) {
        require_once BASE_PATH . '/lib/SessionManagement.php';
        require_once BASE_PATH . '/lib/Application.php';
        
        if ($userId === null) {
            $userId = SessionManagement::getUserId();
        }
        
        $ipAddress = Application::getClientIp();
        
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$userId, $action, $details, $ipAddress]
        );
    }

    public static function getRecentActivity($limit = 100) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT al.*, u.email, u.first_name, u.last_name 
             FROM activity_log al 
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }

    public static function getActivityForUser($userId, $limit = 100) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM activity_log 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }
}
