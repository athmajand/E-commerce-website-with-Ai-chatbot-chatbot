<?php
// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Verification of Products and Seller Registrations Relationship</h2>";
    
    // Check seller_registrations data
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
    
    // Check products data
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
    
    // Add a sample product if none exist
    if (count($products) == 0 && count($sellers) > 0) {
        $first_seller_id = $sellers[0]['id'];
        
        $insert_product = $pdo->prepare("
            INSERT INTO products 
            (farmer_id, name, description, price, stock_quantity, unit, is_available) 
            VALUES 
            (?, 'Sample Product', 'This is a sample product', 99.99, 100, 'kg', 1)
        ");
        
        $insert_product->execute([$first_seller_id]);
        $product_id = $pdo->lastInsertId();
        
        echo "<p style='color:green'>Added a sample product (ID: $product_id) linked to seller ID: $first_seller_id</p>";
        echo "<p>Please refresh the page to see the new product.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
