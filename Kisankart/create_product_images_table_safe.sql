-- Safe script to create product_images table for Kisan Kart
USE kisan_kart;

-- Create product_images table if it doesn't exist
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create indexes only if they don't exist
-- Check and create product_id index
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'kisan_kart' 
     AND TABLE_NAME = 'product_images' 
     AND INDEX_NAME = 'idx_product_images_product_id') = 0,
    'CREATE INDEX idx_product_images_product_id ON product_images(product_id)',
    'SELECT "Index idx_product_images_product_id already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and create primary photo index
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'kisan_kart' 
     AND TABLE_NAME = 'product_images' 
     AND INDEX_NAME = 'idx_product_images_primary') = 0,
    'CREATE INDEX idx_product_images_primary ON product_images(product_id, is_primary)',
    'SELECT "Index idx_product_images_primary already exists" AS message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify table creation and show structure
SELECT 'product_images table setup completed' AS message;
DESCRIBE product_images;

-- Show existing indexes
SHOW INDEX FROM product_images; 