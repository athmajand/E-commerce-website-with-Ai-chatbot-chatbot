const express = require('express');
const router = express.Router();
const adminController = require('../controllers/admin.controller');
const { authenticate, isAdmin } = require('../middleware/auth.middleware');

// All routes require admin authentication
router.use(authenticate, isAdmin);

// Dashboard
router.get('/dashboard', adminController.getDashboardStats);

// User management
router.get('/users', adminController.getAllUsers);
router.get('/users/:id', adminController.getUserDetails);
router.put('/users/:id', adminController.updateUser);
router.delete('/users/:id', adminController.deleteUser);

// Product management
router.get('/products', adminController.getAllProducts);
router.put('/products/:id', adminController.updateProduct);

// Order management
router.get('/orders', adminController.getAllOrders);
router.get('/orders/:id', adminController.getOrderDetails);
router.put('/orders/:id/status', adminController.updateOrderStatus);

// Category management
router.post('/categories', adminController.createCategory);
router.get('/categories', adminController.getAllCategories);
router.post('/subcategories', adminController.createSubcategory);

module.exports = router;
