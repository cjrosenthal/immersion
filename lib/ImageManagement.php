<?php

require_once BASE_PATH . '/lib/Database.php';

class ImageManagement {
    
    public static function createImage($data, $mimeType) {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO images (data, mime_type) VALUES (?, ?)",
            [$data, $mimeType]
        );
        return $db->lastInsertId();
    }

    public static function getImage($id) {
        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT * FROM images WHERE id = ?",
            [$id]
        );
    }

    public static function deleteImage($id) {
        $db = Database::getInstance();
        $db->execute("DELETE FROM images WHERE id = ?", [$id]);
    }

    public static function getImageUrl($imageId) {
        if (!$imageId) {
            // Return inline SVG data URI for default avatar
            return 'data:image/svg+xml,' . urlencode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#e0e0e0"/><circle cx="50" cy="40" r="18" fill="#999"/><ellipse cx="50" cy="85" rx="28" ry="20" fill="#999"/></svg>');
        }

        // Check if cached version exists
        $extension = 'jpg'; // default
        $cachedPath = CACHE_PATH . '/image_' . $imageId . '.' . $extension;
        
        if (file_exists($cachedPath)) {
            return '/cache/image_' . $imageId . '.' . $extension;
        }

        // Return render_image.php URL
        return '/render_image.php?id=' . $imageId;
    }

    public static function cacheImage($imageId, $data, $mimeType) {
        if (!file_exists(CACHE_PATH)) {
            mkdir(CACHE_PATH, 0755, true);
        }

        $extension = self::getExtensionFromMimeType($mimeType);
        $cachedPath = CACHE_PATH . '/image_' . $imageId . '.' . $extension;
        
        file_put_contents($cachedPath, $data);
        
        return '/cache/image_' . $imageId . '.' . $extension;
    }

    private static function getExtensionFromMimeType($mimeType) {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $extensions[$mimeType] ?? 'jpg';
    }
}
