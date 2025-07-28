const { Message, User } = require('../models');
const { Op } = require('sequelize');
const sequelize = require('../config/database');
const chatbotService = require('../services/chatbot.service');

// Send message to chatbot
const sendMessage = async (req, res) => {
  try {
    const { message, conversationId } = req.body;
    const userId = req.user ? req.user.id : null;

    if (!message || message.trim() === '') {
      return res.status(400).json({ message: 'Message cannot be empty' });
    }

    // Generate conversation ID if not provided
    let convId = conversationId;
    if (!convId) {
      convId = chatbotService.generateConversationId(userId || 'anonymous');
    }

    // Save user message
    const userMessage = await Message.create({
      senderId: userId,
      senderRole: userId ? 'customer' : 'anonymous',
      receiverId: null,
      receiverRole: 'bot',
      conversationId: convId,
      message: message.trim(),
      messageType: 'text',
      isFromBot: false,
      isRead: true
    });

    // Process message with chatbot
    const botResponse = await chatbotService.processMessage(message, userId, convId);

    // Save bot response
    const botMessage = await Message.create({
      senderId: null,
      senderRole: 'bot',
      receiverId: userId,
      receiverRole: userId ? 'customer' : 'anonymous',
      conversationId: convId,
      message: botResponse.message,
      messageType: botResponse.quickReplies ? 'quick_reply' : 'text',
      metadata: {
        quickReplies: botResponse.quickReplies,
        intent: botResponse.intent,
        confidence: botResponse.confidence,
        escalated: botResponse.escalated
      },
      isFromBot: true,
      intent: botResponse.intent,
      confidence: botResponse.confidence,
      isRead: false
    });

    res.json({
      conversationId: convId,
      userMessage: {
        id: userMessage.id,
        message: userMessage.message,
        timestamp: userMessage.createdAt,
        isFromBot: false
      },
      botMessage: {
        id: botMessage.id,
        message: botMessage.message,
        timestamp: botMessage.createdAt,
        isFromBot: true,
        intent: botResponse.intent,
        confidence: botResponse.confidence,
        quickReplies: botResponse.quickReplies,
        escalated: botResponse.escalated
      }
    });
  } catch (error) {
    console.error('Send message error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get conversation history
const getConversation = async (req, res) => {
  try {
    const { conversationId } = req.params;
    const { page = 1, limit = 50 } = req.query;
    const offset = (page - 1) * limit;

    if (!conversationId) {
      return res.status(400).json({ message: 'Conversation ID is required' });
    }

    const messages = await Message.findAll({
      where: { conversationId },
      order: [['createdAt', 'ASC']],
      limit: parseInt(limit),
      offset: parseInt(offset),
      attributes: [
        'id',
        'message',
        'messageType',
        'metadata',
        'isFromBot',
        'intent',
        'confidence',
        'createdAt'
      ]
    });

    const formattedMessages = messages.map(msg => ({
      id: msg.id,
      message: msg.message,
      messageType: msg.messageType,
      isFromBot: msg.isFromBot,
      intent: msg.intent,
      confidence: msg.confidence,
      timestamp: msg.createdAt,
      quickReplies: msg.metadata?.quickReplies || null
    }));

    res.json({
      conversationId,
      messages: formattedMessages,
      totalMessages: messages.length
    });
  } catch (error) {
    console.error('Get conversation error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get user's conversations
const getUserConversations = async (req, res) => {
  try {
    if (!req.user) {
      return res.status(401).json({ message: 'Authentication required' });
    }

    const { page = 1, limit = 10 } = req.query;
    const offset = (page - 1) * limit;

    // Get unique conversation IDs for the user
    const conversations = await Message.findAll({
      where: {
        $or: [
          { senderId: req.user.id },
          { receiverId: req.user.id }
        ]
      },
      attributes: ['conversationId'],
      group: ['conversationId'],
      order: [['createdAt', 'DESC']],
      limit: parseInt(limit),
      offset: parseInt(offset)
    });

    // Get the latest message for each conversation
    const conversationDetails = await Promise.all(
      conversations.map(async (conv) => {
        const latestMessage = await Message.findOne({
          where: { conversationId: conv.conversationId },
          order: [['createdAt', 'DESC']],
          attributes: ['message', 'isFromBot', 'createdAt']
        });

        const unreadCount = await Message.count({
          where: {
            conversationId: conv.conversationId,
            receiverId: req.user.id,
            isRead: false
          }
        });

        return {
          conversationId: conv.conversationId,
          latestMessage: latestMessage.message,
          isFromBot: latestMessage.isFromBot,
          lastActivity: latestMessage.createdAt,
          unreadCount
        };
      })
    );

    res.json({
      conversations: conversationDetails,
      totalConversations: conversations.length
    });
  } catch (error) {
    console.error('Get user conversations error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Mark messages as read
const markAsRead = async (req, res) => {
  try {
    const { conversationId } = req.params;

    if (!req.user) {
      return res.status(401).json({ message: 'Authentication required' });
    }

    await Message.update(
      { isRead: true },
      {
        where: {
          conversationId,
          receiverId: req.user.id,
          isRead: false
        }
      }
    );

    res.json({ message: 'Messages marked as read' });
  } catch (error) {
    console.error('Mark as read error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get chatbot statistics (admin only)
const getChatbotStats = async (req, res) => {
  try {
    const totalConversations = await Message.aggregate('conversationId', 'DISTINCT', {
      plain: false
    });

    const totalMessages = await Message.count();

    const botMessages = await Message.count({
      where: { isFromBot: true }
    });

    const userMessages = await Message.count({
      where: { isFromBot: false }
    });

    const escalatedConversations = await Message.count({
      where: {
        isFromBot: true,
        metadata: {
          escalated: true
        }
      }
    });

    // Get popular intents
    const intentStats = await Message.findAll({
      where: {
        isFromBot: true,
        intent: { [Op.ne]: null }
      },
      attributes: [
        'intent',
        [sequelize.fn('COUNT', sequelize.col('intent')), 'count']
      ],
      group: ['intent'],
      order: [[sequelize.literal('count'), 'DESC']],
      limit: 10
    });

    res.json({
      totalConversations: totalConversations.length,
      totalMessages,
      botMessages,
      userMessages,
      escalatedConversations,
      popularIntents: intentStats.map(stat => ({
        intent: stat.intent,
        count: stat.get('count')
      }))
    });
  } catch (error) {
    console.error('Get chatbot stats error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Handle quick reply
const handleQuickReply = async (req, res) => {
  try {
    const { payload, conversationId } = req.body;
    const userId = req.user ? req.user.id : null;

    // Map quick reply payloads to messages
    const quickReplyMessages = {
      'order_status': 'I want to check my order status',
      'delivery_time': 'What are your delivery times?',
      'payment_methods': 'What payment methods do you accept?',
      'return_policy': 'What is your return policy?',
      'human_support': 'I need to speak with a human agent'
    };

    const message = quickReplyMessages[payload] || payload;

    // Process as regular message
    return sendMessage(req, res);
  } catch (error) {
    console.error('Handle quick reply error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  sendMessage,
  getConversation,
  getUserConversations,
  markAsRead,
  getChatbotStats,
  handleQuickReply
};
