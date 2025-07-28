# Kisan Kart Smart Chatbot Integration Guide

## Overview

The Kisan Kart Smart Chatbot is an AI-powered customer support assistant that provides instant answers to common queries like order status, delivery time, product information, and more. It uses NLP (Natural Language Processing) with rule-based fallback and can be easily integrated into any page of your website.

## Features

- **24/7 Availability**: Instant responses without waiting time
- **Natural Language Understanding**: Processes user queries intelligently
- **Order Tracking**: Real-time order status and delivery information
- **Product Information**: Details about farm products, organic options, and farmers
- **Payment & Returns**: Help with payment methods and return policies
- **Escalation to Human Support**: Seamless handoff when needed
- **Mobile Responsive**: Works on all devices and screen sizes
- **Easy Integration**: Simple JavaScript widget that works with any website

## Quick Integration

### Method 1: Simple Integration (Recommended)

Add this single line to any HTML page where you want the chatbot:

```html
<script src="js/chatbot-integration.js"></script>
```

That's it! The chatbot will automatically appear in the bottom-right corner.

### Method 2: Custom Integration

For more control over the chatbot configuration:

```html
<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Include chatbot widget -->
<script src="js/chatbot-widget.js"></script>

<script>
// Custom configuration
window.kisanKartChatbotConfig = {
    apiBaseUrl: 'http://localhost:5000/api', // Your API base URL
    position: 'bottom-right', // or 'bottom-left'
    theme: 'green',
    autoOpen: false
};

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.kisanKartChatbot = new KisanKartChatbot(window.kisanKartChatbotConfig);
});
</script>
```

## Configuration Options

```javascript
const config = {
    // API Configuration
    apiBaseUrl: 'http://localhost:5000/api', // Backend API URL
    
    // Appearance
    position: 'bottom-right', // 'bottom-right' or 'bottom-left'
    theme: 'green', // Color theme
    
    // Behavior
    autoOpen: false, // Auto-open chat on page load
    
    // Page Control
    enableOnPages: ['all'], // ['all'] or specific page patterns
    excludePages: [], // Pages to exclude (e.g., ['/admin', '/login'])
    
    // User Control
    enableForRoles: ['all'], // ['all', 'customer', 'seller', 'admin']
    
    // Offline Mode
    enableOfflineMode: true,
    offlineMessage: 'I\'m currently offline. Please leave a message!'
};
```

## Backend Setup

### 1. Install Dependencies

The chatbot uses existing dependencies in your project:
- Express.js
- Sequelize ORM
- MySQL

### 2. Database Migration

The chatbot requires a `Messages` table. Run this SQL to create it:

```sql
CREATE TABLE Messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    senderId INT NULL,
    senderRole ENUM('customer', 'seller', 'admin', 'bot') NOT NULL,
    receiverId INT NULL,
    receiverRole ENUM('customer', 'seller', 'admin', 'bot') NULL,
    conversationId VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    messageType ENUM('text', 'image', 'file', 'quick_reply', 'card') DEFAULT 'text',
    metadata TEXT NULL,
    isRead BOOLEAN DEFAULT FALSE,
    isFromBot BOOLEAN DEFAULT FALSE,
    intent VARCHAR(255) NULL,
    confidence FLOAT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_conversation_time (conversationId, createdAt),
    INDEX idx_sender_receiver (senderId, receiverId)
);
```

### 3. API Endpoints

The chatbot uses these API endpoints:

- `POST /api/chatbot/message` - Send message to chatbot
- `POST /api/chatbot/quick-reply` - Handle quick reply buttons
- `GET /api/chatbot/conversation/:id` - Get conversation history
- `PUT /api/chatbot/conversation/:id/read` - Mark messages as read
- `GET /api/chatbot/conversations` - Get user's conversations
- `GET /api/chatbot/admin/stats` - Get chatbot statistics (admin only)

## Supported Queries

The chatbot can handle these types of queries:

### Order & Delivery
- "Track my order"
- "Where is my order?"
- "Delivery time"
- "When will it arrive?"
- "Order status"

### Products
- "Tell me about organic products"
- "Product information"
- "Quality guarantee"
- "Fresh produce"
- "Contact farmer"

### Payments & Returns
- "Payment methods"
- "How to pay?"
- "Return policy"
- "Refund process"
- "Cash on delivery"

### Account Help
- "Login help"
- "Register account"
- "Forgot password"
- "Profile update"

### General
- "Hello" / "Hi"
- "Contact support"
- "Technical issue"
- "Thank you" / "Goodbye"

## Customization

### Adding New Intents

Edit `backend/src/services/chatbot.service.js` to add new intents:

```javascript
this.intents = {
    // ... existing intents
    
    new_intent: {
        patterns: ['keyword1', 'keyword2', 'phrase'],
        responses: [
            "Response 1",
            "Response 2"
        ],
        requiresData: false, // Set to true if needs database lookup
        escalate: false // Set to true to escalate to human
    }
};
```

### Styling

The chatbot uses CSS custom properties for easy theming:

```css
:root {
    --chatbot-primary-color: #4CAF50;
    --chatbot-secondary-color: #45a049;
    --chatbot-text-color: #333;
    --chatbot-bg-color: #f8f9fa;
}
```

### Quick Replies

Customize quick reply buttons in the chatbot service:

```javascript
this.quickReplies = [
    { text: "Track My Order", payload: "order_status" },
    { text: "Delivery Time", payload: "delivery_time" },
    { text: "Payment Methods", payload: "payment_methods" },
    // Add more quick replies
];
```

## Programmatic Control

Use these JavaScript functions to control the chatbot:

```javascript
// Open chatbot
window.KisanKartChatbotUtils.openChat();

// Close chatbot
window.KisanKartChatbotUtils.closeChat();

// Update configuration
window.KisanKartChatbotUtils.updateConfig({
    position: 'bottom-left',
    autoOpen: true
});

// Get current configuration
const config = window.KisanKartChatbotUtils.getConfig();
```

## Mobile Responsiveness

The chatbot automatically adapts to mobile devices:
- Full-screen mode on small screens
- Touch-friendly interface
- Responsive typography
- Optimized for mobile keyboards

## Security Features

- **Authentication**: Supports both authenticated and anonymous users
- **Rate Limiting**: Prevents spam (implement in your API middleware)
- **Input Validation**: Sanitizes user input
- **Privacy**: No sensitive data stored in chat logs

## Analytics & Monitoring

Track chatbot performance with these metrics:
- Total conversations
- Popular intents
- Escalation rate
- User satisfaction
- Response accuracy

Access analytics via the admin endpoint:
```javascript
GET /api/chatbot/admin/stats
```

## Troubleshooting

### Common Issues

1. **Chatbot not appearing**
   - Check if Font Awesome is loaded
   - Verify API URL is correct
   - Check browser console for errors

2. **Messages not sending**
   - Verify backend is running
   - Check API endpoints are accessible
   - Confirm database connection

3. **Styling issues**
   - Check for CSS conflicts
   - Verify z-index is high enough
   - Test on different browsers

### Debug Mode

Enable debug mode for troubleshooting:

```javascript
window.kisanKartChatbotConfig = {
    debug: true,
    // ... other config
};
```

## Performance Optimization

- **Lazy Loading**: Widget loads only when needed
- **Caching**: Conversation history cached locally
- **Compression**: Minify JavaScript and CSS for production
- **CDN**: Serve static assets from CDN

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Demo

Visit `frontend/chatbot-demo.html` to see the chatbot in action with sample queries and interactive examples.

## Support

For technical support or questions about the chatbot integration, please contact the development team or create an issue in the project repository.
