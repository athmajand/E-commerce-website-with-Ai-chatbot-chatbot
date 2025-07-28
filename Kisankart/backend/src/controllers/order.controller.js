const { Order, OrderItem, Cart, Product, Payment, Address, Seller, User } = require('../models');
const sequelize = require('../config/database');
const notificationController = require('./notification.controller');

// Generate unique order number
const generateOrderNumber = () => {
  const timestamp = new Date().getTime();
  const random = Math.floor(Math.random() * 1000);
  return `ORD-${timestamp}-${random}`;
};

// Create a new order
const createOrder = async (req, res) => {
  const transaction = await sequelize.transaction();

  try {
    const { shippingAddressId, paymentMethod, deliverySlot, notes } = req.body;

    // Check if address exists
    const address = await Address.findOne({
      where: { id: shippingAddressId, userId: req.user.id }
    });

    if (!address) {
      await transaction.rollback();
      return res.status(404).json({ message: 'Shipping address not found' });
    }

    // Get cart items
    const cartItems = await Cart.findAll({
      where: { userId: req.user.id },
      include: [{ model: Product }],
      transaction
    });

    if (cartItems.length === 0) {
      await transaction.rollback();
      return res.status(400).json({ message: 'Cart is empty' });
    }

    // Check stock availability
    for (const item of cartItems) {
      if (item.Product.stock < item.quantity) {
        await transaction.rollback();
        return res.status(400).json({
          message: `Not enough stock for ${item.Product.name}. Available: ${item.Product.stock}`
        });
      }
    }

    // Calculate total amount
    let totalAmount = 0;
    cartItems.forEach(item => {
      totalAmount += parseFloat(item.price) * item.quantity;
    });

    // Create order
    const order = await Order.create({
      userId: req.user.id,
      orderNumber: generateOrderNumber(),
      totalAmount,
      shippingAddressId,
      paymentMethod,
      deliverySlot: deliverySlot || null,
      notes: notes || null
    }, { transaction });

    // Create order items and update product stock
    for (const item of cartItems) {
      await OrderItem.create({
        orderId: order.id,
        productId: item.productId,
        sellerId: item.Product.sellerId,
        quantity: item.quantity,
        price: item.price
      }, { transaction });

      // Update product stock
      const product = await Product.findByPk(item.productId, { transaction });
      product.stock -= item.quantity;
      await product.save({ transaction });
    }

    // Create payment record
    await Payment.create({
      orderId: order.id,
      userId: req.user.id,
      amount: totalAmount,
      paymentMethod,
      status: paymentMethod === 'cod' ? 'pending' : 'completed'
    }, { transaction });

    // Clear cart
    await Cart.destroy({
      where: { userId: req.user.id },
      transaction
    });

    await transaction.commit();

    // Get complete order with items
    const completeOrder = await Order.findByPk(order.id, {
      include: [
        {
          model: OrderItem,
          include: [{ model: Product }]
        },
        { model: Payment },
        { model: Address }
      ]
    });

    // Send order confirmation notification
    await notificationController.createNotification(
      req.user.id,
      'Order Placed Successfully',
      `Your order #${order.orderNumber} has been placed successfully. We'll update you on its status.`,
      'order',
      order.id,
      null,
      `/orders/${order.id}`
    );

    res.status(201).json(completeOrder);
  } catch (error) {
    await transaction.rollback();
    console.error('Create order error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get user's orders
const getUserOrders = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;

    const { count, rows } = await Order.findAndCountAll({
      where: { userId: req.user.id },
      limit,
      offset,
      include: [
        { model: Payment },
        { model: Address }
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
    console.error('Get user orders error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get order details
const getOrderDetails = async (req, res) => {
  try {
    const { id } = req.params;

    const order = await Order.findOne({
      where: { id, userId: req.user.id },
      include: [
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
        },
        { model: Payment },
        { model: Address }
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

// Cancel order
const cancelOrder = async (req, res) => {
  const transaction = await sequelize.transaction();

  try {
    const { id } = req.params;
    const { reason } = req.body;

    // Find order
    const order = await Order.findOne({
      where: { id, userId: req.user.id },
      include: [{ model: OrderItem }],
      transaction
    });

    if (!order) {
      await transaction.rollback();
      return res.status(404).json({ message: 'Order not found' });
    }

    // Check if order can be cancelled
    if (!['pending', 'processing'].includes(order.orderStatus)) {
      await transaction.rollback();
      return res.status(400).json({
        message: 'Order cannot be cancelled as it is already shipped, delivered, or cancelled'
      });
    }

    // Update order status
    order.orderStatus = 'cancelled';
    order.cancellationReason = reason || 'Cancelled by customer';
    await order.save({ transaction });

    // Update order items status
    for (const item of order.OrderItems) {
      item.status = 'cancelled';
      await item.save({ transaction });

      // Restore product stock
      const product = await Product.findByPk(item.productId, { transaction });
      product.stock += item.quantity;
      await product.save({ transaction });
    }

    // Update payment status if not completed
    const payment = await Payment.findOne({
      where: { orderId: order.id },
      transaction
    });

    if (payment && payment.status === 'pending') {
      payment.status = 'cancelled';
      await payment.save({ transaction });
    }

    await transaction.commit();

    // Send cancellation notification
    await notificationController.createNotification(
      req.user.id,
      'Order Cancelled',
      `Your order #${order.orderNumber} has been cancelled successfully.`,
      'order',
      order.id,
      null,
      `/orders/${order.id}`
    );

    res.json({
      message: 'Order cancelled successfully',
      order
    });
  } catch (error) {
    await transaction.rollback();
    console.error('Cancel order error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get seller's orders
const getSellerOrders = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;

    // Get seller ID
    const seller = await Seller.findOne({ where: { userId: req.user.id } });

    if (!seller) {
      return res.status(403).json({ message: 'Seller profile not found' });
    }

    // Get order items for this seller
    const { count, rows } = await OrderItem.findAndCountAll({
      where: { sellerId: seller.id },
      limit,
      offset,
      include: [
        {
          model: Order,
          include: [
            { model: User, attributes: ['firstName', 'lastName', 'email', 'phone'] },
            { model: Address }
          ]
        },
        { model: Product }
      ],
      order: [['createdAt', 'DESC']]
    });

    res.json({
      orderItems: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalItems: count
    });
  } catch (error) {
    console.error('Get seller orders error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update order status (for sellers)
const updateOrderStatus = async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body;

    // Get seller ID
    const seller = await Seller.findOne({ where: { userId: req.user.id } });

    if (!seller) {
      return res.status(403).json({ message: 'Seller profile not found' });
    }

    // Find order item
    const orderItem = await OrderItem.findOne({
      where: { id, sellerId: seller.id },
      include: [{ model: Order }]
    });

    if (!orderItem) {
      return res.status(404).json({ message: 'Order item not found' });
    }

    // Update status
    orderItem.status = status;
    await orderItem.save();

    // Check if all items in the order have the same status
    const allOrderItems = await OrderItem.findAll({
      where: { orderId: orderItem.orderId }
    });

    const allSameStatus = allOrderItems.every(item => item.status === status);

    // If all items have the same status, update the order status
    if (allSameStatus) {
      const order = await Order.findByPk(orderItem.orderId);
      order.orderStatus = status;
      await order.save();

      // Send notification to customer about order status update
      await notificationController.createOrderStatusNotification(order, status);
    }

    res.json({ message: 'Order status updated successfully' });
  } catch (error) {
    console.error('Update order status error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  createOrder,
  getUserOrders,
  getOrderDetails,
  cancelOrder,
  getSellerOrders,
  updateOrderStatus
};
