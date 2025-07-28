# Kisan Kart - Complete File Structure Analysis

## Overview
This document provides a comprehensive analysis of all files in the Kisan Kart project, their purposes, connections, and interactions.

## Project Architecture

The Kisan Kart project follows a **hybrid architecture** combining:
- **PHP** for core functionality and server-side processing
- **Node.js** for API services and modern JavaScript features
- **MySQL** for database management
- **Frontend** technologies (HTML5, CSS3, JavaScript, Bootstrap 5)

---

## Root Directory Files

### Core Application Files

#### **index.php**
- **Purpose**: Main entry point for the application
- **Functionality**: Homepage with featured products, navigation, and user authentication
- **Connections**: Links to all major sections (products, login, registration)
- **Dependencies**: Includes database connection, session management

#### **conn.php**
- **Purpose**: Database connection configuration
- **Functionality**: Establishes MySQL connection using PDO
- **Usage**: Included by most PHP files requiring database access
- **Configuration**: Host: localhost, Database: kisan_kart, User: root

#### **login.php**
- **Purpose**: User authentication interface
- **Functionality**: Login form for customers, sellers, and admins
- **Features**: Session management, role-based redirection
- **Connections**: Links to customer_dashboard.php, seller dashboard, admin dashboard

#### **login_with_otp.php**
- **Purpose**: Enhanced login with OTP verification
- **Functionality**: Two-factor authentication using email OTP
- **Dependencies**: PHPMailer for email sending
- **Security**: 6-digit OTP with 3-minute expiry

#### **otp_verification.php**
- **Purpose**: OTP verification and validation
- **Functionality**: Verifies OTP codes sent via email
- **Email Configuration**: Gmail SMTP (athmajand2003@gmail.com)
- **Security**: Time-based OTP expiration

#### **logout.php**
- **Purpose**: User session termination
- **Functionality**: Destroys sessions and redirects to homepage
- **Security**: Clears all session variables and cookies

### Registration Files

#### **customer_registration.php**
- **Purpose**: Customer account creation
- **Functionality**: Registration form with email verification
- **Database**: Inserts into customer_registrations table
- **Features**: Password hashing, email validation, OTP verification

#### **seller_registration.php**
- **Purpose**: Seller/farmer account creation
- **Functionality**: Business registration with document upload
- **Database**: Inserts into seller_registrations table
- **Features**: Business details, GST number, bank information

---

## Frontend Directory Structure

### **frontend/**
Main frontend application directory

#### **frontend/index.php**
- **Purpose**: Main homepage with dynamic content
- **Features**: Featured products, categories, search functionality
- **Database Queries**: Fetches products, categories from database
- **JavaScript**: Dynamic product loading, cart functionality

#### **frontend/css/**
Stylesheet directory containing:

##### **frontend/css/style.css**
- **Purpose**: Main application stylesheet
- **Features**: Responsive design, Bootstrap customizations
- **Components**: Navigation, cards, buttons, forms
- **Responsive**: Mobile-first design approach

##### **frontend/css/admin.css**
- **Purpose**: Admin dashboard specific styles
- **Features**: Dashboard layout, charts, tables
- **Components**: Sidebar, statistics cards, data tables

##### **frontend/css/seller.css**
- **Purpose**: Seller dashboard specific styles
- **Features**: Product management interface, order tracking
- **Components**: Product cards, order status indicators

#### **frontend/js/**
JavaScript modules directory

##### **frontend/js/main.js**
- **Purpose**: Core JavaScript functionality
- **Features**: Navigation, authentication, common utilities
- **API Calls**: User authentication, session management
- **Dependencies**: Bootstrap, Font Awesome

##### **frontend/js/products.js**
- **Purpose**: Product-related functionality
- **Features**: Product display, search, filtering, cart operations
- **API Endpoints**: /api/products, /api/cart
- **Functions**: addToCart(), displayProducts(), searchProducts()

