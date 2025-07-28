<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// SQL to add password field to customer_registrations table
$sql = "
-- Add password field to customer_registrations table if it doesn't exist
ALTER TABLE customer_registrations 
ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL DEFAULT '' AFTER phone;

-- Add index for faster login queries
CREATE INDEX IF NOT EXISTS idx_customer_registrations_password ON customer_registrations(password);
";

// Execute SQL queries
try {
    echo "<h2>Updating Customer Registrations Table</h2>";
    
    // Split SQL by semicolon to execute each query separately
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
            echo "Executed query: " . substr($query, 0, 50) . "...<br>";
        }
    }
    
    echo "<p style='color:green;'>Customer registrations table updated successfully!</p>";
    
    // Check if the password field was added
    $query = "SHOW COLUMNS FROM customer_registrations LIKE 'password'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>Password field exists in the customer_registrations table.</p>";
    } else {
        echo "<p style='color:red;'>Password field does not exist in the customer_registrations table!</p>";
    }
    
    echo "<p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error updating customer_registrations table: " . $e->getMessage() . "</p>";
}
?>
