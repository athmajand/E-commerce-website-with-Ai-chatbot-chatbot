const express = require('express');
const router = express.Router();
const { authenticate, isAdmin } = require('../middleware/auth.middleware');
const chatbotController = require('../controllers/chatbot.controller');

// Optional authentication middleware for chatbot (allows anonymous users)
const optionalAuth = (req, res, next) => {
  const token = req.header('Authorization')?.replace('Bearer ', '');
  
  if (token) {
    // If token is provided, use regular authentication
    authenticate(req, res, next);
  } else {
    // If no token, allow anonymous access
    req.user = null;
    next();
  }
};

// Send message to chatbot (allows anonymous users)
router.post('/message', optionalAuth, chatbotController.sendMessage);

// Handle quick reply
router.post('/quick-reply', optionalAuth, chatbotController.handleQuickReply);

// Get conversation history (allows anonymous with conversationId)
router.get('/conversation/:conversationId', optionalAuth, chatbotController.getConversation);

// Mark messages as read (requires authentication)
router.put('/conversation/:conversationId/read', authenticate, chatbotController.markAsRead);

// Get user's conversations (requires authentication)
router.get('/conversations', authenticate, chatbotController.getUserConversations);

// Admin routes
router.get('/admin/stats', authenticate, isAdmin, chatbotController.getChatbotStats);

module.exports = router;
