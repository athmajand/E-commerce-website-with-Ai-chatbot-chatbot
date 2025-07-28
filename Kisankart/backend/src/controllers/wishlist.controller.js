const { Wishlist, Product, Seller, User, Cart } = require('../models');

// Get user's wishlist
const getWishlist = async (req, res) => {
  try {
    const wishlistItems = await Wishlist.findAll({
      where: { userId: req.user.id },
      include: [
        {
          model: Product,
          include: [
            {
              model: Seller,
              include: [
                {
                  model: User,
                  attributes: ['firstName', 'lastName']
                }
              ]
            }
          ]
        }
      ]
    });

    res.json(wishlistItems);
  } catch (error) {
    console.error('Get wishlist error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Add item to wishlist
const addToWishlist = async (req, res) => {
  try {
    const { productId } = req.body;

    // Check if product exists and is active
    const product = await Product.findOne({
      where: { id: productId, isActive: true }
    });

    if (!product) {
      return res.status(404).json({ message: 'Product not found or unavailable' });
    }

    // Check if item already in wishlist
    const existingItem = await Wishlist.findOne({
      where: { userId: req.user.id, productId }
    });

    if (existingItem) {
      return res.status(400).json({ message: 'Product already in wishlist' });
    }

    // Add new item to wishlist
    const wishlistItem = await Wishlist.create({
      userId: req.user.id,
      productId
    });

    res.status(201).json(wishlistItem);
  } catch (error) {
    console.error('Add to wishlist error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Remove item from wishlist
const removeFromWishlist = async (req, res) => {
  try {
    const { id } = req.params;

    // Find wishlist item
    const wishlistItem = await Wishlist.findOne({
      where: { id, userId: req.user.id }
    });

    if (!wishlistItem) {
      return res.status(404).json({ message: 'Wishlist item not found' });
    }

    // Delete wishlist item
    await wishlistItem.destroy();

    res.json({ message: 'Item removed from wishlist' });
  } catch (error) {
    console.error('Remove from wishlist error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Clear wishlist
const clearWishlist = async (req, res) => {
  try {
    // Delete all wishlist items for user
    await Wishlist.destroy({
      where: { userId: req.user.id }
    });

    res.json({ message: 'Wishlist cleared successfully' });
  } catch (error) {
    console.error('Clear wishlist error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Move item from wishlist to cart
const moveToCart = async (req, res) => {
  try {
    const { id } = req.params;
    const { quantity = 1 } = req.body;

    // Find wishlist item
    const wishlistItem = await Wishlist.findOne({
      where: { id, userId: req.user.id },
      include: [{ model: Product }]
    });

    if (!wishlistItem) {
      return res.status(404).json({ message: 'Wishlist item not found' });
    }

    // Check if product is still active
    if (!wishlistItem.Product.isActive) {
      return res.status(400).json({ message: 'Product is no longer available' });
    }

    // Check if product is in stock
    if (wishlistItem.Product.stock < quantity) {
      return res.status(400).json({ message: 'Not enough stock available' });
    }

    // Add to cart
    const cartItem = await Cart.create({
      userId: req.user.id,
      productId: wishlistItem.productId,
      quantity,
      price: wishlistItem.Product.discountPrice || wishlistItem.Product.price
    });

    // Remove from wishlist
    await wishlistItem.destroy();

    res.status(201).json({
      message: 'Item moved to cart successfully',
      cartItem
    });
  } catch (error) {
    console.error('Move to cart error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  getWishlist,
  addToWishlist,
  removeFromWishlist,
  clearWishlist,
  moveToCart
};
