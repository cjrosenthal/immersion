<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/lib/Application.php';
Application::init();
Application::requireAuth();
Application::requireAdmin();

require_once BASE_PATH . '/includes/Header.php';
require_once BASE_PATH . '/includes/Footer.php';

Header::render('Debug & Testing');
?>

<div class="main-content">
    <div class="admin-container">
        <div class="admin-header">
            <h1>🐛 Debug & Component Testing</h1>
            <a href="/" class="btn btn-secondary">Back to Chat</a>
        </div>
        
        <div class="test-section">
            <h2>🎤 Speech Recognition Test</h2>
            <p>Test the browser's speech recognition capability.</p>
            <button type="button" id="testSpeechRecognition" class="btn btn-primary">Start Recording</button>
            <button type="button" id="stopSpeechRecognition" class="btn btn-secondary" disabled>Stop Recording</button>
            <div class="test-result" id="speechResult">
                <strong>Status:</strong> <span id="speechStatus">Ready</span><br>
                <strong>Interim:</strong> <span id="speechInterim">-</span><br>
                <strong>Final:</strong> <span id="speechFinal">-</span>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🔊 Text-to-Speech Test</h2>
            <p>Test OpenAI TTS API integration.</p>
            <textarea id="ttsText" class="test-textarea" placeholder="Enter text to convert to speech...">Hello! This is a test of the text-to-speech system.</textarea>
            <select id="ttsVoice" class="test-select">
                <option value="alloy">Alloy</option>
                <option value="echo">Echo</option>
                <option value="fable">Fable</option>
                <option value="onyx">Onyx</option>
                <option value="nova">Nova</option>
                <option value="shimmer">Shimmer</option>
            </select>
            <button type="button" id="testTTS" class="btn btn-primary">Generate & Play Audio</button>
            <div class="test-result" id="ttsResult">
                <strong>Status:</strong> <span id="ttsStatus">Ready</span>
            </div>
        </div>
        
        <div class="test-section">
            <h2>💬 Claude API Test</h2>
            <p>Test Claude API connectivity and streaming.</p>
            <textarea id="claudePrompt" class="test-textarea" placeholder="Enter a test prompt...">What is 2+2? Please answer briefly.</textarea>
            <button type="button" id="testClaude" class="btn btn-primary">Send to Claude</button>
            <div class="test-result" id="claudeResult">
                <strong>Status:</strong> <span id="claudeStatus">Ready</span><br>
                <strong>Response:</strong> <pre id="claudeResponse">-</pre>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🔄 Full Voice Flow Test</h2>
            <p>Simulate the complete voice interaction: Speech → Claude → TTS</p>
            <input type="text" id="fullFlowText" class="test-input" placeholder="Or type test text here..." value="Tell me a very short joke">
            <button type="button" id="testFullFlow" class="btn btn-primary">Run Full Flow Test</button>
            <div class="test-result" id="fullFlowResult">
                <strong>Status:</strong> <span id="fullFlowStatus">Ready</span><br>
                <strong>Progress:</strong> <span id="fullFlowProgress">-</span>
            </div>
        </div>
        
        <div class="test-section">
            <h2>⚠️ Error Simulation</h2>
            <p>Simulate various error conditions to test error handling.</p>
            <button type="button" id="testNetworkError" class="btn btn-secondary">Simulate Network Error</button>
            <button type="button" id="testAPIError" class="btn btn-secondary">Simulate API Error</button>
            <button type="button" id="testTimeout" class="btn btn-secondary">Simulate Timeout</button>
            <div class="test-result" id="errorResult">
                <strong>Last Error:</strong> <span id="errorMessage">None</span>
            </div>
        </div>
    </div>
</div>

<style>
.test-section {
    background: #f8f9fa;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid #e5e5e5;
}

.test-section h2 {
    margin-bottom: 0.5rem;
    font-size: 1.3rem;
}

.test-section p {
    color: #666;
    margin-bottom: 1rem;
}

.test-textarea {
    width: 100%;
    min-height: 100px;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    font-family: inherit;
    font-size: 1rem;
    margin-bottom: 0.75rem;
    resize: vertical;
}

.test-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    font-size: 1rem;
    margin-bottom: 0.75rem;
}

.test-select {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    font-size: 1rem;
    margin-right: 0.75rem;
    margin-bottom: 0.75rem;
}

.test-result {
    margin-top: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 0.9rem;
}

.test-result pre {
    margin: 0.5rem 0 0 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>

<script>
// Speech Recognition Test
let testRecognition = null;

document.getElementById('testSpeechRecognition').addEventListener('click', () => {
    if (!('webkitSpeechRecognition' in window)) {
        alert('Speech recognition not supported in this browser.');
        return;
    }
    
    testRecognition = new webkitSpeechRecognition();
    testRecognition.continuous = true;
    testRecognition.interimResults = true;
    testRecognition.lang = 'en-US';
    
    testRecognition.onstart = () => {
        document.getElementById('speechStatus').textContent = 'Listening...';
        document.getElementById('testSpeechRecognition').disabled = true;
        document.getElementById('stopSpeechRecognition').disabled = false;
    };
    
    testRecognition.onresult = (event) => {
        let interim = '';
        let final = '';
        
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                final += event.results[i][0].transcript;
            } else {
                interim += event.results[i][0].transcript;
            }
        }
        
        document.getElementById('speechInterim').textContent = interim || '-';
        if (final) {
            document.getElementById('speechFinal').textContent = final;
        }
    };
    
    testRecognition.onerror = (event) => {
        document.getElementById('speechStatus').textContent = 'Error: ' + event.error;
    };
    
    testRecognition.onend = () => {
        document.getElementById('speechStatus').textContent = 'Stopped';
        document.getElementById('testSpeechRecognition').disabled = false;
        document.getElementById('stopSpeechRecognition').disabled = true;
    };
    
    testRecognition.start();
});

