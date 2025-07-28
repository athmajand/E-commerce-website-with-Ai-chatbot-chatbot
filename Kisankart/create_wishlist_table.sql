-- Create the wishlist table
USE kisan_kart;

CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer_product (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES customer_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for faster queries
CREATE INDEX idx_wishlist_customer ON wishlist(customer_id);
CREATE INDEX idx_wishlist_product ON wishlist(product_id);
