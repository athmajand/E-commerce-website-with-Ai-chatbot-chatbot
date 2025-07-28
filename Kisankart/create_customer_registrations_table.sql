-- Create the customer_registrations table
USE kisan_kart;

CREATE TABLE IF NOT EXISTS customer_registrations (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Add indexes for faster searches
    INDEX idx_customer_registrations_email (email),
    INDEX idx_customer_registrations_phone (phone),
    INDEX idx_customer_registrations_status (status)
);

-- Add comments to the table and columns for better documentation
ALTER TABLE customer_registrations
COMMENT = 'Stores customer registration data from the registration form';
