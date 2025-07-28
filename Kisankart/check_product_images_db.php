<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get products with their image URLs
    $query = "SELECT id, name, image_url FROM products ORDER BY id LIMIT 20";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // HTML output
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Product Images Check</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .image-preview { max-width: 100px; max-height: 100px; }
            .success { color: green; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h1>Product Images Check</h1>";
    
    if (count($products) > 0) {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Image URL in DB</th>
                <th>Image Exists?</th>
                <th>Preview</th>
            </tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>{$product['image_url']}</td>";
            
            // Check if image exists
            $image_exists = false;
            $image_path = '';
            
            if (!empty($product['image_url'])) {
                // Handle different path formats
                if (strpos($product['image_url'], 'uploads/products/') !== false) {
                    $image_path = $product['image_url'];
                } else {
                    $filename = basename($product['image_url']);
                    $image_path = 'uploads/products/' . $filename;
                }
                
                // Check if file exists
                $full_path = __DIR__ . '/' . $image_path;
                $image_exists = file_exists($full_path);
                
                // For display in browser
                $display_path = $image_path;
            }
            
            echo "<td class='" . ($image_exists ? 'success' : 'error') . "'>" . ($image_exists ? 'Yes' : 'No') . "</td>";
            
            // Preview
            echo "<td>";
            if ($image_exists) {
                echo "<img src='{$display_path}' class='image-preview' alt='{$product['name']}'>";
            } else if (!empty($product['image_url'])) {
                echo "<img src='https://via.placeholder.com/100x100?text=Missing+Image' class='image-preview' alt='Missing'>";
            } else {
                echo "No image";
            }
            echo "</td>";
            
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No products found in the database.</p>";
    }
    
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
