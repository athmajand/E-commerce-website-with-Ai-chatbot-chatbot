<?php
// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Final Verification of Products and Seller Registrations Relationship</h2>";
    
    // Check table structures
    echo "<h3>Products Table Structure:</h3>";
    $products_structure = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($products_structure as $column) {
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
    
    echo "<h3>Seller Registrations Table Structure:</h3>";
    $seller_structure = $pdo->query("DESCRIBE seller_registrations")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($seller_structure as $column) {
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
    
    // Check foreign key constraints
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
    
    echo "<h3>Foreign Key Constraints:</h3>";
    $fk_constraints = $fk_query->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fk_constraints) > 0) {
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
        echo "<p>No foreign key constraints found on the products table.</p>";
    }
    
    // Show seller_registrations data
    $seller_query = $pdo->query("SELECT id, first_name, last_name, business_name FROM seller_registrations");
    $sellers = $seller_query->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Seller Registrations:</h3>";
    if (count($sellers) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Business Name</th></tr>";
        
        foreach ($sellers as $seller) {
            echo "<tr>";
            echo "<td>" . $seller['id'] . "</td>";
            echo "<td>" . $seller['first_name'] . " " . $seller['last_name'] . "</td>";
            echo "<td>" . $seller['business_name'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No seller registrations found.</p>";
    }
    
    // Show products data
    $products_query = $pdo->query("SELECT id, name, farmer_id FROM products");
    $products = $products_query->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Products:</h3>";
    if (count($products) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Farmer ID</th></tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . $product['name'] . "</td>";
            echo "<td>" . $product['farmer_id'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No products found.</p>";
    }
    
    // Show joined data
    echo "<h3>Products with Seller Information:</h3>";
    $joined_query = $pdo->query("
        SELECT 
            p.id as product_id, 
            p.name as product_name, 
            p.farmer_id,
            s.first_name,
            s.last_name,
            s.business_name
        FROM products p
        LEFT JOIN seller_registrations s ON p.farmer_id = s.id
    ");
    $joined_data = $joined_query->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($joined_data) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Farmer ID</th><th>Seller Name</th><th>Business Name</th></tr>";
        
        foreach ($joined_data as $row) {
            echo "<tr>";
            echo "<td>" . $row['product_id'] . "</td>";
            echo "<td>" . $row['product_name'] . "</td>";
            echo "<td>" . $row['farmer_id'] . "</td>";
            echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
            echo "<td>" . $row['business_name'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No joined data found.</p>";
    }
    
    echo "<h3>Conclusion:</h3>";
    $success = false;
    
    foreach ($fk_constraints as $fk) {
        if ($fk['COLUMN_NAME'] == 'farmer_id' && $fk['REFERENCED_TABLE_NAME'] == 'seller_registrations' && $fk['REFERENCED_COLUMN_NAME'] == 'id') {
            $success = true;
            break;
        }
    }
    
    if ($success) {
        echo "<p style='color:green; font-weight:bold;'>SUCCESS: The products table's farmer_id field is now properly linked to the seller_registrations table's id field!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>The foreign key relationship has not been properly established.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
