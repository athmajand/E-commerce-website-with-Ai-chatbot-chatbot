const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Message = sequelize.define('Message', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  senderId: {
    type: DataTypes.INTEGER,
    allowNull: true // null for bot messages
  },
  senderRole: {
    type: DataTypes.ENUM('customer', 'seller', 'admin', 'bot'),
    allowNull: false
  },
  receiverId: {
    type: DataTypes.INTEGER,
    allowNull: true // null for broadcast messages
  },
  receiverRole: {
    type: DataTypes.ENUM('customer', 'seller', 'admin', 'bot'),
    allowNull: true
  },
  conversationId: {
    type: DataTypes.STRING,
    allowNull: false,
    index: true
  },
  message: {
    type: DataTypes.TEXT,
    allowNull: false
  },
  messageType: {
    type: DataTypes.ENUM('text', 'image', 'file', 'quick_reply', 'card'),
    defaultValue: 'text'
  },
  metadata: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('metadata');
      return rawValue ? JSON.parse(rawValue) : {};
    },
    set(value) {
      this.setDataValue('metadata', JSON.stringify(value));
    }
  },
  isRead: {
    type: DataTypes.BOOLEAN,
    defaultValue: false
  },
  isFromBot: {
    type: DataTypes.BOOLEAN,
    defaultValue: false
  },
  intent: {
    type: DataTypes.STRING,
    allowNull: true // for bot messages to track intent
  },
  confidence: {
    type: DataTypes.FLOAT,
    allowNull: true // confidence score for bot responses
  }
}, {
  timestamps: true,
  indexes: [
    {
      fields: ['conversationId', 'createdAt']
    },
    {
      fields: ['senderId', 'receiverId']
    }
  ]
});

module.exports = Message;
