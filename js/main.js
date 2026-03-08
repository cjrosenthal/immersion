// User menu toggle
function toggleUserMenu() {
    const dropdown = document.getElementById('userMenuDropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userMenuDropdown');
    
    if (dropdown && userMenu && !userMenu.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Chat functionality placeholder
// This will be expanded when implementing the chat feature with Claude API
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');
    const voiceButton = document.getElementById('voiceButton');
    
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = chatInput.value.trim();
            
            if (message) {
                // Placeholder for future chat implementation
                console.log('Message submitted:', message);
                chatInput.value = '';
            }
        });
    }
    
    if (voiceButton) {
        voiceButton.addEventListener('click', function() {
            // Placeholder for future voice implementation
            console.log('Voice button clicked');
            alert('Voice feature will be implemented in the next phase.');
        });
    }
});
