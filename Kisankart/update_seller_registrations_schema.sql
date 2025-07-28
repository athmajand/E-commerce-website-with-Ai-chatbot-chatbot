-- Update seller_registrations table to add additional fields
USE kisan_kart;

-- Add new columns for additional form fields
ALTER TABLE seller_registrations
ADD COLUMN date_of_birth DATE NULL COMMENT 'Seller date of birth' AFTER last_name,
ADD COLUMN id_type VARCHAR(50) NULL COMMENT 'Type of ID document (passport, national_id, etc.)' AFTER pan_number,
ADD COLUMN id_document_path VARCHAR(255) NULL COMMENT 'Path to uploaded ID document' AFTER id_type,
ADD COLUMN tax_classification VARCHAR(50) NULL COMMENT 'Tax classification (individual, business, etc.)' AFTER id_document_path,
ADD COLUMN tax_document_path VARCHAR(255) NULL COMMENT 'Path to uploaded tax document' AFTER tax_classification,
ADD COLUMN bank_account_number VARCHAR(50) NULL COMMENT 'Bank account number' AFTER bank_account_details,
ADD COLUMN account_holder_name VARCHAR(100) NULL COMMENT 'Bank account holder name' AFTER bank_account_number,
ADD COLUMN ifsc_code VARCHAR(20) NULL COMMENT 'IFSC code for Indian banks' AFTER account_holder_name,
ADD COLUMN bank_document_path VARCHAR(255) NULL COMMENT 'Path to uploaded bank document' AFTER ifsc_code,
ADD COLUMN store_display_name VARCHAR(100) NULL COMMENT 'Store display name shown to customers' AFTER bank_document_path,
ADD COLUMN product_categories TEXT NULL COMMENT 'JSON array of product categories' AFTER store_display_name,
ADD COLUMN marketplace VARCHAR(10) NULL COMMENT 'Marketplace country code (IN, US, etc.)' AFTER product_categories,
ADD COLUMN store_logo_path VARCHAR(255) NULL COMMENT 'Path to uploaded store logo' AFTER marketplace;

-- Add index on date_of_birth for potential age verification queries
ALTER TABLE seller_registrations
ADD INDEX idx_date_of_birth (date_of_birth);

-- Add index on marketplace for filtering sellers by marketplace
ALTER TABLE seller_registrations
ADD INDEX idx_marketplace (marketplace);

-- Show the updated table structure
SHOW CREATE TABLE seller_registrations;

-- Sample query to check if columns were added successfully
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_COMMENT
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'kisan_kart' 
    AND TABLE_NAME = 'seller_registrations'
ORDER BY 
    ORDINAL_POSITION;
