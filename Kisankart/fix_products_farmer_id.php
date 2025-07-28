<?php
// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Fixing Products Table Foreign Key</h2>";
    
    // Check if products table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        // Get current products data
        $products_query = $pdo->query("SELECT id, farmer_id FROM products");
        $products = $products_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Products Data:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Product ID</th><th>Current Farmer ID</th></tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . ($product['farmer_id'] ? $product['farmer_id'] : 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Get available seller_registrations IDs
        $sellers_query = $pdo->query("SELECT id FROM seller_registrations");
        $sellers = $sellers_query->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Available Seller Registration IDs:</h3>";
        if (count($sellers) > 0) {
            echo "<ul>";
            foreach ($sellers as $seller_id) {
                echo "<li>ID: " . $seller_id . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No seller registrations found. We need to create at least one seller registration first.</p>";
            
            // Create a sample seller registration if none exist
            $password_hash = password_hash('password123', PASSWORD_DEFAULT);
            
            $insert_seller = $pdo->prepare("
                INSERT INTO seller_registrations 
                (first_name, last_name, email, phone, password, business_name, business_address, is_verified, status) 
                VALUES 
                ('Sample', 'Seller', 'sample@example.com', '9876543210', ?, 'Sample Farm', 'Sample Address', 1, 'approved')
            ");
            
            $insert_seller->execute([$password_hash]);
            $new_seller_id = $pdo->lastInsertId();
            
            echo "<p style='color:green'>Created a sample seller registration with ID: " . $new_seller_id . "</p>";
            
            $sellers = [$new_seller_id];
        }
        
        // Update all products to use a valid seller_id
        if (count($sellers) > 0) {
            $default_seller_id = $sellers[0];
            
            // First, check if there are any invalid farmer_ids
            $invalid_ids = false;
            foreach ($products as $product) {
                if ($product['farmer_id'] && !in_array($product['farmer_id'], $sellers)) {
                    $invalid_ids = true;
                    break;
                }
            }
            
            if ($invalid_ids || count($products) > 0) {
                // Update all products to use the default seller_id
                $update_sql = "UPDATE products SET farmer_id = ? WHERE farmer_id IS NULL OR farmer_id NOT IN (SELECT id FROM seller_registrations)";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$default_seller_id]);
                
                $affected_rows = $update_stmt->rowCount();
                echo "<p style='color:green'>Updated " . $affected_rows . " products to use seller ID: " . $default_seller_id . "</p>";
            } else {
                echo "<p>No products need updating.</p>";
            }
            
            // Now try to add the foreign key constraint
            try {
                // First check if the constraint already exists
                $constraint_check = $pdo->query("
                    SELECT * FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = 'kisan_kart'
                    AND TABLE_NAME = 'products'
                    AND COLUMN_NAME = 'farmer_id'
                    AND CONSTRAINT_NAME = 'fk_farmer_id'
                ");
                
                $constraint_exists = $constraint_check->rowCount() > 0;
                
                if ($constraint_exists) {
                    echo "<p>Foreign key constraint 'fk_farmer_id' already exists.</p>";
                } else {
                    // Add the foreign key constraint
                    $add_fk_sql = "ALTER TABLE products ADD CONSTRAINT fk_farmer_id FOREIGN KEY (farmer_id) REFERENCES seller_registrations(id) ON DELETE CASCADE";
                    $pdo->exec($add_fk_sql);
                    echo "<p style='color:green'>Successfully added foreign key constraint linking farmer_id to seller_registrations(id)</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error adding foreign key constraint: " . $e->getMessage() . "</p>";
                echo "<p>This might be due to remaining data integrity issues. Let's check for NULL values:</p>";
                
                // Check for NULL farmer_id values
                $null_check = $pdo->query("SELECT COUNT(*) FROM products WHERE farmer_id IS NULL");
                $null_count = $null_check->fetchColumn();
                
                if ($null_count > 0) {
                    echo "<p>Found " . $null_count . " products with NULL farmer_id. Updating them...</p>";
                    
                    $update_nulls = $pdo->prepare("UPDATE products SET farmer_id = ? WHERE farmer_id IS NULL");
                    $update_nulls->execute([$default_seller_id]);
                    
                    echo "<p style='color:green'>Updated " . $update_nulls->rowCount() . " products with NULL farmer_id.</p>";
                    
                    // Try adding the constraint again
                    try {
                        $add_fk_sql = "ALTER TABLE products ADD CONSTRAINT fk_farmer_id FOREIGN KEY (farmer_id) REFERENCES seller_registrations(id) ON DELETE CASCADE";
                        $pdo->exec($add_fk_sql);
                        echo "<p style='color:green'>Successfully added foreign key constraint on second attempt!</p>";
                    } catch (PDOException $e2) {
                        echo "<p style='color:red'>Still unable to add constraint: " . $e2->getMessage() . "</p>";
                        echo "<p>Let's try a more aggressive approach:</p>";
                        
                        // Make farmer_id NOT NULL
                        $alter_sql = "ALTER TABLE products MODIFY farmer_id INT NOT NULL";
                        $pdo->exec($alter_sql);
                        echo "<p style='color:green'>Modified farmer_id to be NOT NULL</p>";
                        
                        // Try adding the constraint one more time
                        try {
                            $add_fk_sql = "ALTER TABLE products ADD CONSTRAINT fk_farmer_id FOREIGN KEY (farmer_id) REFERENCES seller_registrations(id) ON DELETE CASCADE";
                            $pdo->exec($add_fk_sql);
                            echo "<p style='color:green'>Successfully added foreign key constraint on third attempt!</p>";
                        } catch (PDOException $e3) {
                            echo "<p style='color:red'>Final attempt failed: " . $e3->getMessage() . "</p>";
                            echo "<p>Manual intervention may be required. Please check the data in phpMyAdmin.</p>";
                        }
                    }
                }
            }
            
            // Show updated products data
            $updated_products = $pdo->query("SELECT id, farmer_id FROM products")->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Updated Products Data:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Product ID</th><th>Updated Farmer ID</th></tr>";
            
            foreach ($updated_products as $product) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . ($product['farmer_id'] ? $product['farmer_id'] : 'NULL') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        // Show foreign key constraints
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
        
        echo "<h3>Current Foreign Key Constraints:</h3>";
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
        
    } else {
        echo "<p>Products table does not exist.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
