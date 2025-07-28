const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Subcategory = sequelize.define('Subcategory', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  categoryId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  name: {
    type: DataTypes.STRING,
    allowNull: false
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  image: {
    type: DataTypes.STRING,
    allowNull: true
  },
  isActive: {
    type: DataTypes.BOOLEAN,
    defaultValue: true
  }
}, {
  timestamps: true
});

module.exports = Subcategory;
