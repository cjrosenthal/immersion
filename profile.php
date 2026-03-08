<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/UserManagement.php';
require_once BASE_PATH . '/lib/ImageManagement.php';
require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

$userId = SessionManagement::getUserId();
$user = UserManagement::getUserById($userId);

if (!$user) {
    Application::setFlashMessage('User not found.', 'error');
    Application::redirect('/index.php');
}

Header::render('Profile');
?>

<div class="profile-container">
    <h1>Profile</h1>
    
    <div class="profile-section">
        <h2>Profile Photo</h2>
        <div class="profile-photo-section">
            <?php if ($user['image_id']): ?>
                <img src="<?php echo htmlspecialchars(ImageManagement::getImageUrl($user['image_id'])); ?>" 
                     alt="Profile Photo" class="profile-photo-large">
            <?php else: 
                $initials = '';
                if ($user['first_name']) {
                    $initials .= strtoupper(substr($user['first_name'], 0, 1));
                }
                if ($user['last_name']) {
                    $initials .= strtoupper(substr($user['last_name'], 0, 1));
                }
                if (empty($initials)) {
                    $initials = strtoupper(substr($user['email'], 0, 2));
                }
            ?>
                <div class="profile-initials-large"><?php echo htmlspecialchars($initials); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="/profile_update_photo.php" enctype="multipart/form-data" class="photo-upload-form">
                <?php 
                require_once BASE_PATH . '/lib/CSRF.php';
                echo CSRF::getTokenField(); 
                ?>
                <div class="form-group">
                    <label for="photo">Upload New Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Upload Photo</button>
            </form>
        </div>
    </div>
    
    <div class="profile-section">
        <h2>Account Information</h2>
        <form method="POST" action="/profile_update.php" class="profile-form">
            <?php echo CSRF::getTokenField(); ?>
            
            <div class="form-group">
                <label for="email">Email</label>
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
            
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
    
    <div class="profile-section">
        <h2>Change Password</h2>
        <form method="POST" action="/profile_change_password.php" class="profile-form">
            <?php echo CSRF::getTokenField(); ?>
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password_confirm">Confirm New Password</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<?php
Footer::render();
?>
