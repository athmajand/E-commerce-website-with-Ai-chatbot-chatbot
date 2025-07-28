<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

// Define expected image mappings for common product types
$expected_image_mappings = [
    'tomato' => ['tomato', 'tomatoes'],
    'potato' => ['potato', 'potatoes'],
    'onion' => ['onion', 'onions'],
    'carrot' => ['carrot', 'carrots'],
    'apple' => ['apple', 'apples'],
    'banana' => ['banana', 'bananas'],
    'orange' => ['orange', 'oranges'],
    'grapes' => ['grape', 'grapes'],
    'strawberry' => ['strawberry', 'strawberries'],
    'milk' => ['milk', 'dairy milk'],
    'cheese' => ['cheese', 'paneer'],
    'butter' => ['butter'],
    'rice' => ['rice', 'basmati'],
    'wheat' => ['wheat', 'atta'],
    'quinoa' => ['quinoa'],
    'spinach' => ['spinach', 'palak'],
    'cucumber' => ['cucumber'],
    'broccoli' => ['broccoli']
];

// HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Check All Product Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .image-preview { max-width: 100px; max-height: 100px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .filter-controls { margin-bottom: 20px; }
        .filter-controls label { margin-right: 10px; }
    </style>
</head>
<body>
    <h1>Check All Product Images</h1>
    
    <div class='filter-controls'>
        <label>
            <input type='checkbox' id='show-all' checked> Show All Products
        </label>
        <label>
            <input type='checkbox' id='show-mismatched'> Show Only Mismatched Images
        </label>
    </div>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all products
    $query = "SELECT id, name, image_url, category_id FROM products ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories
    $cat_query = "SELECT id, name FROM categories";
    $cat_stmt = $conn->prepare($cat_query);
    $cat_stmt->execute();
    $categories = [];
    while ($row = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['id']] = $row['name'];
    }
    
    echo "<table id='products-table'>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Image URL</th>
            <th>Image Match</th>
            <th>Preview</th>
        </tr>";
    
    foreach ($products as $product) {
        $product_name = strtolower($product['name']);
        $image_url = $product['image_url'];
        $category_name = isset($categories[$product['category_id']]) ? $categories[$product['category_id']] : 'Unknown';
        
        // Check if image exists
        $image_exists = false;
        if (!empty($image_url)) {
            $full_path = __DIR__ . '/' . $image_url;
            $image_exists = file_exists($full_path);
        }
        
        // Check if image matches product name
        $image_matches = false;
        $expected_image = '';
        
        if (!empty($image_url)) {
            $image_filename = strtolower(basename($image_url));
            
            foreach ($expected_image_mappings as $image_type => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($product_name, $keyword) !== false) {
                        $expected_image = $image_type;
                        if (strpos($image_filename, $image_type) !== false) {
                            $image_matches = true;
                        }
                        break 2;
                    }
                }
            }
        }
        
        // Determine match status
        $match_status = '';
        $match_class = '';
        
        if (!$image_exists) {
            $match_status = 'Image missing';
            $match_class = 'error';
        } elseif (empty($expected_image)) {
            $match_status = 'Unknown product type';
            $match_class = 'warning';
        } elseif (!$image_matches) {
            $match_status = "Expected {$expected_image}.jpg";
            $match_class = 'error';
        } else {
            $match_status = 'Matches';
            $match_class = 'success';
        }
        
        // Add data-mismatch attribute for filtering
        $mismatch_attr = ($match_class === 'error') ? 'data-mismatch="true"' : '';
        
        echo "<tr {$mismatch_attr}>
            <td>{$product['id']}</td>
            <td>{$product['name']}</td>
            <td>{$category_name}</td>
            <td>{$image_url}</td>
            <td class='{$match_class}'>{$match_status}</td>
            <td>";
        
        if ($image_exists) {
            echo "<img src='{$image_url}' class='image-preview' alt='{$product['name']}'>";
        } else {
            echo "No image";
        }
        
        echo "</td></tr>";
    }
    
    echo "</table>";
    
    // Add JavaScript for filtering
    echo "<script>
        document.getElementById('show-all').addEventListener('change', filterTable);
        document.getElementById('show-mismatched').addEventListener('change', filterTable);
        
        function filterTable() {
            const showAll = document.getElementById('show-all').checked;
            const showMismatched = document.getElementById('show-mismatched').checked;
            const rows = document.querySelectorAll('#products-table tr');
            
            // Skip header row
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const isMismatched = row.hasAttribute('data-mismatch');
                
                if (showAll) {
                    row.style.display = '';
                } else if (showMismatched && isMismatched) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
    </script>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
