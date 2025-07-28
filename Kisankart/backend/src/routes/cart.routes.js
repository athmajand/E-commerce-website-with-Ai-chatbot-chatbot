const express = require('express');
const router = express.Router();
const cartController = require('../controllers/cart.controller');
const { authenticate } = require('../middleware/auth.middleware');

// All routes require authentication
router.use(authenticate);

router.get('/', cartController.getCart);
router.post('/add', cartController.addToCart);
router.put('/:id', cartController.updateCartItem);
router.delete('/:id', cartController.removeFromCart);
router.delete('/', cartController.clearCart);

module.exports = router;
