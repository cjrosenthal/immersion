-- Migration: Chat Functionality
-- Date: 2026-03-07
-- Description: Add chat/voice functionality with threads and messages

-- Add last_message_at to threads table (ignore if already exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'threads' AND COLUMN_NAME = 'last_message_at');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE threads ADD COLUMN last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 'SELECT ''Column already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add interrupted to messages table (ignore if already exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'interrupted');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE messages ADD COLUMN interrupted TINYINT(1) DEFAULT 0', 'SELECT ''Column already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add partial_content to messages table (ignore if already exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'partial_content');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE messages ADD COLUMN partial_content TEXT NULL', 'SELECT ''Column already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create user_preferences table for TTS voice selection
CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT PRIMARY KEY,
    tts_voice VARCHAR(20) DEFAULT 'alloy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add default TTS voice setting
INSERT INTO settings (setting_key, setting_value)
VALUES ('default_tts_voice', 'alloy')
ON DUPLICATE KEY UPDATE setting_value = 'alloy';

-- Add indexes for better performance (ignore if already exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'threads' AND INDEX_NAME = 'idx_last_message_at');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE threads ADD INDEX idx_last_message_at (last_message_at)', 'SELECT ''Index already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND INDEX_NAME = 'idx_thread_created');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE messages ADD INDEX idx_thread_created (thread_id, created_at)', 'SELECT ''Index already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
