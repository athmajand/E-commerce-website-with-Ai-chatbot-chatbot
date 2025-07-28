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
    
    echo "<h2>Seller Profiles Table Check</h2>";
    
    // Check if seller_profiles table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'seller_profiles'");
    $table_exists = $check_table->rowCount() > 0;
    
    echo "seller_profiles table exists: " . ($table_exists ? "Yes" : "No") . "<br>";
    
    if ($table_exists) {
        // Show table structure
        $structure_query = $pdo->query("DESCRIBE seller_profiles");
        $structure = $structure_query->fetchAll();
        
        echo "<h3>Seller Profiles Table Structure:</h3>";
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
        
        // Check if seller_registration_id column exists
        $seller_registration_id_exists = false;
        foreach ($structure as $column) {
            if ($column['Field'] === 'seller_registration_id') {
                $seller_registration_id_exists = true;
                break;
            }
        }
        
        echo "<br>seller_registration_id column exists: " . ($seller_registration_id_exists ? "Yes" : "No") . "<br>";
        
        // Add seller_registration_id column if it doesn't exist
        if (!$seller_registration_id_exists) {
            $alter_query = "ALTER TABLE seller_profiles ADD COLUMN seller_registration_id INT NOT NULL AFTER id";
            $pdo->exec($alter_query);
            echo "<p style='color:green'>Added seller_registration_id column to seller_profiles table.</p>";
            
            // Update existing records to set seller_registration_id = id (as a temporary fix)
            $update_query = "UPDATE seller_profiles SET seller_registration_id = id";
            $pdo->exec($update_query);
            echo "<p style='color:green'>Updated existing records with seller_registration_id = id.</p>";
        }
        
        // Show seller_profiles data
        $profiles_query = $pdo->query("SELECT * FROM seller_profiles");
        $profiles = $profiles_query->fetchAll();
        
        echo "<h3>Seller Profiles Data:</h3>";
        if (count($profiles) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            foreach (array_keys($profiles[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            
            foreach ($profiles as $profile) {
                echo "<tr>";
                foreach ($profile as $value) {
                    echo "<td>" . (is_null($value) ? 'NULL' : $value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No seller profiles found.</p>";
        }
    } else {
        // Create seller_profiles table with seller_registration_id column
        $create_table_sql = "
        CREATE TABLE `seller_profiles` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `seller_registration_id` int(11) NOT NULL,
          `business_name` varchar(100) NOT NULL,
          `business_description` text,
          `business_logo` varchar(255) DEFAULT NULL,
          `business_address` text NOT NULL,
          `business_country` varchar(50) DEFAULT NULL,
          `business_state` varchar(100) DEFAULT NULL,
          `business_city` varchar(100) DEFAULT NULL,
          `business_postal_code` varchar(20) DEFAULT NULL,
          `gst_number` varchar(50) DEFAULT NULL,
          `pan_number` varchar(50) DEFAULT NULL,
          `store_display_name` varchar(100) DEFAULT NULL,
          `product_categories` text,
          `marketplace` varchar(10) DEFAULT NULL,
          `store_logo_path` varchar(255) DEFAULT NULL,
          `status` enum('pending','approved','rejected') DEFAULT 'pending',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `seller_registration_id` (`seller_registration_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($create_table_sql);
        echo "<p style='color:green'>Created seller_profiles table with seller_registration_id column.</p>";
    }
    
    // Check seller_registrations data
    $registrations_query = $pdo->query("SELECT * FROM seller_registrations");
    $registrations = $registrations_query->fetchAll();
    
    echo "<h3>Seller Registrations Data:</h3>";
    if (count($registrations) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Business Name</th><th>Status</th></tr>";
        
        foreach ($registrations as $registration) {
            echo "<tr>";
            echo "<td>" . $registration['id'] . "</td>";
            echo "<td>" . $registration['first_name'] . " " . $registration['last_name'] . "</td>";
            echo "<td>" . $registration['email'] . "</td>";
            echo "<td>" . $registration['business_name'] . "</td>";
            echo "<td>" . $registration['status'] . "</td>";
            echo "</tr>";
            
            // Check if a seller profile exists for this registration
            $profile_check = $pdo->prepare("SELECT * FROM seller_profiles WHERE seller_registration_id = ?");
            $profile_check->execute([$registration['id']]);
            
            if ($profile_check->rowCount() == 0) {
                // Create a seller profile for this registration
                $insert_profile = $pdo->prepare("
                    INSERT INTO seller_profiles 
                    (seller_registration_id, business_name, business_description, business_address, 
                     business_country, business_state, business_city, business_postal_code,
                     gst_number, pan_number, store_display_name, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_profile->execute([
                    $registration['id'],
                    $registration['business_name'],
                    $registration['business_description'],
                    $registration['business_address'],
                    $registration['business_country'],
                    $registration['business_state'],
                    $registration['business_city'],
                    $registration['business_postal_code'],
                    $registration['gst_number'],
                    $registration['pan_number'],
                    $registration['store_display_name'],
                    $registration['status']
                ]);
                
                echo "<p style='color:green'>Created seller profile for " . $registration['first_name'] . " " . $registration['last_name'] . " (ID: " . $registration['id'] . ")</p>";
            }
        }
        
        echo "</table>";
    } else {
        echo "<p>No seller registrations found.</p>";
    }
    
    echo "<p>Now you can go back to the <a href='frontend/seller/products.php'>Seller Products page</a> to try adding a product again.</p>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
