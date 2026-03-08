<?php
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

Header::render('Threads - Admin');
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Threads</h1>
    </div>
    
    <p>Thread management will be implemented when chat functionality is added.</p>
</div>

<?php
Footer::render();
?>
