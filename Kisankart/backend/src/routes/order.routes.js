const express = require('express');
const router = express.Router();
const orderController = require('../controllers/order.controller');
const { authenticate, isSeller } = require('../middleware/auth.middleware');

// All routes require authentication
router.use(authenticate);

// Customer routes
router.post('/', orderController.createOrder);
router.get('/', orderController.getUserOrders);
router.get('/:id', orderController.getOrderDetails);
router.put('/:id/cancel', orderController.cancelOrder);

// Seller routes
router.get('/seller/orders', isSeller, orderController.getSellerOrders);
router.put('/seller/order-items/:id', isSeller, orderController.updateOrderStatus);

module.exports = router;
