<?php
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

$userId = $_GET['id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    Application::setFlashMessage('Invalid user ID.', 'error');
    Application::redirect('/admin/users/list.php');
}

$user = UserManagement::getUserById($userId);

if (!$user) {
    Application::setFlashMessage('User not found.', 'error');
    Application::redirect('/admin/users/list.php');
}

Header::render('Edit User - Admin');
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Edit User</h1>
        <a href="/admin/users/list.php" class="btn btn-secondary">Back to Users</a>
    </div>
    
    <form method="POST" action="/admin/users/update.php" class="admin-form">
        <?php 
        require_once BASE_PATH . '/lib/CSRF.php';
        echo CSRF::getTokenField(); 
        ?>
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" 
                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" 
                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_admin" value="1" 
                       <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                Administrator
            </label>
        </div>
        
        <button type="submit" class="btn btn-primary">Update User</button>
    </form>
    
    <hr>
    
    <h2>Change Password</h2>
    <form method="POST" action="/admin/users/update_password.php" class="admin-form">
        <?php echo CSRF::getTokenField(); ?>
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>

<?php
Footer::render();
?>
