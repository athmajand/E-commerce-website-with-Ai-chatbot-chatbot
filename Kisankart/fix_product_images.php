<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Product Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #1e8449; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .success { color: green; }
        .error { color: red; }
        .image-preview { max-width: 100px; max-height: 100px; }
        button { background-color: #1e8449; color: white; border: none; padding: 10px 15px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Fix Product Images</h1>';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_images'])) {
    try {
        // Direct database connection
        $host = "localhost";
        $db_name = "kisan_kart";
        $username = "root";
        $password = "";
        
        // Try to connect to the database
        $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo '<p>Database connection successful!</p>';
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Get all products
        $query = "SELECT * FROM products";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $updated_count = 0;
        
        echo '<p>Found ' . count($products) . ' products in the database.</p>';
        
        // Start table
        echo '<table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Old Image URL</th>
                <th>New Image URL</th>
                <th>Status</th>
            </tr>';
        
        // Process each product
        foreach ($products as $product) {
            echo '<tr>';
            echo '<td>' . $product['id'] . '</td>';
            echo '<td>' . $product['name'] . '</td>';
            
            // Get old image URL
            $old_image_url = isset($product['image_url']) ? $product['image_url'] : '';
            if (empty($old_image_url) && isset($product['image'])) {
                $old_image_url = $product['image'];
            }
            
            echo '<td>' . $old_image_url . '</td>';
            
            // Generate new image URL
            $new_image_url = '';
            $status = '';
            
            if (!empty($old_image_url)) {
                // Get filename from path
                $filename = basename($old_image_url);
                
                // Create new path
                $new_image_url = $upload_dir . $filename;
                
                // If the old image exists, copy it to the new location
                if (file_exists($old_image_url)) {
                    if (copy($old_image_url, $new_image_url)) {
                        $status = '<span class="success">Copied file</span>';
                    } else {
                        $status = '<span class="error">Failed to copy file</span>';
                    }
                } else {
                    // Create a placeholder image
                    $placeholder_url = 'https://via.placeholder.com/300x200?text=' . urlencode($product['name']);
                    $placeholder_content = file_get_contents($placeholder_url);
                    
                    if ($placeholder_content && file_put_contents($new_image_url, $placeholder_content)) {
                        $status = '<span class="success">Created placeholder</span>';
                    } else {
                        $status = '<span class="error">Failed to create placeholder</span>';
                    }
                }
                
                // Update database
                $update_query = "UPDATE products SET image_url = :image_url WHERE id = :id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':image_url', $new_image_url);
                $update_stmt->bindParam(':id', $product['id']);
                
                if ($update_stmt->execute()) {
                    $status .= ', <span class="success">Updated database</span>';
                    $updated_count++;
                } else {
                    $status .= ', <span class="error">Failed to update database</span>';
                }
            } else {
                // Generate a new placeholder image
                $new_image_url = $upload_dir . 'product_' . $product['id'] . '_placeholder.jpg';
                $placeholder_url = 'https://via.placeholder.com/300x200?text=' . urlencode($product['name']);
                $placeholder_content = file_get_contents($placeholder_url);
                
                if ($placeholder_content && file_put_contents($new_image_url, $placeholder_content)) {
                    $status = '<span class="success">Created placeholder</span>';
                    
                    // Update database
                    $update_query = "UPDATE products SET image_url = :image_url WHERE id = :id";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':image_url', $new_image_url);
                    $update_stmt->bindParam(':id', $product['id']);
                    
                    if ($update_stmt->execute()) {
                        $status .= ', <span class="success">Updated database</span>';
                        $updated_count++;
                    } else {
                        $status .= ', <span class="error">Failed to update database</span>';
                    }
                } else {
                    $status = '<span class="error">Failed to create placeholder</span>';
                }
            }
            
            echo '<td>' . $new_image_url . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        // End table
        echo '</table>';
        
        echo '<p>Updated ' . $updated_count . ' products.</p>';
        
    } catch (PDOException $e) {
        echo '<p class="error">Database error: ' . $e->getMessage() . '</p>';
    } catch (Exception $e) {
        echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
    }
} else {
    // Display form
    echo '<form method="POST">
        <p>This tool will fix product image paths in the database. It will:</p>
        <ol>
            <li>Create a uploads/products/ directory if it doesn\'t exist</li>
            <li>For each product with an image URL, copy the image to the uploads/products/ directory</li>
            <li>For products without an image, create a placeholder image</li>
            <li>Update the database with the new image paths</li>
        </ol>
        <button type="submit" name="fix_images">Fix Product Images</button>
    </form>';
}

echo '<p><a href="check_product_images.php">Check Product Images</a></p>';
echo '</body></html>';
?>
