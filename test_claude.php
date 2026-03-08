<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAdmin(); // Only admins can access test page

require_once BASE_PATH . '/lib/ClaudeService.php';

$config = require BASE_PATH . '/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Claude API Test</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Claude API Connection Test</h1>
    
    <div class="test-box">
        <h2>Configuration</h2>
        <p><strong>API Key:</strong> <?php echo substr($config['api']['claude_key'], 0, 10); ?>...<?php echo substr($config['api']['claude_key'], -4); ?></p>
        <p><strong>API URL:</strong> https://api.anthropic.com/v1/messages</p>
        <p><strong>Model:</strong> <?php echo htmlspecialchars($config['api']['claude_model']); ?></p>
    </div>
    
    <div class="test-box">
        <h2>Test 1: Simple Non-Streaming Request</h2>
        <?php
        try {
            $claude = new ClaudeService($config['api']['claude_key'], $config['api']['claude_model']);
            
            $messages = [
                ['role' => 'user', 'content' => 'Say "Hello, World!" and nothing else.']
            ];
            
            echo "<p class='info'>Sending test message...</p>";
            
            $response = $claude->chat($messages);
            
            echo "<p class='success'>✓ SUCCESS!</p>";
            echo "<p><strong>Response:</strong></p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="test-box">
        <h2>Test 2: Streaming Request</h2>
        <?php
        try {
            $claude = new ClaudeService($config['api']['claude_key'], $config['api']['claude_model']);
            
            $messages = [
                ['role' => 'user', 'content' => 'Count from 1 to 5.']
            ];
            
            echo "<p class='info'>Sending streaming test message...</p>";
            
            $chunks = [];
            $fullResponse = $claude->streamChat($messages, function($chunk) use (&$chunks) {
                $chunks[] = $chunk;
            });
            
            echo "<p class='success'>✓ SUCCESS!</p>";
            echo "<p><strong>Full Response:</strong></p>";
            echo "<pre>" . htmlspecialchars($fullResponse) . "</pre>";
            echo "<p><strong>Received " . count($chunks) . " chunks</strong></p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="test-box">
        <h2>Test 3: Raw cURL Test</h2>
        <?php
        $data = json_encode([
            'model' => $config['api']['claude_model'],
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'user', 'content' => 'Say hi']
            ]
        ]);
        
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $config['api']['claude_key'],
            'anthropic-version: 2023-06-01'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "<p><strong>HTTP Status Code:</strong> " . $httpCode . "</p>";
        
        if ($error) {
            echo "<p class='error'>cURL Error: " . htmlspecialchars($error) . "</p>";
        }
        
        if ($httpCode === 200) {
            echo "<p class='success'>✓ SUCCESS!</p>";
            $decoded = json_decode($response, true);
            if ($decoded && isset($decoded['content'][0]['text'])) {
                echo "<p><strong>Response:</strong> " . htmlspecialchars($decoded['content'][0]['text']) . "</p>";
            }
        } else {
            echo "<p class='error'>✗ FAILED</p>";
        }
        
        echo "<p><strong>Raw Response:</strong></p>";
