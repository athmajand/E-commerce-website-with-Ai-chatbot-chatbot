-- Create seller profiles table if it doesn't exist
CREATE TABLE IF NOT EXISTS seller_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    business_description TEXT,
    business_logo VARCHAR(255),
    business_address TEXT NOT NULL,
    gst_number VARCHAR(50),
    pan_number VARCHAR(50),
    bank_account_details TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_documents TEXT,
    rating FLOAT DEFAULT 0,
    total_reviews INT DEFAULT 0,
    commission_rate FLOAT DEFAULT 10.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
