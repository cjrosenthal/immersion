<?php
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

// Get form data from session if it exists (from failed submission)
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']); // Clear it after retrieving

Header::render('Create User - Admin');
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Create New User</h1>
        <a href="/admin/users/list.php" class="btn btn-secondary">Back to Users</a>
    </div>
    
    <form method="POST" action="/admin/users/create_process.php" class="admin-form">
        <?php 
        require_once BASE_PATH . '/lib/CSRF.php';
        echo CSRF::getTokenField(); 
        ?>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name"
                   value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name"
                   value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_admin" value="1"
                       <?php echo (!empty($formData['is_admin'])) ? 'checked' : ''; ?>>
                Administrator
            </label>
        </div>
        
        <button type="submit" class="btn btn-primary">Create User</button>
    </form>
</div>

<?php
Footer::render();
?>
