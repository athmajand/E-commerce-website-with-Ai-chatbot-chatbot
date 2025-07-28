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
    <title>Product Images Check</title>
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
    </style>
</head>
<body>
    <h1>Product Images Check</h1>';

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
    
    // Get all products
    $query = "SELECT * FROM products";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<p>Found ' . count($products) . ' products in the database.</p>';
    
    // Start table
    echo '<table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Image URL</th>
            <th>Image Path</th>
            <th>Exists?</th>
            <th>Preview</th>
        </tr>';
    
    // Common image directories to check
    $image_directories = [
        '',
        'uploads/products/',
        'images/products/',
        'assets/images/products/',
        'uploads/',
        'images/',
        'assets/images/'
    ];
    
    // Check each product
    foreach ($products as $product) {
        echo '<tr>';
        echo '<td>' . $product['id'] . '</td>';
        echo '<td>' . $product['name'] . '</td>';
        
        // Image URL from database
        $image_url = isset($product['image_url']) ? $product['image_url'] : '';
        if (empty($image_url) && isset($product['image'])) {
            $image_url = $product['image'];
        }
        
        echo '<td>' . $image_url . '</td>';
        
        // Check if image exists
        $image_exists = false;
        $image_path = '';
        $actual_url = '';
        
        if (!empty($image_url)) {
            // Check in different directories
            foreach ($image_directories as $dir) {
                $test_path = __DIR__ . '/' . $dir . basename($image_url);
                if (file_exists($test_path)) {
                    $image_exists = true;
                    $image_path = $test_path;
                    $actual_url = $dir . basename($image_url);
                    break;
                }
            }
        }
        
        echo '<td>' . $image_path . '</td>';
        echo '<td class="' . ($image_exists ? 'success' : 'error') . '">' . ($image_exists ? 'Yes' : 'No') . '</td>';
        
        // Preview
        echo '<td>';
        if ($image_exists) {
            echo '<img src="' . $actual_url . '" class="image-preview" alt="' . $product['name'] . '">';
        } else if (!empty($image_url)) {
            echo '<img src="' . $image_url . '" class="image-preview" alt="' . $product['name'] . '" onerror="this.src=\'https://via.placeholder.com/100x100?text=No+Image\'">';
        } else {
            echo 'No image';
        }
        echo '</td>';
        
        echo '</tr>';
    }
    
    // End table
    echo '</table>';
    
} catch (PDOException $e) {
    echo '<p class="error">Database error: ' . $e->getMessage() . '</p>';
} catch (Exception $e) {
    echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
}

echo '</body></html>';
?>
