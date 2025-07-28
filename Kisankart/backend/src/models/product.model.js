const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Product = sequelize.define('Product', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  sellerId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  name: {
    type: DataTypes.STRING,
    allowNull: false
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: false
  },
  price: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  discountPrice: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: true
  },
  categoryId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  subcategoryId: {
    type: DataTypes.INTEGER,
    allowNull: true
  },
  stock: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 0
  },
  unit: {
    type: DataTypes.STRING,
    allowNull: false,
    defaultValue: 'kg'
  },
  images: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('images');
      return rawValue ? JSON.parse(rawValue) : [];
    },
    set(value) {
      this.setDataValue('images', JSON.stringify(value));
    }
  },
  isActive: {
    type: DataTypes.BOOLEAN,
    defaultValue: true
  },
  isFeatured: {
    type: DataTypes.BOOLEAN,
    defaultValue: false
  },
  averageRating: {
    type: DataTypes.FLOAT,
    defaultValue: 0
  },
  totalReviews: {
    type: DataTypes.INTEGER,
    defaultValue: 0
  },
  tags: {
    type: DataTypes.TEXT,
    allowNull: true,
    get() {
      const rawValue = this.getDataValue('tags');
      return rawValue ? JSON.parse(rawValue) : [];
    },
    set(value) {
      this.setDataValue('tags', JSON.stringify(value));
    }
  }
}, {
  timestamps: true
});

module.exports = Product;
