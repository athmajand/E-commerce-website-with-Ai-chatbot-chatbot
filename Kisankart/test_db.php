<?php
// Headers
header("Content-Type: text/plain; charset=UTF-8");

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection was successful
if (!$db) {
    echo "Database connection failed.\n";
    exit;
}

echo "Database connection successful.\n";

// Check if products table exists
$check_table_query = "SHOW TABLES LIKE 'products'";
$check_table_stmt = $db->prepare($check_table_query);
$check_table_stmt->execute();

if ($check_table_stmt->rowCount() > 0) {
    echo "Products table exists.\n";
    
    // Count products
    $count_query = "SELECT COUNT(*) as total FROM products";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $row = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_products = $row['total'];
    
    echo "Total products: " . $total_products . "\n";
    
    // Get a sample product
    if ($total_products > 0) {
        $sample_query = "SELECT * FROM products LIMIT 1";
        $sample_stmt = $db->prepare($sample_query);
        $sample_stmt->execute();
        $sample_product = $sample_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Sample product:\n";
        print_r($sample_product);
    } else {
        echo "No products found in the table.\n";
    }
} else {
    echo "Products table does not exist.\n";
}
?>
