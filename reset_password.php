<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();

require_once BASE_PATH . '/lib/SessionManagement.php';
require_once BASE_PATH . '/lib/UserManagement.php';

// Redirect if already logged in
if (SessionManagement::isLoggedIn()) {
    Application::redirect('/index.php');
}

$token = $_GET['token'] ?? '';

// Validate token
$tokenData = null;
if ($token) {
    $tokenData = UserManagement::validatePasswordResetToken($token);
}

if (!$tokenData) {
    Application::setFlashMessage('Invalid or expired password reset link.', 'error');
    Application::redirect('/login.php');
}

require_once BASE_PATH . '/lib/SettingsManagement.php';
$siteTitle = SettingsManagement::get('site_title', 'Immersion');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo htmlspecialchars($siteTitle); ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1><?php echo htmlspecialchars($siteTitle); ?></h1>
            <h2>Reset Password</h2>
            
            <?php 
            $flash = Application::getFlashMessage();
            if ($flash): 
            ?>
            <div class="flash-message flash-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="/reset_password_process.php" class="login-form">
                <?php 
                require_once BASE_PATH . '/lib/CSRF.php';
                echo CSRF::getTokenField(); 
                ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>
            
            <div class="login-links">
                <a href="/login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
