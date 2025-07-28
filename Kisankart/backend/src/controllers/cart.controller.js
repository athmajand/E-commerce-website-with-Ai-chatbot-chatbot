const { Cart, Product, Seller, User } = require('../models');

// Get user's cart
const getCart = async (req, res) => {
  try {
    const cartItems = await Cart.findAll({
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
    
    // Calculate total
    let total = 0;
    cartItems.forEach(item => {
      total += parseFloat(item.price) * item.quantity;
    });
    
    res.json({
      items: cartItems,
      total,
      itemCount: cartItems.length
    });
  } catch (error) {
    console.error('Get cart error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Add item to cart
const addToCart = async (req, res) => {
  try {
    const { productId, quantity } = req.body;
    
    // Check if product exists and is active
    const product = await Product.findOne({
      where: { id: productId, isActive: true }
    });
    
    if (!product) {
      return res.status(404).json({ message: 'Product not found or unavailable' });
    }
    
    // Check if product is in stock
    if (product.stock < quantity) {
      return res.status(400).json({ message: 'Not enough stock available' });
    }
    
    // Check if item already in cart
    const existingItem = await Cart.findOne({
      where: { userId: req.user.id, productId }
    });
    
    if (existingItem) {
      // Update quantity
      existingItem.quantity += quantity;
      await existingItem.save();
      
      return res.json(existingItem);
    }
    
    // Add new item to cart
    const cartItem = await Cart.create({
      userId: req.user.id,
      productId,
      quantity,
      price: product.discountPrice || product.price
    });
    
    res.status(201).json(cartItem);
  } catch (error) {
    console.error('Add to cart error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update cart item
const updateCartItem = async (req, res) => {
  try {
    const { id } = req.params;
    const { quantity } = req.body;
    
    // Find cart item
    const cartItem = await Cart.findOne({
      where: { id, userId: req.user.id },
      include: [{ model: Product }]
    });
    
    if (!cartItem) {
      return res.status(404).json({ message: 'Cart item not found' });
    }
    
    // Check if product is in stock
    if (cartItem.Product.stock < quantity) {
      return res.status(400).json({ message: 'Not enough stock available' });
    }
    
    // Update quantity
    cartItem.quantity = quantity;
    await cartItem.save();
    
    res.json(cartItem);
  } catch (error) {
    console.error('Update cart item error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Remove item from cart
const removeFromCart = async (req, res) => {
  try {
    const { id } = req.params;
    
    // Find cart item
    const cartItem = await Cart.findOne({
      where: { id, userId: req.user.id }
    });
    
    if (!cartItem) {
      return res.status(404).json({ message: 'Cart item not found' });
    }
    
    // Delete cart item
    await cartItem.destroy();
    
    res.json({ message: 'Item removed from cart' });
  } catch (error) {
    console.error('Remove from cart error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Clear cart
const clearCart = async (req, res) => {
  try {
    // Delete all cart items for user
    await Cart.destroy({
      where: { userId: req.user.id }
    });
    
    res.json({ message: 'Cart cleared successfully' });
  } catch (error) {
    console.error('Clear cart error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  getCart,
  addToCart,
  updateCartItem,
  removeFromCart,
  clearCart
};
