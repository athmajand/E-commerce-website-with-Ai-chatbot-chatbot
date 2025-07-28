<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if product ID is provided
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 10;

// Fetch product details
try {
    $product_query = "SELECT id, name, image_url, additional_images FROM products WHERE id = ?";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->bindParam(1, $product_id);
    $product_stmt->execute();

    if ($product_stmt->rowCount() > 0) {
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Product Image Information</h2>";
        echo "<p><strong>Product ID:</strong> " . $product['id'] . "</p>";
        echo "<p><strong>Product Name:</strong> " . $product['name'] . "</p>";
        echo "<p><strong>Image URL in Database:</strong> " . $product['image_url'] . "</p>";
        
        // Check if the image file exists
        $image_path = $product['image_url'];
        $absolute_path = __DIR__ . '/' . $image_path;
        
        echo "<p><strong>Absolute Path:</strong> " . $absolute_path . "</p>";
        echo "<p><strong>File Exists:</strong> " . (file_exists($absolute_path) ? 'Yes' : 'No') . "</p>";
        
        // Check alternative paths
        $alt_path1 = 'uploads/products/' . basename($product['image_url']);
        $alt_absolute_path1 = __DIR__ . '/' . $alt_path1;
        
        echo "<p><strong>Alternative Path 1:</strong> " . $alt_path1 . "</p>";
        echo "<p><strong>Alt Path 1 Exists:</strong> " . (file_exists($alt_absolute_path1) ? 'Yes' : 'No') . "</p>";
        
        $alt_path2 = '../uploads/products/' . basename($product['image_url']);
        $alt_absolute_path2 = __DIR__ . '/' . $alt_path2;
        
        echo "<p><strong>Alternative Path 2:</strong> " . $alt_path2 . "</p>";
        echo "<p><strong>Alt Path 2 Exists:</strong> " . (file_exists($alt_absolute_path2) ? 'Yes' : 'No') . "</p>";
        
        // Display additional images
        echo "<h3>Additional Images</h3>";
        if (!empty($product['additional_images'])) {
            $additional_images = json_decode($product['additional_images'], true);
            if (is_array($additional_images)) {
                echo "<ul>";
                foreach ($additional_images as $index => $img) {
                    echo "<li>Image " . ($index + 1) . ": " . $img;
                    $img_absolute_path = __DIR__ . '/' . $img;
                    echo " (Exists: " . (file_exists($img_absolute_path) ? 'Yes' : 'No') . ")</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Additional images format is not valid JSON array.</p>";
            }
        } else {
            echo "<p>No additional images found.</p>";
        }
        
        // Display the actual images
        echo "<h3>Image Preview</h3>";
        echo "<p>Main Image:</p>";
        echo "<img src='" . $product['image_url'] . "' style='max-width: 300px; border: 1px solid #ddd;'><br>";
        echo "<p>Using alternative path 1:</p>";
        echo "<img src='" . $alt_path1 . "' style='max-width: 300px; border: 1px solid #ddd;'><br>";
        
        // List all files in the uploads/products directory
        echo "<h3>Files in uploads/products directory</h3>";
        $uploads_dir = __DIR__ . '/uploads/products/';
        if (is_dir($uploads_dir)) {
            $files = scandir($uploads_dir);
            echo "<ul>";
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    echo "<li>" . $file . "</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<p>The uploads/products directory does not exist or is not accessible.</p>";
        }
    } else {
        echo "<p>Product not found.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
