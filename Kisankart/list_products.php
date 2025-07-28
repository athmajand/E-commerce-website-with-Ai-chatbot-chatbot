<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection is successful
if (!$db) {
    die("Database connection failed. Please check your database settings.");
}

echo "<h1>Products in Database</h1>";

try {
    // Get all products
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY p.category_id, p.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Image URL</th>
                <th>Description</th>
              </tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['category_name']) . "</td>";
            echo "<td>₹" . number_format($product['price'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($product['image_url']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($product['description'], 0, 100)) . "...</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No products found in the database.</p>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
