-- Create the customer_registrations table
-- Run this directly in phpMyAdmin SQL tab

USE kisan_kart;

-- Drop the table if it exists to start fresh
DROP TABLE IF EXISTS customer_registrations;

-- Create the table
CREATE TABLE customer_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verification_token VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for faster searches
CREATE INDEX idx_customer_registrations_email ON customer_registrations(email);
CREATE INDEX idx_customer_registrations_phone ON customer_registrations(phone);
CREATE INDEX idx_customer_registrations_status ON customer_registrations(status);

-- Add comments to the table
ALTER TABLE customer_registrations 
COMMENT = 'Stores customer registration data from the registration form';

-- Insert a test record
INSERT INTO customer_registrations (
    first_name, 
    last_name, 
    email, 
    phone, 
    address, 
    city, 
    state, 
    postal_code, 
    status, 
    verification_token, 
    is_verified
) VALUES (
    'Test', 
    'User', 
    'test@example.com', 
    '9876543210', 
    '123 Test Street', 
    'Test City', 
    'Test State', 
    '123456', 
    'approved', 
    MD5(RAND()), 
    TRUE
);
