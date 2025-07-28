const express = require('express');
const router = express.Router();
const { authenticate } = require('../middleware/auth.middleware');
const reviewController = require('../controllers/review.controller');

// Get product reviews
router.get('/product/:productId', reviewController.getProductReviews);

// Add review (requires authentication)
router.post('/', authenticate, reviewController.addReview);

// Update review (requires authentication)
router.put('/:id', authenticate, reviewController.updateReview);

// Delete review (requires authentication)
router.delete('/:id', authenticate, reviewController.deleteReview);

// Get user's reviews (requires authentication)
router.get('/user', authenticate, reviewController.getUserReviews);

// Mark review as helpful (requires authentication)
router.post('/:id/helpful', authenticate, reviewController.markReviewAsHelpful);

module.exports = router;