##### **frontend/js/cart.js**
- **Purpose**: Shopping cart management
- **Features**: Add/remove items, quantity updates, price calculations
- **Local Storage**: Persistent cart data
- **API Integration**: Cart synchronization with backend

##### **frontend/js/checkout.js**
- **Purpose**: Checkout process management
- **Features**: Order placement, payment processing, address management
- **Payment Integration**: Razorpay API, Cash on Delivery
- **Validation**: Form validation, order confirmation

##### **frontend/js/chatbot-widget.js**
- **Purpose**: AI chatbot interface
- **Features**: Chat window, message handling, quick replies
- **API Integration**: /api/chatbot/message.php
- **NLP**: Intent recognition, context management

### Customer Interface Files

#### **frontend/customer_dashboard.php**
- **Purpose**: Customer main dashboard
- **Features**: Order history, profile management, wishlist
- **Database Queries**: User orders, profile data, recommendations
- **Security**: Session-based authentication

#### **frontend/customer_profile.php**
- **Purpose**: Customer profile management
- **Features**: Personal information, address book, password change
- **Database**: Updates customer_registrations table
- **Validation**: Email, phone number, address validation

#### **frontend/customer_orders.php**
- **Purpose**: Order history and tracking
- **Features**: Order list, status tracking, order details
- **Database**: Queries orders and order_items tables
- **Functionality**: Order cancellation, reorder options

#### **frontend/customer_wishlist.php**
- **Purpose**: Wishlist management
- **Features**: Save products, move to cart, remove items
- **Database**: wishlist table operations
- **JavaScript**: Dynamic wishlist updates

### Product Interface Files

#### **frontend/products.php**
- **Purpose**: Product catalog page
- **Features**: Product grid, search, filtering, pagination
- **Database**: Products, categories, seller information
- **Filters**: Price range, category, seller, availability

#### **frontend/product_details.php**
- **Purpose**: Individual product information
- **Features**: Product images, description, reviews, seller info
- **Database**: Product details, reviews, seller profile
- **Functionality**: Add to cart, add to wishlist, reviews

### Shopping Interface Files

#### **frontend/cart.html**
- **Purpose**: Shopping cart interface
- **Features**: Item list, quantity management, price calculation
- **JavaScript**: Real-time updates, remove items
- **Integration**: Checkout process initiation

#### **frontend/checkout.php**
- **Purpose**: Order placement interface
- **Features**: Address selection, payment method, order summary
- **Payment**: Razorpay integration, COD option
- **Validation**: Address, payment method validation

#### **frontend/payment.php**
- **Purpose**: Payment processing interface
- **Features**: Payment method selection, transaction handling
- **Razorpay**: Credit/debit cards, UPI, net banking
- **Security**: PCI DSS compliance, transaction encryption

---

## Seller Dashboard Files

### **frontend/seller/**
Seller-specific interface directory

#### **frontend/seller/dashboard.php**
- **Purpose**: Seller main dashboard
- **Features**: Sales overview, recent orders, product statistics
- **Database**: Seller orders, products, revenue data
- **Charts**: Sales graphs, performance metrics

#### **frontend/seller/products.php**
- **Purpose**: Product management interface
- **Features**: Product list, add/edit/delete products
- **Database**: Products table CRUD operations
- **Image Upload**: Multiple product images, image editing

#### **frontend/seller/orders.php**
- **Purpose**: Order management for sellers
- **Features**: Order list, status updates, fulfillment
- **Database**: Orders filtered by seller
- **Functionality**: Update order status, shipping details

#### **frontend/seller/profile.php**
- **Purpose**: Seller profile management
- **Features**: Business information, bank details, documents
- **Database**: seller_registrations, seller_profiles tables
- **File Upload**: Business documents, logos

---

## Admin Dashboard Files

### **admin_dashboard.php**
- **Purpose**: Administrative control panel
- **Features**: Platform overview, user management, system stats
- **Database**: All tables for comprehensive reporting
- **Analytics**: User statistics, sales reports, platform metrics

