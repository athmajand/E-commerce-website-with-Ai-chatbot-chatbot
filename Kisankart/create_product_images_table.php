<?php
// Script to create product_images table if it doesn't exist
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Product Images Table Setup</h2>";

// Check if product_images table exists
$table_check = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color:green'>✓ product_images table already exists</p>";
} else {
    echo "<p style='color:orange'>⚠ product_images table missing. Creating it now...</p>";
    
    // Create product_images table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        is_primary TINYINT DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_table_sql)) {
        echo "<p style='color:green'>✓ product_images table created successfully</p>";
        
        // Create index for better performance
        $index_sql = "CREATE INDEX idx_product_images_product_id ON product_images(product_id)";
        if ($conn->query($index_sql)) {
            echo "<p style='color:green'>✓ Index created successfully</p>";
        }
        
        // Create index for primary photos
        $primary_index_sql = "CREATE INDEX idx_product_images_primary ON product_images(product_id, is_primary)";
        if ($conn->query($primary_index_sql)) {
            echo "<p style='color:green'>✓ Primary photo index created successfully</p>";
        }
        
    } else {
        echo "<p style='color:red'>✗ Error creating product_images table: " . $conn->error . "</p>";
    }
}

// Check table structure
$structure_query = $conn->query("DESCRIBE product_images");
if ($structure_query) {
    echo "<h3>Table Structure:</h3>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
    echo "<tbody>";
    while ($column = $structure_query->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}

// Check if there are any existing product images
$count_query = $conn->query("SELECT COUNT(*) as count FROM product_images");
if ($count_query) {
    $count = $count_query->fetch_assoc()['count'];
    echo "<p>Total product images in database: $count</p>";
}

// Check if uploads directory exists
$upload_dir = "uploads/products/";
if (is_dir($upload_dir)) {
    echo "<p style='color:green'>✓ Upload directory exists: $upload_dir</p>";
} else {
    echo "<p style='color:orange'>⚠ Upload directory missing: $upload_dir</p>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color:green'>✓ Created upload directory</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to create upload directory</p>";
    }
}

// Test the query that was failing
echo "<h3>Testing Product Images Query:</h3>";
$test_product_id = 1;
$test_query = $conn->prepare("SELECT image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
if ($test_query) {
    $test_query->bind_param("i", $test_product_id);
    $test_query->execute();
    $test_result = $test_query->get_result();
    
    if ($test_result) {
        echo "<p style='color:green'>✓ Query executed successfully</p>";
        echo "<p>Found " . $test_result->num_rows . " images for product ID $test_product_id</p>";
    } else {
        echo "<p style='color:red'>✗ Query failed: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ Failed to prepare query: " . $conn->error . "</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Images Table Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Product Images Table Setup</h1>
        <p>This script checks and creates the product_images table if needed.</p>
        
        <div class="mt-3">
            <a href="admin_product_view.php?id=1" class="btn btn-primary">Test Admin Product View</a>
            <a href="test_photo_upload.php" class="btn btn-success">Test Photo Upload</a>
            <a href="test_db_fix.php" class="btn btn-secondary">Back to Database Test</a>
        </div>
    </div>
</body>
</html> 