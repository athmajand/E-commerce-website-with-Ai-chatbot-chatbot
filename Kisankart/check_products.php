<?php
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

// Get products by category
try {
    // Get all categories
    $categories_query = "SELECT * FROM categories ORDER BY id";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Products by Category</h1>";

    foreach ($categories as $category) {
        echo "<h2>" . htmlspecialchars($category['name']) . " (ID: " . $category['id'] . ")</h2>";

        // Get products for this category
        $products_query = "SELECT * FROM products WHERE category_id = ? ORDER BY id";
        $products_stmt = $db->prepare($products_query);
        $products_stmt->bindParam(1, $category['id']);
        $products_stmt->execute();
        $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($products) > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Discount Price</th>
                    <th>Stock</th>
                    <th>Farmer ID</th>
                    <th>Image URL</th>
                    <th>Image Preview</th>
                    <th>Image Exists</th>
                    <th>Actions</th>
                  </tr>";

            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td>₹" . number_format($product['price'], 2) . "</td>";
                echo "<td>" . (isset($product['discount_price']) ? "₹" . number_format($product['discount_price'], 2) : "N/A") . "</td>";
                echo "<td>" . $product['stock_quantity'] . "</td>";
                echo "<td>" . $product['farmer_id'] . "</td>";

                // Image URL
                echo "<td>" . htmlspecialchars($product['image_url']) . "</td>";

                // Image preview
                echo "<td>";
                if (!empty($product['image_url'])) {
                    echo "<img src='" . htmlspecialchars($product['image_url']) . "' style='max-width: 100px; max-height: 100px;' alt='Product Image'>";
                } else {
                    echo "No image";
                }
                echo "</td>";

                // Check if image exists
                echo "<td>";
                if (!empty($product['image_url'])) {
                    $image_path = __DIR__ . '/' . $product['image_url'];
                    $exists = file_exists($image_path);
                    echo "Original path: " . ($exists ? "Yes" : "No") . "<br>";

                    // Check alternative paths
                    $alt_paths = [
                        'uploads/products/' . basename($product['image_url']),
                    ];

                    foreach ($alt_paths as $path) {
                        $full_path = __DIR__ . '/' . $path;
                        $alt_exists = file_exists($full_path);
                        echo "Alt path (" . $path . "): " . ($alt_exists ? "Yes" : "No") . "<br>";
                    }
                } else {
                    echo "No image URL";
                }
                echo "</td>";

                // Actions
                echo "<td>
                        <a href='frontend/product_details.php?id=" . $product['id'] . "' target='_blank'>View</a>
                      </td>";

                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No products found for this category.</p>";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
