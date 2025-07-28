const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const CustomerService = sequelize.define('CustomerService', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  orderId: {
    type: DataTypes.INTEGER,
    allowNull: true
  },
  productId: {
    type: DataTypes.INTEGER,
    allowNull: true
  },
  sellerId: {
    type: DataTypes.INTEGER,
    allowNull: true
  },
  subject: {
    type: DataTypes.STRING,
    allowNull: false
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: false
  },
  type: {
    type: DataTypes.ENUM('complaint', 'inquiry', 'return', 'refund', 'other'),
    defaultValue: 'inquiry'
  },
  status: {
    type: DataTypes.ENUM('open', 'in-progress', 'resolved', 'closed'),
    defaultValue: 'open'
  },
  priority: {
    type: DataTypes.ENUM('low', 'medium', 'high', 'urgent'),
    defaultValue: 'medium'
  },
  attachments: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('attachments');
      return rawValue ? JSON.parse(rawValue) : [];
    },
    set(value) {
      this.setDataValue('attachments', JSON.stringify(value));
    }
  },
  assignedTo: {
    type: DataTypes.INTEGER,
    allowNull: true
  },
  resolution: {
    type: DataTypes.TEXT,
    allowNull: true
  }
}, {
  timestamps: true
});

module.exports = CustomerService;
