const { Order, Product, User, OrderItem, CustomerService } = require('../models');
const { Op } = require('sequelize');

class ChatbotService {
  constructor() {
    // Intent patterns with keywords and responses
    this.intents = {
      greeting: {
        patterns: ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'namaste'],
        responses: [
          "Hello! Welcome to Kisankart! 🌱 How can I help you today?",
          "Hi there! I'm here to help you with your queries about fresh farm products. What can I do for you?",
          "Namaste! Welcome to Kisankart - connecting farmers and customers. How may I assist you?"
        ]
      },
      order_status: {
        patterns: ['order status', 'track order', 'where is my order', 'order tracking', 'delivery status', 'order update'],
        responses: [
          "I can help you track your order! Please provide your order number or I can look up your recent orders.",
          "Let me check your order status. Could you share your order number?"
        ],
        requiresData: true
      },
      delivery_time: {
        patterns: ['delivery time', 'when will it arrive', 'shipping time', 'how long delivery', 'delivery estimate'],
        responses: [
          "Our typical delivery times are:\n• Same city: 1-2 days\n• Within state: 2-3 days\n• Other states: 3-5 days\n\nFor fresh produce, we prioritize faster delivery to ensure quality!",
          "Delivery usually takes 1-5 days depending on your location. Fresh farm products are given priority for faster delivery!"
        ]
      },
      payment_methods: {
        patterns: ['payment methods', 'how to pay', 'payment options', 'cash on delivery', 'online payment', 'razorpay'],
        responses: [
          "We accept the following payment methods:\n• Cash on Delivery (COD)\n• Online Payment via Razorpay (Credit/Debit Cards, UPI, Net Banking)\n• Digital Wallets\n\nAll payments are secure and encrypted! 💳"
        ]
      },
      return_policy: {
        patterns: ['return policy', 'refund', 'exchange', 'return product', 'money back'],
        responses: [
          "Our return policy for fresh produce:\n• Report quality issues within 24 hours of delivery\n• We offer full refund for damaged/spoiled items\n• Fresh produce can't be returned unless there's a quality issue\n• Processed items can be returned within 7 days\n\nWould you like to report an issue with your order?"
        ]
      },
      product_info: {
        patterns: ['product information', 'tell me about', 'product details', 'organic', 'fresh', 'quality'],
        responses: [
          "All our products come directly from verified farmers! 🚜\n• 100% fresh from farm\n• No middlemen involved\n• Quality guaranteed\n• Organic options available\n\nWhat specific product are you interested in?"
        ]
      },
      contact_farmer: {
        patterns: ['contact farmer', 'farmer details', 'who is the farmer', 'farmer information'],
        responses: [
          "You can find farmer details on each product page. We believe in transparency and connecting you directly with the farmers who grow your food! 👨‍🌾\n\nWould you like information about a specific product's farmer?"
        ]
      },
      pricing: {
        patterns: ['price', 'cost', 'how much', 'expensive', 'cheap', 'discount', 'offer'],
        responses: [
          "Our prices are farmer-direct, which means:\n• No middleman markup\n• Fair prices for farmers\n• Better value for customers\n• Seasonal discounts available\n\nCheck our homepage for current offers and featured products! 💰"
        ]
      },
      account_help: {
        patterns: ['account', 'login', 'register', 'password', 'profile', 'forgot password'],
        responses: [
          "For account-related help:\n• Registration is free and easy\n• Use OTP verification for secure login\n• Reset password from login page\n• Update profile anytime\n\nNeed help with a specific account issue?"
        ]
      },
      technical_support: {
        patterns: ['website not working', 'technical issue', 'bug', 'error', 'problem with site'],
        responses: [
          "I'm sorry you're experiencing technical difficulties. Let me connect you with our technical support team who can help resolve this quickly."
        ],
        escalate: true
      },
      goodbye: {
        patterns: ['bye', 'goodbye', 'thank you', 'thanks', 'that\'s all'],
        responses: [
          "Thank you for choosing Kisan Kart! Have a great day! 🌱",
          "Goodbye! Feel free to reach out anytime for fresh farm products. Happy shopping! 🛒",
          "Thanks for visiting Kisan Kart! Come back soon for more fresh produce! 🥕🍅"
        ]
      }
    };

