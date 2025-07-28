const { Payment, Order } = require('../models');
const crypto = require('crypto');
const Razorpay = require('razorpay');
const notificationController = require('./notification.controller');

// Razorpay instance
const razorpay = new Razorpay({
  key_id: process.env.RAZORPAY_KEY_ID || 'rzp_test_YOUR_KEY_HERE',
  key_secret: process.env.RAZORPAY_KEY_SECRET || 'YOUR_SECRET_HERE'
});

// Get user's payment history
const getPaymentHistory = async (req, res) => {
  try {
    const payments = await Payment.findAll({
      where: { userId: req.user.id },
      include: [{ model: Order }],
      order: [['createdAt', 'DESC']]
    });

    res.json(payments);
  } catch (error) {
    console.error('Get payment history error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get payment details
const getPaymentDetails = async (req, res) => {
  try {
    const { id } = req.params;

    const payment = await Payment.findOne({
      where: { id, userId: req.user.id },
      include: [{ model: Order }]
    });

    if (!payment) {
      return res.status(404).json({ message: 'Payment not found' });
    }

    res.json(payment);
  } catch (error) {
    console.error('Get payment details error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Create Razorpay order
const createRazorpayOrder = async (req, res) => {
  try {
    const { orderId, amount } = req.body;

    // Check if order exists and belongs to user
    const order = await Order.findOne({
      where: { id: orderId, userId: req.user.id }
    });

    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }

    // Create Razorpay order
    const razorpayOrder = await razorpay.orders.create({
      amount: amount, // amount in paise
      currency: 'INR',
      receipt: `order_${orderId}`,
      payment_capture: 1
    });

    // Update payment with transaction ID
    const payment = await Payment.findOne({
      where: { orderId }
    });

    if (payment) {
      payment.transactionId = razorpayOrder.id;
      await payment.save();
    }

    res.json({
      id: razorpayOrder.id,
      amount: razorpayOrder.amount,
      currency: razorpayOrder.currency
    });
  } catch (error) {
    console.error('Create Razorpay order error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Verify Razorpay payment
const verifyRazorpayPayment = async (req, res) => {
  try {
    const { orderId, paymentId, signature, razorpayOrderId } = req.body;

    // Verify signature
    const generatedSignature = crypto
      .createHmac('sha256', process.env.RAZORPAY_KEY_SECRET || 'YOUR_SECRET_HERE')
      .update(`${razorpayOrderId}|${paymentId}`)
      .digest('hex');

    if (generatedSignature !== signature) {
      return res.status(400).json({ message: 'Invalid signature' });
    }

    // Update payment status
    const payment = await Payment.findOne({
      where: { orderId, userId: req.user.id }
    });

    if (!payment) {
      return res.status(404).json({ message: 'Payment not found' });
    }

    payment.status = 'completed';
    payment.transactionId = paymentId;
    payment.paymentDetails = {
      razorpayOrderId,
      paymentId,
      signature
    };
    await payment.save();

    // Update order status
    const order = await Order.findByPk(orderId);
    if (order) {
      order.paymentStatus = 'completed';
      if (order.orderStatus === 'pending') {
        order.orderStatus = 'processing';
      }
      await order.save();

      // Send payment success notification
      await notificationController.createPaymentStatusNotification(payment, 'completed');

      // Send order status notification if status changed
      if (order.orderStatus === 'processing') {
        await notificationController.createOrderStatusNotification(order, 'processing');
      }
    }

    res.json({ message: 'Payment verified successfully' });
  } catch (error) {
    console.error('Verify payment error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Process Cash on Delivery
const processCOD = async (req, res) => {
  try {
    const { orderId } = req.body;

    // Check if order exists and belongs to user
    const order = await Order.findOne({
      where: { id: orderId, userId: req.user.id }
    });

    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }

    // Update order status
    if (order.orderStatus === 'pending') {
      order.orderStatus = 'processing';
      await order.save();
    }

    res.json({ message: 'Cash on Delivery order processed successfully' });
  } catch (error) {
    console.error('Process COD error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get payment methods
const getPaymentMethods = async (req, res) => {
  try {
    // Return available payment methods
    const paymentMethods = [
      { id: 'razorpay', name: 'Credit/Debit Card', icon: 'bi-credit-card' },
      { id: 'upi', name: 'UPI', icon: 'bi-phone' },
      { id: 'netbanking', name: 'Net Banking', icon: 'bi-bank' },
      { id: 'wallet', name: 'Wallet', icon: 'bi-wallet2' },
      { id: 'cod', name: 'Cash on Delivery', icon: 'bi-cash' }
    ];

    res.json(paymentMethods);
  } catch (error) {
    console.error('Get payment methods error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get payment status
const getPaymentStatus = async (req, res) => {
  try {
    const { orderId } = req.params;

    // Check if order exists and belongs to user
    const order = await Order.findOne({
      where: { id: orderId, userId: req.user.id },
      include: [{ model: Payment }]
    });

    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }

    if (!order.Payment) {
      return res.status(404).json({ message: 'Payment not found' });
    }

    res.json({
      status: order.Payment.status,
      method: order.Payment.paymentMethod,
      amount: order.Payment.amount,
      transactionId: order.Payment.transactionId
    });
  } catch (error) {
    console.error('Get payment status error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  getPaymentHistory,
  getPaymentDetails,
  createRazorpayOrder,
  verifyRazorpayPayment,
  processCOD,
  getPaymentMethods,
  getPaymentStatus
};
