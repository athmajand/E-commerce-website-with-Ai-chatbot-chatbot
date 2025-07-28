<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$db = 'kisan_kart';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Check if products table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'products'");
    $table_exists = $check_table->rowCount() > 0;
    
    echo "Products table exists: " . ($table_exists ? "Yes" : "No") . "<br>";
    
    if ($table_exists) {
        // Get current table structure
        $structure_query = $pdo->query("DESCRIBE products");
        $structure = $structure_query->fetchAll();
        
        // Create a map of existing columns
        $existing_columns = [];
        foreach ($structure as $column) {
            $existing_columns[$column['Field']] = $column;
        }
        
        // Define required columns and their definitions
        $required_columns = [
            'id' => "INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY",
            'name' => "VARCHAR(255) NOT NULL",
            'description' => "TEXT",
            'price' => "DECIMAL(10,2) NOT NULL",
            'discount_price' => "DECIMAL(10,2) DEFAULT NULL",
            'category_id' => "INT(11) DEFAULT NULL",
            'seller_id' => "INT(11) DEFAULT NULL",
            'stock_quantity' => "INT(11) DEFAULT 0",
            'image_url' => "VARCHAR(255) DEFAULT NULL",
            'is_featured' => "TINYINT(1) DEFAULT 0",
            'status' => "ENUM('active','inactive','out_of_stock') DEFAULT 'active'",
            'created_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        // Check for missing columns and add them
        $missing_columns = [];
        foreach ($required_columns as $column => $definition) {
            if (!isset($existing_columns[$column])) {
                $missing_columns[$column] = $definition;
            }
        }
        
        if (!empty($missing_columns)) {
            echo "<h3>Missing columns that need to be added:</h3>";
            echo "<ul>";
            foreach ($missing_columns as $column => $definition) {
                echo "<li>$column: $definition</li>";
                
                // Add the missing column
                $alter_query = "ALTER TABLE products ADD COLUMN $column $definition";
                $pdo->exec($alter_query);
                echo "<span style='color:green'>✓ Added column $column</span><br>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:green'>All required columns exist in the products table.</p>";
        }
        
        // Show updated table structure
        $updated_structure_query = $pdo->query("DESCRIBE products");
        $updated_structure = $updated_structure_query->fetchAll();
        
        echo "<h3>Current Products Table Structure:</h3>";
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
        
    } else {
        // Create products table with all required columns
        $create_table_sql = "
        CREATE TABLE `products` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `description` text,
          `price` decimal(10,2) NOT NULL,
          `discount_price` decimal(10,2) DEFAULT NULL,
          `category_id` int(11) DEFAULT NULL,
          `seller_id` int(11) DEFAULT NULL,
          `stock_quantity` int(11) DEFAULT 0,
          `image_url` varchar(255) DEFAULT NULL,
          `is_featured` tinyint(1) DEFAULT 0,
          `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `category_id` (`category_id`),
          KEY `seller_id` (`seller_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($create_table_sql);
        echo "<p style='color:green'>Created products table with all required columns.</p>";
    }
    
    echo "<p>Now you can go back to the <a href='frontend/seller/products.php'>Seller Products page</a> to try adding a product again.</p>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
