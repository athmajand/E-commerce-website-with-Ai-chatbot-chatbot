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
    echo "<h2>Database Connection Test</h2>";
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Database connection successful!<br>";
    
    // Check if categories table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'categories'");
    $table_exists = $check_table->rowCount() > 0;
    
    echo "Categories table exists: " . ($table_exists ? "Yes" : "No") . "<br>";
    
    if ($table_exists) {
        // Count categories
        $count_query = $pdo->query("SELECT COUNT(*) as count FROM categories");
        $count = $count_query->fetch()['count'];
        echo "Number of categories: $count<br>";
        
        // List categories
        $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
        echo "<h3>Categories:</h3>";
        echo "<pre>";
        print_r($categories);
        echo "</pre>";
        
        // Test the exact query used in products.php
        echo "<h3>Testing the exact query from products.php:</h3>";
        $categories_query = "SELECT id, name FROM categories ORDER BY name";
        $categories_stmt = $pdo->prepare($categories_query);
        $categories_stmt->execute();
        
        $categories_result = [];
        while ($row = $categories_stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories_result[] = $row;
        }
        
        echo "<pre>";
        print_r($categories_result);
        echo "</pre>";
    }
    
    // Check if seller_registrations table exists
    echo "<h3>Checking seller_registrations table:</h3>";
    $check_seller_table = $pdo->query("SHOW TABLES LIKE 'seller_registrations'");
    $seller_table_exists = $check_seller_table->rowCount() > 0;
    
    echo "seller_registrations table exists: " . ($seller_table_exists ? "Yes" : "No") . "<br>";
    
    if ($seller_table_exists) {
        // Count seller registrations
        $count_query = $pdo->query("SELECT COUNT(*) as count FROM seller_registrations");
        $count = $count_query->fetch()['count'];
        echo "Number of seller registrations: $count<br>";
        
        if ($count == 0) {
            echo "<p>No seller registrations found. You may need to register as a seller first.</p>";
        }
    }
    
    // Check session data
    echo "<h3>Session Data:</h3>";
    session_start();
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
