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
    <title>Upload Test Product Image</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #1e8449; }
        form { margin: 20px 0; }
        label { display: block; margin-bottom: 5px; }
        input, select { margin-bottom: 15px; padding: 8px; width: 300px; }
        button { background-color: #1e8449; color: white; border: none; padding: 10px 15px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Upload Test Product Image</h1>';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if file was uploaded
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            // Get product ID
            $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            
            if ($product_id <= 0) {
                throw new Exception("Invalid product ID");
            }
            
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Get file info
            $file_name = $_FILES['product_image']['name'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Generate unique filename
            $new_file_name = 'product_' . $product_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Connect to database
                $host = "localhost";
                $db_name = "kisan_kart";
                $username = "root";
                $password = "";
                
                $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Update product image URL
                $query = "UPDATE products SET image_url = :image_url WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':image_url', $upload_path);
                $stmt->bindParam(':id', $product_id);
                $stmt->execute();
                
                echo '<p class="success">Image uploaded successfully and product updated!</p>';
                echo '<p>Image path: ' . $upload_path . '</p>';
                echo '<p><img src="' . $upload_path . '" style="max-width: 300px;"></p>';
            } else {
                throw new Exception("Failed to move uploaded file");
            }
        } else {
            throw new Exception("No file uploaded or upload error");
        }
    } catch (Exception $e) {
        echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
    }
}

// Get products from database
try {
    $host = "localhost";
    $db_name = "kisan_kart";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $query = "SELECT id, name FROM products ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Display form
    echo '<form method="POST" enctype="multipart/form-data">
        <div>
            <label for="product_id">Select Product:</label>
            <select name="product_id" id="product_id" required>';
    
    foreach ($products as $product) {
        echo '<option value="' . $product['id'] . '">' . $product['id'] . ' - ' . $product['name'] . '</option>';
    }
    
    echo '</select>
        </div>
        <div>
            <label for="product_image">Product Image:</label>
            <input type="file" name="product_image" id="product_image" accept="image/*" required>
        </div>
        <button type="submit">Upload Image</button>
    </form>';
    
} catch (Exception $e) {
    echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
}

echo '<p><a href="check_product_images.php">Check Product Images</a></p>';
echo '</body></html>';
?>
