<?php
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';
require_once BASE_PATH . '/lib/Database.php';
require_once BASE_PATH . '/lib/UserManagement.php';

// Get all threads with user information
$db = Database::getInstance();
$threads = $db->fetchAll(
    "SELECT t.*, u.email, u.first_name, u.last_name,
            (SELECT COUNT(*) FROM messages WHERE thread_id = t.id) as message_count
     FROM threads t
     LEFT JOIN users u ON t.user_id = u.id
     ORDER BY t.last_message_at DESC
     LIMIT 100"
);

Header::render('Threads - Admin');
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>All Conversation Threads</h1>
    </div>
    
    <?php if (empty($threads)): ?>
        <p>No threads found.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>User</th>
                    <th>Messages</th>
                    <th>Last Activity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($threads as $thread): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($thread['id']); ?></td>
                        <td>
                            <a href="/admin/threads/view.php?id=<?php echo $thread['id']; ?>">
                                <?php echo htmlspecialchars($thread['name']); ?>
                            </a>
                        </td>
                        <td>
                            <?php 
                            if ($thread['first_name'] && $thread['last_name']) {
                                echo htmlspecialchars($thread['first_name'] . ' ' . $thread['last_name']);
                            } else {
                                echo htmlspecialchars($thread['email']);
                            }
                            ?>
                        </td>
                        <td><?php echo $thread['message_count']; ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($thread['last_message_at'])); ?></td>
                        <td>
                            <a href="/admin/threads/view.php?id=<?php echo $thread['id']; ?>" class="btn btn-small btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
Footer::render();
?>
