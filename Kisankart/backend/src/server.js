const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');

// Load environment variables
dotenv.config();

// Import database connection
const db = require('./config/database');

// Import routes
const userRoutes = require('./routes/user.routes');
const productRoutes = require('./routes/product.routes');
const cartRoutes = require('./routes/cart.routes');
const wishlistRoutes = require('./routes/wishlist.routes');
const orderRoutes = require('./routes/order.routes');
const paymentRoutes = require('./routes/payment.routes');
const shippingRoutes = require('./routes/shipping.routes');
const sellerRoutes = require('./routes/seller.routes');
const searchRoutes = require('./routes/search.routes');
const notificationRoutes = require('./routes/notification.routes');
const reviewRoutes = require('./routes/review.routes');
const customerServiceRoutes = require('./routes/customerService.routes');
const chatbotRoutes = require('./routes/chatbot.routes');
const adminRoutes = require('./routes/admin.routes');

// Create Express app
const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Test database connection
db.authenticate()
  .then(() => console.log('Database connected...'))
  .catch(err => console.log('Error: ' + err));

// Routes
app.use('/api/users', userRoutes);
app.use('/api/products', productRoutes);
app.use('/api/cart', cartRoutes);
app.use('/api/wishlist', wishlistRoutes);
app.use('/api/orders', orderRoutes);
app.use('/api/payments', paymentRoutes);
app.use('/api/shipping', shippingRoutes);
app.use('/api/sellers', sellerRoutes);
app.use('/api/search', searchRoutes);
app.use('/api/notifications', notificationRoutes);
app.use('/api/reviews', reviewRoutes);
app.use('/api/customer-service', customerServiceRoutes);
app.use('/api/chatbot', chatbotRoutes);

app.use('/api/admin', adminRoutes);

// Root route
app.get('/', (req, res) => {
  res.json({ message: 'Welcome to Kisankart API' });
});

// Start server
const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});

module.exports = app;
