const express = require('express');
const router = express.Router();
const { authenticate } = require('../middleware/auth.middleware');
const { Order } = require('../models');

// All routes require authentication
router.use(authenticate);

// Get available delivery slots
router.get('/delivery-slots', (req, res) => {
  try {
    // Generate delivery slots for the next 7 days
    const slots = [];
    const today = new Date();
    
    for (let i = 1; i <= 7; i++) {
      const date = new Date(today);
      date.setDate(today.getDate() + i);
      
      // Morning slot (9 AM - 12 PM)
      const morningSlot = new Date(date);
      morningSlot.setHours(9, 0, 0, 0);
      
      // Afternoon slot (1 PM - 4 PM)
      const afternoonSlot = new Date(date);
      afternoonSlot.setHours(13, 0, 0, 0);
      
      // Evening slot (5 PM - 8 PM)
      const eveningSlot = new Date(date);
      eveningSlot.setHours(17, 0, 0, 0);
      
      slots.push({
        date: date.toISOString().split('T')[0],
        slots: [
          {
            id: `morning-${date.toISOString().split('T')[0]}`,
            time: '9:00 AM - 12:00 PM',
            timestamp: morningSlot.toISOString()
          },
          {
            id: `afternoon-${date.toISOString().split('T')[0]}`,
            time: '1:00 PM - 4:00 PM',
            timestamp: afternoonSlot.toISOString()
          },
          {
            id: `evening-${date.toISOString().split('T')[0]}`,
            time: '5:00 PM - 8:00 PM',
            timestamp: eveningSlot.toISOString()
          }
        ]
      });
    }
    
    res.json(slots);
  } catch (error) {
    console.error('Get delivery slots error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Update delivery slot
router.put('/orders/:id/delivery-slot', async (req, res) => {
  try {
    const { id } = req.params;
    const { deliverySlot } = req.body;
    
    const order = await Order.findOne({
      where: { id, userId: req.user.id }
    });
    
    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }
    
    // Check if order can be updated
    if (!['pending', 'processing'].includes(order.orderStatus)) {
      return res.status(400).json({ message: 'Delivery slot cannot be updated at this stage' });
    }
    
    // Update delivery slot
    order.deliverySlot = deliverySlot;
    await order.save();
    
    res.json({ message: 'Delivery slot updated successfully', order });
  } catch (error) {
    console.error('Update delivery slot error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Track order
router.get('/track/:orderNumber', async (req, res) => {
  try {
    const { orderNumber } = req.params;
    
    const order = await Order.findOne({
      where: { orderNumber, userId: req.user.id }
    });
    
    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }
    
    // Generate tracking information based on order status
    let trackingInfo = [];
    
    // Order placed
    trackingInfo.push({
      status: 'Order Placed',
      description: 'Your order has been placed successfully',
      timestamp: order.createdAt,
      completed: true
    });
    
    // Payment confirmed
    trackingInfo.push({
      status: 'Payment Confirmed',
      description: 'Payment has been confirmed',
      timestamp: order.updatedAt,
      completed: order.paymentStatus === 'completed'
    });
    
    // Processing
    trackingInfo.push({
      status: 'Processing',
      description: 'Your order is being processed',
      timestamp: order.updatedAt,
      completed: ['processing', 'shipped', 'delivered'].includes(order.orderStatus)
    });
    
    // Shipped
    trackingInfo.push({
      status: 'Shipped',
      description: 'Your order has been shipped',
      timestamp: order.updatedAt,
      completed: ['shipped', 'delivered'].includes(order.orderStatus)
    });
    
    // Out for delivery
    trackingInfo.push({
      status: 'Out for Delivery',
      description: 'Your order is out for delivery',
      timestamp: order.updatedAt,
      completed: order.orderStatus === 'delivered'
    });
    
    // Delivered
    trackingInfo.push({
      status: 'Delivered',
      description: 'Your order has been delivered',
      timestamp: order.updatedAt,
      completed: order.orderStatus === 'delivered'
    });
    
    // If order is cancelled
    if (order.orderStatus === 'cancelled') {
      trackingInfo = [
        {
          status: 'Order Placed',
          description: 'Your order has been placed successfully',
          timestamp: order.createdAt,
          completed: true
        },
        {
          status: 'Cancelled',
          description: `Your order has been cancelled. Reason: ${order.cancelReason || 'Not specified'}`,
          timestamp: order.updatedAt,
          completed: true
        }
      ];
    }
    
    res.json({
      order,
      trackingInfo
    });
  } catch (error) {
    console.error('Track order error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;