    // Quick reply options
    this.quickReplies = [
      { text: "Track My Order", payload: "order_status" },
      { text: "Delivery Time", payload: "delivery_time" },
      { text: "Payment Methods", payload: "payment_methods" },
      { text: "Return Policy", payload: "return_policy" },
      { text: "Contact Support", payload: "human_support" }
    ];
  }

  // Main method to process user message
  async processMessage(message, userId, conversationId) {
    try {
      const intent = this.detectIntent(message);
      let response;

      if (intent.name === 'order_status' && intent.confidence > 0.7) {
        response = await this.handleOrderStatus(message, userId);
      } else if (intent.escalate) {
        response = await this.escalateToHuman(userId, message);
      } else if (intent.requiresData) {
        response = await this.handleDataRequiredIntent(intent, message, userId);
      } else {
        response = this.getRandomResponse(intent.responses);
      }

      return {
        message: response,
        intent: intent.name,
        confidence: intent.confidence,
        quickReplies: intent.name === 'greeting' ? this.quickReplies : null,
        escalated: intent.escalate || false
      };
    } catch (error) {
      console.error('Chatbot processing error:', error);
      return {
        message: "I'm sorry, I'm having trouble understanding. Let me connect you with a human agent.",
        intent: 'error',
        confidence: 0,
        escalated: true
      };
    }
  }

  // Detect intent from user message
  detectIntent(message) {
    const lowerMessage = message.toLowerCase();
    let bestMatch = { name: 'unknown', confidence: 0, responses: ["I'm not sure I understand. Could you please rephrase your question?"] };

    for (const [intentName, intentData] of Object.entries(this.intents)) {
      let score = 0;
      let matches = 0;

      for (const pattern of intentData.patterns) {
        if (lowerMessage.includes(pattern.toLowerCase())) {
          matches++;
          score += pattern.length; // Longer patterns get higher scores
        }
      }

      if (matches > 0) {
        const confidence = (score / lowerMessage.length) * (matches / intentData.patterns.length);
        if (confidence > bestMatch.confidence) {
          bestMatch = {
            name: intentName,
            confidence: confidence,
            responses: intentData.responses,
            requiresData: intentData.requiresData || false,
            escalate: intentData.escalate || false
          };
        }
      }
    }

    return bestMatch;
  }

  // Handle order status queries
  async handleOrderStatus(message, userId) {
    try {
      // Extract order number from message if present
      const orderNumberMatch = message.match(/\b\d{6,}\b/);

      if (orderNumberMatch) {
        const orderNumber = orderNumberMatch[0];
        const order = await Order.findOne({
          where: {
            orderNumber: orderNumber,
            userId: userId
          },
          include: [
            {
              model: OrderItem,
              include: [{ model: Product }]
            }
          ]
        });

        if (order) {
          return this.formatOrderStatus(order);
        } else {
          return "I couldn't find an order with that number. Please check the order number or let me show you your recent orders.";
        }
      } else {
        // Show recent orders
        const recentOrders = await Order.findAll({
          where: { userId: userId },
          limit: 3,
          order: [['createdAt', 'DESC']],
          include: [
            {
              model: OrderItem,
              include: [{ model: Product }]
            }
          ]
        });

        if (recentOrders.length > 0) {
          return this.formatRecentOrders(recentOrders);
        } else {
          return "You don't have any orders yet. Browse our fresh products and place your first order! 🛒";
        }
      }
    } catch (error) {
      console.error('Order status error:', error);
      return "I'm having trouble accessing your order information. Please try again or contact support.";
    }
  }

  // Format order status response
  formatOrderStatus(order) {
    const statusEmojis = {
      'pending': '⏳',
      'confirmed': '✅',
      'processing': '📦',
      'shipped': '🚚',
      'delivered': '✅',
      'cancelled': '❌'
    };

    const emoji = statusEmojis[order.status] || '📋';

    return `${emoji} Order #${order.orderNumber}\n\nStatus: ${order.status.toUpperCase()}\nTotal: ₹${order.totalAmount}\nItems: ${order.OrderItems.length}\n\n${this.getStatusMessage(order.status)}`;
  }

  // Format recent orders response
  formatRecentOrders(orders) {
    let response = "Here are your recent orders:\n\n";

    orders.forEach((order, index) => {
      const statusEmojis = {
        'pending': '⏳',
        'confirmed': '✅',
        'processing': '📦',
        'shipped': '🚚',
        'delivered': '✅',
        'cancelled': '❌'
      };

      const emoji = statusEmojis[order.status] || '📋';
      response += `${emoji} Order #${order.orderNumber}\nStatus: ${order.status}\nTotal: ₹${order.totalAmount}\n\n`;
    });

    response += "Reply with an order number for detailed tracking information.";
    return response;
  }

  // Get status-specific message
  getStatusMessage(status) {
    const messages = {
      'pending': 'Your order is being reviewed and will be confirmed soon.',
      'confirmed': 'Your order has been confirmed and is being prepared.',
      'processing': 'Your order is being packed and will be shipped soon.',
      'shipped': 'Your order is on its way! You should receive it within 1-2 days.',
      'delivered': 'Your order has been delivered. Enjoy your fresh produce!',
      'cancelled': 'Your order has been cancelled. If you need help, please contact support.'
    };

    return messages[status] || 'Order status updated.';
  }

  // Handle intents that require additional data
  async handleDataRequiredIntent(intent, message, userId) {
    // This can be expanded for more complex data requirements
    return this.getRandomResponse(intent.responses);
  }

  // Escalate to human support
  async escalateToHuman(userId, message) {
    try {
      // Create a customer service request
      await CustomerService.create({
        userId: userId,
        subject: 'Chatbot Escalation',
        description: `User message: ${message}\n\nUser requested human assistance.`,
        type: 'inquiry',
        status: 'open',
        priority: 'medium'
      });

      return "I've connected you with our support team. A human agent will assist you shortly. You can also visit our Customer Service page for immediate help.";
    } catch (error) {
      console.error('Escalation error:', error);
      return "I'm connecting you with our support team. Please visit our Customer Service page or call our helpline for immediate assistance.";
    }
  }

  // Get random response from array
  getRandomResponse(responses) {
    return responses[Math.floor(Math.random() * responses.length)];
  }

  // Generate conversation ID
  generateConversationId(userId) {
    return `conv_${userId}_${Date.now()}`;
  }
}

module.exports = new ChatbotService();
