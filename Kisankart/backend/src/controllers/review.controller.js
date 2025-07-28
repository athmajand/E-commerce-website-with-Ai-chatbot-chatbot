const { Review, Product, User, Order, OrderItem, Seller } = require('../models');
const { Op } = require('sequelize');
const sequelize = require('../config/database');

// Get product reviews
const getProductReviews = async (req, res) => {
  try {
    const { productId } = req.params;
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;
    const filter = req.query.filter || 'all'; // all, positive, negative, with_images
    const sort = req.query.sort || 'newest'; // newest, oldest, highest, lowest, helpful

    // Build where clause
    const whereClause = {
      productId,
      isApproved: true
    };

    // Add filter
    if (filter === 'positive') {
      whereClause.rating = { [Op.gte]: 4 };
    } else if (filter === 'negative') {
      whereClause.rating = { [Op.lte]: 2 };
    } else if (filter === 'with_images') {
      whereClause.images = { [Op.not]: null };
    }

    // Build order clause
    let order = [['createdAt', 'DESC']]; // default: newest

    if (sort === 'oldest') {
      order = [['createdAt', 'ASC']];
    } else if (sort === 'highest') {
      order = [['rating', 'DESC'], ['createdAt', 'DESC']];
    } else if (sort === 'lowest') {
      order = [['rating', 'ASC'], ['createdAt', 'DESC']];
    } else if (sort === 'helpful') {
      order = [['helpfulCount', 'DESC'], ['createdAt', 'DESC']];
    }

    const { count, rows } = await Review.findAndCountAll({
      where: whereClause,
      limit,
      offset,
      include: [
        {
          model: User,
          attributes: ['firstName', 'lastName', 'profileImage']
        }
      ],
      order
    });

    // Get rating summary
    const ratingSummary = await Review.findAll({
      where: { productId, isApproved: true },
      attributes: [
        'rating',
        [sequelize.fn('COUNT', sequelize.col('rating')), 'count']
      ],
      group: ['rating'],
      raw: true
    });

    // Format rating summary
    const formattedRatingSummary = {
      5: 0, 4: 0, 3: 0, 2: 0, 1: 0
    };

    ratingSummary.forEach(item => {
      formattedRatingSummary[item.rating] = parseInt(item.count);
    });

    // Calculate total reviews and average rating
    const totalReviews = Object.values(formattedRatingSummary).reduce((sum, count) => sum + count, 0);
    const weightedSum = Object.entries(formattedRatingSummary).reduce((sum, [rating, count]) => sum + (parseInt(rating) * count), 0);
    const averageRating = totalReviews > 0 ? (weightedSum / totalReviews).toFixed(1) : 0;

    res.json({
      reviews: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalReviews: count,
      ratingSummary: formattedRatingSummary,
      averageRating
    });
  } catch (error) {
    console.error('Get product reviews error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Add review (requires authentication)
const addReview = async (req, res) => {
  try {
    const { productId, orderId, rating, title, comment, images } = req.body;

    // Check if product exists
    const product = await Product.findOne({
      where: { id: productId, isActive: true }
    });

    if (!product) {
      return res.status(404).json({ message: 'Product not found' });
    }

    // Check if user has purchased the product
    let isVerifiedPurchase = false;

    if (orderId) {
      const orderItem = await OrderItem.findOne({
        where: {
          orderId,
          productId,
          status: 'delivered'
        },
        include: [
          {
            model: Order,
            where: { userId: req.user.id }
          }
        ]
      });

      isVerifiedPurchase = !!orderItem;
    }

    // Check if user has already reviewed this product
    const existingReview = await Review.findOne({
      where: {
        userId: req.user.id,
        productId
      }
    });

    if (existingReview) {
      return res.status(400).json({ message: 'You have already reviewed this product' });
    }

    // Create review
    const review = await Review.create({
      userId: req.user.id,
      productId,
      orderId: orderId || null,
      rating,
      title: title || null,
      comment: comment || null,
      images: images || null,
      isVerifiedPurchase
    });

    // Update product rating
    await updateProductRating(productId);

    // Update seller rating if applicable
    if (product.sellerId) {
      await updateSellerRating(product.sellerId);
    }

    res.status(201).json(review);
  } catch (error) {
    console.error('Add review error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update review (requires authentication)
const updateReview = async (req, res) => {
  try {
    const { id } = req.params;
    const { rating, title, comment, images } = req.body;

    // Find review
    const review = await Review.findOne({
      where: { id, userId: req.user.id }
    });

    if (!review) {
      return res.status(404).json({ message: 'Review not found' });
    }

    // Update fields
    if (rating) review.rating = rating;
    if (title !== undefined) review.title = title;
    if (comment !== undefined) review.comment = comment;
    if (images !== undefined) review.images = images;

    await review.save();

    // Update product rating
    await updateProductRating(review.productId);

    // Get product to update seller rating
    const product = await Product.findByPk(review.productId);
    if (product && product.sellerId) {
      await updateSellerRating(product.sellerId);
    }

    res.json(review);
  } catch (error) {
    console.error('Update review error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Delete review (requires authentication)
const deleteReview = async (req, res) => {
  try {
    const { id } = req.params;

    // Find review
    const review = await Review.findOne({
      where: { id, userId: req.user.id }
    });

    if (!review) {
      return res.status(404).json({ message: 'Review not found' });
    }

    // Get product to update seller rating later
    const product = await Product.findByPk(review.productId);
    const sellerId = product ? product.sellerId : null;

    // Delete review
    await review.destroy();

    // Update product rating
    await updateProductRating(review.productId);

    // Update seller rating if applicable
    if (sellerId) {
      await updateSellerRating(sellerId);
    }

    res.json({ message: 'Review deleted successfully' });
  } catch (error) {
    console.error('Delete review error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get user's reviews (requires authentication)
const getUserReviews = async (req, res) => {
  try {
    const reviews = await Review.findAll({
      where: { userId: req.user.id },
      include: [
        { model: Product }
      ],
      order: [['createdAt', 'DESC']]
    });

    res.json(reviews);
  } catch (error) {
    console.error('Get user reviews error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Mark review as helpful (requires authentication)
const markReviewAsHelpful = async (req, res) => {
  try {
    const { id } = req.params;

    // Find review
    const review = await Review.findByPk(id);

    if (!review) {
      return res.status(404).json({ message: 'Review not found' });
    }

    // Increment helpful count
    review.helpfulCount += 1;
    await review.save();

    res.json({ message: 'Review marked as helpful', helpfulCount: review.helpfulCount });
  } catch (error) {
    console.error('Mark review as helpful error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Helper function to update product rating
const updateProductRating = async (productId) => {
  const allReviews = await Review.findAll({
    where: { productId, isApproved: true },
    attributes: ['rating']
  });

  const product = await Product.findByPk(productId);

  if (allReviews.length > 0) {
    const totalRating = allReviews.reduce((sum, review) => sum + review.rating, 0);
    const averageRating = totalRating / allReviews.length;
    product.averageRating = averageRating;
  } else {
    product.averageRating = 0;
  }

  product.totalReviews = allReviews.length;
  await product.save();
};

// Helper function to update seller rating
const updateSellerRating = async (sellerId) => {
  // Get all products by this seller
  const products = await Product.findAll({
    where: { sellerId },
    attributes: ['id']
  });

  const productIds = products.map(product => product.id);

  // Get all reviews for these products
  const reviews = await Review.findAll({
    where: {
      productId: { [Op.in]: productIds },
      isApproved: true
    },
    attributes: ['rating']
  });

  const seller = await Seller.findByPk(sellerId);

  if (reviews.length > 0) {
    const totalRating = reviews.reduce((sum, review) => sum + review.rating, 0);
    const averageRating = totalRating / reviews.length;
    seller.rating = averageRating;
  } else {
    seller.rating = 0;
  }

  seller.totalReviews = reviews.length;
  await seller.save();
};

module.exports = {
  getProductReviews,
  addReview,
  updateReview,
  deleteReview,
  getUserReviews,
  markReviewAsHelpful
};
