const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Order = sequelize.define('Order', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  orderNumber: {
    type: DataTypes.STRING,
    allowNull: false,
    unique: true
  },
  totalAmount: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  shippingAddressId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  paymentMethod: {
    type: DataTypes.STRING,
    allowNull: false
  },
  paymentStatus: {
    type: DataTypes.ENUM('pending', 'completed', 'failed', 'refunded'),
    defaultValue: 'pending'
  },
  orderStatus: {
    type: DataTypes.ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled'),
    defaultValue: 'pending'
  },
  deliverySlot: {
    type: DataTypes.DATE,
    allowNull: true
  },
  trackingNumber: {
    type: DataTypes.STRING,
    allowNull: true
  },
  shippingCarrier: {
    type: DataTypes.STRING,
    allowNull: true
  },
  notes: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  invoiceNumber: {
    type: DataTypes.STRING,
    allowNull: true
  },
  cancelReason: {
    type: DataTypes.TEXT,
    allowNull: true
  }
}, {
  timestamps: true
});

module.exports = Order;
