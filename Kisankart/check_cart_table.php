<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    // Connect to the database
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the cart table structure
    $stmt = $db->query("SHOW CREATE TABLE cart");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Cart Table Structure</h2>";
    echo "<pre>" . $row['Create Table'] . "</pre>";
    
    // Get the products table structure
    $stmt = $db->query("SHOW CREATE TABLE products");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Products Table Structure</h2>";
    echo "<pre>" . $row['Create Table'] . "</pre>";
    
    // Check for any products in the cart
    $stmt = $db->query("SELECT * FROM cart");
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Cart Items</h2>";
    if (count($cartItems) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Customer ID</th><th>Product ID</th><th>Quantity</th><th>Created At</th><th>Updated At</th></tr>";
        foreach ($cartItems as $item) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . $item['customer_id'] . "</td>";
            echo "<td>" . $item['product_id'] . "</td>";
            echo "<td>" . $item['quantity'] . "</td>";
            echo "<td>" . $item['created_at'] . "</td>";
            echo "<td>" . $item['updated_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No items in the cart.</p>";
    }
    
    // Check for products in the products table
    $stmt = $db->query("SELECT id, name FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Available Products</h2>";
    if (count($products) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . $product['name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No products available.</p>";
    }
    
} catch(PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
