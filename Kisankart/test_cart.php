<?php
// Simple test script to check if cart.php is working
echo "<h1>Cart Test</h1>";

// Test 1: Check if cart.php file exists
$cartFile = 'frontend/cart.php';
if (file_exists($cartFile)) {
    echo "<p style='color: green;'>✓ Cart.php file exists</p>";
} else {
    echo "<p style='color: red;'>✗ Cart.php file not found</p>";
}

// Test 2: Check if API cart.php exists
$apiCartFile = 'api/cart.php';
if (file_exists($apiCartFile)) {
    echo "<p style='color: green;'>✓ API cart.php file exists</p>";
} else {
    echo "<p style='color: red;'>✗ API cart.php file not found</p>";
}

// Test 3: Check if Auth class exists
$authFile = 'api/config/auth.php';
if (file_exists($authFile)) {
    echo "<p style='color: green;'>✓ Auth.php file exists</p>";
} else {
    echo "<p style='color: red;'>✗ Auth.php file not found</p>";
}

// Test 4: Check database connection
try {
    include_once 'api/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 5: Check if cart table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'cart'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Cart table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Cart table not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking cart table: " . $e->getMessage() . "</p>";
}

// Test 6: Check if customer_registrations table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'customer_registrations'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Customer registrations table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Customer registrations table not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking customer_registrations table: " . $e->getMessage() . "</p>";
}

// Test 7: Check if products table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Products table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Products table not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking products table: " . $e->getMessage() . "</p>";
}

// Test 8: Check if seller_registrations table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'seller_registrations'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Seller registrations table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Seller registrations table not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking seller_registrations table: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Access URLs:</h2>";
echo "<p><a href='http://localhost:8080/Kisankart/frontend/cart.php' target='_blank'>Frontend Cart Page</a></p>";
echo "<p><a href='http://localhost:8080/Kisankart/api/cart.php' target='_blank'>API Cart Endpoint</a></p>";
echo "<p><a href='http://localhost:8080/Kisankart/frontend/cart.html' target='_blank'>HTML Cart Page</a></p>";

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Make sure XAMPP Apache and MySQL are running</li>";
echo "<li>Access the frontend cart page to see if it loads correctly</li>";
echo "<li>If you're not logged in, you should see a login required message</li>";
echo "<li>If you are logged in, the cart should load your items</li>";
echo "</ol>";
?> 