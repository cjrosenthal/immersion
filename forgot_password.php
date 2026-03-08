<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();

require_once BASE_PATH . '/lib/SessionManagement.php';

// Redirect if already logged in
if (SessionManagement::isLoggedIn()) {
    Application::redirect('/index.php');
}

require_once BASE_PATH . '/lib/SettingsManagement.php';
$siteTitle = SettingsManagement::get('site_title', 'Immersion');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo htmlspecialchars($siteTitle); ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1><?php echo htmlspecialchars($siteTitle); ?></h1>
            <h2>Forgot Password</h2>
            
            <?php 
            $flash = Application::getFlashMessage();
            if ($flash): 
            ?>
            <div class="flash-message flash-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
            <?php endif; ?>
            
            <p>Enter your email address and we'll send you a link to reset your password.</p>
            
            <form method="POST" action="/forgot_password_process.php" class="login-form">
                <?php 
                require_once BASE_PATH . '/lib/CSRF.php';
                echo CSRF::getTokenField(); 
                ?>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
            
            <div class="login-links">
                <a href="/login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
