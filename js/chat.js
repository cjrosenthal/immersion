/**
 * Chat and Voice Management
 */

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
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        
        if (!message || this.isStreaming) {
            return;
        }
        
        // Clear input
        input.value = '';
        
        // Display user message
        this.displayMessage('user', message);
        
        // Send to server and stream response
        await this.sendMessage(message);
    }
    
    async sendMessage(message) {
        this.isStreaming = true;
        
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
                        } else if (data.type === 'chunk') {
                            contentElement.textContent += data.text;
                            this.scrollToBottom();
                        } else if (data.type === 'done') {
                            // Streaming complete
                        } else if (data.type === 'error') {
                            contentElement.textContent = 'Error: ' + data.message;
                        }
                    }
                }
            }
            
        } catch (error) {
            console.error('Chat error:', error);
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
        this.currentAudio = null;
        this.isPlaying = false;
        this.currentResponseText = '';
        this.spokenText = '';
        this.ttsVoice = 'alloy';
        this.silenceTimer = null;
        this.interimTranscript = '';
    }
    
    init() {
        const voiceButton = document.getElementById('voiceButton');
        if (voiceButton) {
            voiceButton.addEventListener('click', () => this.handleVoiceButtonClick());
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
        
        // Clear any timers
        if (this.silenceTimer) {
            clearTimeout(this.silenceTimer);
            this.silenceTimer = null;
        }
    }
    
    startListening() {
        if (!this.recognition) return;
        
        this.interimTranscript = '';
        this.recognition.start();
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
        
        // Clear existing silence timer
        if (this.silenceTimer) {
            clearTimeout(this.silenceTimer);
        }
        
        // If we have final transcript, wait for pause then send
        if (finalTranscript) {
            const fullTranscript = finalTranscript.trim();
            
            if (fullTranscript) {
                // Wait for 1.5 seconds of silence before sending
                this.silenceTimer = setTimeout(() => {
                    this.sendVoiceMessage(fullTranscript);
                }, 1500);
            }
        }
    }
    
    handleSpeechError(event) {
        console.error('Speech recognition error:', event.error);
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
    
    async sendVoiceMessage(transcript) {
        // Stop listening
        this.recognition.stop();
        
        // Display user message
        this.chatManager.displayMessage('user', transcript);
        
        // Get response from Claude
        this.state = 'speaking';
        this.currentResponseText = '';
        this.spokenText = '';
        this.updateUI();
        
        try {
            // Send message and get response
            const response = await fetch('/chat_send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: transcript,
                    thread_id: this.chatManager.currentThreadId
                })
            });
            
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            
            // Create message placeholder
            const messageId = this.chatManager.displayMessage('assistant', '', false);
            
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
                        } else if (data.type === 'chunk') {
                            this.currentResponseText += data.text;
                        } else if (data.type === 'done') {
                            // Start speaking the response
                            await this.speakResponse(this.currentResponseText, messageId);
                        }
                    }
                }
            }
            
        } catch (error) {
            console.error('Voice message error:', error);
            alert('Error: ' + error.message);
            this.state = 'listening';
            this.updateUI();
            if (this.state === 'listening') {
                this.startListening();
            }
        }
    }
    
    async speakResponse(text, messageId) {
        // Break into chunks
        const response = await fetch('/chat_speak.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({text: 'CHUNK_TEST', voice: this.ttsVoice})
        });
        
        // Use SpeechService to break into chunks (we'll do this client-side)
        const chunks = this.breakIntoChunks(text);
        
        this.audioQueue = chunks;
        this.isPlaying = true;
        
        await this.playNextChunk(messageId);
    }
    
    breakIntoChunks(text, minLength = 50) {
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
    
    async playNextChunk(messageId) {
        if (this.audioQueue.length === 0 || !this.isPlaying) {
            // Done playing
            this.finishSpeaking(messageId);
            return;
        }
        
        const chunk = this.audioQueue.shift();
        
        try {
            // Generate audio for this chunk
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
            
            this.currentAudio = new Audio(audioUrl);
            this.currentAudio.onended = () => {
                URL.revokeObjectURL(audioUrl);
                this.spokenText += chunk + ' ';
                this.playNextChunk(messageId);
            };
            
            this.currentAudio.play();
            
        } catch (error) {
            console.error('Error playing audio:', error);
            this.finishSpeaking(messageId);
        }
    }
    
    finishSpeaking(messageId) {
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
        // Stop current audio
        if (this.currentAudio) {
            this.currentAudio.pause();
            this.currentAudio = null;
        }
        
        // Clear queue
        this.audioQueue = [];
        this.isPlaying = false;
        
        // Update UI with what was spoken so far
        const messageElement = document.querySelector('[data-message-id]:last-child');
        if (messageElement) {
            const contentElement = messageElement.querySelector('.message-content');
            contentElement.textContent = this.spokenText.trim();
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

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    const chatManager = new ChatManager();
    chatManager.init();
    
    const voiceManager = new VoiceManager(chatManager);
    voiceManager.init();
});
