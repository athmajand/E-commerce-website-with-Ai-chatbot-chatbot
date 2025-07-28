<?php
// Test script to directly test admin product view functionality
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

// Create mysqli connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Admin Product View Test</h2>";

// Test the exact query from admin_product_view.php
$product_id = 1; // Test with product ID 1

// Check seller_registrations table structure
$seller_columns_query = "SHOW COLUMNS FROM seller_registrations";
$seller_columns_result = $conn->query($seller_columns_query);

$seller_columns = [];
if ($seller_columns_result) {
    while ($column = $seller_columns_result->fetch_assoc()) {
        $seller_columns[] = $column['Field'];
    }
}

// Build the seller name part of the query based on available columns
$seller_name_part = "CONCAT('Seller #', p.seller_id)";
if (in_array('name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.name, " . $seller_name_part . ")";
}
if (in_array('full_name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.full_name, " . $seller_name_part . ")";
}
if (in_array('business_name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.business_name, " . $seller_name_part . ")";
}
if (in_array('company_name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.company_name, " . $seller_name_part . ")";
}
if (in_array('first_name', $seller_columns) && in_array('last_name', $seller_columns)) {
    $seller_name_part = "COALESCE(CONCAT(sr.first_name, ' ', sr.last_name), " . $seller_name_part . ")";
}

// Build the seller email part of the query
$seller_email_part = "NULL";
if (in_array('email', $seller_columns)) {
    $seller_email_part = "sr.email";
}

// Build the seller phone part of the query
$seller_phone_part = "NULL";
if (in_array('phone', $seller_columns)) {
    $seller_phone_part = "sr.phone";
}

// Get product details using the exact query from admin_product_view.php
$product_query = "SELECT p.*, c.name as category_name,
                 " . $seller_name_part . " as seller_name,
                 " . $seller_email_part . " as seller_email,
                 " . $seller_phone_part . " as seller_phone
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN seller_registrations sr ON p.seller_id = sr.id
                 WHERE p.id = ?";

$product_stmt = $conn->prepare($product_query);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows == 0) {
    echo "<p style='color:orange'>⚠ No product found with ID " . $product_id . "</p>";
    echo "<p>Available product IDs:</p>";
    
    // Show available product IDs
    $available_products = $conn->query("SELECT id, name FROM products LIMIT 5");
    if ($available_products) {
        echo "<ul>";
        while ($product = $available_products->fetch_assoc()) {
            echo "<li>ID: " . $product['id'] . " - " . $product['name'] . "</li>";
        }
        echo "</ul>";
    }
} else {
    $product = $product_result->fetch_assoc();
    
    echo "<p style='color:green'>✓ Product query executed successfully!</p>";
    echo "<h3>Product Details:</h3>";
    echo "<table class='table table-striped'>";
    echo "<tr><td><strong>ID:</strong></td><td>" . $product['id'] . "</td></tr>";
    echo "<tr><td><strong>Name:</strong></td><td>" . $product['name'] . "</td></tr>";
    echo "<tr><td><strong>Category:</strong></td><td>" . ($product['category_name'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>Price:</strong></td><td>₹" . number_format($product['price'], 2) . "</td></tr>";
    echo "<tr><td><strong>Stock:</strong></td><td>" . $product['stock'] . "</td></tr>";
    echo "<tr><td><strong>Seller ID:</strong></td><td>" . $product['seller_id'] . "</td></tr>";
    echo "<tr><td><strong>Seller Name:</strong></td><td>" . ($product['seller_name'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>Seller Email:</strong></td><td>" . ($product['seller_email'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td><strong>Seller Phone:</strong></td><td>" . ($product['seller_phone'] ?? 'N/A') . "</td></tr>";
    echo "</table>";
    
    echo "<p style='color:green'>✓ All seller information retrieved correctly!</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Product View Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Admin Product View Functionality Test</h1>
        <p>This page tests the exact query used in admin_product_view.php to ensure the farmer_id to seller_id fix is working.</p>
        
        <div class="mt-3">
            <a href="test_db_fix.php" class="btn btn-secondary">Back to Database Test</a>
            <a href="admin_product_view.php?id=1" class="btn btn-primary">Test Actual Admin Page</a>
        </div>
    </div>
</body>
</html> 