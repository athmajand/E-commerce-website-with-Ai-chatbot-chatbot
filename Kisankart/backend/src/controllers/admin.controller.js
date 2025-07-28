const { User, Product, Order, OrderItem, Seller, Payment, CustomerService, Category, Subcategory } = require('../models');
const { Op } = require('sequelize');
const sequelize = require('../config/database');

// Dashboard statistics
const getDashboardStats = async (req, res) => {
  try {
    // Get total users count
    const totalUsers = await User.count();
    
    // Get users by role
    const customerCount = await User.count({ where: { role: 'customer' } });
    const sellerCount = await User.count({ where: { role: 'seller' } });
    const adminCount = await User.count({ where: { role: 'admin' } });
    
    // Get total products count
    const totalProducts = await Product.count();
    
    // Get total orders count
    const totalOrders = await Order.count();
    
    // Get orders by status
    const pendingOrders = await Order.count({ where: { orderStatus: 'pending' } });
    const processingOrders = await Order.count({ where: { orderStatus: 'processing' } });
    const shippedOrders = await Order.count({ where: { orderStatus: 'shipped' } });
    const deliveredOrders = await Order.count({ where: { orderStatus: 'delivered' } });
    const cancelledOrders = await Order.count({ where: { orderStatus: 'cancelled' } });
    
    // Get total revenue
    const totalRevenue = await Payment.sum('amount', { 
      where: { status: 'completed' } 
    });
    
    // Get recent orders
    const recentOrders = await Order.findAll({
      limit: 5,
      order: [['createdAt', 'DESC']],
      include: [{ model: User, attributes: ['firstName', 'lastName', 'email'] }]
    });
    
    // Get recent users
    const recentUsers = await User.findAll({
      limit: 5,
      order: [['createdAt', 'DESC']],
      attributes: { exclude: ['password'] }
    });
    
    res.json({
      userStats: {
        total: totalUsers,
        customers: customerCount,
        sellers: sellerCount,
        admins: adminCount
      },
      productStats: {
        total: totalProducts
      },
      orderStats: {
        total: totalOrders,
        pending: pendingOrders,
        processing: processingOrders,
        shipped: shippedOrders,
        delivered: deliveredOrders,
        cancelled: cancelledOrders
      },
      financialStats: {
        totalRevenue: totalRevenue || 0
      },
      recentOrders,
      recentUsers
    });
  } catch (error) {
    console.error('Get dashboard stats error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// User management
const getAllUsers = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    const search = req.query.search || '';
    const role = req.query.role;
    
    const whereClause = {};
    
    // Add search condition
    if (search) {
      whereClause[Op.or] = [
        { firstName: { [Op.like]: `%${search}%` } },
        { lastName: { [Op.like]: `%${search}%` } },
        { email: { [Op.like]: `%${search}%` } },
        { phone: { [Op.like]: `%${search}%` } }
      ];
    }
    
    // Add role filter
    if (role) {
      whereClause.role = role;
    }
    
    const { count, rows } = await User.findAndCountAll({
      where: whereClause,
      limit,
      offset,
      attributes: { exclude: ['password'] },
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      users: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalUsers: count
    });
  } catch (error) {
    console.error('Get all users error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get user details
const getUserDetails = async (req, res) => {
  try {
    const { id } = req.params;
    
    const user = await User.findByPk(id, {
      attributes: { exclude: ['password'] },
      include: [
        { model: Seller }
      ]
    });
    
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }
    
    // Get user's orders
    const orders = await Order.findAll({
      where: { userId: id },
      limit: 5,
      order: [['createdAt', 'DESC']]
    });
    
    // Get user's products if seller
    let products = [];
    if (user.role === 'seller' && user.Seller) {
      products = await Product.findAll({
        where: { sellerId: user.Seller.id },
        limit: 5,
        order: [['createdAt', 'DESC']]
      });
    }
    
    res.json({
      user,
      orders,
      products
    });
  } catch (error) {
    console.error('Get user details error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update user
const updateUser = async (req, res) => {
  try {
    const { id } = req.params;
    const { firstName, lastName, email, phone, role, isActive } = req.body;
    
    const user = await User.findByPk(id);
    
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }
    
    // Update fields
    if (firstName) user.firstName = firstName;
    if (lastName) user.lastName = lastName;
    if (email) user.email = email;
    if (phone !== undefined) user.phone = phone;
    if (role) user.role = role;
    if (isActive !== undefined) user.isActive = isActive;
    
    await user.save();
    
    res.json({
      message: 'User updated successfully',
      user: {
        id: user.id,
        firstName: user.firstName,
        lastName: user.lastName,
        email: user.email,
        phone: user.phone,
        role: user.role,
        isActive: user.isActive
      }
    });
  } catch (error) {
    console.error('Update user error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Delete user
const deleteUser = async (req, res) => {
  try {
    const { id } = req.params;
    
    const user = await User.findByPk(id);
    
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }
    
    // Instead of deleting, set isActive to false
    user.isActive = false;
    await user.save();
    
    res.json({ message: 'User deleted successfully' });
  } catch (error) {
    console.error('Delete user error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Product management
const getAllProducts = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    const search = req.query.search || '';
    const category = req.query.category;
    
    const whereClause = {};
    
    // Add search condition
    if (search) {
      whereClause[Op.or] = [
        { name: { [Op.like]: `%${search}%` } },
        { description: { [Op.like]: `%${search}%` } }
      ];
    }
    
    // Add category filter
    if (category) {
      whereClause.categoryId = category;
    }
    
    const { count, rows } = await Product.findAndCountAll({
      where: whereClause,
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

// Update product
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
      isActive,
      isFeatured
    } = req.body;
    
    const product = await Product.findByPk(id);
    
    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }
    
    // Update fields
    if (name) product.name = name;
    if (description) product.description = description;
    if (price) product.price = price;
    if (discountPrice !== undefined) product.discountPrice = discountPrice;
    if (categoryId) product.categoryId = categoryId;
    if (subcategoryId !== undefined) product.subcategoryId = subcategoryId;
    if (stock !== undefined) product.stock = stock;
    if (isActive !== undefined) product.isActive = isActive;
    if (isFeatured !== undefined) product.isFeatured = isFeatured;
    
    await product.save();
    
    res.json({
      message: 'Product updated successfully',
      product
    });
  } catch (error) {
    console.error('Update product error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Order management
const getAllOrders = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    const status = req.query.status;
    
    const whereClause = {};
    
    // Add status filter
    if (status) {
      whereClause.orderStatus = status;
    }
    
    const { count, rows } = await Order.findAndCountAll({
      where: whereClause,
      limit,
      offset,
      include: [
        { 
          model: User,
          attributes: ['firstName', 'lastName', 'email', 'phone']
        },
        { model: Payment }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      orders: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalOrders: count
    });
  } catch (error) {
    console.error('Get all orders error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get order details
const getOrderDetails = async (req, res) => {
  try {
    const { id } = req.params;
    
    const order = await Order.findByPk(id, {
      include: [
        { 
          model: User,
          attributes: ['firstName', 'lastName', 'email', 'phone']
        },
        { model: Payment },
        { model: Address },
        { 
          model: OrderItem,
          include: [
            { 
              model: Product,
              include: [
                { 
                  model: Seller,
                  include: [
                    {
                      model: User,
                      attributes: ['firstName', 'lastName']
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    });
    
    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }
    
    res.json(order);
  } catch (error) {
    console.error('Get order details error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update order status
const updateOrderStatus = async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body;
    
    const order = await Order.findByPk(id, {
      include: [{ model: OrderItem }]
    });
    
    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }
    
    // Update order status
    order.orderStatus = status;
    await order.save();
    
    // Update all order items status
    for (const item of order.OrderItems) {
      item.status = status;
      await item.save();
    }
    
    res.json({
      message: 'Order status updated successfully',
      order
    });
  } catch (error) {
    console.error('Update order status error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Category management
const createCategory = async (req, res) => {
  try {
    const { name, description, image } = req.body;
    
    // Check if category already exists
    const existingCategory = await Category.findOne({ where: { name } });
    if (existingCategory) {
      return res.status(400).json({ message: 'Category already exists' });
    }
    
    const category = await Category.create({
      name,
      description,
      image
    });
    
    res.status(201).json(category);
  } catch (error) {
    console.error('Create category error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get all categories
const getAllCategories = async (req, res) => {
  try {
    const categories = await Category.findAll({
      include: [{ model: Subcategory }]
    });
    
    res.json(categories);
  } catch (error) {
    console.error('Get all categories error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Create subcategory
const createSubcategory = async (req, res) => {
  try {
    const { categoryId, name, description, image } = req.body;
    
    // Check if category exists
    const category = await Category.findByPk(categoryId);
    if (!category) {
      return res.status(404).json({ message: 'Category not found' });
    }
    
    const subcategory = await Subcategory.create({
      categoryId,
      name,
      description,
      image
    });
    
    res.status(201).json(subcategory);
  } catch (error) {
    console.error('Create subcategory error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  getDashboardStats,
  getAllUsers,
  getUserDetails,
  updateUser,
  deleteUser,
  getAllProducts,
  updateProduct,
  getAllOrders,
  getOrderDetails,
  updateOrderStatus,
  createCategory,
  getAllCategories,
  createSubcategory
};
