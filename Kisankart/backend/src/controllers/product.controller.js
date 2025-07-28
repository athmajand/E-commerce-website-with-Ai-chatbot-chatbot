const { Product, Category, Subcategory, Seller, User, Review } = require('../models');
const { Op } = require('sequelize');

// Get all products with pagination
const getAllProducts = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    
    const { count, rows } = await Product.findAndCountAll({
      where: { isActive: true },
      limit,
      offset,
      include: [
        { model: Category },
        { model: Subcategory },
        { 
          model: Seller,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName']
            }
          ]
        }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      products: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalProducts: count
    });
  } catch (error) {
    console.error('Get all products error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get product by ID
const getProductById = async (req, res) => {
  try {
    const { id } = req.params;
    
    const product = await Product.findOne({
      where: { id, isActive: true },
      include: [
        { model: Category },
        { model: Subcategory },
        { 
          model: Seller,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName']
            }
          ]
        },
        {
          model: Review,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName', 'profileImage']
            }
          ],
          limit: 5,
          order: [['createdAt', 'DESC']]
        }
      ]
    });
    
    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }
    
    res.json(product);
  } catch (error) {
    console.error('Get product by ID error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get products by category
const getProductsByCategory = async (req, res) => {
  try {
    const { categoryId } = req.params;
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    
    const { count, rows } = await Product.findAndCountAll({
      where: { 
        categoryId,
        isActive: true 
      },
      limit,
      offset,
      include: [
        { model: Category },
        { model: Subcategory },
        { 
          model: Seller,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName']
            }
          ]
        }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      products: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalProducts: count
    });
  } catch (error) {
    console.error('Get products by category error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get products by subcategory
const getProductsBySubcategory = async (req, res) => {
  try {
    const { subcategoryId } = req.params;
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    
    const { count, rows } = await Product.findAndCountAll({
      where: { 
        subcategoryId,
        isActive: true 
      },
      limit,
      offset,
      include: [
        { model: Category },
        { model: Subcategory },
        { 
          model: Seller,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName']
            }
          ]
        }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      products: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalProducts: count
    });
  } catch (error) {
    console.error('Get products by subcategory error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Search products
const searchProducts = async (req, res) => {
  try {
    const { query } = req.query;
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    
    const { count, rows } = await Product.findAndCountAll({
      where: { 
        [Op.and]: [
          { isActive: true },
          {
            [Op.or]: [
              { name: { [Op.like]: `%${query}%` } },
              { description: { [Op.like]: `%${query}%` } },
              { tags: { [Op.like]: `%${query}%` } }
            ]
          }
        ]
      },
      limit,
      offset,
      include: [
        { model: Category },
        { model: Subcategory },
        { 
          model: Seller,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName']
            }
          ]
        }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      products: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalProducts: count
    });
  } catch (error) {
    console.error('Search products error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Create a new product (for sellers)
const createProduct = async (req, res) => {
  try {
    const {
      name,
      description,
      price,
      discountPrice,
      categoryId,
      subcategoryId,
      stock,
      unit,
      images,
      tags
    } = req.body;
    
    // Get seller ID from user
    const seller = await Seller.findOne({ where: { userId: req.user.id } });
    
    if (!seller) {
      return res.status(403).json({ message: 'Seller profile not found' });
    }
    
    const product = await Product.create({
      sellerId: seller.id,
      name,
      description,
      price,
      discountPrice,
      categoryId,
      subcategoryId,
      stock,
      unit,
      images,
      tags
    });
    
    res.status(201).json(product);
  } catch (error) {
    console.error('Create product error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update a product (for sellers)
const updateProduct = async (req, res) => {
  try {
    const { id } = req.params;
    const {
      name,
      description,
      price,
      discountPrice,
      categoryId,
      subcategoryId,
      stock,
      unit,
      images,
      tags,
      isActive
    } = req.body;
    
    // Get seller ID from user
    const seller = await Seller.findOne({ where: { userId: req.user.id } });
    
    if (!seller) {
      return res.status(403).json({ message: 'Seller profile not found' });
    }
    
    // Find product
    const product = await Product.findOne({
      where: { 
        id,
        sellerId: seller.id
      }
    });
    
    if (!product) {
      return res.status(404).json({ message: 'Product not found or you do not have permission to update it' });
    }
    
    // Update fields
    if (name) product.name = name;
    if (description) product.description = description;
    if (price) product.price = price;
    if (discountPrice !== undefined) product.discountPrice = discountPrice;
    if (categoryId) product.categoryId = categoryId;
    if (subcategoryId !== undefined) product.subcategoryId = subcategoryId;
    if (stock !== undefined) product.stock = stock;
    if (unit) product.unit = unit;
    if (images) product.images = images;
    if (tags) product.tags = tags;
    if (isActive !== undefined) product.isActive = isActive;
    
    await product.save();
    
    res.json(product);
  } catch (error) {
    console.error('Update product error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Delete a product (for sellers)
const deleteProduct = async (req, res) => {
  try {
    const { id } = req.params;
    
    // Get seller ID from user
    const seller = await Seller.findOne({ where: { userId: req.user.id } });
    
    if (!seller) {
      return res.status(403).json({ message: 'Seller profile not found' });
    }
    
    // Find product
    const product = await Product.findOne({
      where: { 
        id,
        sellerId: seller.id
      }
    });
    
    if (!product) {
      return res.status(404).json({ message: 'Product not found or you do not have permission to delete it' });
    }
    
    // Instead of deleting, set isActive to false
    product.isActive = false;
    await product.save();
    
    res.json({ message: 'Product deleted successfully' });
  } catch (error) {
    console.error('Delete product error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get featured products
const getFeaturedProducts = async (req, res) => {
  try {
    const limit = parseInt(req.query.limit) || 10;
    
    const products = await Product.findAll({
      where: { 
        isActive: true,
        isFeatured: true
      },
      limit,
      include: [
        { model: Category },
        { model: Subcategory },
        { 
          model: Seller,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName']
            }
          ]
        }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json(products);
  } catch (error) {
    console.error('Get featured products error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  getAllProducts,
  getProductById,
  getProductsByCategory,
  getProductsBySubcategory,
  searchProducts,
  createProduct,
  updateProduct,
  deleteProduct,
  getFeaturedProducts
};