### **admin_customers.php**
- **Purpose**: Customer management interface
- **Features**: Customer list, account status, profile management
- **Database**: customer_registrations table
- **Actions**: Activate/deactivate accounts, view details

### **admin_sellers.php**
- **Purpose**: Seller management interface
- **Features**: Seller verification, approval process, performance tracking
- **Database**: seller_registrations, seller_profiles tables
- **Workflow**: Verification process, document review

### **admin_products.php**
- **Purpose**: Product moderation interface
- **Features**: Product approval, quality control, category management
- **Database**: products, categories tables
- **Moderation**: Approve/reject products, edit details

### **admin_orders.php**
- **Purpose**: Order monitoring and management
- **Features**: All platform orders, dispute resolution, analytics
- **Database**: orders, order_items tables
- **Reports**: Sales reports, order analytics

---

## API Directory Structure

### **api/**
Backend API services directory

#### **api/index.php**
- **Purpose**: API router and entry point
- **Functionality**: Routes requests to appropriate controllers
- **Endpoints**: RESTful API routing
- **Authentication**: JWT token validation

#### **api/config/**
Configuration files directory

##### **api/config/database.php**
- **Purpose**: Database connection class
- **Features**: PDO connection, error handling, connection pooling
- **Configuration**: MySQL settings, buffered queries
- **Security**: Connection encryption, error logging

##### **api/config/database.sql**
- **Purpose**: Database schema definition
- **Tables**: All 13 core tables with relationships
- **Constraints**: Foreign keys, indexes, data types
- **Sample Data**: Initial data for testing

#### **api/controllers/**
Business logic controllers

##### **api/controllers/auth_controller.php**
- **Purpose**: Authentication logic
- **Features**: Login, registration, JWT token generation
- **Security**: Password hashing, session management
- **Endpoints**: /api/auth/login, /api/auth/register

##### **api/controllers/product_controller.php**
- **Purpose**: Product management logic
- **Features**: CRUD operations, search, filtering
- **Database**: Products, categories tables
- **Endpoints**: /api/products, /api/categories

##### **api/controllers/user_controller.php**
- **Purpose**: User management logic
- **Features**: Profile management, address handling
- **Database**: User tables, addresses
- **Security**: Role-based access control

#### **api/models/**
Data access layer

##### **api/models/User.php**
- **Purpose**: User data model
- **Database**: customer_registrations table
- **Methods**: CRUD operations, validation
- **Security**: Input sanitization, SQL injection prevention

##### **api/models/Product.php**
- **Purpose**: Product data model
- **Database**: products table
- **Methods**: Search, filtering, inventory management
- **Features**: Image handling, category relationships

---

## Chatbot Implementation

### **api/chatbot/**
AI chatbot system directory

#### **api/chatbot/ChatbotService.php**
- **Purpose**: Main chatbot logic
- **Features**: Intent recognition, response generation
- **NLP**: Pattern matching, context awareness
- **Database**: messages table for conversation history

#### **api/chatbot/ChatbotServiceML.php**
- **Purpose**: Enhanced ML-based chatbot
- **Features**: Machine learning, pattern learning
- **AI**: Advanced NLP, confidence scoring
- **Learning**: Adaptive responses, user behavior analysis

#### **api/chatbot/message.php**
- **Purpose**: Chatbot API endpoint
- **Features**: Message processing, response delivery
- **Database**: Conversation storage, user context
- **Integration**: Frontend chatbot widget

---

## Backend Node.js Structure

### **backend/src/**
Node.js API services

#### **backend/src/server.js**
- **Purpose**: Express.js server configuration
- **Features**: API routing, middleware setup
- **Endpoints**: RESTful API endpoints
- **Integration**: Database connection, authentication

#### **backend/src/models/**
Sequelize ORM models

##### **backend/src/models/user.model.js**
- **Purpose**: User model for Node.js
- **ORM**: Sequelize model definition
- **Relationships**: Associations with other models
- **Validation**: Data validation rules

