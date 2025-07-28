const express = require('express');
const router = express.Router();
const { authenticate, isSeller } = require('../middleware/auth.middleware');
const { Seller, User, Product, OrderItem, Order, Payment } = require('../models');
const sequelize = require('../config/database');

// All routes require authentication
router.use(authenticate);

// Get seller profile
router.get('/profile', isSeller, async (req, res) => {
  try {
    const seller = await Seller.findOne({
      where: { userId: req.user.id },
      include: [
        {
          model: User,
          attributes: ['firstName', 'lastName', 'email', 'phone']
        }
      ]
    });
    
    if (!seller) {
      return res.status(404).json({ message: 'Seller profile not found' });
    }
    
    res.json(seller);
  } catch (error) {
    console.error('Get seller profile error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Create seller profile
router.post('/profile', async (req, res) => {
  try {
    const {
      businessName,
      businessDescription,
      businessLogo,
      businessAddress,
      gstNumber,
      panNumber,
      bankAccountDetails,
      verificationDocuments
    } = req.body;
    
    // Check if user already has a seller profile
    const existingSeller = await Seller.findOne({
      where: { userId: req.user.id }
    });
    
    if (existingSeller) {
      return res.status(400).json({ message: 'Seller profile already exists' });
    }
    
    // Update user role to seller
    const user = await User.findByPk(req.user.id);
    user.role = 'seller';
    await user.save();
    
    // Create seller profile
    const seller = await Seller.create({
      userId: req.user.id,
      businessName,
      businessDescription,
      businessLogo,
      businessAddress,
      gstNumber,
      panNumber,
      bankAccountDetails,
      verificationDocuments
    });
    
    res.status(201).json(seller);
  } catch (error) {
    console.error('Create seller profile error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Update seller profile
router.put('/profile', isSeller, async (req, res) => {
  try {
    const {
      businessName,
      businessDescription,
      businessLogo,
      businessAddress,
      gstNumber,
      panNumber,
      bankAccountDetails,
      verificationDocuments
    } = req.body;
    
    const seller = await Seller.findOne({
      where: { userId: req.user.id }
    });
    
    if (!seller) {
      return res.status(404).json({ message: 'Seller profile not found' });
    }
    
    // Update fields
    if (businessName) seller.businessName = businessName;
    if (businessDescription !== undefined) seller.businessDescription = businessDescription;
    if (businessLogo !== undefined) seller.businessLogo = businessLogo;
    if (businessAddress) seller.businessAddress = businessAddress;
    if (gstNumber !== undefined) seller.gstNumber = gstNumber;
    if (panNumber !== undefined) seller.panNumber = panNumber;
    if (bankAccountDetails !== undefined) seller.bankAccountDetails = bankAccountDetails;
    if (verificationDocuments !== undefined) seller.verificationDocuments = verificationDocuments;
    
    await seller.save();
    
    res.json(seller);
  } catch (error) {
    console.error('Update seller profile error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get seller products
router.get('/products', isSeller, async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    
    // Get seller ID
    const seller = await Seller.findOne({ where: { userId: req.user.id } });
    
    if (!seller) {
      return res.status(404).json({ message: 'Seller profile not found' });
    }
    
    const { count, rows } = await Product.findAndCountAll({
      where: { sellerId: seller.id },
      limit,
      offset,
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      products: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalProducts: count
    });
  } catch (error) {
    console.error('Get seller products error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get seller dashboard stats
router.get('/dashboard', isSeller, async (req, res) => {
  try {
    // Get seller ID
    const seller = await Seller.findOne({ where: { userId: req.user.id } });
    
    if (!seller) {
      return res.status(404).json({ message: 'Seller profile not found' });
    }
    
    // Get total products
    const totalProducts = await Product.count({
      where: { sellerId: seller.id }
    });
    
    // Get active products
    const activeProducts = await Product.count({
      where: { sellerId: seller.id, isActive: true }
    });
    
    // Get total orders
    const totalOrders = await OrderItem.count({
      where: { sellerId: seller.id }
    });
    
    // Get orders by status
    const pendingOrders = await OrderItem.count({
      where: { sellerId: seller.id, status: 'pending' }
    });
    
    const processingOrders = await OrderItem.count({
      where: { sellerId: seller.id, status: 'processing' }
    });
    
    const shippedOrders = await OrderItem.count({
      where: { sellerId: seller.id, status: 'shipped' }
    });
    
    const deliveredOrders = await OrderItem.count({
      where: { sellerId: seller.id, status: 'delivered' }
    });
    
    const cancelledOrders = await OrderItem.count({
      where: { sellerId: seller.id, status: 'cancelled' }
    });
    
    // Get total revenue
    const totalRevenue = await OrderItem.sum('price', {
      where: { 
        sellerId: seller.id,
        status: 'delivered'
      }
    });
    
    // Get recent orders
    const recentOrders = await OrderItem.findAll({
      where: { sellerId: seller.id },
      limit: 5,
      include: [
        { 
          model: Order,
          include: [
            { model: User, attributes: ['firstName', 'lastName', 'email'] }
          ]
        },
        { model: Product }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      productStats: {
        total: totalProducts,
        active: activeProducts
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
      recentOrders
    });
  } catch (error) {
    console.error('Get seller dashboard stats error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;
