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
    
    echo "<h2>Seller Tables Check</h2>";
    
    // Check if seller_profiles table exists
    $check_profiles_table = $pdo->query("SHOW TABLES LIKE 'seller_profiles'");
    $profiles_table_exists = $check_profiles_table->rowCount() > 0;
    
    echo "seller_profiles table exists: " . ($profiles_table_exists ? "Yes" : "No") . "<br>";
    
    // Check if seller_registrations table exists
    $check_registrations_table = $pdo->query("SHOW TABLES LIKE 'seller_registrations'");
    $registrations_table_exists = $check_registrations_table->rowCount() > 0;
    
    echo "seller_registrations table exists: " . ($registrations_table_exists ? "Yes" : "No") . "<br><br>";
    
    // Create seller_profiles table if it doesn't exist
    if (!$profiles_table_exists) {
        $create_profiles_sql = "
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
        
        $pdo->exec($create_profiles_sql);
        echo "<p style='color:green'>Created seller_profiles table.</p>";
        $profiles_table_exists = true;
    }
    
    // Check seller_registrations data
    if ($registrations_table_exists) {
        $registrations_query = $pdo->query("SELECT * FROM seller_registrations");
        $registrations = $registrations_query->fetchAll();
        
        echo "<h3>Seller Registrations:</h3>";
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
            }
            
            echo "</table>";
            
            // Check if seller profiles exist for these registrations
            if ($profiles_table_exists) {
                foreach ($registrations as $registration) {
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
            }
        } else {
            echo "<p>No seller registrations found.</p>";
            
            // Add a sample seller registration
            $password_hash = password_hash('password123', PASSWORD_DEFAULT);
            
            $insert_registration = $pdo->prepare("
                INSERT INTO seller_registrations 
                (first_name, last_name, email, phone, password, business_name, business_address, 
                 business_city, business_state, business_postal_code, is_verified, status) 
                VALUES 
                ('Test', 'Seller', 'seller@example.com', '9876543210', ?, 'Test Farm', '123 Farm Road', 
                 'Test City', 'Test State', '123456', 1, 'approved')
            ");
            
            $insert_registration->execute([$password_hash]);
            $seller_registration_id = $pdo->lastInsertId();
            
            echo "<p style='color:green'>Added a sample seller registration (ID: $seller_registration_id).</p>";
            
            // Add a seller profile for this registration
            if ($profiles_table_exists) {
                $insert_profile = $pdo->prepare("
                    INSERT INTO seller_profiles 
                    (seller_registration_id, business_name, business_description, business_address, 
                     business_city, business_state, business_postal_code, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_profile->execute([
                    $seller_registration_id,
                    'Test Farm',
                    'A test farm for selling agricultural products',
                    '123 Farm Road',
                    'Test City',
                    'Test State',
                    '123456',
                    'approved'
                ]);
                
                echo "<p style='color:green'>Created seller profile for the sample seller.</p>";
            }
            
            echo "<p>Login credentials:<br>";
            echo "Email: seller@example.com<br>";
            echo "Password: password123</p>";
        }
    }
    
    // Check seller_profiles data
    if ($profiles_table_exists) {
        $profiles_query = $pdo->query("SELECT * FROM seller_profiles");
        $profiles = $profiles_query->fetchAll();
        
        echo "<h3>Seller Profiles:</h3>";
        if (count($profiles) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Registration ID</th><th>Business Name</th><th>Status</th></tr>";
            
            foreach ($profiles as $profile) {
                echo "<tr>";
                echo "<td>" . $profile['id'] . "</td>";
                echo "<td>" . $profile['seller_registration_id'] . "</td>";
                echo "<td>" . $profile['business_name'] . "</td>";
                echo "<td>" . $profile['status'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No seller profiles found.</p>";
        }
    }
    
    // Check session data
    echo "<h3>Session Data:</h3>";
    session_start();
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<p>Now you can go back to the <a href='frontend/seller/products.php'>Seller Products page</a> to try adding a product again.</p>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
