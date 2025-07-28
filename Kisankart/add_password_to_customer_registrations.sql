-- Add password field to customer_registrations table
USE kisan_kart;

-- Add password field if it doesn't exist
ALTER TABLE customer_registrations 
ADD COLUMN password VARCHAR(255) NOT NULL AFTER phone;

-- Add index for faster login queries
CREATE INDEX idx_customer_registrations_password ON customer_registrations(password);
