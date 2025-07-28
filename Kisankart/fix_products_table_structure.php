<?php
// Script to check and fix the products table structure
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Products Table Structure Check</h1>";

try {
    // Check if products table exists
    $check_table_query = "SHOW TABLES LIKE 'products'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();
    
    if ($check_table_stmt->rowCount() > 0) {
        echo "<p style='color: green;'>Products table exists.</p>";
        
        // Get current table structure
        $describe_query = "DESCRIBE products";
        $describe_stmt = $db->prepare($describe_query);
        $describe_stmt->execute();
        
        $columns = [];
        while ($row = $describe_stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        
        echo "<h2>Current Table Structure</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $field => $data) {
            echo "<tr>";
            echo "<td>" . $field . "</td>";
            echo "<td>" . $data['Type'] . "</td>";
            echo "<td>" . $data['Null'] . "</td>";
            echo "<td>" . $data['Key'] . "</td>";
            echo "<td>" . $data['Default'] . "</td>";
            echo "<td>" . $data['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Define required columns
        $required_columns = [
            'id' => ['type' => 'int(11)', 'null' => 'NO', 'key' => 'PRI', 'default' => null, 'extra' => 'auto_increment'],
            'name' => ['type' => 'varchar(255)', 'null' => 'NO', 'key' => '', 'default' => null, 'extra' => ''],
            'description' => ['type' => 'text', 'null' => 'YES', 'key' => '', 'default' => null, 'extra' => ''],
            'price' => ['type' => 'decimal(10,2)', 'null' => 'NO', 'key' => '', 'default' => null, 'extra' => ''],
            'discount_price' => ['type' => 'decimal(10,2)', 'null' => 'YES', 'key' => '', 'default' => null, 'extra' => ''],
            'category_id' => ['type' => 'int(11)', 'null' => 'YES', 'key' => 'MUL', 'default' => null, 'extra' => ''],
            'seller_id' => ['type' => 'int(11)', 'null' => 'YES', 'key' => 'MUL', 'default' => null, 'extra' => ''],
            'farmer_id' => ['type' => 'int(11)', 'null' => 'YES', 'key' => '', 'default' => null, 'extra' => ''],
            'stock_quantity' => ['type' => 'int(11)', 'null' => 'NO', 'key' => '', 'default' => '0', 'extra' => ''],
            'image_url' => ['type' => 'varchar(255)', 'null' => 'YES', 'key' => '', 'default' => null, 'extra' => ''],
            'additional_images' => ['type' => 'text', 'null' => 'YES', 'key' => '', 'default' => null, 'extra' => ''],
            'is_featured' => ['type' => 'tinyint(1)', 'null' => 'YES', 'key' => '', 'default' => '0', 'extra' => ''],
            'status' => ['type' => "enum('active','inactive','out_of_stock')", 'null' => 'YES', 'key' => '', 'default' => 'active', 'extra' => ''],
            'created_at' => ['type' => 'datetime', 'null' => 'YES', 'key' => '', 'default' => 'CURRENT_TIMESTAMP', 'extra' => ''],
            'updated_at' => ['type' => 'datetime', 'null' => 'YES', 'key' => '', 'default' => 'CURRENT_TIMESTAMP', 'extra' => '']
        ];
        
        // Check for missing columns
        $missing_columns = [];
        foreach ($required_columns as $column => $details) {
            if (!isset($columns[$column])) {
                $missing_columns[] = $column;
            }
        }
        
        if (!empty($missing_columns)) {
            echo "<h2>Missing Columns</h2>";
            echo "<p>The following columns are missing from the products table:</p>";
            echo "<ul>";
            foreach ($missing_columns as $column) {
                echo "<li>" . $column . "</li>";
            }
            echo "</ul>";
            
            // Add missing columns
            echo "<h2>Adding Missing Columns</h2>";
            foreach ($missing_columns as $column) {
                $column_def = $required_columns[$column];
                $sql = "ALTER TABLE products ADD COLUMN " . $column . " " . $column_def['type'];
                
                if ($column_def['null'] === 'NO') {
                    $sql .= " NOT NULL";
                }
                
                if ($column_def['default'] !== null) {
                    $sql .= " DEFAULT " . ($column_def['default'] === 'CURRENT_TIMESTAMP' ? 'CURRENT_TIMESTAMP' : "'" . $column_def['default'] . "'");
                }
                
                if ($column_def['extra'] !== '') {
                    $sql .= " " . $column_def['extra'];
                }
                
                try {
                    $db->exec($sql);
                    echo "<p style='color: green;'>Added column: " . $column . "</p>";
                } catch (PDOException $e) {
                    echo "<p style='color: red;'>Error adding column " . $column . ": " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p style='color: green;'>All required columns exist in the products table.</p>";
        }
        
        // Check for indexes
        $indexes_query = "SHOW INDEX FROM products";
        $indexes_stmt = $db->prepare($indexes_query);
        $indexes_stmt->execute();
        
        $indexes = [];
        while ($row = $indexes_stmt->fetch(PDO::FETCH_ASSOC)) {
            $indexes[$row['Key_name']][] = $row['Column_name'];
        }
        
        echo "<h2>Current Indexes</h2>";
        echo "<ul>";
        foreach ($indexes as $key_name => $columns) {
            echo "<li>" . $key_name . ": " . implode(", ", $columns) . "</li>";
        }
        echo "</ul>";
        
        // Add missing indexes
        $required_indexes = [
            'seller_id' => ['seller_id'],
            'farmer_id' => ['farmer_id']
        ];
        
        foreach ($required_indexes as $index_name => $columns) {
            if (!isset($indexes[$index_name])) {
                $sql = "ALTER TABLE products ADD INDEX " . $index_name . " (" . implode(", ", $columns) . ")";
                try {
                    $db->exec($sql);
                    echo "<p style='color: green;'>Added index: " . $index_name . "</p>";
                } catch (PDOException $e) {
                    echo "<p style='color: red;'>Error adding index " . $index_name . ": " . $e->getMessage() . "</p>";
                }
            }
        }
        
        // Check for sample data
        $count_query = "SELECT COUNT(*) as count FROM products";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<h2>Data Check</h2>";
        echo "<p>Total products: " . $count . "</p>";
        
        if ($count == 0) {
            echo "<p style='color: orange;'>No products found. You may want to add some sample products.</p>";
            echo "<p><a href='add_sample_products.php' style='color: blue;'>Add Sample Products</a></p>";
        }
    } else {
        echo "<p style='color: red;'>Products table does not exist!</p>";
        
        // Create products table
        $create_table_sql = "
        CREATE TABLE `products` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `description` text,
          `price` decimal(10,2) NOT NULL,
          `discount_price` decimal(10,2) DEFAULT NULL,
          `category_id` int(11) DEFAULT NULL,
          `seller_id` int(11) DEFAULT NULL,
          `farmer_id` int(11) DEFAULT NULL,
          `stock_quantity` int(11) DEFAULT 0,
          `image_url` varchar(255) DEFAULT NULL,
          `additional_images` TEXT,
          `is_featured` tinyint(1) DEFAULT 0,
          `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `category_id` (`category_id`),
          KEY `seller_id` (`seller_id`),
          KEY `farmer_id` (`farmer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $db->exec($create_table_sql);
            echo "<p style='color: green;'>Products table created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Error creating products table: " . $e->getMessage() . "</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// Links
echo "<h2>Next Steps</h2>";
echo "<p><a href='add_sample_products.php'>Add Sample Products</a></p>";
echo "<p><a href='check_seller_session.php'>Check Seller Session</a></p>";
echo "<p><a href='frontend/seller/products.php'>Go to Seller Products Management</a></p>";
?>
