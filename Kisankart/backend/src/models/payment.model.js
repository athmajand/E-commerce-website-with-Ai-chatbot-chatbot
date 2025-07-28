const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Payment = sequelize.define('Payment', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  orderId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  amount: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  paymentMethod: {
    type: DataTypes.STRING,
    allowNull: false
  },
  transactionId: {
    type: DataTypes.STRING,
    allowNull: true
  },
  status: {
    type: DataTypes.ENUM('pending', 'completed', 'failed', 'refunded'),
    defaultValue: 'pending'
  },
  paymentDetails: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('paymentDetails');
      return rawValue ? JSON.parse(rawValue) : {};
    },
    set(value) {
      this.setDataValue('paymentDetails', JSON.stringify(value));
    }
  }
}, {
  timestamps: true
});

module.exports = Payment;
