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
    
    if (!$table_exists) {
        // Create categories table
        $create_table_sql = "
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT,
            image_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($create_table_sql);
        echo "Categories table created successfully.<br>";
    }
    
    // Check if there are any categories
    $count_query = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $count = $count_query->fetch()['count'];
    echo "Number of categories: $count<br>";
    
    // If no categories, add some sample categories
    if ($count == 0) {
        $sample_categories = [
            ['name' => 'Vegetables', 'description' => 'Fresh vegetables directly from farms'],
            ['name' => 'Fruits', 'description' => 'Seasonal and exotic fruits'],
            ['name' => 'Grains', 'description' => 'Rice, wheat, and other grains'],
            ['name' => 'Dairy', 'description' => 'Milk, cheese, and other dairy products'],
            ['name' => 'Spices', 'description' => 'Organic spices and herbs'],
            ['name' => 'Organic', 'description' => 'Certified organic products']
        ];
        
        $insert_sql = "INSERT INTO categories (name, description) VALUES (:name, :description)";
        $stmt = $pdo->prepare($insert_sql);
        
        foreach ($sample_categories as $category) {
            $stmt->execute([
                ':name' => $category['name'],
                ':description' => $category['description']
            ]);
        }
        
        echo "Added " . count($sample_categories) . " sample categories.<br>";
    }
    
    // List all categories
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
    echo "<h3>Categories:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
    
    foreach ($categories as $category) {
        echo "<tr>";
        echo "<td>" . $category['id'] . "</td>";
        echo "<td>" . $category['name'] . "</td>";
        echo "<td>" . $category['description'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>Now you can go back to the <a href='frontend/seller/products.php'>Seller Products page</a> to see if the categories are showing up.</p>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
