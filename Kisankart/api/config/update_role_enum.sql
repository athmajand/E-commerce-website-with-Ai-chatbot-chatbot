-- Update the role ENUM in the users table to include 'seller'
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'farmer', 'customer', 'seller') NOT NULL;
