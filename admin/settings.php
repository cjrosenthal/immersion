<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/lib/SettingsManagement.php';
require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

$siteTitle = SettingsManagement::get('site_title', 'Immersion');

Header::render('Settings - Admin');
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Global Settings</h1>
    </div>
    
    <form method="POST" action="/admin/settings_update.php" class="admin-form">
        <?php 
        require_once BASE_PATH . '/lib/CSRF.php';
        echo CSRF::getTokenField(); 
        ?>
        
        <div class="form-group">
            <label for="site_title">Site Title</label>
            <input type="text" id="site_title" name="site_title" 
                   value="<?php echo htmlspecialchars($siteTitle); ?>" required>
            <p class="form-help">This title appears on the login page and in the header.</p>
        </div>
        
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php
Footer::render();
?>
