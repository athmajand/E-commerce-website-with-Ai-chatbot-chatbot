-- Update the seller_profiles table
USE kisan_kart;

-- Check if the seller_profiles table exists
SELECT COUNT(*) INTO @table_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'kisan_kart'
AND TABLE_NAME = 'seller_profiles';

-- If seller_profiles table doesn't exist, create it
SET @create_table_sql = CONCAT('
    CREATE TABLE IF NOT EXISTS seller_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
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
        INDEX (seller_id)
    )
');

PREPARE stmt FROM @create_table_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- If table exists, check if user_id column exists
SELECT COUNT(*) INTO @column_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'kisan_kart'
AND TABLE_NAME = 'seller_profiles'
AND COLUMN_NAME = 'user_id';

-- If user_id column exists, rename it to seller_id
SET @rename_column_sql = IF(@column_exists > 0,
    'ALTER TABLE seller_profiles CHANGE COLUMN user_id seller_id INT NOT NULL',
    'SELECT "No column to rename"');

PREPARE stmt FROM @rename_column_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if foreign key exists
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'kisan_kart'
AND CONSTRAINT_NAME = 'seller_profiles_ibfk_1'
AND TABLE_NAME = 'seller_profiles';

-- If foreign key exists, drop it
SET @drop_fk_sql = IF(@fk_exists > 0,
    'ALTER TABLE seller_profiles DROP FOREIGN KEY seller_profiles_ibfk_1',
    'SELECT "No foreign key to drop"');

PREPARE stmt FROM @drop_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key to seller_registrations
SET @add_fk_sql = 'ALTER TABLE seller_profiles ADD CONSTRAINT seller_profiles_fk_1 FOREIGN KEY (seller_id) REFERENCES seller_registrations(id) ON DELETE CASCADE';

PREPARE stmt FROM @add_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
