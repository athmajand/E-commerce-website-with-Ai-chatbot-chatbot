-- Add farmer_id column to products table
ALTER TABLE products ADD COLUMN farmer_id INT NULL AFTER seller_id;

-- Initialize farmer_id with seller_id values
UPDATE products SET farmer_id = seller_id;

-- Make farmer_id non-nullable
ALTER TABLE products MODIFY COLUMN farmer_id INT NOT NULL;

-- Optional: Add foreign key constraint if needed
-- ALTER TABLE products ADD CONSTRAINT fk_products_farmers 
-- FOREIGN KEY (farmer_id) REFERENCES seller_registrations(id);
