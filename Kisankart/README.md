# Kisan Kart - Connecting Farmers and Customers

Kisan Kart is an e-commerce platform designed to connect farmers directly with customers, eliminating middlemen and ensuring better prices for both parties. This platform allows farmers to sell their produce online and customers to buy fresh farm products directly from the source.

## Features

- User authentication and authorization
- Product management
- Shopping cart and wishlist
- Order processing
- Payment integration (Cash on Delivery and Razorpay)
- Seller management
- Admin dashboard
- Reviews and ratings
- Customer service
- Dynamic product display from database

## Tech Stack

### Backend
- Node.js
- Express.js
- MySQL (with Sequelize ORM)
- JWT for authentication

### Frontend
- HTML5
- CSS3
- JavaScript
- Bootstrap 5

## Prerequisites

- Node.js (v14 or higher)
- MySQL (v5.7 or higher)
- XAMPP (for local development)

## Installation

1. Clone the repository
   ```
   git clone https://github.com/yourusername/kisan-kart.git
   cd kisan-kart
   ```

2. Install dependencies
   ```
   npm install
   ```

3. Create a MySQL database named `kisan_kart`

4. Create a `.env` file in the root directory with the following variables:
   ```
   PORT=5000
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=your_password
   DB_NAME=kisan_kart
   JWT_SECRET=your_jwt_secret
   JWT_EXPIRES_IN=7d
   RAZORPAY_KEY_ID=your_razorpay_key_id
   RAZORPAY_KEY_SECRET=your_razorpay_key_secret
   ```

5. Initialize the database
   ```
   npm run init-db
   ```

6. Start the server
   ```
   npm run dev
   ```

7. Open your browser and navigate to `http://localhost:5000`

## Project Structure

```
kisan-kart/
├── backend/
│   ├── src/
│   │   ├── config/
│   │   ├── controllers/
│   │   ├── middleware/
│   │   ├── models/
│   │   ├── routes/
│   │   ├── utils/
│   │   └── server.js
│   └── README.md
├── frontend/
│   ├── admin/
│   ├── assets/
│   ├── css/
│   ├── js/
│   ├── seller/
│   └── index.html
├── .env
├── .gitignore
├── package.json
└── README.md
```

## API Documentation

### Authentication

- `POST /api/users/register` - Register a new user
- `POST /api/users/login` - Login user

### User Management

- `GET /api/users/profile` - Get user profile
- `PUT /api/users/profile` - Update user profile
- `PUT /api/users/change-password` - Change password
- `GET /api/users/addresses` - Get user addresses
- `POST /api/users/addresses` - Add new address
- `PUT /api/users/addresses/:id` - Update address
- `DELETE /api/users/addresses/:id` - Delete address

### Products

- `GET /api/products` - Get all products
- `GET /api/products/featured` - Get featured products
- `GET /api/products/:id` - Get product by ID
- `GET /api/products/category/:categoryId` - Get products by category
- `GET /api/products/subcategory/:subcategoryId` - Get products by subcategory
- `GET /api/products/search` - Search products
- `POST /api/products` - Create new product (seller only)
- `PUT /api/products/:id` - Update product (seller only)
- `DELETE /api/products/:id` - Delete product (seller only)

### Cart

- `GET /api/cart` - Get user's cart
- `POST /api/cart/add` - Add item to cart
- `PUT /api/cart/:id` - Update cart item
- `DELETE /api/cart/:id` - Remove item from cart
- `DELETE /api/cart` - Clear cart

### Wishlist

- `GET /api/wishlist` - Get user's wishlist
- `POST /api/wishlist/add` - Add item to wishlist
- `DELETE /api/wishlist/:id` - Remove item from wishlist
- `DELETE /api/wishlist` - Clear wishlist
- `POST /api/wishlist/:id/move-to-cart` - Move item from wishlist to cart

### Orders

- `POST /api/orders` - Create new order
- `GET /api/orders` - Get user's orders
- `GET /api/orders/:id` - Get order details
- `PUT /api/orders/:id/cancel` - Cancel order

### Payments

- `GET /api/payments/history` - Get payment history
- `GET /api/payments/:id` - Get payment details
- `POST /api/payments/create-order` - Create Razorpay order
- `POST /api/payments/verify` - Verify Razorpay payment
- `POST /api/payments/cod` - Process Cash on Delivery
- `GET /api/payments/methods` - Get payment methods
- `GET /api/payments/status/:orderId` - Get payment status

## License

This project is licensed under the ISC License.

## Dynamic Product Display

The application now displays products dynamically from the database. The featured products section on the homepage fetches products from the `products` table in the database.

### Adding Products to the Database

To add products to the database:

1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Select the `kisan_kart` database
3. Select the `products` table
4. Click on the "Insert" tab
5. Fill in the product details:
   - `name`: Product name
   - `description`: Product description
   - `price`: Regular price
   - `discount_price`: Discounted price (optional)
   - `category_id`: Category ID (optional)
   - `seller_id`: Seller ID (optional)
   - `stock_quantity`: Available stock
   - `image_url`: Path to product image (optional)
   - `is_featured`: Set to 1 to display on homepage
   - `status`: Set to 'active' to make product visible
6. Click "Go" to insert the product

### Sample Product Data

You can use the following SQL to insert sample products:

```sql
INSERT INTO `products`
(`name`, `description`, `price`, `discount_price`, `stock_quantity`, `is_featured`, `status`)
VALUES
('Organic Tomatoes', 'Fresh organic tomatoes from local farms', 120.00, 99.00, 50, 1, 'active'),
('Premium Rice', 'High-quality basmati rice', 350.00, 320.00, 100, 1, 'active'),
('Fresh Apples', 'Crisp and juicy apples', 180.00, 150.00, 75, 1, 'active'),
('Organic Potatoes', 'Premium quality potatoes grown without pesticides', 80.00, NULL, 120, 1, 'active'),
('Fresh Onions', 'High-quality onions from local farms', 60.00, 50.00, 200, 1, 'active'),
('Organic Carrots', 'Freshly harvested organic carrots', 90.00, 75.00, 80, 1, 'active');
```

## Acknowledgements

- [Bootstrap](https://getbootstrap.com/)
- [Razorpay](https://razorpay.com/)
- [Sequelize](https://sequelize.org/)
- [Express.js](https://expressjs.com/)
