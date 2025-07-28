<?php
// Test script for photo upload functionality
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Photo Upload System Test</h2>";

// Test 1: Check if product_images table exists
$table_check = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color:green'>✓ product_images table exists</p>";
} else {
    echo "<p style='color:red'>✗ product_images table missing</p>";
}

// Test 2: Check uploads directory
$upload_dir = "uploads/products/";
if (is_dir($upload_dir)) {
    echo "<p style='color:green'>✓ Upload directory exists: $upload_dir</p>";
} else {
    echo "<p style='color:orange'>⚠ Upload directory missing: $upload_dir</p>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color:green'>✓ Created upload directory</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to create upload directory</p>";
    }
}

// Test 3: Check if there are any products
$products_query = $conn->query("SELECT id, name FROM products LIMIT 5");
if ($products_query && $products_query->num_rows > 0) {
    echo "<p style='color:green'>✓ Found " . $products_query->num_rows . " products</p>";
    echo "<h3>Available Products:</h3>";
    echo "<ul>";
    while ($product = $products_query->fetch_assoc()) {
        echo "<li>ID: " . $product['id'] . " - " . $product['name'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>✗ No products found</p>";
}

// Test 4: Check product_images data
$images_query = $conn->query("SELECT COUNT(*) as count FROM product_images");
if ($images_query) {
    $image_count = $images_query->fetch_assoc()['count'];
    echo "<p>Total product images in database: $image_count</p>";
}

// Test 5: Check sample product images
$sample_images = $conn->query("SELECT pi.id, pi.image_path, pi.is_primary, p.name as product_name 
                               FROM product_images pi 
                               JOIN products p ON pi.product_id = p.id 
                               LIMIT 3");
if ($sample_images && $sample_images->num_rows > 0) {
    echo "<h3>Sample Product Images:</h3>";
    echo "<div class='row'>";
    while ($image = $sample_images->fetch_assoc()) {
        echo "<div class='col-md-4 mb-3'>";
        echo "<div class='card'>";
        if (file_exists($image['image_path'])) {
            echo "<img src='" . $image['image_path'] . "' class='card-img-top' style='height: 200px; object-fit: cover;' alt='Product Image'>";
        } else {
            echo "<div class='card-img-top bg-light d-flex align-items-center justify-content-center' style='height: 200px;'>";
            echo "<span class='text-muted'>File not found</span>";
            echo "</div>";
        }
        echo "<div class='card-body'>";
        echo "<h6 class='card-title'>" . $image['product_name'] . "</h6>";
        echo "<p class='card-text'>";
        echo "Primary: " . ($image['is_primary'] ? 'Yes' : 'No') . "<br>";
        echo "Path: " . $image['image_path'];
        echo "</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p style='color:orange'>⚠ No product images found in database</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Photo Upload Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Photo Upload System Test</h1>
        <p>This page tests the photo upload functionality and database structure.</p>
        
        <div class="mt-3">
            <a href="admin_product_view.php?id=1" class="btn btn-primary">Test Admin Product View</a>
            <a href="test_db_fix.php" class="btn btn-secondary">Back to Database Test</a>
        </div>
    </div>
</body>
</html> 