<?php

require_once BASE_PATH . '/lib/Database.php';

class SettingsManagement {
    
    public static function get($key, $default = null) {
        $db = Database::getInstance();
        $setting = $db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = ?",
            [$key]
        );
        
        return $setting ? $setting['setting_value'] : $default;
    }

    public static function set($key, $value) {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO settings (setting_key, setting_value) 
             VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = ?",
            [$key, $value, $value]
        );
    }

    public static function getAll() {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    public static function delete($key) {
        $db = Database::getInstance();
        $db->execute("DELETE FROM settings WHERE setting_key = ?", [$key]);
    }
}
