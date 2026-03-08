<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/Database.php';
require_once BASE_PATH . '/lib/SpeechService.php';

header('Content-Type: application/json');

try {
    $userId = SessionManagement::getUserId();
    $db = Database::getInstance();
    
    // Handle GET - retrieve preferences
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $prefs = $db->fetchOne(
            "SELECT * FROM user_preferences WHERE user_id = ?",
            [$userId]
        );
        
        if (!$prefs) {
            // Return defaults
            $prefs = [
                'user_id' => $userId,
                'tts_voice' => 'alloy'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'preferences' => $prefs
        ]);
        return;
    }
    
    // Handle POST - update preferences
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $ttsVoice = $input['tts_voice'] ?? null;
        
        if ($ttsVoice && !SpeechService::isValidVoice($ttsVoice)) {
            throw new Exception('Invalid TTS voice');
        }
        
        // Upsert preferences
        $db->execute(
            "INSERT INTO user_preferences (user_id, tts_voice) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE tts_voice = ?",
            [$userId, $ttsVoice, $ttsVoice]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Preferences updated'
        ]);
        return;
    }
    
    throw new Exception('Invalid request method');
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
