# Kisan Kart Backend

This is the backend for the Kisan Kart e-commerce platform, an online marketplace connecting farmers directly with customers.

## Features

- User authentication and authorization
- Product management
- Shopping cart and wishlist
- Order processing
- Payment integration
- Seller management
- Admin dashboard
- Reviews and ratings
- Customer service

## Tech Stack

- Node.js
- Express.js
- MySQL (with Sequelize ORM)
- JWT for authentication

## Prerequisites

- Node.js (v14 or higher)
- MySQL (v5.7 or higher)

## Installation

1. Clone the repository
2. Navigate to the backend directory
3. Install dependencies:
   ```
   npm install
   ```
4. Create a MySQL database named `kisan_kart`
5. Update the `.env` file with your database credentials
6. Initialize the database:
   ```
   node src/utils/initDb.js
   ```
7. Start the server:
   ```
   npm start
   ```

## Environment Variables

Create a `.env` file in the root directory with the following variables:

```
PORT=5000
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=kisan_kart
JWT_SECRET=your_jwt_secret
JWT_EXPIRES_IN=7d
NODE_ENV=development
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

### Orders

- `POST /api/orders` - Create new order
- `GET /api/orders` - Get user's orders
- `GET /api/orders/:id` - Get order details
- `PUT /api/orders/:id/cancel` - Cancel order
- `GET /api/orders/seller/orders` - Get seller's orders (seller only)
- `PUT /api/orders/seller/order-items/:id` - Update order status (seller only)

### Admin

- `GET /api/admin/dashboard` - Get dashboard statistics
- `GET /api/admin/users` - Get all users
- `GET /api/admin/users/:id` - Get user details
- `PUT /api/admin/users/:id` - Update user
- `DELETE /api/admin/users/:id` - Delete user
- `GET /api/admin/products` - Get all products
- `PUT /api/admin/products/:id` - Update product
- `GET /api/admin/orders` - Get all orders
- `GET /api/admin/orders/:id` - Get order details
- `PUT /api/admin/orders/:id/status` - Update order status
- `POST /api/admin/categories` - Create category
- `GET /api/admin/categories` - Get all categories
- `POST /api/admin/subcategories` - Create subcategory

## License

This project is licensed under the MIT License.
