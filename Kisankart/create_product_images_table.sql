-- Create product_images table for Kisan Kart
USE kisan_kart;

-- Create product_images table
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_product_images_product_id ON product_images(product_id);
CREATE INDEX idx_product_images_primary ON product_images(product_id, is_primary);

-- Verify table creation
SELECT 'product_images table created successfully' AS message;
DESCRIBE product_images; 