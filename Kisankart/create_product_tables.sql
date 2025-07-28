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

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create seller_registrations table if not exists
CREATE TABLE IF NOT EXISTS seller_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample categories if needed
INSERT IGNORE INTO categories (name, description) VALUES 
('Vegetables', 'Fresh farm vegetables'),
('Fruits', 'Seasonal fruits'),
('Grains', 'Various grains and pulses');
