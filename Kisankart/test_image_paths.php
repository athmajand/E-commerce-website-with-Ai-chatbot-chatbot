<?php
// Test script to verify image paths

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the image to test
$test_images = [
    'carrot_hd.jpg',
    'tomato_hd.jpg',
    'apple_hd.jpg',
    'banana_hd.jpg'
];

// HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Image Paths</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .image-test { margin-bottom: 20px; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        img { max-width: 200px; max-height: 200px; display: block; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Test Image Paths</h1>
    
    <h2>Direct Path Test</h2>";

// Test direct path
foreach ($test_images as $image) {
    $direct_path = "uploads/products/{$image}";
    $exists = file_exists($direct_path);
    
    echo "<div class='image-test'>";
    echo "<h3>Image: {$image}</h3>";
    echo "<p>Path: {$direct_path}</p>";
    echo "<p>Exists: " . ($exists ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    
    if ($exists) {
        echo "<img src='{$direct_path}' alt='{$image}'>";
    }
    echo "</div>";
}

// Test relative path from frontend folder
echo "<h2>Frontend Relative Path Test</h2>";

foreach ($test_images as $image) {
    $frontend_path = "../uploads/products/{$image}";
    $frontend_real_path = realpath("frontend/{$frontend_path}");
    $exists = $frontend_real_path !== false;
    
    echo "<div class='image-test'>";
    echo "<h3>Image: {$image}</h3>";
    echo "<p>Path from frontend: {$frontend_path}</p>";
    echo "<p>Real path: " . ($frontend_real_path ?: "Not found") . "</p>";
    echo "<p>Exists: " . ($exists ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    
    echo "<p>HTML img tag test:</p>";
    echo "<img src='uploads/products/{$image}' alt='{$image}' onerror=\"this.src='https://via.placeholder.com/200x200?text=Not+Found'; this.style.border='2px solid red';\">";
    
    echo "<p>HTML img tag test with ../:</p>";
    echo "<img src='frontend/{$frontend_path}' alt='{$image}' onerror=\"this.src='https://via.placeholder.com/200x200?text=Not+Found'; this.style.border='2px solid red';\">";
    echo "</div>";
}

// Test file permissions
echo "<h2>File Permissions Test</h2>";

foreach ($test_images as $image) {
    $path = "uploads/products/{$image}";
    
    if (file_exists($path)) {
        $perms = fileperms($path);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        $perms_human = '';
        
        // Owner
        $perms_human .= (($perms & 0x0100) ? 'r' : '-');
        $perms_human .= (($perms & 0x0080) ? 'w' : '-');
        $perms_human .= (($perms & 0x0040) ? 'x' : '-');
        
        // Group
        $perms_human .= (($perms & 0x0020) ? 'r' : '-');
        $perms_human .= (($perms & 0x0010) ? 'w' : '-');
        $perms_human .= (($perms & 0x0008) ? 'x' : '-');
        
        // World
        $perms_human .= (($perms & 0x0004) ? 'r' : '-');
        $perms_human .= (($perms & 0x0002) ? 'w' : '-');
        $perms_human .= (($perms & 0x0001) ? 'x' : '-');
        
        echo "<div class='image-test'>";
        echo "<h3>Image: {$image}</h3>";
        echo "<p>Path: {$path}</p>";
        echo "<p>Permissions (octal): {$perms_octal}</p>";
        echo "<p>Permissions (human): {$perms_human}</p>";
        echo "<p>File size: " . filesize($path) . " bytes</p>";
        echo "<p>Last modified: " . date("F d Y H:i:s", filemtime($path)) . "</p>";
        echo "</div>";
    } else {
        echo "<div class='image-test'>";
        echo "<h3>Image: {$image}</h3>";
        echo "<p>Path: {$path}</p>";
        echo "<p class='error'>File does not exist</p>";
        echo "</div>";
    }
}

// Test image URLs in database
echo "<h2>Database Image URLs Test</h2>";

try {
    // Connect to database
    $host = "localhost";
    $db_name = "kisan_kart";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get products with image URLs
    $query = "SELECT id, name, image_url FROM products WHERE image_url LIKE '%carrot%' OR image_url LIKE '%tomato%' OR image_url LIKE '%apple%' OR image_url LIKE '%banana%' LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "<div class='image-test'>";
        echo "<h3>Product: {$product['name']} (ID: {$product['id']})</h3>";
        echo "<p>Image URL in DB: " . ($product['image_url'] ?: "None") . "</p>";
        
        if (!empty($product['image_url'])) {
            $filename = basename($product['image_url']);
            $direct_path = "uploads/products/{$filename}";
            $frontend_path = "../uploads/products/{$filename}";
            
            $exists = file_exists($direct_path);
            
            echo "<p>Direct path: {$direct_path}</p>";
            echo "<p>Frontend path: {$frontend_path}</p>";
            echo "<p>File exists: " . ($exists ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
            
            echo "<p>Direct path test:</p>";
            echo "<img src='{$direct_path}' alt='{$product['name']}' onerror=\"this.src='https://via.placeholder.com/200x200?text=Not+Found'; this.style.border='2px solid red';\">";
            
            echo "<p>Frontend path test:</p>";
            echo "<img src='frontend/{$frontend_path}' alt='{$product['name']}' onerror=\"this.src='https://via.placeholder.com/200x200?text=Not+Found'; this.style.border='2px solid red';\">";
        }
        
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>Database error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
