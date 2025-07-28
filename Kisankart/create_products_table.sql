-- Create the products table
USE kisan_kart;

-- Create categories table first
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    discount_price DECIMAL(10, 2),
    category_id INT,
    seller_id INT,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    additional_images TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (seller_id) REFERENCES seller_profiles(id) ON DELETE CASCADE
    -- Note: seller_profiles.seller_id now references seller_registrations(id) instead of users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create product_attributes table for additional product details
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for faster queries
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_seller ON products(seller_id);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_product_attributes_product ON product_attributes(product_id);
