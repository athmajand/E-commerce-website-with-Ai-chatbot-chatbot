<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h3>Database Connection Test</h3>";
if ($db) {
    echo "<p style='color:green'>Database connection successful!</p>";
} else {
    echo "<p style='color:red'>Database connection failed!</p>";
}

// Check if products table exists
try {
    $check_table = $db->query("SHOW TABLES LIKE 'products'");
    $table_exists = $check_table->rowCount() > 0;
    
    echo "<p>Products table exists: " . ($table_exists ? "Yes" : "No") . "</p>";
    
    if ($table_exists) {
        // Count products
        $count_query = $db->query("SELECT COUNT(*) as count FROM products");
        $count = $count_query->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p>Number of products in database: $count</p>";
        
        if ($count > 0) {
            // Show sample of products
            $products_query = "SELECT * FROM products LIMIT 5";
            $products_stmt = $db->prepare($products_query);
            $products_stmt->execute();
            $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Sample Products:</h4>";
            echo "<pre>";
            print_r($products);
            echo "</pre>";
            
            // Try the exact query used in featured_products.php
            echo "<h4>Testing Featured Products Query:</h4>";
            $featured_query = "SELECT p.*, c.name as category_name 
                              FROM products p
                              LEFT JOIN categories c ON p.category_id = c.id
                              WHERE p.is_featured = 1 AND p.status = 'active'
                              ORDER BY p.created_at DESC
                              LIMIT 6";
            
            try {
                $featured_stmt = $db->prepare($featured_query);
                $featured_stmt->execute();
                $featured_count = $featured_stmt->rowCount();
                
                echo "<p>Number of featured products: $featured_count</p>";
                
                if ($featured_count > 0) {
                    $featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo "<pre>";
                    print_r($featured_products);
                    echo "</pre>";
                } else {
                    echo "<p>No featured products found. Trying alternative query...</p>";
                    
                    // Try alternative query for latest products
                    $latest_query = "SELECT p.*, c.name as category_name 
                                    FROM products p
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    WHERE p.status = 'active'
                                    ORDER BY p.created_at DESC
                                    LIMIT 6";
                    
                    $latest_stmt = $db->prepare($latest_query);
                    $latest_stmt->execute();
                    $latest_count = $latest_stmt->rowCount();
                    
                    echo "<p>Number of latest products: $latest_count</p>";
                    
                    if ($latest_count > 0) {
                        $latest_products = $latest_stmt->fetchAll(PDO::FETCH_ASSOC);
                        echo "<pre>";
                        print_r($latest_products);
                        echo "</pre>";
                    } else {
                        echo "<p style='color:red'>No products found with either query!</p>";
                        
                        // Check if there are any products with any status
                        $any_query = "SELECT * FROM products LIMIT 6";
                        $any_stmt = $db->prepare($any_query);
                        $any_stmt->execute();
                        $any_count = $any_stmt->rowCount();
                        
                        echo "<p>Number of products with any status: $any_count</p>";
                        
                        if ($any_count > 0) {
                            $any_products = $any_stmt->fetchAll(PDO::FETCH_ASSOC);
                            echo "<pre>";
                            print_r($any_products);
                            echo "</pre>";
                        }
                    }
                }
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error executing featured products query: " . $e->getMessage() . "</p>";
            }
            
            // Check products table structure
            echo "<h4>Products Table Structure:</h4>";
            $structure_query = $db->query("DESCRIBE products");
            $structure = $structure_query->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($structure as $column) {
                echo "<tr>";
                foreach ($column as $key => $value) {
                    echo "<td>" . (is_null($value) ? 'NULL' : $value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p style='color:red'>No products found in the database!</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
}
?>