document.getElementById('stopSpeechRecognition').addEventListener('click', () => {
    if (testRecognition) {
        testRecognition.stop();
    }
});

// TTS Test
document.getElementById('testTTS').addEventListener('click', async () => {
    const text = document.getElementById('ttsText').value;
    const voice = document.getElementById('ttsVoice').value;
    
    if (!text.trim()) {
        alert('Please enter some text.');
        return;
    }
    
    document.getElementById('ttsStatus').textContent = 'Generating audio...';
    
    try {
        const response = await fetch('/chat_speak.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ text, voice })
        });
        
        if (!response.ok) {
            throw new Error('TTS request failed');
        }
        
        const audioBlob = await response.blob();
        const audioUrl = URL.createObjectURL(audioBlob);
        const audio = new Audio(audioUrl);
        
        document.getElementById('ttsStatus').textContent = 'Playing...';
        
        audio.onended = () => {
            URL.revokeObjectURL(audioUrl);
            document.getElementById('ttsStatus').textContent = 'Complete';
        };
        
        audio.play();
        
    } catch (error) {
        document.getElementById('ttsStatus').textContent = 'Error: ' + error.message;
    }
});

// Claude API Test
document.getElementById('testClaude').addEventListener('click', async () => {
    const prompt = document.getElementById('claudePrompt').value;
    
    if (!prompt.trim()) {
        alert('Please enter a prompt.');
        return;
    }
    
    document.getElementById('claudeStatus').textContent = 'Sending...';
    document.getElementById('claudeResponse').textContent = '';
    
    try {
        const response = await fetch('/chat_send.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: prompt })
        });
        
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let fullResponse = '';
        
        while (true) {
            const {value, done} = await reader.read();
            if (done) break;
            
            buffer += decoder.decode(value, {stream: true});
            const lines = buffer.split('\n');
            buffer = lines.pop();
            
            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = JSON.parse(line.substring(6));
                    
                    if (data.type === 'chunk') {
                        fullResponse += data.text;
                        document.getElementById('claudeResponse').textContent = fullResponse;
                        document.getElementById('claudeStatus').textContent = 'Streaming...';
                    } else if (data.type === 'done') {
                        document.getElementById('claudeStatus').textContent = 'Complete';
                    } else if (data.type === 'error') {
                        document.getElementById('claudeStatus').textContent = 'Error: ' + data.message;
                    }
                }
            }
        }
        
    } catch (error) {
        document.getElementById('claudeStatus').textContent = 'Error: ' + error.message;
        document.getElementById('claudeResponse').textContent = error.stack;
    }
});

// Full Flow Test
document.getElementById('testFullFlow').addEventListener('click', async () => {
    const text = document.getElementById('fullFlowText').value;
    
    if (!text.trim()) {
        alert('Please enter test text.');
        return;
    }
    
    document.getElementById('fullFlowStatus').textContent = 'Running...';
    document.getElementById('fullFlowProgress').textContent = 'Step 1: Sending to Claude...';
    
    try {
        // Step 1: Send to Claude
        const response = await fetch('/chat_send.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: text })
        });
        
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let fullResponse = '';
        
        while (true) {
            const {value, done} = await reader.read();
            if (done) break;
            
            buffer += decoder.decode(value, {stream: true});
            const lines = buffer.split('\n');
            buffer = lines.pop();
            
            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = JSON.parse(line.substring(6));
                    if (data.type === 'chunk') {
                        fullResponse += data.text;
                    }
                }
            }
        }
        
        document.getElementById('fullFlowProgress').textContent = 'Step 2: Converting to speech...';
        
        // Step 2: Convert to TTS
        const ttsResponse = await fetch('/chat_speak.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ text: fullResponse, voice: 'alloy' })
        });
        
        const audioBlob = await ttsResponse.blob();
        const audioUrl = URL.createObjectURL(audioBlob);
        const audio = new Audio(audioUrl);
        
        document.getElementById('fullFlowProgress').textContent = 'Step 3: Playing audio...';
        
        audio.onended = () => {
            URL.revokeObjectURL(audioUrl);
            document.getElementById('fullFlowStatus').textContent = 'Complete!';
            document.getElementById('fullFlowProgress').textContent = 'All steps completed successfully';
        };
        
        audio.play();
        
    } catch (error) {
        document.getElementById('fullFlowStatus').textContent = 'Error';
        document.getElementById('fullFlowProgress').textContent = error.message;
    }
});

// Error Simulation
document.getElementById('testNetworkError').addEventListener('click', () => {
    document.getElementById('errorMessage').textContent = 'Network error simulated';
    debugLogger.log('SIMULATED_ERROR', 'Network error', 'Connection failed');
});

document.getElementById('testAPIError').addEventListener('click', () => {
    document.getElementById('errorMessage').textContent = 'API error simulated';
    debugLogger.log('API_ERROR', 'Simulated API failure', 'Status 500');
});

document.getElementById('testTimeout').addEventListener('click', () => {
    document.getElementById('errorMessage').textContent = 'Timeout simulated';
    debugLogger.log('SIMULATED_ERROR', 'Request timeout', 'No response after 30s');
});
</script>

<script src="/js/chat.js"></script>

<?php
Footer::render();
?>
