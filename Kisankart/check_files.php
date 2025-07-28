<?php
// Headers
header("Content-Type: text/plain; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Current directory: " . __DIR__ . "\n\n";

// Check for database.php file
$paths = [
    __DIR__ . '/api/config/database.php',
    __DIR__ . '/../api/config/database.php',
    'api/config/database.php',
    '../api/config/database.php'
];

echo "Checking for database.php file:\n";
foreach ($paths as $path) {
    echo "Path: " . $path . " - " . (file_exists($path) ? "EXISTS" : "NOT FOUND") . "\n";
}

// Check for products table
echo "\nChecking database connection and products table:\n";

// Try to include database.php
$database_included = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "Including database file from: " . $path . "\n";
        include_once $path;
        $database_included = true;
        break;
    }
}

if (!$database_included) {
    echo "Could not find database.php file!\n";
    exit;
}

// Try to connect to database
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "Database connection failed!\n";
        exit;
    }
    
    echo "Database connection successful!\n";
    
    // Check if products table exists
    $check_table_query = "SHOW TABLES LIKE 'products'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();
    
    if ($check_table_stmt->rowCount() > 0) {
        echo "Products table exists!\n";
        
        // Count products
        $count_query = "SELECT COUNT(*) as total FROM products";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $row = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total_products = $row['total'];
        
        echo "Total products: " . $total_products . "\n";
        
        // Get table structure
        $structure_query = "DESCRIBE products";
        $structure_stmt = $db->prepare($structure_query);
        $structure_stmt->execute();
        
        echo "\nProducts table structure:\n";
        while ($row = $structure_stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } else {
        echo "Products table does not exist!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
