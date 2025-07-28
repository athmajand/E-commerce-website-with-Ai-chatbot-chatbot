<?php
// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Fixing Product Farmer IDs</h2>";
    
    // Check if products table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        // Get all products
        $products_query = $pdo->query("SELECT id, seller_id, farmer_id FROM products");
        $products = $products_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Products Data:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Product ID</th><th>Seller ID</th><th>Farmer ID</th></tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . ($product['seller_id'] ? $product['seller_id'] : 'NULL') . "</td>";
            echo "<td>" . ($product['farmer_id'] ? $product['farmer_id'] : 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Get all seller profiles
        $seller_profiles_query = $pdo->query("SELECT id, seller_id FROM seller_profiles");
        $seller_profiles = $seller_profiles_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Seller Profiles:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Profile ID</th><th>Seller ID (from seller_registrations)</th></tr>";
        
        foreach ($seller_profiles as $profile) {
            echo "<tr>";
            echo "<td>" . $profile['id'] . "</td>";
            echo "<td>" . ($profile['seller_id'] ? $profile['seller_id'] : 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Create a mapping of seller_profile.id to seller_registrations.id
        $seller_map = [];
        foreach ($seller_profiles as $profile) {
            if ($profile['seller_id']) {
                $seller_map[$profile['id']] = $profile['seller_id'];
            }
        }
        
        // Update products with missing or invalid farmer_id
        $updated_count = 0;
        $error_count = 0;
        
        foreach ($products as $product) {
            $product_id = $product['id'];
            $seller_id = $product['seller_id'];
            $farmer_id = $product['farmer_id'];
            
            // Check if farmer_id is missing or invalid
            $needs_update = false;
            
            if ($farmer_id === null || $farmer_id === '') {
                $needs_update = true;
                echo "<p>Product ID {$product_id} has NULL farmer_id</p>";
            } else {
                // Check if farmer_id exists in seller_registrations
                $check_query = $pdo->prepare("SELECT id FROM seller_registrations WHERE id = ?");
                $check_query->execute([$farmer_id]);
                if ($check_query->rowCount() === 0) {
                    $needs_update = true;
                    echo "<p>Product ID {$product_id} has invalid farmer_id: {$farmer_id}</p>";
                }
            }
            
            if ($needs_update) {
                // Get the corresponding seller_registration_id from the seller_map
                if (isset($seller_map[$seller_id])) {
                    $new_farmer_id = $seller_map[$seller_id];
                    
                    // Update the product
                    $update_query = $pdo->prepare("UPDATE products SET farmer_id = ? WHERE id = ?");
                    $update_query->execute([$new_farmer_id, $product_id]);
                    
                    echo "<p style='color:green'>Updated product ID {$product_id}: Set farmer_id to {$new_farmer_id}</p>";
                    $updated_count++;
                } else {
                    echo "<p style='color:red'>Could not update product ID {$product_id}: No mapping found for seller_id {$seller_id}</p>";
                    $error_count++;
                }
            }
        }
        
        echo "<h3>Update Summary:</h3>";
        echo "<p>Total products: " . count($products) . "</p>";
        echo "<p>Updated products: {$updated_count}</p>";
        echo "<p>Failed updates: {$error_count}</p>";
        
        // Verify all products now have valid farmer_ids
        $invalid_query = $pdo->query("
            SELECT p.id, p.farmer_id 
            FROM products p 
            LEFT JOIN seller_registrations s ON p.farmer_id = s.id 
            WHERE s.id IS NULL
        ");
        $invalid_products = $invalid_query->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($invalid_products) > 0) {
            echo "<h3>Products Still With Invalid farmer_id:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Product ID</th><th>Invalid Farmer ID</th></tr>";
            
            foreach ($invalid_products as $product) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . ($product['farmer_id'] ? $product['farmer_id'] : 'NULL') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            echo "<p style='color:red'>There are still " . count($invalid_products) . " products with invalid farmer_id values.</p>";
        } else {
            echo "<p style='color:green'>All products now have valid farmer_id values!</p>";
        }
        
    } else {
        echo "<p>Products table does not exist.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
