<?php
// Comprehensive database structure verification script
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

// Create mysqli connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Structure Verification</h2>";

// List of required tables
$required_tables = [
    'seller_registrations',
    'categories', 
    'products',
    'customer_registrations',
    'customer_profiles',
    'addresses',
    'cart',
    'wishlist',
    'orders',
    'order_items',
    'admin',
    'seller_orders',
    'product_images',
    'delivery_slots',
    'notifications'
];

echo "<h3>Table Existence Check</h3>";
$missing_tables = [];
$existing_tables = [];

foreach ($required_tables as $table) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($check_query);
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color:green'>✓ Table '$table' exists</p>";
        $existing_tables[] = $table;
    } else {
        echo "<p style='color:red'>✗ Table '$table' missing</p>";
        $missing_tables[] = $table;
    }
}

echo "<h3>Critical Column Checks</h3>";

// Check products table structure
$products_columns = $conn->query("SHOW COLUMNS FROM products");
if ($products_columns) {
    $columns = [];
    while ($column = $products_columns->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    $required_columns = ['id', 'name', 'price', 'stock', 'seller_id', 'category_id'];
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "<p style='color:green'>✓ Products table has '$col' column</p>";
        } else {
            echo "<p style='color:red'>✗ Products table missing '$col' column</p>";
        }
    }
}

// Check order_items table structure
$order_items_columns = $conn->query("SHOW COLUMNS FROM order_items");
if ($order_items_columns) {
    $columns = [];
    while ($column = $order_items_columns->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    $required_columns = ['id', 'order_id', 'product_id', 'seller_id', 'quantity', 'unit_price', 'total_price'];
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "<p style='color:green'>✓ Order_items table has '$col' column</p>";
        } else {
            echo "<p style='color:red'>✗ Order_items table missing '$col' column</p>";
        }
    }
}

echo "<h3>Foreign Key Relationships</h3>";

// Check foreign key constraints
$fk_query = "SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = '$dbname' 
AND REFERENCED_TABLE_NAME IS NOT NULL";

$fk_result = $conn->query($fk_query);

if ($fk_result) {
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Table</th><th>Column</th><th>References</th><th>Referenced Column</th></tr></thead>";
    echo "<tbody>";
    
    while ($fk = $fk_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $fk['TABLE_NAME'] . "</td>";
        echo "<td>" . $fk['COLUMN_NAME'] . "</td>";
        echo "<td>" . $fk['REFERENCED_TABLE_NAME'] . "</td>";
        echo "<td>" . $fk['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
}

echo "<h3>Sample Data Verification</h3>";

// Check for sample data
$tables_with_data = ['seller_registrations', 'categories', 'products', 'admin'];
foreach ($tables_with_data as $table) {
    $count_query = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($count_query);
    
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p>Table '$table': $count records</p>";
    }
}

echo "<h3>Query Tests</h3>";

// Test the critical queries
$test_queries = [
    "Products with seller info" => "SELECT p.id, p.name, sr.name as seller_name FROM products p LEFT JOIN seller_registrations sr ON p.seller_id = sr.id LIMIT 3",
    "Order items with seller info" => "SELECT oi.id, p.name as product_name, sr.name as seller_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id LEFT JOIN seller_registrations sr ON oi.seller_id = sr.id LIMIT 3"
];

foreach ($test_queries as $description => $query) {
    $result = $conn->query($query);
    
    if ($result) {
        echo "<p style='color:green'>✓ $description query works</p>";
        if ($result->num_rows > 0) {
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . implode(" - ", $row) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color:red'>✗ $description query failed: " . $conn->error . "</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Structure Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Database Structure Verification</h1>
        <p>This page verifies that all required tables, columns, and relationships exist in the database.</p>
        
        <div class="mt-3">
            <a href="test_db_fix.php" class="btn btn-secondary">Back to Database Test</a>
            <a href="test_admin_product_view.php" class="btn btn-primary">Test Admin Product View</a>
            <a href="admin_product_view.php?id=1" class="btn btn-success">Test Actual Admin Page</a>
        </div>
    </div>
</body>
</html> 