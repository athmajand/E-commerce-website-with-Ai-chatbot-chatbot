<?php
// Script to check database tables
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Database Tables</h1>";

try {
    // List all tables
    $tables_query = "SHOW TABLES";
    $tables_stmt = $db->prepare($tables_query);
    $tables_stmt->execute();
    
    echo "<h2>All Tables</h2>";
    echo "<ul>";
    while ($row = $tables_stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    
    // Check products table structure
    $products_query = "DESCRIBE products";
    $products_stmt = $db->prepare($products_query);
    $products_stmt->execute();
    
    echo "<h2>Products Table Structure</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if products table has data
    $count_query = "SELECT COUNT(*) as count FROM products";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h2>Products Count</h2>";
    echo "<p>Total products: " . $count . "</p>";
    
    if ($count > 0) {
        // Show sample products
        $sample_query = "SELECT * FROM products LIMIT 5";
        $sample_stmt = $db->prepare($sample_query);
        $sample_stmt->execute();
        
        echo "<h2>Sample Products</h2>";
        echo "<table border='1'>";
        echo "<tr>";
        
        // Get column names
        $column_count = $sample_stmt->columnCount();
        for ($i = 0; $i < $column_count; $i++) {
            $col = $sample_stmt->getColumnMeta($i);
            echo "<th>" . $col['name'] . "</th>";
        }
        echo "</tr>";
        
        // Get data
        while ($row = $sample_stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check seller_profiles table
    $seller_profiles_query = "DESCRIBE seller_profiles";
    $seller_profiles_stmt = $db->prepare($seller_profiles_query);
    $seller_profiles_stmt->execute();
    
    echo "<h2>Seller Profiles Table Structure</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $seller_profiles_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
