<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Update Product Images</h1>";

// Define the product image mappings
$product_image_mappings = [
    'Fresh Kale' => 'fresh_kale.jpg',
    'Organic Lettuce' => 'organic_lettuce.jpg',
    'Fresh Tomatoes' => 'fresh_tomatoes.jpg',
    'Green Bell Peppers' => 'green_bell_peppers.jpg',
    'Fresh Cucumbers' => 'fresh_cucumbers.jpg',
    'Organic Eggplant' => 'broccoli.jpg', // Using broccoli image for eggplant
    'Fresh Apples' => 'apple.jpg',
    'carrot' => 'carrot.jpg',
    'Fresh Spinach' => 'spinach.jpg',
    'Organic Broccoli' => 'broccoli.jpg'
];

try {
    // Direct database connection
    $host = "localhost";
    $db_name = "kisan_kart";
    $username = "root";
    $password = "";

    // Try to connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all products
    $query = "SELECT id, name, image_url FROM products";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>ID</th>
            <th>Name</th>
            <th>Old Image URL</th>
            <th>New Image URL</th>
            <th>Status</th>
          </tr>";

    $updated_count = 0;

    foreach ($products as $product) {
        $product_name = $product['name'];
        $old_image_url = $product['image_url'];
        $new_image_url = null;
        $status = "No matching image found";

        // Check if we have a mapping for this product
        if (isset($product_image_mappings[$product_name])) {
            $image_filename = $product_image_mappings[$product_name];
            $new_image_url = 'uploads/products/' . $image_filename;
            
            // Check if the file exists
            $full_path = __DIR__ . '/' . $new_image_url;
            if (file_exists($full_path)) {
                // Update the database
                $update_query = "UPDATE products SET image_url = :image_url WHERE id = :id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':image_url', $new_image_url);
                $update_stmt->bindParam(':id', $product['id']);
                
                if ($update_stmt->execute()) {
                    $status = "<span style='color: green;'>Updated successfully</span>";
                    $updated_count++;
                } else {
                    $status = "<span style='color: red;'>Update failed</span>";
                }
            } else {
                $status = "<span style='color: orange;'>Image file not found: {$full_path}</span>";
            }
        }

        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product_name) . "</td>";
        echo "<td>" . htmlspecialchars($old_image_url ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($new_image_url ?? 'No change') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>Updated {$updated_count} out of " . count($products) . " products.</p>";
    
    echo "<p><a href='check_product_images.php' class='btn btn-primary'>Check Product Images</a></p>";

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ff0000;'>";
    echo "<h3>Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " on line " . $e->getLine() . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ff0000;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " on line " . $e->getLine() . "</p>";
    echo "</div>";
}
?>
