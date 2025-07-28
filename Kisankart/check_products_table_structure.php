<?php
// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Products Table Structure</h2>";
    
    // Check if products table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        // Get table structure
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
        
        // Check for seller_id or farmer_id column
        $seller_id_exists = false;
        $farmer_id_exists = false;
        
        foreach ($structure as $column) {
            if ($column['Field'] == 'seller_id') {
                $seller_id_exists = true;
            }
            if ($column['Field'] == 'farmer_id') {
                $farmer_id_exists = true;
            }
        }
        
        echo "<br>seller_id column exists: " . ($seller_id_exists ? "Yes" : "No");
        echo "<br>farmer_id column exists: " . ($farmer_id_exists ? "Yes" : "No");
        
        // Check seller_registrations table structure
        echo "<h2>Seller Registrations Table Structure</h2>";
        $sr_structure_query = $pdo->query("DESCRIBE seller_registrations");
        $sr_structure = $sr_structure_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($sr_structure as $column) {
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
        
    } else {
        echo "<p>Products table does not exist.</p>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
