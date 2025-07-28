<?php
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
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
