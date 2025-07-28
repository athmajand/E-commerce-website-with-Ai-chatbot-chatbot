const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Seller = sequelize.define('Seller', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false,
    unique: true
  },
  businessName: {
    type: DataTypes.STRING,
    allowNull: false
  },
  businessDescription: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  businessLogo: {
    type: DataTypes.STRING,
    allowNull: true
  },
  businessAddress: {
    type: DataTypes.TEXT,
    allowNull: false
  },
  gstNumber: {
    type: DataTypes.STRING,
    allowNull: true
  },
  panNumber: {
    type: DataTypes.STRING,
    allowNull: true
  },
  bankAccountDetails: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('bankAccountDetails');
      return rawValue ? JSON.parse(rawValue) : {};
    },
    set(value) {
      this.setDataValue('bankAccountDetails', JSON.stringify(value));
    }
  },
  isVerified: {
    type: DataTypes.BOOLEAN,
    defaultValue: false
  },
  verificationDocuments: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('verificationDocuments');
      return rawValue ? JSON.parse(rawValue) : [];
    },
    set(value) {
      this.setDataValue('verificationDocuments', JSON.stringify(value));
    }
  },
  rating: {
    type: DataTypes.FLOAT,
    defaultValue: 0
  },
  totalReviews: {
    type: DataTypes.INTEGER,
    defaultValue: 0
  },
  commissionRate: {
    type: DataTypes.FLOAT,
    defaultValue: 10.0
  }
}, {
  timestamps: true
});

module.exports = Seller;