#### **backend/src/controllers/**
API endpoint controllers

##### **backend/src/controllers/cart.controller.js**
- **Purpose**: Shopping cart API logic
- **Features**: Add/remove items, quantity management
- **Database**: Cart table operations
- **Security**: User authentication, data validation

---

## Database Management Files

### Schema Files

#### **create_products_table.sql**
- **Purpose**: Products table creation
- **Structure**: Product catalog schema
- **Relationships**: Categories, sellers
- **Indexes**: Performance optimization

#### **create_orders_table.sql**
- **Purpose**: Orders and order_items tables
- **Structure**: Order management schema
- **Relationships**: Customers, products, sellers
- **Constraints**: Data integrity rules

#### **create_wishlist_table.sql**
- **Purpose**: Wishlist functionality
- **Structure**: User wishlist schema
- **Relationships**: Customers, products
- **Indexes**: Query optimization

### Setup and Maintenance Files

#### **setup_database.php**
- **Purpose**: Database initialization
- **Features**: Table creation, sample data insertion
- **Automation**: Complete database setup
- **Error Handling**: Rollback on failures

#### **setup_tables.php**
- **Purpose**: Table structure setup
- **Features**: Execute SQL files, dependency management
- **Validation**: Table existence checks
- **Logging**: Setup progress tracking

---

## File Upload and Media Management

### **uploads/**
User-uploaded content directory

#### **uploads/products/**
- **Purpose**: Product images storage
- **Features**: Multiple image support, thumbnails
- **Security**: File type validation, size limits
- **Organization**: Timestamp-based naming

#### **uploads/seller/**
- **Purpose**: Seller documents and logos
- **Subdirectories**:
  - `bank_documents/` - Banking information
  - `id_documents/` - Identity verification
  - `store_logos/` - Business branding
  - `tax_documents/` - Tax registration

### **images/**
Static image assets

#### **images/Product image/**
- **Purpose**: Sample product images
- **Content**: 50+ product images across categories
- **Format**: WebP, JPG for optimization
- **Categories**: Fruits, vegetables, grains, spices

#### **images/categories/**
- **Purpose**: Category images
- **Content**: Category icons and banners
- **Usage**: Navigation, product filtering

---

## Email and Communication

### **PHPMailer/**
Email functionality library

#### **PHPMailer/src/PHPMailer.php**
- **Purpose**: Email sending functionality
- **Features**: SMTP configuration, HTML emails
- **Configuration**: Gmail SMTP settings
- **Security**: App-specific passwords

#### **PHPMailer/src/SMTP.php**
- **Purpose**: SMTP protocol handling
- **Features**: Secure email transmission
- **Encryption**: SSL/TLS support
- **Authentication**: OAuth2, password authentication

---

## Utility and Helper Files

### **includes/**
Shared PHP components

#### **includes/navbar.php**
- **Purpose**: Navigation component
- **Features**: Responsive navigation, user menu
- **Authentication**: Role-based menu items
- **Cart**: Cart count display

#### **includes/header_helpers.php**
- **Purpose**: HTML head section helpers
- **Features**: Meta tags, CSS/JS includes
- **SEO**: Search engine optimization
- **Performance**: Resource optimization

### Testing and Debug Files

#### **test_*.php** files
- **Purpose**: Various testing utilities
- **Features**: Database testing, API testing, functionality verification
- **Debug**: Error logging, connection testing
- **Validation**: Data integrity checks

#### **check_*.php** files
- **Purpose**: System validation utilities
- **Features**: Database structure validation, file existence checks
- **Monitoring**: System health checks
- **Troubleshooting**: Error diagnosis

---

## Configuration Files

### **package.json**
- **Purpose**: Node.js dependencies
- **Dependencies**: Express.js, Sequelize, JWT libraries
- **Scripts**: Development, testing, production commands
- **Version**: Package version management

