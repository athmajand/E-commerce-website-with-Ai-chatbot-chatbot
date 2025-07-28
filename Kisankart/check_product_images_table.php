<?php
// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Checking Product Images Table</h2>";
    
    // Check if product_images table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'product_images'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p style='color:green'>The product_images table exists.</p>";
        
        // Get table structure
        $structure_query = $pdo->query("DESCRIBE product_images");
        $structure = $structure_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
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
        
        // Check for existing data
        $data_query = $pdo->query("SELECT * FROM product_images LIMIT 10");
        $data = $data_query->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($data) > 0) {
            echo "<h3>Sample Data:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            foreach (array_keys($data[0]) as $key) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr>";
            
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . $value . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No data found in the product_images table.</p>";
        }
    } else {
        echo "<p style='color:red'>The product_images table does not exist.</p>";
        
        // Create the table
        echo "<h3>Creating product_images table...</h3>";
        
        $create_table_sql = "
        CREATE TABLE product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )";
        
        try {
            $pdo->exec($create_table_sql);
            echo "<p style='color:green'>Successfully created product_images table!</p>";
            
            // Verify the table was created
            $verify_query = $pdo->query("SHOW TABLES LIKE 'product_images'");
            if ($verify_query->rowCount() > 0) {
                echo "<p style='color:green'>Verified that product_images table exists.</p>";
                
                // Show the structure
                $structure_query = $pdo->query("DESCRIBE product_images");
                $structure = $structure_query->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Table Structure:</h3>";
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
            } else {
                echo "<p style='color:red'>Failed to verify that product_images table was created.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating product_images table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check if products table has additional_images column
    $additional_images_check = $pdo->query("SHOW COLUMNS FROM products LIKE 'additional_images'");
    $additional_images_exists = $additional_images_check->rowCount() > 0;
    
    if (!$additional_images_exists) {
        echo "<p>Adding additional_images column to products table...</p>";
        
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN additional_images TEXT AFTER image_url");
            echo "<p style='color:green'>Successfully added additional_images column to products table!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error adding additional_images column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:green'>The products table already has an additional_images column.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Database connection error: " . $e->getMessage() . "</p>";
}
?>
