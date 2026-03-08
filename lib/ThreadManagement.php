<?php

require_once BASE_PATH . '/lib/Database.php';

class ThreadManagement {
    
    public static function createThread($userId, $name) {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO threads (user_id, name, last_message_at) VALUES (?, ?, NOW())",
            [$userId, $name]
        );
        return $db->lastInsertId();
    }
    
    public static function getThread($threadId) {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM threads WHERE id = ?",
            [$threadId]
        );
    }
    
    public static function getUserThreads($userId, $limit = 50) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM threads WHERE user_id = ? ORDER BY last_message_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }
    
    public static function getOrCreateCurrentThread($userId) {
        // Get most recent thread for user
        $db = Database::getInstance();
        $thread = $db->fetchOne(
            "SELECT * FROM threads WHERE user_id = ? ORDER BY last_message_at DESC LIMIT 1",
            [$userId]
        );
        
        if ($thread) {
            return $thread;
        }
        
        // Create new thread
        $threadId = self::createThread($userId, 'New Conversation');
        return self::getThread($threadId);
    }
    
    public static function updateThreadName($threadId, $name) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE threads SET name = ? WHERE id = ?",
            [$name, $threadId]
        );
    }
    
    public static function updateLastMessageTime($threadId) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE threads SET last_message_at = NOW() WHERE id = ?",
            [$threadId]
        );
    }
    
    public static function autoNameThreadFromFirstMessage($threadId, $message) {
        // Extract first 50 characters for thread name
        $name = substr($message, 0, 50);
        if (strlen($message) > 50) {
            $name .= '...';
        }
        self::updateThreadName($threadId, $name);
    }
    
    public static function deleteThread($threadId) {
        $db = Database::getInstance();
        $db->execute("DELETE FROM threads WHERE id = ?", [$threadId]);
    }
    
    public static function getUserThreadCount($userId) {
        $db = Database::getInstance();
        $result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM threads WHERE user_id = ?",
            [$userId]
        );
        return $result['count'] ?? 0;
    }
}