### **.env** (if exists)
- **Purpose**: Environment variables
- **Configuration**: Database credentials, API keys
- **Security**: Sensitive data protection
- **Deployment**: Environment-specific settings

---

## File Interconnections and Data Flow

### Authentication Flow
```
login.php → auth_controller.php → User.php → Database
↓
Session/JWT Token → Role-based Dashboard Redirect
```

### Product Display Flow
```
index.php → products.js → /api/products → product_controller.php → Product.php → Database
```

### Order Processing Flow
```
checkout.php → payment.php → order_controller.php → Order.php → Database
↓
Email Notification (PHPMailer) → Seller Dashboard Update
```

### Chatbot Flow
```
chatbot-widget.js → /api/chatbot/message.php → ChatbotService.php → Database
```

## Detailed File Dependencies and Connections

### Core Dependencies Map

#### **Database Connection Chain**
```
conn.php ← api/config/database.php ← All PHP files requiring DB access
```

#### **Authentication Chain**
```
login.php → auth_controller.php → User.php → JWT Token → Protected Pages
```

#### **Session Management**
```
login.php → $_SESSION variables → All dashboard pages → logout.php
```

### Frontend-Backend Integration

#### **AJAX API Calls**
- **products.js** → `/api/products` → **product_controller.php**
- **cart.js** → `/api/cart` → **cart_controller.php**
- **checkout.js** → `/api/orders` → **order_controller.php**
- **chatbot-widget.js** → `/api/chatbot/message.php` → **ChatbotService.php**

#### **Form Submissions**
- **customer_registration.php** → **customer_registrations** table
- **seller_registration.php** → **seller_registrations** table
- **product forms** → **products** table

### File Inclusion Hierarchy

#### **Common Includes**
```php
// Most PHP files include:
include_once 'conn.php';                    // Database connection
include_once 'includes/navbar.php';         // Navigation
include_once 'includes/header_helpers.php'; // HTML head section
```

#### **Dashboard Includes**
```php
// Dashboard files include:
include_once 'includes/sidebar.php';        // Sidebar navigation
include_once 'api/config/database.php';     // Enhanced DB connection
```

### JavaScript Module Dependencies

#### **Core JavaScript Loading Order**
1. **Bootstrap 5.3** (CDN)
2. **Font Awesome 6** (CDN)
3. **main.js** (Core functionality)
4. **Specific page JS** (products.js, cart.js, etc.)
5. **chatbot-integration.js** (AI chatbot)

#### **JavaScript File Relationships**
```javascript
main.js (base functions) ← products.js ← cart.js ← checkout.js
                        ← customer-dashboard.js
                        ← seller dashboard files
```

### Database Table Relationships

#### **User Management Tables**
```sql
users (central auth) ← customer_registrations (customer details)
                    ← seller_registrations (seller details)
                    ← addresses (user addresses)
```

#### **Product Management Tables**
```sql
categories ← products ← cart (customer cart items)
                    ← wishlist (customer wishlist)
                    ← order_items (order details)
```

#### **Order Management Tables**
```sql
customer_registrations ← orders ← order_items → products
                                             → seller_registrations
```

### Email System Integration

#### **PHPMailer Usage**
- **login_with_otp.php** → PHPMailer → Gmail SMTP
- **otp_verification.php** → PHPMailer → OTP emails
- **customer_registration.php** → PHPMailer → Welcome emails
- **order confirmations** → PHPMailer → Order notifications

#### **Email Configuration Chain**
```
Gmail Account (athmajand2003@gmail.com) → App Password → PHPMailer → SMTP
```

### Payment System Integration

#### **Razorpay Integration Flow**
```
checkout.php → payment.js → Razorpay API → payment_controller.php → Database
```

#### **Payment Files Connection**
- **frontend/payment.php** → **frontend/js/payment.js** → **Razorpay SDK**
- **payment_controller.php** → **Payment.php model** → **payments table**

