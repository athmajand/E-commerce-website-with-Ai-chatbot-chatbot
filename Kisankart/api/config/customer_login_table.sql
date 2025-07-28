-- Create the customer_logins table
CREATE TABLE IF NOT EXISTS customer_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) UNIQUE,
    password VARCHAR(255) NOT NULL,
    customer_profile_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE
);

-- Add index for faster login queries
CREATE INDEX idx_customer_logins_email ON customer_logins(email);
CREATE INDEX idx_customer_logins_phone ON customer_logins(phone);
