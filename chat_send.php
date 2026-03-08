<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/ThreadManagement.php';
require_once BASE_PATH . '/lib/MessageManagement.php';
require_once BASE_PATH . '/lib/ClaudeService.php';
require_once BASE_PATH . '/lib/ActivityLogManagement.php';

// Get config for API key
$config = require BASE_PATH . '/config.php';

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Disable output buffering
if (ob_get_level()) ob_end_clean();

try {
    $userId = SessionManagement::getUserId();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? '';
    $threadId = $input['thread_id'] ?? null;
    
    if (empty($message)) {
        throw new Exception('Message is required');
    }
    
    // Get or create thread
    if ($threadId) {
        $thread = ThreadManagement::getThread($threadId);
        if (!$thread || $thread['user_id'] != $userId) {
            throw new Exception('Invalid thread');
        }
    } else {
        $thread = ThreadManagement::getOrCreateCurrentThread($userId);
        $threadId = $thread['id'];
    }
    
    // Save user message
    MessageManagement::addMessage($threadId, 'user', $message);
    
    // Auto-name thread if this is the first message
    if (MessageManagement::getThreadMessageCount($threadId) == 1) {
        ThreadManagement::autoNameThreadFromFirstMessage($threadId, $message);
    }
    
    // Get conversation context
    $context = MessageManagement::getThreadContext($threadId);
    
    // Initialize Claude service
    $claude = new ClaudeService($config['api']['claude_key'], $config['api']['claude_model'] ?? 'claude-3-sonnet-20240229');
    
    // Send thread ID first
    echo "data: " . json_encode(['type' => 'thread_id', 'thread_id' => $threadId]) . "\n\n";
    flush();
    
    // Stream response from Claude
    $fullResponse = '';
    $claude->streamChat($context, function($chunk) use (&$fullResponse) {
        $fullResponse .= $chunk;
        echo "data: " . json_encode(['type' => 'chunk', 'text' => $chunk]) . "\n\n";
        flush();
    });
    
    // Save assistant message
    MessageManagement::addMessage($threadId, 'assistant', $fullResponse);
    
    // Update thread timestamp
    ThreadManagement::updateLastMessageTime($threadId);
    
    // Log activity
    ActivityLogManagement::log('chat_message', 'Sent message in thread ' . $threadId);
    
    // Send completion signal
    echo "data: " . json_encode(['type' => 'done', 'full_text' => $fullResponse]) . "\n\n";
    flush();
    
} catch (Exception $e) {
    echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
    flush();
}
