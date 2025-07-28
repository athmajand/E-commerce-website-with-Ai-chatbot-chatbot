-- Fix seller_registrations table script
-- This script will:
-- 1. Check if the table exists and create it if it doesn't
-- 2. Add any missing columns
-- 3. Ensure email and phone have unique constraints

USE kisan_kart;

-- Check if table exists, if not create it
CREATE TABLE IF NOT EXISTS seller_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
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

-- Add unique constraints if they don't exist
-- First check if email index exists
SET @email_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = 'kisan_kart'
    AND table_name = 'seller_registrations'
    AND index_name = 'email'
);

-- Add email unique constraint if it doesn't exist
SET @sql = IF(@email_index_exists = 0, 
    'ALTER TABLE seller_registrations ADD UNIQUE INDEX email (email)',
    'SELECT "Email index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if phone index exists
SET @phone_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = 'kisan_kart'
    AND table_name = 'seller_registrations'
    AND index_name = 'phone'
);

-- Add phone unique constraint if it doesn't exist
SET @sql = IF(@phone_index_exists = 0, 
    'ALTER TABLE seller_registrations ADD UNIQUE INDEX phone (phone)',
    'SELECT "Phone index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check for duplicate emails and fix them
CREATE TEMPORARY TABLE IF NOT EXISTS temp_duplicate_emails AS
SELECT email, COUNT(*) as count
FROM seller_registrations
GROUP BY email
HAVING count > 1;

-- Update duplicate emails to make them unique
UPDATE seller_registrations sr
JOIN temp_duplicate_emails tde ON sr.email = tde.email
SET sr.email = CONCAT(sr.email, '-', sr.id)
WHERE tde.count > 1;

-- Check for duplicate phones and fix them
CREATE TEMPORARY TABLE IF NOT EXISTS temp_duplicate_phones AS
SELECT phone, COUNT(*) as count
FROM seller_registrations
GROUP BY phone
HAVING count > 1;

-- Update duplicate phones to make them unique
UPDATE seller_registrations sr
JOIN temp_duplicate_phones tdp ON sr.phone = tdp.phone
SET sr.phone = CONCAT(sr.phone, '-', sr.id)
WHERE tdp.count > 1;

-- Drop temporary tables
DROP TEMPORARY TABLE IF EXISTS temp_duplicate_emails;
DROP TEMPORARY TABLE IF EXISTS temp_duplicate_phones;

-- Show the current structure of the table
SHOW CREATE TABLE seller_registrations;

-- Show any existing records
SELECT id, first_name, last_name, email, phone, business_name, status 
FROM seller_registrations 
LIMIT 10;
