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
    
    echo "<h1>Check Delivery Slot Column</h1>";
    
    // Check current delivery_slot column definition
    $columns_query = "SHOW COLUMNS FROM orders LIKE 'delivery_slot'";
    $columns_stmt = $db->prepare($columns_query);
    $columns_stmt->execute();
    
    if ($columns_stmt->rowCount() === 0) {
        echo "<p style='color:red;'>Delivery slot column does not exist!</p>";
        
        // Add delivery_slot column
        echo "<h2>Adding Delivery Slot Column</h2>";
        
        try {
            $alter_query = "ALTER TABLE orders ADD COLUMN delivery_slot VARCHAR(50) DEFAULT NULL AFTER shipping_postal_code";
            $db->exec($alter_query);
            echo "<p style='color:green;'>Delivery slot column added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error adding delivery slot column: " . $e->getMessage() . "</p>";
        }
    } else {
        $column = $columns_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Current Delivery Slot Column:</h2>";
        echo "<pre>";
        print_r($column);
        echo "</pre>";
        
        // Check if column needs to be modified
        if ($column['Type'] !== 'varchar(50)') {
            echo "<h2>Modifying Delivery Slot Column</h2>";
            
            try {
                $alter_query = "ALTER TABLE orders MODIFY COLUMN delivery_slot VARCHAR(50) DEFAULT NULL";
                $db->exec($alter_query);
                echo "<p style='color:green;'>Delivery slot column modified successfully!</p>";
                
                // Check updated column definition
                $columns_query = "SHOW COLUMNS FROM orders LIKE 'delivery_slot'";
                $columns_stmt = $db->prepare($columns_query);
                $columns_stmt->execute();
                $column = $columns_stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<h2>Updated Delivery Slot Column:</h2>";
                echo "<pre>";
                print_r($column);
                echo "</pre>";
            } catch (PDOException $e) {
                echo "<p style='color:red;'>Error modifying delivery slot column: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color:green;'>Delivery slot column is already correctly defined.</p>";
        }
    }
    
    // Check for tracking_number column
    $columns_query = "SHOW COLUMNS FROM orders LIKE 'tracking_number'";
    $columns_stmt = $db->prepare($columns_query);
    $columns_stmt->execute();
    
    if ($columns_stmt->rowCount() === 0) {
        echo "<p style='color:red;'>Tracking number column does not exist!</p>";
        
        // Add tracking_number column
        echo "<h2>Adding Tracking Number Column</h2>";
        
        try {
            $alter_query = "ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(100) DEFAULT NULL AFTER delivery_slot";
            $db->exec($alter_query);
            echo "<p style='color:green;'>Tracking number column added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error adding tracking number column: " . $e->getMessage() . "</p>";
        }
    } else {
        $column = $columns_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Current Tracking Number Column:</h2>";
        echo "<pre>";
        print_r($column);
        echo "</pre>";
        
        echo "<p style='color:green;'>Tracking number column exists.</p>";
    }
    
    // Check for notes column
    $columns_query = "SHOW COLUMNS FROM orders LIKE 'notes'";
    $columns_stmt = $db->prepare($columns_query);
    $columns_stmt->execute();
    
    if ($columns_stmt->rowCount() === 0) {
        echo "<p style='color:red;'>Notes column does not exist!</p>";
        
        // Add notes column
        echo "<h2>Adding Notes Column</h2>";
        
        try {
            $alter_query = "ALTER TABLE orders ADD COLUMN notes TEXT DEFAULT NULL AFTER tracking_number";
            $db->exec($alter_query);
            echo "<p style='color:green;'>Notes column added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error adding notes column: " . $e->getMessage() . "</p>";
        }
    } else {
        $column = $columns_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Current Notes Column:</h2>";
        echo "<pre>";
        print_r($column);
        echo "</pre>";
        
        echo "<p style='color:green;'>Notes column exists.</p>";
    }
    
    // Show final table structure
    $table_structure_query = "SHOW CREATE TABLE orders";
    $table_structure_stmt = $db->prepare($table_structure_query);
    $table_structure_stmt->execute();
    $table_structure = $table_structure_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Final Orders Table Structure:</h2>";
    echo "<pre>" . $table_structure['Create Table'] . "</pre>";
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
