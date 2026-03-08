<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/ThreadManagement.php';

header('Content-Type: application/json');

try {
    $userId = SessionManagement::getUserId();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? 'New Conversation';
    
    // Create thread
    $threadId = ThreadManagement::createThread($userId, $name);
    $thread = ThreadManagement::getThread($threadId);
    
    echo json_encode([
        'success' => true,
        'thread' => $thread
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
