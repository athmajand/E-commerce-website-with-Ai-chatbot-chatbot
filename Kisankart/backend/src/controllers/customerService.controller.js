const { CustomerService, User, Order, Product, Seller } = require('../models');
const notificationController = require('./notification.controller');

// Create a new customer service request
const createRequest = async (req, res) => {
  try {
    const {
      orderId,
      productId,
      subject,
      description,
      type,
      attachments
    } = req.body;
    
    // If orderId is provided, check if it belongs to the user
    if (orderId) {
      const order = await Order.findOne({
        where: { id: orderId, userId: req.user.id }
      });
      
      if (!order) {
        return res.status(404).json({ message: 'Order not found' });
      }
    }
    
    // If productId is provided, check if it exists
    let sellerId = null;
    if (productId) {
      const product = await Product.findOne({
        where: { id: productId, isActive: true }
      });
      
      if (!product) {
        return res.status(404).json({ message: 'Product not found' });
      }
      
      // Get seller ID
      sellerId = product.sellerId;
    }
    
    // Create customer service request
    const customerService = await CustomerService.create({
      userId: req.user.id,
      orderId: orderId || null,
      productId: productId || null,
      sellerId,
      subject,
      description,
      type,
      attachments: attachments || null
    });
    
    // Send notification to admin
    const admins = await User.findAll({
      where: { role: 'admin', isActive: true }
    });
    
    for (const admin of admins) {
      await notificationController.createNotification(
        admin.id,
        'New Customer Service Request',
        `A new ${type} request has been submitted: ${subject}`,
        'system',
        customerService.id,
        null,
        `/admin/customer-service/${customerService.id}`
      );
    }
    
    // Send confirmation notification to user
    await notificationController.createNotification(
      req.user.id,
      'Request Submitted Successfully',
      `Your ${type} request "${subject}" has been submitted. We'll get back to you soon.`,
      'system',
      customerService.id,
      null,
      `/customer-service/${customerService.id}`
    );
    
    res.status(201).json(customerService);
  } catch (error) {
    console.error('Create customer service request error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get user's customer service requests
const getUserRequests = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    
    const { count, rows } = await CustomerService.findAndCountAll({
      where: { userId: req.user.id },
      limit,
      offset,
      include: [
        { model: Order },
        { model: Product }
      ],
      order: [['createdAt', 'DESC']]
    });
    
    res.json({
      requests: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalRequests: count
    });
  } catch (error) {
    console.error('Get customer service requests error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get customer service request details
const getRequestDetails = async (req, res) => {
  try {
    const { id } = req.params;
    
    const customerService = await CustomerService.findOne({
      where: { id, userId: req.user.id },
      include: [
        { model: Order },
        { model: Product },
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
    });
    
    if (!customerService) {
      return res.status(404).json({ message: 'Customer service request not found' });
    }
    
    res.json(customerService);
  } catch (error) {
    console.error('Get customer service request details error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update customer service request
const updateRequest = async (req, res) => {
  try {
    const { id } = req.params;
    const { description, attachments } = req.body;
    
    const customerService = await CustomerService.findOne({
      where: { id, userId: req.user.id }
    });
    
    if (!customerService) {
      return res.status(404).json({ message: 'Customer service request not found' });
    }
    
    // Check if request can be updated
    if (['resolved', 'closed'].includes(customerService.status)) {
      return res.status(400).json({ message: 'Request cannot be updated as it is already resolved or closed' });
    }
    
    // Update fields
    if (description) customerService.description = description;
    if (attachments) customerService.attachments = attachments;
    
    await customerService.save();
    
    // Send notification to admin
    const admins = await User.findAll({
      where: { role: 'admin', isActive: true }
    });
    
    for (const admin of admins) {
      await notificationController.createNotification(
        admin.id,
        'Customer Service Request Updated',
        `Request #${customerService.id} has been updated by the customer.`,
        'system',
        customerService.id,
        null,
        `/admin/customer-service/${customerService.id}`
      );
    }
    
    res.json(customerService);
  } catch (error) {
    console.error('Update customer service request error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get all customer service requests (admin only)
const getAllRequests = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    const status = req.query.status;
    const type = req.query.type;
    
    const whereClause = {};
    
    // Add status filter
    if (status) {
      whereClause.status = status;
    }
    
    // Add type filter
    if (type) {
      whereClause.type = type;
    }
    
    const { count, rows } = await CustomerService.findAndCountAll({
      where: whereClause,
      limit,
      offset,
      include: [
        { 
          model: User,
          attributes: ['firstName', 'lastName', 'email', 'phone']
        },
        { model: Order },
        { model: Product },
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
      requests: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalRequests: count
    });
  } catch (error) {
    console.error('Get all customer service requests error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update customer service request status (admin only)
const updateRequestStatus = async (req, res) => {
  try {
    const { id } = req.params;
    const { status, priority, assignedTo, resolution } = req.body;
    
    const customerService = await CustomerService.findByPk(id);
    
    if (!customerService) {
      return res.status(404).json({ message: 'Customer service request not found' });
    }
    
    // Update fields
    if (status) customerService.status = status;
    if (priority) customerService.priority = priority;
    if (assignedTo !== undefined) customerService.assignedTo = assignedTo;
    if (resolution !== undefined) customerService.resolution = resolution;
    
    await customerService.save();
    
    // Send notification to user if status changed
    if (status && status !== customerService.status) {
      let title, message;
      
      switch (status) {
        case 'in-progress':
          title = 'Request In Progress';
          message = `Your ${customerService.type} request "${customerService.subject}" is now being processed.`;
          break;
        case 'resolved':
          title = 'Request Resolved';
          message = `Your ${customerService.type} request "${customerService.subject}" has been resolved.`;
          break;
        case 'closed':
          title = 'Request Closed';
          message = `Your ${customerService.type} request "${customerService.subject}" has been closed.`;
          break;
        default:
          title = 'Request Status Updated';
          message = `Your ${customerService.type} request "${customerService.subject}" status has been updated to ${status}.`;
      }
      
      await notificationController.createNotification(
        customerService.userId,
        title,
        message,
        'system',
        customerService.id,
        null,
        `/customer-service/${customerService.id}`
      );
    }
    
    res.json(customerService);
  } catch (error) {
    console.error('Update customer service request status error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  createRequest,
  getUserRequests,
  getRequestDetails,
  updateRequest,
  getAllRequests,
  updateRequestStatus
};
