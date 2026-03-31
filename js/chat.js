/**
 * Chat and Voice Management
 */

class DebugLogger {
    constructor() {
        this.logs = [];
        this.maxLogs = 100;
        this.enabled = localStorage.getItem('debugMode') === 'true';
    }
    
    log(type, message, data = null) {
        if (!this.enabled) return;
        
        const timestamp = new Date().toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            fractionalSecondDigits: 3
        });
        
        const logEntry = {
            timestamp,
            type,
            message,
            data
        };
        
        this.logs.push(logEntry);
        
        // Keep only last maxLogs entries
        if (this.logs.length > this.maxLogs) {
            this.logs.shift();
        }
        
        // Display in debug panel
        this.displayLog(logEntry);
        
        // Also log to console for debugging
        console.log(`[${timestamp}] ${type}: ${message}`, data || '');
    }
    
    displayLog(entry) {
        const container = document.getElementById('debugLogContainer');
        if (!container) return;
        
        const logDiv = document.createElement('div');
        logDiv.className = `debug-log-entry debug-log-${this.getLogClass(entry.type)}`;
        
        const icon = this.getLogIcon(entry.type);
        const dataStr = entry.data ? ` | ${JSON.stringify(entry.data).substring(0, 50)}` : '';
        
        logDiv.innerHTML = `
            <span class="debug-log-time">${entry.timestamp}</span>
            <span class="debug-log-icon">${icon}</span>
            <span class="debug-log-message">${this.escapeHtml(entry.message)}${dataStr}</span>
        `;
        
        container.appendChild(logDiv);
        container.scrollTop = container.scrollHeight;
    }
    
    getLogClass(type) {
        if (type.includes('ERROR')) return 'error';
        if (type.includes('SPEECH') || type.includes('LISTENING') || type.includes('VOICE')) return 'speech';
        if (type.includes('TTS') || type.includes('AUDIO')) return 'audio';
        if (type.includes('API') || type.includes('RESPONSE') || type.includes('MESSAGE')) return 'api';
        if (type.includes('INTERRUPTED') || type.includes('STOPPED')) return 'warning';
        return 'info';
    }
    
    getLogIcon(type) {
        if (type.includes('ERROR')) return '❌';
        if (type.includes('SPEECH') || type.includes('LISTENING')) return '🎤';
        if (type.includes('VOICE')) return '🗣️';
        if (type.includes('TTS') || type.includes('AUDIO')) return '🔊';
        if (type.includes('API') || type.includes('RESPONSE')) return '💬';
        if (type.includes('MESSAGE')) return '📤';
        if (type.includes('INTERRUPTED')) return '⏸️';
        if (type.includes('STOPPED')) return '⏹️';
        return 'ℹ️';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    clear() {
        this.logs = [];
        const container = document.getElementById('debugLogContainer');
        if (container) {
            container.innerHTML = '';
        }
    }
    
    export() {
        return JSON.stringify(this.logs, null, 2);
    }
    
    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('debugMode', this.enabled);
        
        const sidebar = document.getElementById('debugSidebar');
        if (sidebar) {
            sidebar.classList.toggle('active', this.enabled);
        }
        
        return this.enabled;
    }
}

// Global debug logger instance
const debugLogger = new DebugLogger();

class ChatManager {
    constructor() {
        this.currentThreadId = null;
        this.isStreaming = false;
        this.currentEventSource = null;
    }
    
