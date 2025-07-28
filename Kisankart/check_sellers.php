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
    
    // Check if seller_registrations table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'seller_registrations'");
    $table_exists = $check_table->rowCount() > 0;
    
    echo "seller_registrations table exists: " . ($table_exists ? "Yes" : "No") . "<br>";
    
    if ($table_exists) {
        // Count seller registrations
        $count_query = $pdo->query("SELECT COUNT(*) as count FROM seller_registrations");
        $count = $count_query->fetch()['count'];
        echo "Number of seller registrations: $count<br>";
        
        if ($count == 0) {
            // Add a sample seller registration
            $password_hash = password_hash('password123', PASSWORD_DEFAULT);
            
            $insert_sql = "INSERT INTO seller_registrations 
                (first_name, last_name, email, phone, password, business_name, business_address, is_verified, status) 
                VALUES 
                ('Test', 'Seller', 'seller@example.com', '9876543210', ?, 'Test Farm', '123 Farm Road, Test City', 1, 'approved')";
            
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute([$password_hash]);
            
            echo "Added a sample seller registration.<br>";
            echo "Login credentials:<br>";
            echo "Email: seller@example.com<br>";
            echo "Password: password123<br>";
        } else {
            // List all seller registrations
            $sellers = $pdo->query("SELECT id, first_name, last_name, email, phone, status FROM seller_registrations")->fetchAll();
            echo "<h3>Seller Registrations:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr>";
            
            foreach ($sellers as $seller) {
                echo "<tr>";
                echo "<td>" . $seller['id'] . "</td>";
                echo "<td>" . $seller['first_name'] . " " . $seller['last_name'] . "</td>";
                echo "<td>" . $seller['email'] . "</td>";
                echo "<td>" . $seller['phone'] . "</td>";
                echo "<td>" . $seller['status'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } else {
        echo "The seller_registrations table does not exist. Please run the database setup script first.";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
