-- Create database if not exists
CREATE DATABASE IF NOT EXISTS kisan_kart;
USE kisan_kart;

-- Create seller_registrations table
CREATE TABLE IF NOT EXISTS seller_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    image_path VARCHAR(255),
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    seller_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (seller_id) REFERENCES seller_registrations(id)
);

-- Create customer_registrations table
CREATE TABLE IF NOT EXISTS customer_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    status TINYINT DEFAULT 1,
    email_verified TINYINT DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expiry TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create customer_profiles table
CREATE TABLE IF NOT EXISTS customer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    profile_picture VARCHAR(255),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    preferences TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_registrations(id) ON DELETE CASCADE
);

-- Create addresses table
CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    address_type ENUM('home', 'office', 'other') DEFAULT 'home',
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'India',
    is_default TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_registrations(id) ON DELETE CASCADE
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (customer_id, product_id)
);

-- Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (customer_id, product_id)
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    address_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cod', 'online', 'upi') DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    delivery_slot VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_registrations(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    seller_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (seller_id) REFERENCES seller_registrations(id)
);

-- Create admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    status TINYINT DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create seller_orders table (for seller dashboard)
CREATE TABLE IF NOT EXISTS seller_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    seller_id INT NOT NULL,
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    seller_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES seller_registrations(id)
);

-- Create product_images table
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create delivery_slots table
CREATE TABLE IF NOT EXISTS delivery_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_orders INT DEFAULT 50,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('customer', 'seller', 'admin') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT IGNORE INTO seller_registrations (name, email) VALUES 
('Farm Fresh', 'farmfresh@example.com'),
('Organic Valley', 'organicvalley@example.com');

INSERT IGNORE INTO categories (name) VALUES 
('Vegetables'), 
('Fruits'), 
('Dairy'),
('Grains'),
('Spices');

INSERT IGNORE INTO products (name, description, category_id, price, stock, seller_id) VALUES
('Tomatoes', 'Fresh red tomatoes', 1, 50.00, 100, 1),
('Apples', 'Organic Kashmiri apples', 2, 120.00, 50, 2),
('Milk', 'Pure cow milk', 3, 25.00, 200, 1),
('Basmati Rice', 'Premium quality basmati rice', 4, 80.00, 150, 1),
('Black Pepper', 'Freshly ground black pepper', 5, 45.00, 75, 2);

-- Insert sample admin
INSERT IGNORE INTO admin (username, email, password, full_name, role) VALUES
('admin', 'admin@kisankart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Insert sample delivery slots
INSERT IGNORE INTO delivery_slots (slot_name, start_time, end_time, max_orders) VALUES
('Morning (8 AM - 12 PM)', '08:00:00', '12:00:00', 50),
('Afternoon (12 PM - 4 PM)', '12:00:00', '16:00:00', 50),
('Evening (4 PM - 8 PM)', '16:00:00', '20:00:00', 50);

-- Verification queries
SELECT 'Database and tables created successfully' AS message;
SELECT COUNT(*) AS product_count FROM products;
SELECT COUNT(*) AS seller_count FROM seller_registrations;
SELECT COUNT(*) AS category_count FROM categories;
SELECT COUNT(*) AS admin_count FROM admin;
SELECT COUNT(*) AS delivery_slot_count FROM delivery_slots;