    init() {
        const form = document.getElementById('chatForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        const newChatButton = document.getElementById('newChatButton');
        if (newChatButton) {
            newChatButton.addEventListener('click', () => this.startNewChat());
        }
        
        // Get current thread ID from hidden input if present
        const threadInput = document.getElementById('currentThreadId');
        if (threadInput && threadInput.value) {
            this.currentThreadId = threadInput.value;
        }
    }
    
    startNewChat() {
        // Redirect to homepage without thread parameter
        window.location.href = '/';
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        
        if (!message || this.isStreaming) {
            return;
        }
        
        debugLogger.log('TEXT_MODE', 'User sent text message', message.substring(0, 50) + (message.length > 50 ? '...' : ''));
        
        // Clear input
        input.value = '';
        
        // Display user message
        this.displayMessage('user', message);
        
        // Send to server and stream response
        await this.sendMessage(message);
    }
    
    async sendMessage(message) {
        this.isStreaming = true;
        
        debugLogger.log('MESSAGE_SENT', 'Sending to Claude API', message.substring(0, 50) + (message.length > 50 ? '...' : ''));
        
        try {
            // Create assistant message placeholder
            const messageId = this.displayMessage('assistant', '', true);
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            const contentElement = messageElement.querySelector('.message-content');
            
            // Use EventSource for SSE
            const response = await fetch('/chat_send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    thread_id: this.currentThreadId
                })
            });
            
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            
            while (true) {
                const {value, done} = await reader.read();
                if (done) break;
                
                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop(); // Keep incomplete line in buffer
                
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = JSON.parse(line.substring(6));
                        
                        if (data.type === 'thread_id') {
                            this.currentThreadId = data.thread_id;
                            debugLogger.log('THREAD_CREATED', 'New thread', data.thread_id);
                        } else if (data.type === 'chunk') {
                            contentElement.textContent += data.text;
                            this.scrollToBottom();
                        } else if (data.type === 'done') {
                            debugLogger.log('RESPONSE_COMPLETE', 'Full response received', contentElement.textContent.substring(0, 50) + '...');
                        } else if (data.type === 'error') {
                            contentElement.textContent = 'Error: ' + data.message;
                            debugLogger.log('API_ERROR', 'Error from API', data.message);
                        }
                    }
                }
            }
            
        } catch (error) {
            console.error('Chat error:', error);
            debugLogger.log('API_ERROR', 'Chat error', error.message);
            alert('Error sending message: ' + error.message);
        } finally {
            this.isStreaming = false;
        }
    }
    
    displayMessage(role, content, isStreaming = false) {
        const messagesContainer = document.getElementById('chatMessages');
        
        // Remove welcome message if present
        const welcome = messagesContainer.querySelector('.welcome-message');
        if (welcome) {
            welcome.remove();
        }
        
        const messageId = 'msg-' + Date.now() + '-' + Math.random();
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message chat-message-${role}`;
        messageDiv.setAttribute('data-message-id', messageId);
        
        messageDiv.innerHTML = `
            <div class="message-content">${this.escapeHtml(content)}</div>
            ${isStreaming ? '<div class="message-streaming">●</div>' : ''}
        `;
        
        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
        
        return messageId;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    scrollToBottom() {
        const messagesContainer = document.getElementById('chatMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

class VoiceManager {
    constructor(chatManager) {
        this.chatManager = chatManager;
        this.state = 'off'; // 'off', 'listening', 'speaking'
        this.recognition = null;
        this.audioQueue = [];
        this.audioBuffer = []; // Pre-generated audio buffer
        this.currentAudio = null;
        this.isPlaying = false;
        this.currentResponseText = '';
        this.spokenText = '';
        this.ttsVoice = 'alloy';
        this.playbackSpeed = parseFloat(localStorage.getItem('playbackSpeed') || '1.0');
        this.silenceTimer = null;
        this.interimTranscript = '';
        this.accumulatedTranscript = '';
        this.isGenerating = false; // Track if we're generating audio
    }
    
    init() {
        const voiceButton = document.getElementById('voiceButton');
        if (voiceButton) {
            voiceButton.addEventListener('click', () => this.handleVoiceButtonClick());
        }
        
        // Initialize playback speed selector
        const speedSelector = document.getElementById('playbackSpeed');
        if (speedSelector) {
            // Set initial value from localStorage
            speedSelector.value = this.playbackSpeed.toString();
            
            // Listen for changes
            speedSelector.addEventListener('change', (e) => {
                this.playbackSpeed = parseFloat(e.target.value);
                localStorage.setItem('playbackSpeed', this.playbackSpeed);
                debugLogger.log('PLAYBACK_SPEED', `Speed changed to ${this.playbackSpeed}x`);
            });
        }
        
        // Initialize speech recognition
        if ('webkitSpeechRecognition' in window) {
            this.recognition = new webkitSpeechRecognition();
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.recognition.lang = 'en-US';
            
            this.recognition.onresult = (event) => this.handleSpeechResult(event);
            this.recognition.onerror = (event) => this.handleSpeechError(event);
            this.recognition.onend = () => this.handleSpeechEnd();
        }
        
        // Load user preferences
        this.loadPreferences();
    }
    
    async loadPreferences() {
        try {
            const response = await fetch('/user_preferences.php');
            const data = await response.json();
            if (data.success) {
                this.ttsVoice = data.preferences.tts_voice || 'alloy';
            }
        } catch (error) {
            console.error('Error loading preferences:', error);
        }
    }
    
    handleVoiceButtonClick() {
        if (this.state === 'off') {
            this.startVoiceMode();
        } else if (this.state === 'listening') {
            this.stopVoiceMode();
        } else if (this.state === 'speaking') {
            this.interrupt();
        }
    }
    
    startVoiceMode() {
        if (!this.recognition) {
            alert('Speech recognition not supported in this browser. Please use Chrome.');
            return;
        }
        
        debugLogger.log('VOICE_MODE_STARTED', 'User activated voice mode');
        
        this.state = 'listening';
        this.updateUI();
        
        // Disable text input
        const input = document.getElementById('chatInput');
        input.disabled = true;
        input.placeholder = 'Click "Turn off Voice" to re-enable';
        
        // Start listening
        this.startListening();
    }
    
    stopVoiceMode() {
        debugLogger.log('VOICE_MODE_STOPPED', 'User deactivated voice mode');
        
        // Preserve current response text if we're in the middle of speaking
        if (this.state === 'speaking' && this.currentResponseText) {
            const messageElement = document.querySelector('[data-message-id]:last-child');
            if (messageElement && messageElement.classList.contains('chat-message-assistant')) {
                const contentElement = messageElement.querySelector('.message-content');
                if (contentElement) {
                    contentElement.textContent = this.currentResponseText;
                }
                // Remove streaming indicator if present
                const streamingIndicator = messageElement.querySelector('.message-streaming');
                if (streamingIndicator) {
                    streamingIndicator.remove();
                }
            }
        }
        
        this.state = 'off';
        this.updateUI();
        
        // Re-enable text input
        const input = document.getElementById('chatInput');
        input.disabled = false;
        input.placeholder = 'Type your message here...';
        
        // Stop listening
        if (this.recognition) {
            this.recognition.stop();
        }
        
        // Stop any currently playing audio
        if (this.currentAudio) {
            this.currentAudio.pause();
            this.currentAudio = null;
            debugLogger.log('AUDIO_STOPPED', 'Audio playback stopped by user');
        }
        
        // Clear audio queue and buffer
        this.audioQueue = [];
        this.audioBuffer.forEach(audio => URL.revokeObjectURL(audio.url));
        this.audioBuffer = [];
        this.isPlaying = false;
        this.isGenerating = false;
        
        // Clear any timers
        if (this.silenceTimer) {
            clearTimeout(this.silenceTimer);
            this.silenceTimer = null;
        }
    }
    
    startListening() {
        if (!this.recognition) return;
        
        this.interimTranscript = '';
        this.accumulatedTranscript = '';
        this.recognition.start();
        debugLogger.log('LISTENING_STARTED', 'Microphone activated');
        console.log('Started listening...');
    }
    
    handleSpeechResult(event) {
        let interimTranscript = '';
        let finalTranscript = '';
        
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0].transcript;
            if (event.results[i].isFinal) {
                finalTranscript += transcript;
            } else {
                interimTranscript += transcript;
            }
        }
        
        this.interimTranscript = interimTranscript;
        
        // Log speech detection
        if (interimTranscript) {
            debugLogger.log('SPEECH_DETECTED', 'Interim transcript', interimTranscript.substring(0, 50));
        }
        
        // Accumulate final transcripts
        if (finalTranscript) {
            this.accumulatedTranscript += finalTranscript + ' ';
            debugLogger.log('SPEECH_FINAL', 'Final transcript', finalTranscript.substring(0, 50) + (finalTranscript.length > 50 ? '...' : ''));
        }
        
        // Clear existing silence timer and set a new one
        if (this.silenceTimer) {
            clearTimeout(this.silenceTimer);
        }
        
        // Wait for 1.0 seconds of silence before sending (reduced from 1.5s for faster response)
        // (only if we have accumulated some speech)
        if (this.accumulatedTranscript.trim()) {
            debugLogger.log('SILENCE_TIMER', 'Waiting for pause (1.0s)');
            this.silenceTimer = setTimeout(() => {
                const messageToSend = this.accumulatedTranscript.trim();
                if (messageToSend) {
                    this.sendVoiceMessage(messageToSend);
                }
            }, 1000);
        }
    }
    
    handleSpeechError(event) {
        console.error('Speech recognition error:', event.error);
        debugLogger.log('SPEECH_ERROR', 'Recognition error', event.error);
        if (event.error === 'no-speech') {
            // Restart listening
            if (this.state === 'listening') {
                this.startListening();
            }
        }
    }
    
    handleSpeechEnd() {
        // Restart if still in listening mode
        if (this.state === 'listening') {
            setTimeout(() => {
                if (this.state === 'listening') {
                    this.startListening();
                }
            }, 100);
        }
    }
    
    simpleCleanTranscription(rawTranscript) {
        // Simple client-side cleaning - fast and effective
        let cleaned = rawTranscript.trim();
        
        // Capitalize first letter
        if (cleaned.length > 0) {
            cleaned = cleaned.charAt(0).toUpperCase() + cleaned.slice(1);
        }
        
        // Add period at end if missing punctuation
        if (cleaned.length > 0 && !/[.!?]$/.test(cleaned)) {
            cleaned += '.';
        }
        
        // Remove duplicate words (simple check)
        cleaned = cleaned.replace(/\b(\w+)\s+\1\b/gi, '$1');
        
        debugLogger.log('TRANSCRIPTION_CLEANED', 'Simple cleaning applied', cleaned.substring(0, 50));
        return cleaned;
    }
    
    async sendVoiceMessage(transcript) {
        debugLogger.log('LISTENING_STOPPED', 'Sending voice message');
        debugLogger.log('RAW_TRANSCRIPT', 'Raw speech-to-text', transcript.substring(0, 50) + (transcript.length > 50 ? '...' : ''));
        
        // Clear the accumulated transcript
        this.accumulatedTranscript = '';
        
        // Stop listening
        this.recognition.stop();
        
        // Quick client-side cleaning (no API call delay)
        const cleanedTranscript = this.simpleCleanTranscription(transcript);
        
        debugLogger.log('MESSAGE_SENT', 'Sending to Claude API (voice)', cleanedTranscript.substring(0, 50) + (cleanedTranscript.length > 50 ? '...' : ''));
        
        // Display cleaned user message
        this.chatManager.displayMessage('user', cleanedTranscript);
        
        // Get response from Claude
        this.state = 'speaking';
        this.currentResponseText = '';
        this.spokenText = '';
        this.pendingSentences = ''; // Track partial sentences
        this.hasStartedPlaying = false; // Track if we've started audio
        this.updateUI();
        
        try {
            // Send message and get response
            const response = await fetch('/chat_send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: cleanedTranscript,
                    thread_id: this.chatManager.currentThreadId
                })
            });
            
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            
            // Create message placeholder with streaming indicator
            const messageId = this.chatManager.displayMessage('assistant', '', true);
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            const contentElement = messageElement.querySelector('.message-content');
            
            // Initialize audio queue for stream-based TTS
            this.audioQueue = [];
            this.audioBuffer = [];
            this.isPlaying = true;
            this.isGenerating = false;
            
            while (true) {
                const {value, done} = await reader.read();
                if (done) break;
                
                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop();
                
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = JSON.parse(line.substring(6));
                        
                        if (data.type === 'thread_id') {
                            this.chatManager.currentThreadId = data.thread_id;
                            debugLogger.log('THREAD_CREATED', 'New thread', data.thread_id);
                        } else if (data.type === 'chunk') {
                            this.currentResponseText += data.text;
                            this.pendingSentences += data.text;
                            contentElement.textContent = this.currentResponseText;
                            this.chatManager.scrollToBottom();
                            
                            // Check if we have complete sentences to speak
                            await this.processStreamingSentences(messageId);
                            
                        } else if (data.type === 'done') {
                            debugLogger.log('RESPONSE_COMPLETE', 'Full response received', this.currentResponseText.substring(0, 50) + '...');
                            
                            // Process any remaining text
                            if (this.pendingSentences.trim()) {
                                this.audioQueue.push(this.pendingSentences.trim());
                                this.pendingSentences = '';
                                await this.fillAudioBuffer();
                            }
                            
                            // Remove streaming indicator
                            const streamingIndicator = messageElement.querySelector('.message-streaming');
                            if (streamingIndicator) {
                                streamingIndicator.remove();
                            }
                            
                            // If we haven't started playing yet, start now
                            if (!this.hasStartedPlaying) {
                                this.hasStartedPlaying = true;
                                await this.playNextChunk(messageId);
                            }
                        }
                    }
                }
            }
            
        } catch (error) {
            console.error('Voice message error:', error);
            debugLogger.log('API_ERROR', 'Voice message error', error.message);
            alert('Error: ' + error.message);
            this.state = 'listening';
            this.updateUI();
            if (this.state === 'listening') {
                this.startListening();
            }
        }
    }
    
    async processStreamingSentences(messageId) {
        // Look for complete sentences (ending with . ! ?)
        const sentenceMatch = this.pendingSentences.match(/[^.!?]+[.!?]+/);
        
        if (sentenceMatch) {
            const completeSentence = sentenceMatch[0];
            this.pendingSentences = this.pendingSentences.substring(completeSentence.length);
            
            debugLogger.log('STREAM_TTS', 'Complete sentence ready for TTS', completeSentence.substring(0, 30) + '...');
            
            // Add to queue
            this.audioQueue.push(completeSentence.trim());
            
            // Generate TTS in background
            await this.fillAudioBuffer();
            
            // Start playing if we haven't already
            if (!this.hasStartedPlaying && this.audioBuffer.length > 0) {
                this.hasStartedPlaying = true;
                debugLogger.log('STREAM_TTS', 'Starting audio playback early');
                await this.playNextChunk(messageId);
            }
        }
    }
    
    async speakResponse(text, messageId) {
        debugLogger.log('TTS_STARTED', 'Converting response to speech', text.substring(0, 50) + '...');
        
        // Use SpeechService to break into chunks (we'll do this client-side)
        const chunks = this.breakIntoChunks(text, 100); // Increased chunk size for efficiency
        debugLogger.log('TTS_CHUNKS', `Split into ${chunks.length} chunks`);
        
        this.audioQueue = chunks;
        this.audioBuffer = [];
        this.isPlaying = true;
        this.isGenerating = false;
        
        // Pre-generate first 2 chunks to build buffer
        await this.fillAudioBuffer();
        
        // Start playback
        await this.playNextChunk(messageId);
    }
    
    breakIntoChunks(text, minLength = 100) {
        const sentences = text.match(/[^.!?]+[.!?]+/g) || [text];
        const chunks = [];
        let currentChunk = '';
        
        for (const sentence of sentences) {
            currentChunk += sentence;
            if (currentChunk.length >= minLength) {
                chunks.push(currentChunk.trim());
                currentChunk = '';
            }
        }
        
        if (currentChunk.trim()) {
            chunks.push(currentChunk.trim());
        }
        
        return chunks;
    }
    
    async fillAudioBuffer() {
        // Generate up to 1 chunk in advance (reduced from 2 for faster response)
        const maxBuffer = 1;
        
        while (this.audioQueue.length > 0 && this.audioBuffer.length < maxBuffer && !this.isGenerating) {
            this.isGenerating = true;
            const chunk = this.audioQueue.shift();
            
            try {
                debugLogger.log('TTS_PREFETCH', 'Pre-generating chunk', chunk.substring(0, 30) + '...');
                
                const response = await fetch('/chat_speak.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        text: chunk,
                        voice: this.ttsVoice
                    })
                });
                
                const audioBlob = await response.blob();
                const audioUrl = URL.createObjectURL(audioBlob);
                
                this.audioBuffer.push({
                    url: audioUrl,
                    text: chunk
                });
                
                debugLogger.log('TTS_BUFFERED', `Buffer size: ${this.audioBuffer.length}`);
                
            } catch (error) {
                console.error('Error generating audio chunk:', error);
                debugLogger.log('TTS_ERROR', 'Error pre-generating chunk', error.message);
            }
            
            this.isGenerating = false;
        }
    }
    
    async playNextChunk(messageId) {
        // Check if we have buffered audio or need to wait
        if (this.audioBuffer.length === 0) {
            // No buffered audio
            if (this.audioQueue.length === 0 && !this.isGenerating) {
                // Truly done
                this.finishSpeaking(messageId);
                return;
            }
            
            // Wait for buffer to fill
            debugLogger.log('AUDIO_WAITING', 'Waiting for buffer to fill...');
            await new Promise(resolve => setTimeout(resolve, 100));
            return this.playNextChunk(messageId);
        }
        
        if (!this.isPlaying) {
            // User stopped playback
            this.finishSpeaking(messageId);
            return;
        }
        
        // Get next chunk from buffer
        const audioData = this.audioBuffer.shift();
        
        debugLogger.log('AUDIO_PLAYING', `Playing chunk at ${this.playbackSpeed}x speed (buffer: ${this.audioBuffer.length})`);
        
        this.currentAudio = new Audio(audioData.url);
        
        // Apply playback speed
        this.currentAudio.playbackRate = this.playbackSpeed;
        
        this.currentAudio.onended = () => {
            URL.revokeObjectURL(audioData.url);
            debugLogger.log('AUDIO_ENDED', `Chunk finished`);
            this.spokenText += audioData.text + ' ';
            
            // Refill buffer while playing
            this.fillAudioBuffer();
            
            // Play next chunk
            this.playNextChunk(messageId);
        };
        
        this.currentAudio.play();
        
        // Start generating next chunk in parallel
        this.fillAudioBuffer();
    }
    
    finishSpeaking(messageId) {
        debugLogger.log('TTS_COMPLETE', 'All audio playback complete');
        
        this.isPlaying = false;
        
        // Update message with full text
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageElement) {
            const contentElement = messageElement.querySelector('.message-content');
            contentElement.textContent = this.currentResponseText;
        }
        
        // Return to listening mode
        this.state = 'listening';
        this.updateUI();
        this.startListening();
    }
    
    interrupt() {
        debugLogger.log('INTERRUPTED', 'User interrupted playback');
        
        // Stop current audio
        if (this.currentAudio) {
            this.currentAudio.pause();
            this.currentAudio = null;
        }
        
        // Clear queue and buffer
        this.audioQueue = [];
        this.audioBuffer.forEach(audio => URL.revokeObjectURL(audio.url));
        this.audioBuffer = [];
        this.isPlaying = false;
        this.isGenerating = false;
        
        // Update UI with full response text received (not just what was spoken)
        const messageElement = document.querySelector('[data-message-id]:last-child');
        if (messageElement && messageElement.classList.contains('chat-message-assistant')) {
            const contentElement = messageElement.querySelector('.message-content');
            if (contentElement && this.currentResponseText) {
                contentElement.textContent = this.currentResponseText;
            }
            // Remove streaming indicator if present
            const streamingIndicator = messageElement.querySelector('.message-streaming');
            if (streamingIndicator) {
                streamingIndicator.remove();
            }
        }
        
        // Return to listening
        this.state = 'listening';
        this.updateUI();
        this.startListening();
    }
    
    updateUI() {
        const voiceButton = document.getElementById('voiceButton');
        if (!voiceButton) return;
        
        if (this.state === 'off') {
            voiceButton.textContent = 'Use Voice';
            voiceButton.className = 'btn btn-secondary btn-voice';
        } else if (this.state === 'listening') {
            voiceButton.textContent = 'Turn off Voice';
            voiceButton.className = 'btn btn-secondary btn-voice';
        } else if (this.state === 'speaking') {
            voiceButton.textContent = 'Interrupt';
            voiceButton.className = 'btn btn-secondary btn-voice btn-interrupt';
        }
    }
}

// Initialize debug controls
function initDebugControls() {
    const debugToggle = document.getElementById('debugToggle');
    const debugClear = document.getElementById('debugClear');
    const debugExport = document.getElementById('debugExport');
    const debugSidebar = document.getElementById('debugSidebar');
    const debugHeader = document.getElementById('debugHeader');
    const debugResizeHandle = document.getElementById('debugResizeHandle');
    
    if (debugToggle) {
        // Set initial state
        if (debugLogger.enabled && debugSidebar) {
            debugSidebar.classList.add('active');
        }
        
        debugToggle.addEventListener('click', () => {
            const isEnabled = debugLogger.toggle();
            debugToggle.textContent = isEnabled ? '🐛 Debug (ON)' : '🐛 Debug (OFF)';
            debugToggle.classList.toggle('active', isEnabled);
        });
        
        // Set button text based on initial state
        debugToggle.textContent = debugLogger.enabled ? '🐛 Debug (ON)' : '🐛 Debug (OFF)';
        debugToggle.classList.toggle('active', debugLogger.enabled);
    }
    
    if (debugClear) {
        debugClear.addEventListener('click', () => {
            debugLogger.clear();
        });
    }
    
    if (debugExport) {
        debugExport.addEventListener('click', () => {
            const logs = debugLogger.export();
            navigator.clipboard.writeText(logs).then(() => {
                alert('Debug logs copied to clipboard!');
            });
        });
    }
    
    // Resize functionality
    if (debugResizeHandle && debugSidebar) {
        let isResizing = false;
        let startX = 0;
        let startWidth = 0;
        
        debugResizeHandle.addEventListener('mousedown', (e) => {
            isResizing = true;
            startX = e.clientX;
            startWidth = debugSidebar.offsetWidth;
            debugSidebar.classList.add('dragging');
            e.preventDefault();
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isResizing) return;
            
            const deltaX = startX - e.clientX;
            const newWidth = Math.max(250, Math.min(800, startWidth + deltaX));
            debugSidebar.style.width = newWidth + 'px';
        });
        
        document.addEventListener('mouseup', () => {
            if (isResizing) {
                isResizing = false;
                debugSidebar.classList.remove('dragging');
            }
        });
    }
    
    // Drag functionality
    if (debugHeader && debugSidebar) {
        let isDragging = false;
        let startY = 0;
        let startTop = 0;
        
        debugHeader.addEventListener('mousedown', (e) => {
            // Only drag if clicking on header, not buttons
            if (e.target.tagName === 'BUTTON' || e.target.closest('.btn-debug-small')) {
                return;
            }
            
            isDragging = true;
            startY = e.clientY;
            startTop = debugSidebar.offsetTop;
            debugSidebar.classList.add('dragging');
            e.preventDefault();
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            const deltaY = e.clientY - startY;
            const newTop = Math.max(0, Math.min(window.innerHeight - 100, startTop + deltaY));
            debugSidebar.style.top = newTop + 'px';
            debugSidebar.style.height = (window.innerHeight - newTop) + 'px';
        });
        
        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                debugSidebar.classList.remove('dragging');
            }
        });
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    const chatManager = new ChatManager();
    chatManager.init();
    
    const voiceManager = new VoiceManager(chatManager);
    voiceManager.init();
    
    // Initialize debug controls if present
    initDebugControls();
});
