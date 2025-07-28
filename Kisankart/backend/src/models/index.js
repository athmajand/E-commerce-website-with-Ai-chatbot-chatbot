const User = require('./user.model');
const Address = require('./address.model');
const Product = require('./product.model');
const Category = require('./category.model');
const Subcategory = require('./subcategory.model');
const Cart = require('./cart.model');
const Wishlist = require('./wishlist.model');
const Order = require('./order.model');
const OrderItem = require('./orderItem.model');
const Payment = require('./payment.model');
const Review = require('./review.model');
const Seller = require('./seller.model');
const Notification = require('./notification.model');
const CustomerService = require('./customerService.model');
const Message = require('./message.model');

// User relationships
User.hasMany(Address, { foreignKey: 'userId' });
Address.belongsTo(User, { foreignKey: 'userId' });

User.hasMany(Cart, { foreignKey: 'userId' });
Cart.belongsTo(User, { foreignKey: 'userId' });

User.hasMany(Wishlist, { foreignKey: 'userId' });
Wishlist.belongsTo(User, { foreignKey: 'userId' });

User.hasMany(Order, { foreignKey: 'userId' });
Order.belongsTo(User, { foreignKey: 'userId' });

User.hasMany(Review, { foreignKey: 'userId' });
Review.belongsTo(User, { foreignKey: 'userId' });

User.hasMany(Notification, { foreignKey: 'userId' });
Notification.belongsTo(User, { foreignKey: 'userId' });

User.hasMany(CustomerService, { foreignKey: 'userId' });
CustomerService.belongsTo(User, { foreignKey: 'userId' });

User.hasOne(Seller, { foreignKey: 'userId' });
Seller.belongsTo(User, { foreignKey: 'userId' });

// Category relationships
Category.hasMany(Subcategory, { foreignKey: 'categoryId' });
Subcategory.belongsTo(Category, { foreignKey: 'categoryId' });

Category.hasMany(Product, { foreignKey: 'categoryId' });
Product.belongsTo(Category, { foreignKey: 'categoryId' });

Subcategory.hasMany(Product, { foreignKey: 'subcategoryId' });
Product.belongsTo(Subcategory, { foreignKey: 'subcategoryId' });

// Product relationships
Product.hasMany(Cart, { foreignKey: 'productId' });
Cart.belongsTo(Product, { foreignKey: 'productId' });

Product.hasMany(Wishlist, { foreignKey: 'productId' });
Wishlist.belongsTo(Product, { foreignKey: 'productId' });

Product.hasMany(OrderItem, { foreignKey: 'productId' });
OrderItem.belongsTo(Product, { foreignKey: 'productId' });

Product.hasMany(Review, { foreignKey: 'productId' });
Review.belongsTo(Product, { foreignKey: 'productId' });

Product.hasMany(CustomerService, { foreignKey: 'productId' });
CustomerService.belongsTo(Product, { foreignKey: 'productId' });

// Seller relationships
Seller.hasMany(Product, { foreignKey: 'sellerId' });
Product.belongsTo(Seller, { foreignKey: 'sellerId' });

Seller.hasMany(OrderItem, { foreignKey: 'sellerId' });
OrderItem.belongsTo(Seller, { foreignKey: 'sellerId' });

Seller.hasMany(CustomerService, { foreignKey: 'sellerId' });
CustomerService.belongsTo(Seller, { foreignKey: 'sellerId' });

// Order relationships
Order.hasMany(OrderItem, { foreignKey: 'orderId' });
OrderItem.belongsTo(Order, { foreignKey: 'orderId' });

Order.hasOne(Payment, { foreignKey: 'orderId' });
Payment.belongsTo(Order, { foreignKey: 'orderId' });

Order.hasMany(CustomerService, { foreignKey: 'orderId' });
CustomerService.belongsTo(Order, { foreignKey: 'orderId' });

Order.hasMany(Review, { foreignKey: 'orderId' });
Review.belongsTo(Order, { foreignKey: 'orderId' });

// Message relationships
User.hasMany(Message, {
  foreignKey: 'senderId',
  constraints: false,
  scope: {
    senderRole: 'customer'
  }
});

User.hasMany(Message, {
  foreignKey: 'receiverId',
  constraints: false,
  scope: {
    receiverRole: 'customer'
  }
});

// Export all models
module.exports = {
  User,
  Address,
  Product,
  Category,
  Subcategory,
  Cart,
  Wishlist,
  Order,
  OrderItem,
  Payment,
  Review,
  Seller,
  Notification,
  CustomerService,
  Message
};
