<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/SpeechService.php';

// Get config for API key
$config = require BASE_PATH . '/config.php';

try {
    $userId = SessionManagement::getUserId();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $text = $input['text'] ?? '';
    $voice = $input['voice'] ?? 'alloy';
    
    if (empty($text)) {
        throw new Exception('Text is required');
    }
    
    if (!SpeechService::isValidVoice($voice)) {
        throw new Exception('Invalid voice');
    }
    
    // Initialize speech service
    $speech = new SpeechService($config['api']['openai_key']);
    
    // Generate speech
    $audioData = $speech->generateSpeech($text, $voice);
    
    // Return audio as MP3
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audioData));
    header('Cache-Control: no-cache');
    echo $audioData;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
