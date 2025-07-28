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
    
    echo "<h1>Fix Payment Method Column</h1>";
    
    // Check current payment_method column definition
    $columns_query = "SHOW COLUMNS FROM orders LIKE 'payment_method'";
    $columns_stmt = $db->prepare($columns_query);
    $columns_stmt->execute();
    $column = $columns_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Current Payment Method Column:</h2>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
    
    // Modify payment_method column to accept more values
    echo "<h2>Modifying Payment Method Column</h2>";
    
    try {
        $alter_query = "ALTER TABLE orders MODIFY COLUMN payment_method VARCHAR(50) NOT NULL";
        $db->exec($alter_query);
        echo "<p style='color:green;'>Payment method column modified successfully!</p>";
        
        // Check updated column definition
        $columns_query = "SHOW COLUMNS FROM orders LIKE 'payment_method'";
        $columns_stmt = $db->prepare($columns_query);
        $columns_stmt->execute();
        $column = $columns_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Updated Payment Method Column:</h2>";
        echo "<pre>";
        print_r($column);
        echo "</pre>";
        
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error modifying payment method column: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
