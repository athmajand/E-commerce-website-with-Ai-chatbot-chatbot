const express = require('express');
const router = express.Router();
const { authenticate } = require('../middleware/auth.middleware');
const paymentController = require('../controllers/payment.controller');
const { Payment, Order } = require('../models');

// All routes require authentication
router.use(authenticate);

// Get user's payment history
router.get('/history', paymentController.getPaymentHistory);

// Get payment details
router.get('/:id', paymentController.getPaymentDetails);

// Create Razorpay order
router.post('/create-order', paymentController.createRazorpayOrder);

// Verify Razorpay payment
router.post('/verify', paymentController.verifyRazorpayPayment);

// Process Cash on Delivery
router.post('/cod', paymentController.processCOD);

// Get payment methods
router.get('/methods', paymentController.getPaymentMethods);

// Get payment status
router.get('/status/:orderId', paymentController.getPaymentStatus);

// Update payment status (webhook endpoint - no auth required)
router.post('/webhook', async (req, res) => {
  try {
    const { transactionId, status, paymentDetails } = req.body;

    // Find payment by transaction ID
    const payment = await Payment.findOne({
      where: { transactionId }
    });

    if (!payment) {
      return res.status(404).json({ message: 'Payment not found' });
    }

    // Update payment status
    payment.status = status;
    if (paymentDetails) {
      payment.paymentDetails = paymentDetails;
    }
    await payment.save();

    // If payment is completed, update order status
    if (status === 'completed') {
      const order = await Order.findByPk(payment.orderId);
      if (order) {
        order.paymentStatus = 'completed';
        if (order.orderStatus === 'pending') {
          order.orderStatus = 'processing';
        }
        await order.save();
      }
    }

    res.json({ message: 'Payment status updated successfully' });
  } catch (error) {
    console.error('Update payment status error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;
