<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

Header::render('Let\'s chat.');
?>

<div class="chat-container">
    <div class="chat-header">
        <h1>Let's chat.</h1>
    </div>
    
    <div class="chat-messages" id="chatMessages">
        <div class="welcome-message">
            <p>Welcome! Start a conversation or click "Use Voice" to speak.</p>
        </div>
    </div>
    
    <div class="chat-input-container">
        <form id="chatForm" class="chat-form">
            <input type="text" 
                   id="chatInput" 
                   class="chat-input" 
                   placeholder="Type your message here..." 
                   autocomplete="off">
            <button type="submit" class="btn btn-primary btn-submit">Send</button>
            <button type="button" class="btn btn-secondary btn-voice" id="voiceButton">Use Voice</button>
        </form>
    </div>
</div>

<?php
Footer::render();
?>
