<?php
// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Updating Products Table</h2>";
    
    // Check if products table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        // Get table structure
        $structure_query = $pdo->query("DESCRIBE products");
        $structure = $structure_query->fetchAll(PDO::FETCH_ASSOC);
        
        // Check for farmer_id column
        $farmer_id_exists = false;
        foreach ($structure as $column) {
            if ($column['Field'] == 'farmer_id') {
                $farmer_id_exists = true;
                break;
            }
        }
        
        // Check for foreign key constraints on farmer_id
        $fk_check = $pdo->query("
            SELECT * FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'kisan_kart'
            AND TABLE_NAME = 'products'
            AND COLUMN_NAME = 'farmer_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $has_fk = $fk_check->rowCount() > 0;
        
        echo "<p>farmer_id column exists: " . ($farmer_id_exists ? "Yes" : "No") . "</p>";
        echo "<p>farmer_id has foreign key constraint: " . ($has_fk ? "Yes" : "No") . "</p>";
        
        // If foreign key exists, drop it
        if ($has_fk) {
            $fk_data = $fk_check->fetch(PDO::FETCH_ASSOC);
            $constraint_name = $fk_data['CONSTRAINT_NAME'];
            
            $drop_fk_sql = "ALTER TABLE products DROP FOREIGN KEY $constraint_name";
            $pdo->exec($drop_fk_sql);
            echo "<p style='color:green'>Dropped foreign key constraint: $constraint_name</p>";
        }
        
        // If farmer_id doesn't exist, add it
        if (!$farmer_id_exists) {
            $add_column_sql = "ALTER TABLE products ADD COLUMN farmer_id INT AFTER id";
            $pdo->exec($add_column_sql);
            echo "<p style='color:green'>Added farmer_id column to products table</p>";
        }
        
        // Add foreign key constraint to seller_registrations
        $add_fk_sql = "ALTER TABLE products ADD CONSTRAINT fk_farmer_id FOREIGN KEY (farmer_id) REFERENCES seller_registrations(id) ON DELETE CASCADE";
        $pdo->exec($add_fk_sql);
        echo "<p style='color:green'>Added foreign key constraint linking farmer_id to seller_registrations(id)</p>";
        
        // Show updated structure
        echo "<h3>Updated Products Table Structure:</h3>";
        $updated_structure = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($updated_structure as $column) {
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
        
        // Show foreign key constraints
        echo "<h3>Foreign Key Constraints:</h3>";
        $fk_query = $pdo->query("
            SELECT 
                CONSTRAINT_NAME, 
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'kisan_kart'
            AND TABLE_NAME = 'products'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fk_constraints = $fk_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Constraint Name</th><th>Column</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
        
        foreach ($fk_constraints as $fk) {
            echo "<tr>";
            echo "<td>" . $fk['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $fk['COLUMN_NAME'] . "</td>";
            echo "<td>" . $fk['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $fk['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } else {
        echo "<p>Products table does not exist.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
