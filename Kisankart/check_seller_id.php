<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
    
    echo "<h2>Seller ID Check</h2>";
    
    // Get seller ID from session
    $seller_id = $_SESSION['user_id'] ?? null;
    echo "Current seller_id from session: " . ($seller_id ?? 'Not set') . "<br>";
    
    // Check if seller exists in seller_registrations
    if ($seller_id) {
        $seller_check = $pdo->prepare("SELECT * FROM seller_registrations WHERE id = ?");
        $seller_check->execute([$seller_id]);
        $seller_exists = $seller_check->rowCount() > 0;
        
        echo "Seller exists in seller_registrations: " . ($seller_exists ? "Yes" : "No") . "<br>";
        
        if ($seller_exists) {
            $seller_data = $seller_check->fetch();
            echo "<h3>Seller Registration Data:</h3>";
            echo "<pre>";
            print_r($seller_data);
            echo "</pre>";
        }
    }
    
    // Check if seller exists in seller_profiles
    if ($seller_id) {
        $profile_check = $pdo->prepare("SELECT * FROM seller_profiles WHERE seller_registration_id = ?");
        $profile_check->execute([$seller_id]);
        $profile_exists = $profile_check->rowCount() > 0;
        
        echo "Seller exists in seller_profiles: " . ($profile_exists ? "Yes" : "No") . "<br>";
        
        if ($profile_exists) {
            $profile_data = $profile_check->fetch();
            echo "<h3>Seller Profile Data:</h3>";
            echo "<pre>";
            print_r($profile_data);
            echo "</pre>";
        }
    }
    
    // Check if products table has foreign key constraint
    $check_fk = $pdo->query("
        SELECT * FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = 'kisan_kart'
        AND TABLE_NAME = 'products'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ");
    
    $fk_exists = $check_fk->rowCount() > 0;
    echo "Foreign key constraints on products table: " . ($fk_exists ? "Yes" : "No") . "<br>";
    
    if ($fk_exists) {
        $fk_data = $check_fk->fetchAll();
        echo "<pre>";
        print_r($fk_data);
        echo "</pre>";
    }
    
    // Check if there are any products with the current seller_id
    if ($seller_id) {
        $products_check = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
        $products_check->execute([$seller_id]);
        $products_count = $products_check->fetch()['count'];
        
        echo "Number of products with seller_id = $seller_id: $products_count<br>";
    }
    
    // Check session data
    echo "<h3>Session Data:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<p>Now you can go back to the <a href='frontend/seller/products.php'>Seller Products page</a> to try adding a product again.</p>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
