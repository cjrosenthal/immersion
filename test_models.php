<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin();

require_once BASE_PATH . '/lib/ClaudeService.php';
$config = require BASE_PATH . '/config.php';

// List of common Claude models to test
$modelsToTest = [
    'claude-3-5-sonnet-20241022',
    'claude-3-5-sonnet-20240620',
    'claude-3-sonnet-20240229',
    'claude-3-opus-20240229',
    'claude-3-haiku-20240307',
    'claude-2.1',
    'claude-2.0'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Claude Model Finder</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test-box { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; }
        .testing { color: blue; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 12px; }
        .model-name { font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>
    <h1>🔍 Claude Model Finder</h1>
    <p>Testing which Claude models your API key has access to...</p>
    <p><strong>API Key:</strong> <?php echo substr($config['api']['claude_key'], 0, 10); ?>...<?php echo substr($config['api']['claude_key'], -4); ?></p>
    
    <?php
    $workingModels = [];
    
    foreach ($modelsToTest as $model) {
        echo '<div class="test-box">';
        echo '<p class="model-name">Testing: ' . htmlspecialchars($model) . '</p>';
        echo '<p class="testing">Sending test message...</p>';
        
        try {
            $claude = new ClaudeService($config['api']['claude_key'], $model);
            $response = $claude->chat([
                ['role' => 'user', 'content' => 'Say "OK"']
            ]);
            
            echo '<p class="success">✓ SUCCESS! This model works!</p>';
            echo '<p>Response: <code>' . htmlspecialchars($response) . '</code></p>';
            $workingModels[] = $model;
            
        } catch (Exception $e) {
            echo '<p class="error">✗ Failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        echo '</div>';
        flush();
    }
    ?>
    
    <div class="test-box" style="background: #e8f5e9;">
        <h2>✅ Summary</h2>
        <?php if (count($workingModels) > 0): ?>
            <p><strong>Working Models (<?php echo count($workingModels); ?>):</strong></p>
            <ul>
            <?php foreach ($workingModels as $model): ?>
                <li><code><?php echo htmlspecialchars($model); ?></code></li>
            <?php endforeach; ?>
            </ul>
            
            <h3>📝 Next Steps:</h3>
            <p>Add this to your <code>config.local.php</code>:</p>
            <pre>'api' => [
    'claude_key' => '...',
    'claude_model' => '<?php echo htmlspecialchars($workingModels[0]); ?>',
    'openai_key' => '...'
]</pre>
        <?php else: ?>
            <p class="error">❌ No working models found!</p>
            <p>This might mean:</p>
            <ul>
                <li>Your API key is invalid</li>
                <li>Your API key doesn't have access to the Claude API</li>
                <li>Your account doesn't have credits</li>
            </ul>
        <?php endif; ?>
    </div>
    
    <p><a href="/test_claude.php">← Back to Claude Test</a> | <a href="/index.php">Go to Chat</a></p>
</body>
</html>
