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

// Read SQL file
$sql = file_get_contents('api/config/update_backend_schema.sql');

// Execute SQL queries
try {
    echo "<h2>Updating Database Schema</h2>";
    
    // Split SQL by semicolon to execute each query separately
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
            echo "Executed query: " . substr($query, 0, 50) . "...<br>";
        }
    }
    
    echo "<p style='color:green;'>Database schema updated successfully!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error updating database schema: " . $e->getMessage() . "</p>";
}

// Check if tables were created successfully
try {
    echo "<h2>Verifying Database Tables</h2>";
    
    // List of tables to check
    $tables = [
        'users',
        'seller_profiles',
        'products',
        'product_images',
        'product_reviews',
        'seller_reviews',
        'orders',
        'order_items',
        'cart',
        'wishlist',
        'user_addresses'
    ];
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "<p>Table '$table' exists.</p>";
        } else {
            echo "<p style='color:red;'>Table '$table' does not exist!</p>";
        }
    }
    
    echo "<p style='color:green;'>Database verification completed.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error verifying database tables: " . $e->getMessage() . "</p>";
}
?>
