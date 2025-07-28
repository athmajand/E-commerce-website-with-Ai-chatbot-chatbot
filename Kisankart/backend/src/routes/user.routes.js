const express = require('express');
const router = express.Router();
const userController = require('../controllers/user.controller');
const { authenticate } = require('../middleware/auth.middleware');

// Public routes
router.post('/register', userController.register);
router.post('/login', userController.login);

// Protected routes
router.get('/profile', authenticate, userController.getProfile);
router.put('/profile', authenticate, userController.updateProfile);
router.put('/change-password', authenticate, userController.changePassword);

// Address routes
router.get('/addresses', authenticate, userController.getAddresses);
router.post('/addresses', authenticate, userController.addAddress);
router.put('/addresses/:id', authenticate, userController.updateAddress);
router.delete('/addresses/:id', authenticate, userController.deleteAddress);

module.exports = router;
