<?php
// Direct script to create the customer_registrations table
// Run this file directly in the browser

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// SQL to create the table
$sql = "
USE kisan_kart;

-- Drop the table if it exists to start fresh
DROP TABLE IF EXISTS customer_registrations;

-- Create the table
CREATE TABLE customer_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verification_token VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes for faster searches
CREATE INDEX idx_customer_registrations_email ON customer_registrations(email);
CREATE INDEX idx_customer_registrations_phone ON customer_registrations(phone);
CREATE INDEX idx_customer_registrations_status ON customer_registrations(status);

-- Add comments to the table
ALTER TABLE customer_registrations 
COMMENT = 'Stores customer registration data from the registration form';
";

// Execute each statement separately
$statements = array_filter(array_map('trim', explode(';', $sql)), 'strlen');

echo "<h1>Creating customer_registrations table</h1>";
echo "<pre>";

try {
    foreach ($statements as $statement) {
        // Skip comments
        if (substr(trim($statement), 0, 2) == '--') {
            continue;
        }
        
        // Execute the statement
        $result = $db->exec($statement);
        echo "Executed: " . substr($statement, 0, 50) . "...\n";
    }
    
    // Check if table exists
    $check_query = "SHOW TABLES LIKE 'customer_registrations'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "\nSUCCESS: Customer registrations table created!\n";
        
        // Show table structure
        $describe_query = "DESCRIBE customer_registrations";
        $stmt = $db->prepare($describe_query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nTable structure:\n";
        echo "+--------------+------------------+------+-----+-------------------+----------------+\n";
        echo "| Field        | Type             | Null | Key | Default           | Extra          |\n";
        echo "+--------------+------------------+------+-----+-------------------+----------------+\n";
        
        foreach ($columns as $column) {
            printf("| %-12s | %-16s | %-4s | %-3s | %-17s | %-14s |\n",
                $column['Field'],
                $column['Type'],
                $column['Null'],
                $column['Key'],
                $column['Default'] ?: 'NULL',
                $column['Extra']
            );
        }
        
        echo "+--------------+------------------+------+-----+-------------------+----------------+\n";
    } else {
        echo "\nERROR: Table was not created successfully.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='setup_customer_registrations.php'>Go to Setup Page</a> | <a href='view_customer_registrations.php'>View Registrations</a></p>";
?>
