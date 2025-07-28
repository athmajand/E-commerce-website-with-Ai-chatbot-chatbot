<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debugging Featured Products</h2>";

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Function to get featured products
function getFeaturedProducts($db, $limit = 6) {
    $products = [];

    try {
        echo "<p>Starting getFeaturedProducts function...</p>";
        
        // Use a simple query first to check if the products table exists
        try {
            $check_table = $db->query("SHOW TABLES LIKE 'products'");
            $table_exists = $check_table && $check_table->rowCount() > 0;

            if (!$table_exists) {
                echo "<p style='color: red;'>✗ Products table does not exist</p>";
                return $products;
            }
            echo "<p style='color: green;'>✓ Products table exists</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error checking products table: " . $e->getMessage() . "</p>";
            return $products;
        }

        // First, check if is_featured column exists
        $check_column = $db->query("SHOW COLUMNS FROM products LIKE 'is_featured'");
        $is_featured_exists = $check_column && $check_column->rowCount() > 0;
        echo "<p>is_featured column exists: " . ($is_featured_exists ? "Yes" : "No") . "</p>";

        // Check if status column exists
        $check_status = $db->query("SHOW COLUMNS FROM products LIKE 'status'");
        $status_exists = $check_status && $check_status->rowCount() > 0;
        echo "<p>status column exists: " . ($status_exists ? "Yes" : "No") . "</p>";

        // Check if created_at column exists
        $check_created_at = $db->query("SHOW COLUMNS FROM products LIKE 'created_at'");
        $created_at_exists = $check_created_at && $check_created_at->rowCount() > 0;
        echo "<p>created_at column exists: " . ($created_at_exists ? "Yes" : "No") . "</p>";

        // Determine the ORDER BY clause
        $order_by = $created_at_exists ? "p.created_at DESC" : "p.id DESC";
        echo "<p>Order by: {$order_by}</p>";

        // Construct query based on available columns
        if ($is_featured_exists && $status_exists) {
            $query = "SELECT p.* FROM products p WHERE p.is_featured = 1 AND p.status = 'active' ORDER BY $order_by LIMIT $limit";
        } elseif ($status_exists) {
            $query = "SELECT p.* FROM products p WHERE p.status = 'active' ORDER BY $order_by LIMIT $limit";
        } elseif ($is_featured_exists) {
            $query = "SELECT p.* FROM products p WHERE p.is_featured = 1 ORDER BY $order_by LIMIT $limit";
        } else {
            $query = "SELECT p.* FROM products p ORDER BY $order_by LIMIT $limit";
        }

        echo "<p>Query: <code>{$query}</code></p>";

        // Execute query
        $stmt = $db->prepare($query);
        $stmt->execute();

        echo "<p>Query executed successfully</p>";

        // If no products found, try a simpler query
        if ($stmt->rowCount() == 0) {
            echo "<p>No products found with first query, trying simpler query...</p>";
            $query = "SELECT * FROM products ORDER BY id DESC LIMIT $limit";
            echo "<p>Simple query: <code>{$query}</code></p>";
            $stmt = $db->prepare($query);
            $stmt->execute();
        }

        // Fetch all products
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Products fetched: " . count($products) . "</p>";

    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ PDO Error: " . $e->getMessage() . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ General Error: " . $e->getMessage() . "</p>";
    }

    return $products;
}

// Get featured products
$featured_products = getFeaturedProducts($db);

echo "<h3>Results:</h3>";
if (empty($featured_products)) {
    echo "<p style='color: orange;'>⚠ No products found</p>";
} else {
    echo "<p style='color: green;'>✓ Found " . count($featured_products) . " products</p>";
    echo "<ul>";
    foreach ($featured_products as $product) {
        echo "<li>ID: {$product['id']} - Name: {$product['name']} - Price: ₹{$product['price']}</li>";
    }
    echo "</ul>";
}
?> 