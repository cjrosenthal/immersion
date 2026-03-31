<?php
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';
require_once BASE_PATH . '/lib/ThreadManagement.php';
require_once BASE_PATH . '/lib/MessageManagement.php';
require_once BASE_PATH . '/lib/UserManagement.php';

// Get thread ID
$threadId = $_GET['id'] ?? null;

if (!$threadId) {
    $_SESSION['error'] = 'Thread ID is required';
    header('Location: /admin/threads/list.php');
    exit;
}

// Get thread details
$thread = ThreadManagement::getThread($threadId);

if (!$thread) {
    $_SESSION['error'] = 'Thread not found';
    header('Location: /admin/threads/list.php');
    exit;
}

// Get user details
$user = UserManagement::getUserById($thread['user_id']);

// Get all messages in this thread
$messages = MessageManagement::getThreadMessages($threadId, 1000);

Header::render('View Thread - Admin');
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Thread: <?php echo htmlspecialchars($thread['name']); ?></h1>
        <a href="/admin/threads/list.php" class="btn btn-secondary">← Back to Threads</a>
    </div>
    
    <div class="thread-info" style="background: #f5f5f5; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
        <p><strong>Thread ID:</strong> <?php echo htmlspecialchars($thread['id']); ?></p>
        <p><strong>User:</strong> 
            <?php 
            if ($user) {
                echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')');
            } else {
                echo 'Unknown User';
            }
            ?>
        </p>
        <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($thread['created_at'])); ?></p>
        <p><strong>Last Activity:</strong> <?php echo date('M j, Y g:i A', strtotime($thread['last_message_at'])); ?></p>
        <p><strong>Total Messages:</strong> <?php echo count($messages); ?></p>
    </div>
    
    <h2>Conversation</h2>
    
    <?php if (empty($messages)): ?>
        <p>No messages in this thread.</p>
    <?php else: ?>
        <div class="thread-messages" style="background: #fff; padding: 1.5rem; border-radius: 0.5rem;">
            <?php foreach ($messages as $message): ?>
                <div class="thread-message" style="margin-bottom: 1.5rem; padding: 1rem; border-left: 4px solid <?php echo $message['role'] === 'user' ? '#10a37f' : '#667eea'; ?>; background: #f9f9f9; border-radius: 0.25rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <strong style="color: <?php echo $message['role'] === 'user' ? '#10a37f' : '#667eea'; ?>; text-transform: capitalize;">
                            <?php echo htmlspecialchars($message['role']); ?>
                        </strong>
                        <span style="color: #888; font-size: 0.875rem;">
                            <?php echo date('M j, Y g:i:s A', strtotime($message['created_at'])); ?>
                        </span>
                    </div>
                    <div style="white-space: pre-wrap; line-height: 1.6;">
                        <?php echo htmlspecialchars($message['content']); ?>
                    </div>
                    <?php if ($message['interrupted']): ?>
                        <div style="margin-top: 0.5rem; color: #e74c3c; font-style: italic; font-size: 0.875rem;">
                            ⚠️ Interrupted
                            <?php if ($message['partial_content']): ?>
                                <br>
                                <span style="color: #888;">Partial content: <?php echo htmlspecialchars(substr($message['partial_content'], 0, 100)); ?>...</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
Footer::render();
?>