### Image Upload System

#### **Image Processing Chain**
```
Seller Dashboard → File Upload → uploads/products/ → Database (image_url)
                                                   → Image optimization
```

#### **Image Display Chain**
```
Database (image_url) → products.php → HTML img tags → Browser display
```

### Security Implementation Chain

#### **Authentication Security**
```
Password Input → bcrypt hashing → Database storage
Login → Password verification → JWT token → Protected access
```

#### **Input Validation Chain**
```
User Input → JavaScript validation → PHP sanitization → Database insertion
```

### API Routing System

#### **PHP API Router**
```
/api/endpoint → api/index.php → Route parsing → Controller selection → Method execution
```

#### **Node.js API Router**
```
/api/endpoint → backend/src/server.js → Route matching → Controller → Response
```

### Error Handling and Logging

#### **Error Flow**
```
Application Error → Error Handler → Log File → Admin Notification (optional)
```

#### **Debug Files Chain**
```
check_*.php files → System validation → Error reporting → Fix recommendations
```

## Critical File Functions and Use Cases

### **Essential Core Files**

#### **conn.php** - Database Foundation
```php
// Critical for: All database operations
// Used by: 90% of PHP files
// Function: PDO connection establishment
// Error handling: Connection failure management
```

#### **index.php** - Application Entry Point
```php
// Critical for: First user interaction
// Features: Homepage, featured products, navigation
// Database queries: Products, categories
// Security: Session initialization
```

#### **login.php** - Authentication Gateway
```php
// Critical for: User access control
// Features: Multi-role login, session management
// Security: Password verification, role-based routing
// Integration: OTP system, dashboard redirection
```

### **User Management Critical Files**

#### **customer_registration.php** - Customer Onboarding
```php
// Purpose: New customer account creation
// Validation: Email uniqueness, password strength
// Security: Password hashing, email verification
// Database: customer_registrations table insertion
// Email: Welcome email, OTP verification
```

#### **seller_registration.php** - Seller Onboarding
```php
// Purpose: Business account creation
// Features: Business details, document upload
// Validation: GST number, bank details
// File handling: Document storage, logo upload
// Workflow: Verification process initiation
```

### **Dashboard Critical Files**

#### **frontend/customer_dashboard.php** - Customer Hub
```php
// Purpose: Customer main interface
// Features: Order history, profile, recommendations
// Database queries: Orders, wishlist, addresses
// Security: Session validation, user-specific data
// Integration: Chatbot, cart, profile management
```

#### **frontend/seller/dashboard.php** - Seller Control Center
```php
// Purpose: Seller business management
// Features: Sales analytics, order management
// Database queries: Seller orders, products, revenue
// Charts: Sales graphs, performance metrics
// Tools: Product management, order fulfillment
```

#### **admin_dashboard.php** - Platform Control
```php
// Purpose: Administrative oversight
// Features: Platform statistics, user management
// Database queries: All tables for reporting
// Analytics: User growth, sales trends, system health
// Controls: User approval, product moderation
```

### **E-commerce Critical Files**

#### **frontend/products.php** - Product Catalog
```php
// Purpose: Product discovery and browsing
// Features: Search, filtering, pagination
// Database: Products with seller and category data
// Performance: Optimized queries, image loading
// User experience: Responsive grid, quick actions
```

#### **frontend/checkout.php** - Order Processing
```php
// Purpose: Order finalization
// Features: Address selection, payment processing
// Validation: Cart contents, address completeness
// Payment: Razorpay integration, COD handling
// Security: Order validation, fraud prevention
```

#### **add_to_cart.php** - Cart Management
```php
// Purpose: Shopping cart operations
// Features: Add items, quantity management
// Database: Cart table operations
// Validation: Stock availability, user authentication
// AJAX: Real-time cart updates
```

### **API Critical Files**

#### **api/index.php** - API Gateway
```php
// Purpose: API request routing
// Features: RESTful endpoint management
// Security: Authentication middleware
// Error handling: Standardized error responses
// Documentation: API endpoint listing
```

