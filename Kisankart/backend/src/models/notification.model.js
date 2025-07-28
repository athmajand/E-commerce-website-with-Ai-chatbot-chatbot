const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Notification = sequelize.define('Notification', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  title: {
    type: DataTypes.STRING,
    allowNull: false
  },
  message: {
    type: DataTypes.TEXT,
    allowNull: false
  },
  type: {
    type: DataTypes.ENUM('order', 'payment', 'delivery', 'promotion', 'system'),
    defaultValue: 'system'
  },
  referenceId: {
    type: DataTypes.INTEGER,
    allowNull: true
  },
  isRead: {
    type: DataTypes.BOOLEAN,
    defaultValue: false
  },
  image: {
    type: DataTypes.STRING,
    allowNull: true
  },
  actionUrl: {
    type: DataTypes.STRING,
    allowNull: true
  }
}, {
  timestamps: true
});

module.exports = Notification;
