-- SQL to modify the customer_profiles table to remove dependency on users table
USE kisan_kart;

-- First, create a backup of the existing customer_profiles table
CREATE TABLE IF NOT EXISTS customer_profiles_backup AS SELECT * FROM customer_profiles;

-- Drop foreign key constraint
SET FOREIGN_KEY_CHECKS=0;

-- Create a new customer_profiles table without the foreign key constraint
CREATE TABLE IF NOT EXISTS customer_profiles_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Copy data from the old table to the new one
-- Note: This will need to be adjusted to include first_name, last_name, email, and phone from the users table
INSERT INTO customer_profiles_new (id, address, city, state, postal_code, profile_image, created_at, updated_at)
SELECT cp.id, cp.address, cp.city, cp.state, cp.postal_code, cp.profile_image, cp.created_at, cp.updated_at
FROM customer_profiles cp;

-- Update the new table with user information
UPDATE customer_profiles_new cpn
JOIN customer_profiles cp ON cpn.id = cp.id
JOIN users u ON cp.user_id = u.id
SET cpn.first_name = u.firstName,
    cpn.last_name = u.lastName,
    cpn.email = u.email,
    cpn.phone = u.phone;

-- Drop the old table
DROP TABLE customer_profiles;

-- Rename the new table
RENAME TABLE customer_profiles_new TO customer_profiles;

-- Update the customer_logins table to reference the new customer_profiles table
-- This assumes the customer_logins table exists
ALTER TABLE customer_logins
DROP FOREIGN KEY customer_logins_ibfk_1;

ALTER TABLE customer_logins
ADD CONSTRAINT customer_logins_ibfk_1
FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- Add indexes for faster searches
CREATE INDEX idx_customer_profiles_email ON customer_profiles(email);
CREATE INDEX idx_customer_profiles_phone ON customer_profiles(phone);
