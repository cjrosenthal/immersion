<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';
require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/ThreadManagement.php';
require_once BASE_PATH . '/lib/MessageManagement.php';

$userId = SessionManagement::getUserId();
$userInfo = require_once BASE_PATH . '/lib/UserManagement.php';
$currentUser = UserManagement::getUserById($userId);
$isAdmin = $currentUser['is_admin'] ?? false;

$currentThreadId = $_GET['thread'] ?? null;
$currentThread = null;
$messages = [];

// If viewing a specific thread, load it
if ($currentThreadId) {
    $currentThread = ThreadManagement::getThread($currentThreadId);
    if ($currentThread && $currentThread['user_id'] == $userId) {
        $messages = MessageManagement::getThreadMessages($currentThreadId, 1000);
    } else {
        $currentThreadId = null;
    }
}

// Get user's threads for sidebar
$userThreads = ThreadManagement::getUserThreads($userId, 50);

Header::render('Let\'s chat.');
?>

<div class="chat-layout">
    <!-- Sidebar with thread list -->
    <div class="thread-sidebar">
        <button type="button" class="btn btn-primary btn-block" id="newChatButton">+ New Chat</button>
        
        <div class="thread-list">
            <?php foreach ($userThreads as $thread): ?>
                <a href="/?thread=<?php echo $thread['id']; ?>" 
                   class="thread-item <?php echo ($currentThreadId == $thread['id']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($thread['name']); ?>
                </a>
            <?php endforeach; ?>
            <?php if (empty($userThreads)): ?>
                <div class="thread-item-empty">No previous conversations</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main chat area -->
    <div class="chat-container">
        <div class="chat-header">
            <h1>Let's chat.</h1>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="chat-message chat-message-<?php echo $message['role']; ?>">
                        <div class="message-content"><?php echo htmlspecialchars($message['content']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="welcome-message">
                    <p>Welcome! Start a conversation or click "Use Voice" to speak.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="chat-input-container">
            <form id="chatForm" class="chat-form">
                <input type="hidden" id="currentThreadId" value="<?php echo htmlspecialchars($currentThreadId ?? ''); ?>">
                <input type="text" 
                       id="chatInput" 
                       class="chat-input" 
                       placeholder="Type your message here..." 
                       autocomplete="off">
                <button type="submit" class="btn btn-primary btn-submit">Send</button>
                <button type="button" class="btn btn-secondary btn-voice" id="voiceButton">Use Voice</button>
                <select id="playbackSpeed" class="playback-speed-selector" title="Audio playback speed">
                    <option value="0.75">🔊 0.75x</option>
                    <option value="1.0" selected>🔊 1.0x</option>
                    <option value="1.25">🔊 1.25x</option>
                    <option value="1.5">🔊 1.5x</option>
                    <option value="2.0">🔊 2.0x</option>
                </select>
            </form>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Debug Sidebar (Admin Only) -->
<div class="debug-sidebar" id="debugSidebar">
    <div class="debug-resize-handle" id="debugResizeHandle" title="Drag to resize"></div>
    <div class="debug-header" id="debugHeader">
        <h3>🐛 Debug Console</h3>
        <div class="debug-controls">
            <button type="button" class="btn-debug-small" id="debugClear" title="Clear logs">🗑️</button>
            <button type="button" class="btn-debug-small" id="debugExport" title="Export logs">📋</button>
        </div>
    </div>
    <div class="debug-log-container" id="debugLogContainer">
        <!-- Debug logs will appear here -->
    </div>
</div>

<!-- Debug Toggle Button (Admin Only) -->
<button type="button" class="debug-toggle-btn" id="debugToggle">🐛 Debug (OFF)</button>
<?php endif; ?>

<?php
Footer::render();
?>
<script src="/js/chat.js"></script>
