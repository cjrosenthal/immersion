<?php

require_once BASE_PATH . '/lib/Database.php';

class MessageManagement {
    
    public static function addMessage($threadId, $role, $content, $interrupted = false, $partialContent = null) {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO messages (thread_id, role, content, interrupted, partial_content) VALUES (?, ?, ?, ?, ?)",
            [$threadId, $role, $content, $interrupted ? 1 : 0, $partialContent]
        );
        return $db->lastInsertId();
    }
    
    public static function getMessage($messageId) {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM messages WHERE id = ?",
            [$messageId]
        );
    }
    
    public static function getThreadMessages($threadId, $limit = 100) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM messages WHERE thread_id = ? ORDER BY created_at ASC LIMIT ?",
            [$threadId, $limit]
        );
    }
    
    public static function getThreadContext($threadId, $limit = 20) {
        // Get RECENT messages (not oldest) formatted for Claude API
        $db = Database::getInstance();
        
        // Get the last N messages in reverse order, then flip to chronological
        $messages = $db->fetchAll(
            "SELECT * FROM messages WHERE thread_id = ? ORDER BY created_at DESC LIMIT ?",
            [$threadId, $limit]
        );
        
        // Reverse to get chronological order (oldest to newest)
        $messages = array_reverse($messages);
        
        $context = [];
        foreach ($messages as $message) {
            $context[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        return $context;
    }
    
    public static function markInterrupted($messageId, $partialContent) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE messages SET interrupted = 1, partial_content = ? WHERE id = ?",
            [$partialContent, $messageId]
        );
    }
    
    public static function updateMessageContent($messageId, $content) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE messages SET content = ? WHERE id = ?",
            [$content, $messageId]
        );
    }
    
    public static function deleteMessage($messageId) {
        $db = Database::getInstance();
        $db->execute("DELETE FROM messages WHERE id = ?", [$messageId]);
    }
    
    public static function getThreadMessageCount($threadId) {
        $db = Database::getInstance();
        $result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM messages WHERE thread_id = ?",
            [$threadId]
        );
        return $result['count'] ?? 0;
    }
    
    public static function getLastMessage($threadId) {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM messages WHERE thread_id = ? ORDER BY created_at DESC LIMIT 1",
            [$threadId]
        );
    }
}
