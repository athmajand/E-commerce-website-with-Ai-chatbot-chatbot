<?php
// Add image_settings column to products table
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Adding image_settings column to products table</h2>";
    
    // Check if column already exists
    $check_column = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_settings'");
    $column_exists = $check_column->rowCount() > 0;
    
    if ($column_exists) {
        echo "<p style='color:orange'>The image_settings column already exists.</p>";
    } else {
        // Add the column
        $add_column_sql = "ALTER TABLE products ADD COLUMN image_settings TEXT NULL AFTER additional_images";
        $pdo->exec($add_column_sql);
        echo "<p style='color:green'>Successfully added image_settings column to products table!</p>";
    }
    
    // Verify the column was added
    $verify_query = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_settings'");
    if ($verify_query->rowCount() > 0) {
        echo "<p style='color:green'>Verified that image_settings column exists.</p>";
        
        // Show the column details
        $column_info = $verify_query->fetch(PDO::FETCH_ASSOC);
        echo "<h3>Column Details:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        echo "<tr>";
        echo "<td>" . $column_info['Field'] . "</td>";
        echo "<td>" . $column_info['Type'] . "</td>";
        echo "<td>" . $column_info['Null'] . "</td>";
        echo "<td>" . $column_info['Key'] . "</td>";
        echo "<td>" . $column_info['Default'] . "</td>";
        echo "<td>" . $column_info['Extra'] . "</td>";
        echo "</tr>";
        echo "</table>";
    }
    
    // Show current table structure
    echo "<h3>Current Products Table Structure:</h3>";
    $structure_query = $pdo->query("DESCRIBE products");
    $structure = $structure_query->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
