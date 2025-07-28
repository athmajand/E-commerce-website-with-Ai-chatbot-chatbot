-- Fix seller_registrations table script (direct version without variables)
-- This script will:
-- 1. Check if the table exists and create it if it doesn't
-- 2. Handle duplicate emails and phones
-- 3. No variables or prepared statements are used

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

-- Create temporary table for duplicate emails
CREATE TEMPORARY TABLE IF NOT EXISTS temp_duplicate_emails AS
SELECT email, COUNT(*) as count
FROM seller_registrations
GROUP BY email
HAVING count > 1;

-- Update duplicate emails to make them unique
UPDATE seller_registrations sr
JOIN temp_duplicate_emails tde ON sr.email = tde.email
SET sr.email = CONCAT(sr.email, '-', sr.id)
WHERE EXISTS (SELECT 1 FROM temp_duplicate_emails WHERE email = sr.email);

-- Create temporary table for duplicate phones
CREATE TEMPORARY TABLE IF NOT EXISTS temp_duplicate_phones AS
SELECT phone, COUNT(*) as count
FROM seller_registrations
GROUP BY phone
HAVING count > 1;

-- Update duplicate phones to make them unique
UPDATE seller_registrations sr
JOIN temp_duplicate_phones tdp ON sr.phone = tdp.phone
SET sr.phone = CONCAT(sr.phone, '-', sr.id)
WHERE EXISTS (SELECT 1 FROM temp_duplicate_phones WHERE phone = sr.phone);

-- Drop temporary tables
DROP TEMPORARY TABLE IF EXISTS temp_duplicate_emails;
DROP TEMPORARY TABLE IF EXISTS temp_duplicate_phones;

-- Show the current structure of the table
SHOW CREATE TABLE seller_registrations;

-- Show any existing records
SELECT id, first_name, last_name, email, phone, business_name, status 
FROM seller_registrations 
LIMIT 10;
