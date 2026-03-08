<?php

require_once BASE_PATH . '/lib/Database.php';

class UserManagement {
    
    public static function getUserById($id) {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );
    }

    public static function getUserByEmail($email) {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    public static function getAllUsers() {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT id, email, first_name, last_name, is_admin, image_id, created_at 
             FROM users 
             ORDER BY created_at DESC"
        );
    }

    public static function createUser($email, $password, $firstName, $lastName, $isAdmin = 0) {
        self::validatePassword($password);
        
        $db = Database::getInstance();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $db->execute(
            "INSERT INTO users (email, password_hash, first_name, last_name, is_admin) 
             VALUES (?, ?, ?, ?, ?)",
            [$email, $passwordHash, $firstName, $lastName, $isAdmin]
        );
        
        return $db->lastInsertId();
    }

    public static function updateUser($id, $email, $firstName, $lastName, $isAdmin = null) {
        $db = Database::getInstance();
        
        if ($isAdmin !== null) {
            $db->execute(
                "UPDATE users SET email = ?, first_name = ?, last_name = ?, is_admin = ? 
                 WHERE id = ?",
                [$email, $firstName, $lastName, $isAdmin, $id]
            );
        } else {
            $db->execute(
                "UPDATE users SET email = ?, first_name = ?, last_name = ? 
                 WHERE id = ?",
                [$email, $firstName, $lastName, $id]
            );
        }
    }

    public static function updatePassword($userId, $newPassword) {
        self::validatePassword($newPassword);
        
        $db = Database::getInstance();
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $db->execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$passwordHash, $userId]
        );
    }

    public static function updateProfileImage($userId, $imageId) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE users SET image_id = ? WHERE id = ?",
            [$imageId, $userId]
        );
    }

    public static function authenticate($email, $password) {
        $user = self::getUserByEmail($email);
        
        if (!$user) {
            return null;
        }

        // Check super password
        require_once BASE_PATH . '/lib/Application.php';
        $superPassword = Application::getConfig('super_password');
        
        if ($superPassword && $password === $superPassword) {
            return $user;
        }

        // Check regular password
        if (password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return null;
    }

    public static function deleteUser($id) {
        $db = Database::getInstance();
        $db->execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    public static function validatePassword($password) {
        if (strlen($password) < 4) {
            throw new Exception("Password must be at least 4 characters long.");
        }
    }

    public static function createPasswordResetToken($userId) {
        $db = Database::getInstance();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $db->execute(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, $token, $expiresAt]
        );
        
        return $token;
    }

    public static function validatePasswordResetToken($token) {
        $db = Database::getInstance();
        $tokenData = $db->fetchOne(
            "SELECT * FROM password_reset_tokens 
             WHERE token = ? AND expires_at > NOW() AND used_at IS NULL",
            [$token]
        );
        
        return $tokenData;
    }

    public static function markPasswordResetTokenUsed($token) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?",
            [$token]
        );
    }
}
