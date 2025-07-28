-- Create the seller_registrations table
USE kisan_kart;

CREATE TABLE IF NOT EXISTS seller_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    business_description TEXT,
    business_logo VARCHAR(255),
    business_address TEXT NOT NULL,
    business_country VARCHAR(50),
    business_state VARCHAR(100),
    business_city VARCHAR(100),
    business_postal_code VARCHAR(20),
    gst_number VARCHAR(50),
    pan_number VARCHAR(50),
    bank_account_details TEXT,
    verification_token VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    last_login TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Update any existing data (this is a placeholder - actual migration would depend on existing data)
-- INSERT INTO seller_registrations (first_name, last_name, email, phone, password, business_name, business_address)
-- SELECT u.firstName, u.lastName, u.email, u.phone, u.password, sp.business_name, sp.business_address
-- FROM users u
-- JOIN seller_profiles sp ON u.id = sp.user_id
-- WHERE u.role = 'seller';