#### **api/controllers/auth_controller.php** - Authentication API
```php
// Purpose: User authentication services
// Features: Login, registration, token management
// Security: JWT generation, password validation
// Integration: OTP verification, role assignment
// Response: Standardized auth responses
```

#### **api/controllers/product_controller.php** - Product API
```php
// Purpose: Product data services
// Features: CRUD operations, search, filtering
// Database: Products, categories, inventory
// Performance: Caching, optimized queries
// Integration: Image handling, seller data
```

### **Chatbot Critical Files**

#### **api/chatbot/ChatbotService.php** - AI Engine
```php
// Purpose: Intelligent customer support
// Features: Intent recognition, response generation
// NLP: Pattern matching, context management
// Database: Conversation history, learning data
// Integration: Order tracking, product queries
```

#### **frontend/js/chatbot-widget.js** - Chat Interface
```javascript
// Purpose: User chat interface
// Features: Chat window, message handling
// API integration: Real-time communication
// UX: Typing indicators, quick replies
// Persistence: Conversation history
```

### **Payment Critical Files**

#### **frontend/js/payment.js** - Payment Processing
```javascript
// Purpose: Payment method handling
// Features: Razorpay integration, COD processing
// Security: PCI compliance, transaction validation
// UX: Payment method selection, status updates
// Error handling: Payment failure management
```

#### **payment_processing.php** - Payment Backend
```php
// Purpose: Payment transaction processing
// Features: Payment verification, order confirmation
// Security: Transaction validation, fraud detection
// Database: Payment records, order updates
// Integration: Email notifications, inventory updates
```

### **Security Critical Files**

#### **otp_verification.php** - Two-Factor Authentication
```php
// Purpose: Enhanced security verification
// Features: OTP generation, email sending
// Security: Time-based expiration, attempt limiting
// Email: PHPMailer integration, SMTP configuration
// Validation: OTP format, user verification
```

#### **api/middleware/auth.php** - Access Control
```php
// Purpose: API security middleware
// Features: JWT validation, role-based access
// Security: Token verification, permission checking
// Error handling: Unauthorized access responses
// Integration: All protected API endpoints
```

### **Database Management Critical Files**

#### **setup_database.php** - Database Initialization
```php
// Purpose: Complete database setup
// Features: Table creation, sample data insertion
// Validation: Schema verification, dependency checking
// Error handling: Rollback on failures
// Automation: One-click database setup
```

#### **api/config/database.sql** - Schema Definition
```sql
-- Purpose: Complete database structure
-- Tables: All 13 core tables with relationships
-- Constraints: Foreign keys, indexes, validations
-- Sample data: Initial data for testing
-- Performance: Optimized indexes and queries
```

### **File Upload Critical Files**

#### **api/utils/FileUploader.php** - File Management
```php
// Purpose: Secure file upload handling
// Features: Type validation, size limits
// Security: File sanitization, path validation
// Storage: Organized directory structure
// Integration: Product images, seller documents
```

### **Email System Critical Files**

#### **PHPMailer/src/PHPMailer.php** - Email Engine
```php
// Purpose: Email functionality
// Features: SMTP configuration, HTML emails
// Security: Encrypted transmission, authentication
// Integration: OTP, notifications, confirmations
// Configuration: Gmail SMTP settings
```

### **Performance and Monitoring Files**

#### **check_database.php** - System Health
```php
// Purpose: Database connectivity monitoring
// Features: Connection testing, table validation
// Diagnostics: Performance metrics, error detection
// Maintenance: Health checks, optimization suggestions
// Alerts: System status reporting
```

This comprehensive file structure demonstrates a well-organized, scalable e-commerce platform with clear separation of concerns, proper security implementations, and modern web development practices. Each file serves a specific purpose in the overall system architecture, with clear dependencies and integration points that ensure smooth operation of the entire platform.
