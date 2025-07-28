const { Notification, User, Order, Product, Wishlist } = require('../models');

// Get user's notifications
const getUserNotifications = async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    const type = req.query.type; // Filter by notification type

    // Build where clause
    const whereClause = { userId: req.user.id };

    // Add type filter if provided
    if (type) {
      whereClause.type = type;
    }

    const { count, rows } = await Notification.findAndCountAll({
      where: whereClause,
      limit,
      offset,
      order: [['createdAt', 'DESC']]
    });

    // Get unread count
    const unreadCount = await Notification.count({
      where: { userId: req.user.id, isRead: false }
    });

    res.json({
      notifications: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalNotifications: count,
      unreadCount
    });
  } catch (error) {
    console.error('Get notifications error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Mark notification as read
const markAsRead = async (req, res) => {
  try {
    const { id } = req.params;

    const notification = await Notification.findOne({
      where: { id, userId: req.user.id }
    });

    if (!notification) {
      return res.status(404).json({ message: 'Notification not found' });
    }

    notification.isRead = true;
    await notification.save();

    // Get updated unread count
    const unreadCount = await Notification.count({
      where: { userId: req.user.id, isRead: false }
    });

    res.json({
      message: 'Notification marked as read',
      unreadCount
    });
  } catch (error) {
    console.error('Mark notification as read error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Mark all notifications as read
const markAllAsRead = async (req, res) => {
  try {
    await Notification.update(
      { isRead: true },
      { where: { userId: req.user.id, isRead: false } }
    );

    res.json({
      message: 'All notifications marked as read',
      unreadCount: 0
    });
  } catch (error) {
    console.error('Mark all notifications as read error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Delete notification
const deleteNotification = async (req, res) => {
  try {
    const { id } = req.params;

    const notification = await Notification.findOne({
      where: { id, userId: req.user.id }
    });

    if (!notification) {
      return res.status(404).json({ message: 'Notification not found' });
    }

    await notification.destroy();

    // Get updated unread count
    const unreadCount = await Notification.count({
      where: { userId: req.user.id, isRead: false }
    });

    res.json({
      message: 'Notification deleted successfully',
      unreadCount
    });
  } catch (error) {
    console.error('Delete notification error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Delete all notifications
const deleteAllNotifications = async (req, res) => {
  try {
    await Notification.destroy({
      where: { userId: req.user.id }
    });

    res.json({
      message: 'All notifications deleted successfully',
      unreadCount: 0
    });
  } catch (error) {
    console.error('Delete all notifications error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Create notification (internal function)
const createNotification = async (userId, title, message, type, referenceId, image, actionUrl) => {
  try {
    const notification = await Notification.create({
      userId,
      title,
      message,
      type: type || 'system',
      referenceId: referenceId || null,
      image: image || null,
      actionUrl: actionUrl || null,
      isRead: false
    });

    return notification;
  } catch (error) {
    console.error('Create notification error:', error);
    return null;
  }
};

// Create order status notification
const createOrderStatusNotification = async (order, status) => {
  try {
    const userId = order.userId;
    let title, message, actionUrl;

    switch (status) {
      case 'processing':
        title = 'Order Confirmed';
        message = `Your order #${order.orderNumber} has been confirmed and is being processed.`;
        break;
      case 'shipped':
        title = 'Order Shipped';
        message = `Your order #${order.orderNumber} has been shipped and is on its way to you.`;
        break;
      case 'delivered':
        title = 'Order Delivered';
        message = `Your order #${order.orderNumber} has been delivered. Enjoy your purchase!`;
        break;
      case 'cancelled':
        title = 'Order Cancelled';
        message = `Your order #${order.orderNumber} has been cancelled.`;
        break;
      default:
        title = 'Order Update';
        message = `Your order #${order.orderNumber} status has been updated to ${status}.`;
    }

    actionUrl = `/orders/${order.id}`;

    await createNotification(
      userId,
      title,
      message,
      'order',
      order.id,
      null,
      actionUrl
    );
  } catch (error) {
    console.error('Create order status notification error:', error);
  }
};

// Create payment status notification
const createPaymentStatusNotification = async (payment, status) => {
  try {
    const userId = payment.userId;
    let title, message, actionUrl;

    switch (status) {
      case 'completed':
        title = 'Payment Successful';
        message = `Your payment of ₹${payment.amount} for order #${payment.Order.orderNumber} has been successfully processed.`;
        break;
      case 'failed':
        title = 'Payment Failed';
        message = `Your payment of ₹${payment.amount} for order #${payment.Order.orderNumber} has failed. Please try again.`;
        break;
      case 'refunded':
        title = 'Payment Refunded';
        message = `Your payment of ₹${payment.amount} for order #${payment.Order.orderNumber} has been refunded.`;
        break;
      default:
        title = 'Payment Update';
        message = `Your payment status for order #${payment.Order.orderNumber} has been updated to ${status}.`;
    }

    actionUrl = `/orders/${payment.orderId}`;

    await createNotification(
      userId,
      title,
      message,
      'payment',
      payment.id,
      null,
      actionUrl
    );
  } catch (error) {
    console.error('Create payment status notification error:', error);
  }
};

// Create product notification
const createProductNotification = async (product, type) => {
  try {
    // Get all users who have this product in their wishlist
    const wishlistUsers = await Wishlist.findAll({
      where: { productId: product.id },
      attributes: ['userId'],
      raw: true
    });

    const userIds = wishlistUsers.map(item => item.userId);

    if (userIds.length === 0) {
      return;
    }

    let title, message, actionUrl;

    switch (type) {
      case 'price_drop':
        title = 'Price Drop Alert';
        message = `The price of ${product.name} has dropped! Check it out now.`;
        break;
      case 'back_in_stock':
        title = 'Back in Stock';
        message = `${product.name} is back in stock! Don't miss out.`;
        break;
      default:
        title = 'Product Update';
        message = `There's an update for ${product.name} in your wishlist.`;
    }

    actionUrl = `/products/${product.id}`;

    // Create notification for each user
    for (const userId of userIds) {
      await createNotification(
        userId,
        title,
        message,
        'promotion',
        product.id,
        product.images && product.images.length > 0 ? product.images[0] : null,
        actionUrl
      );
    }
  } catch (error) {
    console.error('Create product notification error:', error);
  }
};

module.exports = {
  getUserNotifications,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  deleteAllNotifications,
  createNotification,
  createOrderStatusNotification,
  createPaymentStatusNotification,
  createProductNotification
};
