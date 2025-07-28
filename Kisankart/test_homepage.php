<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Homepage Database Connection and Products</h2>";

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test the exact query from homepage
try {
    echo "<h3>Testing Featured Products Query</h3>";
    
    // Get featured products directly
    $query = "SELECT * FROM products WHERE is_featured = 1 AND status = 'active' ORDER BY id DESC LIMIT 6";
    echo "<p>Query: <code>$query</code></p>";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Featured products found: <strong>" . count($featured_products) . "</strong></p>";
    
    if (!empty($featured_products)) {
        echo "<h4>Featured Products:</h4>";
        echo "<ul>";
        foreach ($featured_products as $product) {
            echo "<li>ID: {$product['id']} - Name: {$product['name']} - Price: ₹{$product['price']} - Featured: {$product['is_featured']} - Status: {$product['status']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ No featured products found with first query</p>";
        
        // Try a simpler query
        $query2 = "SELECT * FROM products ORDER BY id DESC LIMIT 6";
        echo "<p>Trying simpler query: <code>$query2</code></p>";
        
        $stmt2 = $db->prepare($query2);
        $stmt2->execute();
        $all_products = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>All products found: <strong>" . count($all_products) . "</strong></p>";
        
        if (!empty($all_products)) {
            echo "<h4>All Products:</h4>";
            echo "<ul>";
            foreach ($all_products as $product) {
                echo "<li>ID: {$product['id']} - Name: {$product['name']} - Price: ₹{$product['price']} - Featured: {$product['is_featured']} - Status: {$product['status']}</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database query error: " . $e->getMessage() . "</p>";
}

// Test if the homepage can be accessed
echo "<hr>";
echo "<h3>Testing Homepage Access</h3>";
echo "<p><a href='frontend/index.php' target='_blank'>Open Homepage in New Tab</a></p>";

// Check if there are any PHP errors in the error log
echo "<hr>";
echo "<h3>Checking for PHP Errors</h3>";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    echo "<p>Error log path: $error_log_path</p>";
    $recent_errors = file_get_contents($error_log_path);
    if ($recent_errors) {
        echo "<h4>Recent Errors:</h4>";
        echo "<pre>" . htmlspecialchars(substr($recent_errors, -1000)) . "</pre>";
    } else {
        echo "<p>No recent errors found in log.</p>";
    }
} else {
    echo "<p>Error log not found or not configured.</p>";
}
?> 