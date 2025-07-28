<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

// Define product-specific image mappings
$product_image_mappings = [
    // Fruits
    'Organic Bananas' => 'banana.jpg',
    'Fresh Oranges' => 'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab12?q=80&w=1470&auto=format&fit=crop',
    'Sweet Strawberries' => 'strawberry.jpg',
    
    // Dairy
    'Fresh Milk' => 'milk.jpg',
    'Organic Cheese' => 'cheese.jpg',
    'Fresh Yogurt' => 'https://images.unsplash.com/photo-1571212515416-fef01fc43637?q=80&w=1470&auto=format&fit=crop',
    'Organic Butter' => 'butter.jpg',
    
    // Grains
    'Organic Rice' => 'https://images.unsplash.com/photo-1586201375761-83865001e8ac?q=80&w=1470&auto=format&fit=crop',
    'Whole Wheat' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?q=80&w=1470&auto=format&fit=crop',
    'Organic Quinoa' => 'https://images.unsplash.com/photo-1586295166487-b265f7e83a7f?q=80&w=1480&auto=format&fit=crop'
];

// HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Mismatched Product Images</title>
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
    <h1>Fix Mismatched Product Images</h1>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    echo "<table>
        <tr>
            <th>Product</th>
            <th>Status</th>
            <th>Old Image</th>
            <th>New Image</th>
        </tr>";
    
    // Process each product image mapping
    foreach ($product_image_mappings as $product_name => $image_source) {
        echo "<tr>";
        echo "<td>{$product_name}</td>";
        
        // Find the product by exact name
        $query = "SELECT id, name, image_url FROM products WHERE name = :name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $product_name);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $old_image_url = $product['image_url'];
            $new_image_url = '';
            
            // Check if the image source is a URL or a local file
            if (filter_var($image_source, FILTER_VALIDATE_URL)) {
                // It's a URL, download the image
                $filename = strtolower(str_replace(' ', '_', $product_name)) . '.jpg';
                $save_path = $upload_dir . $filename;
                
                // Get image content
                $image_content = @file_get_contents($image_source);
                
                if ($image_content !== false) {
                    // Save image to file
                    if (file_put_contents($save_path, $image_content)) {
                        $new_image_url = 'uploads/products/' . $filename;
                    } else {
                        echo "<td><span class='error'>Failed to save image</span></td>";
                        echo "<td colspan='2'>Error saving to {$save_path}</td>";
                        echo "</tr>";
                        continue;
                    }
                } else {
                    echo "<td><span class='error'>Failed to download image</span></td>";
                    echo "<td colspan='2'>Error downloading from {$image_source}</td>";
                    echo "</tr>";
                    continue;
                }
            } else {
                // It's a local file, just use it
                $new_image_url = 'uploads/products/' . $image_source;
                
                // Check if the file exists
                if (!file_exists($upload_dir . $image_source)) {
                    echo "<td><span class='error'>Image file not found</span></td>";
                    echo "<td colspan='2'>File not found: {$upload_dir}{$image_source}</td>";
                    echo "</tr>";
                    continue;
                }
            }
            
            // Update the product image URL
            $update_query = "UPDATE products SET image_url = :image_url WHERE id = :id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':image_url', $new_image_url);
            $update_stmt->bindParam(':id', $product['id']);
            
            if ($update_stmt->execute()) {
                echo "<td><span class='success'>Updated successfully</span></td>";
                
                // Show old and new images
                echo "<td>";
                if (!empty($old_image_url)) {
                    echo "<img src='{$old_image_url}' class='image-preview' alt='Old image'>";
                } else {
                    echo "No image";
                }
                echo "</td>";
                echo "<td><img src='{$new_image_url}' class='image-preview' alt='New image'></td>";
            } else {
                echo "<td><span class='error'>Failed to update database</span></td>";
                echo "<td colspan='2'>Error updating product ID {$product['id']}</td>";
            }
        } else {
            echo "<td><span class='error'>Product not found</span></td>";
            echo "<td colspan='2'>No product with name '{$product_name}'</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
