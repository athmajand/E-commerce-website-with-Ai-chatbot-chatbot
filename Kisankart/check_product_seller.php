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
    
    echo "<h2>Product Seller ID Check</h2>";
    
    // Get seller ID from session
    $seller_id = $_SESSION['user_id'] ?? null;
    echo "Current seller_id from session: " . ($seller_id ?? 'Not set') . "<br>";
    
    // Check if seller exists in seller_profiles
    if ($seller_id) {
        $seller_check = $pdo->prepare("SELECT * FROM seller_profiles WHERE seller_id = ?");
        $seller_check->execute([$seller_id]);
        $seller_exists = $seller_check->rowCount() > 0;
        
        echo "Seller exists in seller_profiles: " . ($seller_exists ? "Yes" : "No") . "<br>";
        
        if ($seller_exists) {
            $seller_profile = $seller_check->fetch();
            $seller_profile_id = $seller_profile['id'];
            echo "Seller profile ID: $seller_profile_id<br>";
            
            // Check if there are any products with this seller_id
            $products_check = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
            $products_check->execute([$seller_profile_id]);
            $products_count = $products_check->fetch()['count'];
            
            echo "Number of products with seller_id = $seller_profile_id: $products_count<br>";
            
            if ($products_count > 0) {
                // List products
                $products_query = $pdo->prepare("
                    SELECT p.*, c.name as category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.seller_id = ?
                ");
                $products_query->execute([$seller_profile_id]);
                $products = $products_query->fetchAll();
                
                echo "<h3>Products:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Discount Price</th><th>Category</th><th>Status</th></tr>";
                
                foreach ($products as $product) {
                    echo "<tr>";
                    echo "<td>" . $product['id'] . "</td>";
                    echo "<td>" . $product['name'] . "</td>";
                    echo "<td>" . $product['price'] . "</td>";
                    echo "<td>" . ($product['discount_price'] ?? 'NULL') . "</td>";
                    echo "<td>" . ($product['category_name'] ?? 'Uncategorized') . "</td>";
                    echo "<td>" . $product['status'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        } else {
            // Create a seller profile
            echo "<p>Creating a seller profile for seller_id $seller_id...</p>";
            
            $insert_profile = $pdo->prepare("
                INSERT INTO seller_profiles 
                (seller_id, business_name, business_description, business_address, is_verified)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $insert_profile->execute([
                $seller_id,
                'Test Business',
                'A test business description',
                'Test Address',
                1
            ]);
            
            $seller_profile_id = $pdo->lastInsertId();
            echo "<p style='color:green'>Created seller profile with ID: $seller_profile_id</p>";
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
    echo "<br>Foreign key constraints on products table: " . ($fk_exists ? "Yes" : "No") . "<br>";
    
    if ($fk_exists) {
        $fk_data = $check_fk->fetchAll();
        echo "<pre>";
        print_r($fk_data);
        echo "</pre>";
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
