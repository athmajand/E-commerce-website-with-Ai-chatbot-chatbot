const express = require('express');
const router = express.Router();
const { authenticate } = require('../middleware/auth.middleware');
const wishlistController = require('../controllers/wishlist.controller');

// All routes require authentication
router.use(authenticate);

// Get user's wishlist
router.get('/', wishlistController.getWishlist);

// Add item to wishlist
router.post('/add', wishlistController.addToWishlist);

// Remove item from wishlist
router.delete('/:id', wishlistController.removeFromWishlist);

// Clear wishlist
router.delete('/', wishlistController.clearWishlist);

// Move item from wishlist to cart
router.post('/:id/move-to-cart', wishlistController.moveToCart);

module.exports = router;
