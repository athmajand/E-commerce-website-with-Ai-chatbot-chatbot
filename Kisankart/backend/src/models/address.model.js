const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Address = sequelize.define('Address', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  userId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  addressLine1: {
    type: DataTypes.STRING,
    allowNull: false
  },
  addressLine2: {
    type: DataTypes.STRING,
    allowNull: true
  },
  city: {
    type: DataTypes.STRING,
    allowNull: false
  },
  state: {
    type: DataTypes.STRING,
    allowNull: false
  },
  postalCode: {
    type: DataTypes.STRING,
    allowNull: false
  },
  country: {
    type: DataTypes.STRING,
    allowNull: false,
    defaultValue: 'India'
  },
  isDefault: {
    type: DataTypes.BOOLEAN,
    defaultValue: false
  },
  addressType: {
    type: DataTypes.ENUM('home', 'work', 'other'),
    defaultValue: 'home'
  },
  contactPhone: {
    type: DataTypes.STRING,
    allowNull: false
  }
}, {
  timestamps: true
});

module.exports = Address;
