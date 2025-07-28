class KisanKartChatbot {
    constructor(options = {}) {
        this.apiBaseUrl = options.apiBaseUrl || window.location.origin + '/Kisankart/api/chatbot';
        this.position = options.position || 'bottom-right';
        this.theme = options.theme || 'green';
        this.conversationId = localStorage.getItem('chatbot_conversation_id') || null;
        this.isOpen = false;
        this.isTyping = false;
        this.debug = options.debug || false;

        if (this.debug) {
            console.log('Chatbot initialized with API URL:', this.apiBaseUrl);
        }

        this.init();
    }

    init() {
        this.createChatWidget();
        this.attachEventListeners();
        this.loadConversationHistory();
    }

    createChatWidget() {
        // Create chat widget HTML
        const chatWidget = document.createElement('div');
        chatWidget.id = 'kisankart-chatbot';
        chatWidget.className = `chatbot-widget ${this.position} ${this.theme}`;

        chatWidget.innerHTML = `
            <div class="chatbot-toggle" id="chatbot-toggle">
                <i class="fas fa-comments"></i>
                <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
            </div>

            <div class="chatbot-container" id="chatbot-container" style="display: none;">
                <div class="chatbot-header">
                    <div class="chatbot-header-info">
                        <div class="bot-avatar-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h4>Kisankart Assistant</h4>
                            <span class="status">Online</span>
                        </div>
                    </div>
                    <div class="chatbot-controls">
                        <button class="minimize-btn" id="minimize-btn">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button class="close-btn" id="close-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="chatbot-messages" id="chatbot-messages">
                    <div class="welcome-message">
                        <div class="message bot-message">
                            <div class="message-content">
                                <p>Hello! Welcome to Kisankart! 🌱</p>
                                <p>I'm here to help you with:</p>
                                <ul>
                                    <li>Order tracking</li>
                                    <li>Product information</li>
                                    <li>Delivery details</li>
                                    <li>Payment methods</li>
                                    <li>And much more!</li>
                                </ul>
                                <p>How can I assist you today?</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="chatbot-quick-replies" id="chatbot-quick-replies" style="display: none;">
                </div>

                <div class="chatbot-typing" id="chatbot-typing" style="display: none;">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span>Assistant is typing...</span>
                </div>

                <div class="chatbot-input">
                    <div class="input-container">
                        <input type="text" id="chatbot-input" placeholder="Type your message..." maxlength="500">
                        <button id="send-btn" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="input-footer">
                        <small>Powered by Kisankart AI</small>
                    </div>
                </div>
            </div>
        `;

        // Add CSS styles
        this.addStyles();

        // Append to body
        document.body.appendChild(chatWidget);
    }

    addStyles() {
        if (document.getElementById('chatbot-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'chatbot-styles';
        styles.textContent = `
            .chatbot-widget {
                position: fixed;
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .chatbot-widget.bottom-right {
                bottom: 20px;
                right: 20px;
            }

            .chatbot-widget.bottom-left {
                bottom: 20px;
                left: 20px;
            }

            .chatbot-toggle {
                width: 60px;
                height: 60px;
                background: #4CAF50;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: all 0.3s ease;
                position: relative;
            }

            .chatbot-toggle:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            }

            .chatbot-toggle i {
                color: white;
                font-size: 24px;
            }

            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #ff4444;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }

            .chatbot-container {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 350px;
                height: 500px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0,0,0,0.12);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                animation: slideUp 0.3s ease;
            }

            @keyframes slideUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .chatbot-header {
                background: #4CAF50;
                color: white;
                padding: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .chatbot-header-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .bot-avatar-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                color: white;
            }

            .chatbot-header h4 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }

            .status {
                font-size: 12px;
                opacity: 0.9;
            }

            .chatbot-controls {
                display: flex;
                gap: 5px;
            }

            .chatbot-controls button {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 5px;
                border-radius: 4px;
                transition: background 0.2s;
            }

            .chatbot-controls button:hover {
                background: rgba(255,255,255,0.2);
            }

            .chatbot-messages {
                flex: 1;
                overflow-y: auto;
                padding: 15px;
                background: #f8f9fa;
            }

            .message {
                margin-bottom: 15px;
                display: flex;
                align-items: flex-start;
                gap: 8px;
            }

            .bot-message {
                justify-content: flex-start;
            }

            .user-message {
                justify-content: flex-end;
            }

            .message-content {
                max-width: 80%;
                padding: 12px 16px;
                border-radius: 18px;
                line-height: 1.4;
                font-size: 14px;
            }

            .bot-message .message-content {
                background: white;
                border: 1px solid #e0e0e0;
                border-bottom-left-radius: 4px;
            }

            .user-message .message-content {
                background: #4CAF50;
                color: white;
                border-bottom-right-radius: 4px;
            }

            .message-content ul {
                margin: 8px 0;
                padding-left: 20px;
            }

            .message-content li {
                margin: 4px 0;
            }

            .chatbot-quick-replies {
                padding: 10px 15px;
                background: white;
                border-top: 1px solid #e0e0e0;
            }

            .quick-reply-btn {
                display: inline-block;
                background: #f0f0f0;
                border: 1px solid #ddd;
                border-radius: 20px;
                padding: 8px 16px;
                margin: 4px;
                cursor: pointer;
                font-size: 13px;
                transition: all 0.2s;
            }

            .quick-reply-btn:hover {
                background: #4CAF50;
                color: white;
                border-color: #4CAF50;
            }

            .chatbot-typing {
                padding: 15px;
                background: white;
                border-top: 1px solid #e0e0e0;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 13px;
                color: #666;
            }

            .typing-indicator {
                display: flex;
                gap: 4px;
            }

            .typing-indicator span {
                width: 6px;
                height: 6px;
                background: #4CAF50;
                border-radius: 50%;
                animation: typing 1.4s infinite;
            }

            .typing-indicator span:nth-child(2) {
                animation-delay: 0.2s;
            }

            .typing-indicator span:nth-child(3) {
                animation-delay: 0.4s;
            }

            @keyframes typing {
                0%, 60%, 100% { transform: translateY(0); }
                30% { transform: translateY(-10px); }
            }

            .chatbot-input {
                background: white;
                border-top: 1px solid #e0e0e0;
            }

            .input-container {
                display: flex;
                padding: 15px;
                gap: 10px;
            }

            #chatbot-input {
                flex: 1;
                border: 1px solid #ddd;
                border-radius: 20px;
                padding: 10px 15px;
                font-size: 14px;
                outline: none;
                transition: border-color 0.2s;
            }

            #chatbot-input:focus {
                border-color: #4CAF50;
            }

            #send-btn {
                background: #4CAF50;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                color: white;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            #send-btn:disabled {
                background: #ccc;
                cursor: not-allowed;
            }

            #send-btn:not(:disabled):hover {
                background: #45a049;
                transform: scale(1.05);
            }

            .input-footer {
                padding: 0 15px 10px;
                text-align: center;
            }

            .input-footer small {
                color: #999;
                font-size: 11px;
            }

            @media (max-width: 480px) {
                .chatbot-container {
                    width: 300px;
                    height: 450px;
                }
            }
        `;

        document.head.appendChild(styles);
    }

    attachEventListeners() {
        const toggle = document.getElementById('chatbot-toggle');
        const container = document.getElementById('chatbot-container');
        const closeBtn = document.getElementById('close-btn');
        const minimizeBtn = document.getElementById('minimize-btn');
        const input = document.getElementById('chatbot-input');
        const sendBtn = document.getElementById('send-btn');

        toggle.addEventListener('click', () => this.toggleChat());
        closeBtn.addEventListener('click', () => this.closeChat());
        minimizeBtn.addEventListener('click', () => this.minimizeChat());

        input.addEventListener('input', (e) => {
            sendBtn.disabled = e.target.value.trim() === '';
        });

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !sendBtn.disabled) {
                this.sendMessage();
            }
        });

        sendBtn.addEventListener('click', () => this.sendMessage());
    }

    toggleChat() {
        const container = document.getElementById('chatbot-container');
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    openChat() {
        const container = document.getElementById('chatbot-container');
        container.style.display = 'flex';
        this.isOpen = true;

        // Focus input
        setTimeout(() => {
            document.getElementById('chatbot-input').focus();
        }, 300);
    }

    closeChat() {
        const container = document.getElementById('chatbot-container');
        container.style.display = 'none';
        this.isOpen = false;
    }

    minimizeChat() {
        this.closeChat();
    }

    async sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();

        if (!message) return;

        // Clear input
        input.value = '';
        document.getElementById('send-btn').disabled = true;

        // Add user message to chat
        this.addMessage(message, false);

        // Show typing indicator
        this.showTyping();

        try {
            const response = await this.callChatbotAPI(message);

            // Hide typing indicator
            this.hideTyping();

            // Add bot response
            this.addMessage(response.botMessage.message, true);

            // Show quick replies if available
            if (response.botMessage.quickReplies) {
                this.showQuickReplies(response.botMessage.quickReplies);
            }

            // Update conversation ID
            if (response.conversationId) {
                this.conversationId = response.conversationId;
                localStorage.setItem('chatbot_conversation_id', this.conversationId);
            }

        } catch (error) {
            console.error('Chatbot error:', error);
            if (this.debug) {
                console.log('API URL attempted:', `${this.apiBaseUrl}/message.php`);
                console.log('Full error details:', error);
                console.log('Trying fallback to simple API...');
            }

            // Try fallback to simple API
            try {
                const fallbackResponse = await this.callSimpleChatbotAPI(message);
                this.hideTyping();
                this.addMessage(fallbackResponse.botMessage.message, true);

                if (fallbackResponse.botMessage.quickReplies) {
                    this.showQuickReplies(fallbackResponse.botMessage.quickReplies);
                }

                if (this.debug) {
                    console.log('Fallback API successful');
                }
                return;
            } catch (fallbackError) {
                console.error('Fallback API also failed:', fallbackError);
            }

            this.hideTyping();
            this.addMessage('Sorry, I\'m having trouble connecting. Please try again later.', true);
        }
    }

    async callChatbotAPI(message) {
        const token = localStorage.getItem('jwt_token');
        const headers = {
            'Content-Type': 'application/json'
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const apiUrl = `${this.apiBaseUrl}/message.php`;

        if (this.debug) {
            console.log('Making API call to:', apiUrl);
            console.log('Request payload:', { message, conversationId: this.conversationId });
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                message: message,
                conversationId: this.conversationId
            })
        });

        if (this.debug) {
            console.log('API response status:', response.status);
            console.log('API response headers:', response.headers);
        }

        if (!response.ok) {
            const errorText = await response.text();
            if (this.debug) {
                console.log('API error response:', errorText);
            }
            throw new Error(`Network response was not ok: ${response.status} - ${errorText}`);
        }

        const jsonResponse = await response.json();

        if (this.debug) {
            console.log('API response data:', jsonResponse);
        }

        return jsonResponse;
    }

    async callSimpleChatbotAPI(message) {
        const apiUrl = `${this.apiBaseUrl}/message_simple.php`;

        if (this.debug) {
            console.log('Making fallback API call to:', apiUrl);
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                conversationId: this.conversationId
            })
        });

        if (!response.ok) {
            throw new Error(`Fallback API failed: ${response.status}`);
        }

        return await response.json();
    }

    addMessage(message, isBot) {
        const messagesContainer = document.getElementById('chatbot-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isBot ? 'bot-message' : 'user-message'}`;

        messageDiv.innerHTML = `
            <div class="message-content">
                ${this.formatMessage(message)}
            </div>
        `;

        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    formatMessage(message) {
        // Convert line breaks to <br> and format basic markdown
        return message
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>');
    }

    showTyping() {
        document.getElementById('chatbot-typing').style.display = 'flex';
        this.isTyping = true;
    }

    hideTyping() {
        document.getElementById('chatbot-typing').style.display = 'none';
        this.isTyping = false;
    }

    showQuickReplies(quickReplies) {
        const container = document.getElementById('chatbot-quick-replies');
        container.innerHTML = '';

        if (!quickReplies || quickReplies.length === 0) {
            container.style.display = 'none';
            return;
        }

        quickReplies.forEach(reply => {
            const btn = document.createElement('span');
            btn.className = 'quick-reply-btn';

            // Handle both string and object formats
            if (typeof reply === 'string') {
                btn.textContent = reply;
                btn.addEventListener('click', () => {
                    this.handleQuickReplyText(reply);
                });
            } else {
                btn.textContent = reply.text || reply.label || reply;
                btn.addEventListener('click', () => {
                    this.handleQuickReply(reply);
                });
            }

            container.appendChild(btn);
        });

        container.style.display = 'block';
    }

    hideQuickReplies() {
        document.getElementById('chatbot-quick-replies').style.display = 'none';
    }

    async handleQuickReplyText(replyText) {
        this.hideQuickReplies();

        // Add user message
        this.addMessage(replyText, false);

        // Show typing indicator
        this.showTyping();

        try {
            const response = await this.callChatbotAPI(replyText);

            // Hide typing indicator
            this.hideTyping();

            // Add bot response
            this.addMessage(response.botMessage.message, true);

            // Show quick replies if available
            if (response.botMessage.quickReplies) {
                this.showQuickReplies(response.botMessage.quickReplies);
            }

            // Update conversation ID
            if (response.conversationId) {
                this.conversationId = response.conversationId;
                localStorage.setItem('chatbot_conversation_id', this.conversationId);
            }

        } catch (error) {
            console.error('Quick reply error:', error);

            // Try fallback API
            try {
                const fallbackResponse = await this.callSimpleChatbotAPI(replyText);
                this.hideTyping();
                this.addMessage(fallbackResponse.botMessage.message, true);

                if (fallbackResponse.botMessage.quickReplies) {
                    this.showQuickReplies(fallbackResponse.botMessage.quickReplies);
                }
            } catch (fallbackError) {
                console.error('Fallback API also failed:', fallbackError);
                this.hideTyping();
                this.addMessage('Sorry, I\'m having trouble processing that request.', true);
            }
        }
    }

    async handleQuickReply(reply) {
        this.hideQuickReplies();

        // Add user message
        this.addMessage(reply.text, false);

        // Show typing
        this.showTyping();

        try {
            const token = localStorage.getItem('jwt_token');
            const headers = {
                'Content-Type': 'application/json'
            };

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const response = await fetch(`${this.apiBaseUrl}/chatbot/quick-reply`, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    payload: reply.payload,
                    conversationId: this.conversationId
                })
            });

            const data = await response.json();

            this.hideTyping();
            this.addMessage(data.botMessage.message, true);

            if (data.botMessage.quickReplies) {
                this.showQuickReplies(data.botMessage.quickReplies);
            }

        } catch (error) {
            console.error('Quick reply error:', error);
            this.hideTyping();
            this.addMessage('Sorry, I\'m having trouble processing that request.', true);
        }
    }

    async loadConversationHistory() {
        if (!this.conversationId) return;

        try {
            const token = localStorage.getItem('jwt_token');
            const headers = {};

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const response = await fetch(`${this.apiBaseUrl}/chatbot/conversation/${this.conversationId}`, {
                headers: headers
            });

            if (response.ok) {
                const data = await response.json();
                const messagesContainer = document.getElementById('chatbot-messages');

                // Clear welcome message
                messagesContainer.innerHTML = '';

                // Add conversation history
                data.messages.forEach(msg => {
                    this.addMessage(msg.message, msg.isFromBot);
                });
            }
        } catch (error) {
            console.error('Error loading conversation history:', error);
        }
    }
}

// Auto-initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if chatbot should be enabled (you can add conditions here)
    if (typeof window.kisanKartChatbotConfig !== 'undefined') {
        window.kisanKartChatbot = new KisanKartChatbot(window.kisanKartChatbotConfig);
    } else {
        window.kisanKartChatbot = new KisanKartChatbot();
    }
});

// Export for manual initialization
window.KisanKartChatbot = KisanKartChatbot;
