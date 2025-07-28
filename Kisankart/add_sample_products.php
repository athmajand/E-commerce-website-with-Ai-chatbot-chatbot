<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection is successful
if (!$db) {
    die("Database connection failed. Please check your database settings.");
}

echo "<h2>Adding Sample Products to Database</h2>";

// Check if products table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'products'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<p style='color: red;'>✗ Products table does not exist. Creating it...</p>";
        
        // Create products table
        $create_table_sql = "CREATE TABLE `products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `price` decimal(10,2) NOT NULL,
            `discount_price` decimal(10,2) DEFAULT NULL,
            `category_id` int(11) DEFAULT NULL,
            `seller_id` int(11) DEFAULT NULL,
            `stock_quantity` int(11) DEFAULT 0,
            `image_url` varchar(255) DEFAULT NULL,
            `additional_images` TEXT,
            `is_featured` tinyint(1) DEFAULT 0,
            `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `category_id` (`category_id`),
            KEY `seller_id` (`seller_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($create_table_sql);
        echo "<p style='color: green;'>✓ Products table created successfully</p>";
    } else {
        echo "<p style='color: green;'>✓ Products table exists</p>";
    }
    
    // Check if categories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'categories'");
    $categories_table_exists = $stmt->rowCount() > 0;
    
    if (!$categories_table_exists) {
        echo "<p style='color: red;'>✗ Categories table does not exist. Creating it...</p>";
        
        // Create categories table
        $create_categories_sql = "CREATE TABLE `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text,
            `parent_id` int(11) DEFAULT NULL,
            `image_url` varchar(255) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($create_categories_sql);
        echo "<p style='color: green;'>✓ Categories table created successfully</p>";
        
        // Insert sample categories
        $categories_sql = "INSERT INTO `categories` (`name`, `description`) VALUES 
            ('Vegetables', 'Fresh farm vegetables'),
            ('Fruits', 'Seasonal fruits'),
            ('Grains', 'Various grains and pulses'),
            ('Dairy', 'Fresh dairy products'),
            ('Spices', 'Aromatic spices'),
            ('Organic', 'Organic products'),
            ('Other', 'Other farm products')";
        
        $db->exec($categories_sql);
        echo "<p style='color: green;'>✓ Sample categories added successfully</p>";
    } else {
        echo "<p style='color: green;'>✓ Categories table exists</p>";
    }
    
    // Check if there are any products
    $stmt = $db->query("SELECT COUNT(*) as count FROM products");
    $product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($product_count == 0) {
        echo "<p style='color: orange;'>⚠ No products found. Adding sample products...</p>";
        
        // Get category IDs
        $stmt = $db->query("SELECT id, name FROM categories");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $category_map = [];
        foreach ($categories as $cat) {
            $category_map[strtolower($cat['name'])] = $cat['id'];
        }
        
        // Sample products data
        $sample_products = [
            [
                'name' => 'Fresh Tomatoes',
                'description' => '1 Kg Fresh Tomatoes – Juicy, Farm‑Fresh & Flavorful. Experience the essence of farm-fresh tomatoes, hand-picked at peak ripeness for maximum flavor and nutrition.',
                'price' => 35.00,
                'discount_price' => 30.00,
                'category_id' => $category_map['vegetables'] ?? 1,
                'stock_quantity' => 100,
                'image_url' => 'uploads/products/1750485945_Tomato.webp',
                'is_featured' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Organic Spinach',
                'description' => '1 Kg Fresh Spinach – Nutritious & Naturally Green. Bring home farm-fresh, tender spinach leaves packed with essential vitamins and minerals.',
                'price' => 90.00,
                'discount_price' => 80.00,
                'category_id' => $category_map['vegetables'] ?? 1,
                'stock_quantity' => 75,
                'image_url' => 'uploads/products/1750515445_Spinach.jpg',
                'is_featured' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Fresh Garlic',
                'description' => '1 Kg Fresh Garlic – Nature\'s Flavor Powerhouse. Add bold, aromatic flavor and countless health benefits to your cooking with our premium garlic.',
                'price' => 80.00,
                'discount_price' => 60.00,
                'category_id' => $category_map['vegetables'] ?? 1,
                'stock_quantity' => 50,
                'image_url' => 'uploads/products/1750483759_Garlic.jpg',
                'is_featured' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Fresh Apples',
                'description' => '1 Kg Apples are hand‑picked at peak ripeness. Enjoy a satisfying crunch and rich, natural sweetness in every bite.',
                'price' => 160.00,
                'discount_price' => 140.00,
                'category_id' => $category_map['fruits'] ?? 2,
                'stock_quantity' => 60,
                'image_url' => 'uploads/products/1750442556_Apple.webp',
                'is_featured' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Wheat Flour',
                'description' => '1 Kg Wheat Flour (Atta) – Freshly Milled & Pure. Bring home soft, nutritious, and freshly milled wheat flour for your daily bread needs.',
                'price' => 70.00,
                'discount_price' => 60.00,
                'category_id' => $category_map['grains'] ?? 3,
                'stock_quantity' => 200,
                'image_url' => 'uploads/products/1750486097_wheat flour 1.jpg',
                'is_featured' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Black Pepper',
                'description' => '1Kg Black Pepper is clean, well-dried, and ready to fuel your kitchen or livestock feed. Premium quality black pepper with intense flavor.',
                'price' => 800.00,
                'discount_price' => 750.00,
                'category_id' => $category_map['spices'] ?? 5,
                'stock_quantity' => 25,
                'image_url' => 'uploads/products/1750443213_Black pepper 1.webp',
                'is_featured' => 1,
                'status' => 'active'
            ]
        ];
        
        // Insert sample products
        $insert_sql = "INSERT INTO `products` 
            (`name`, `description`, `price`, `discount_price`, `category_id`, `stock_quantity`, `image_url`, `is_featured`, `status`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($insert_sql);
        
        foreach ($sample_products as $product) {
            $stmt->execute([
                $product['name'],
                $product['description'],
                $product['price'],
                $product['discount_price'],
                $product['category_id'],
                $product['stock_quantity'],
                $product['image_url'],
                $product['is_featured'],
                $product['status']
            ]);
        }
        
        echo "<p style='color: green;'>✓ Sample products added successfully</p>";
        
        // Verify products were added
        $stmt = $db->query("SELECT COUNT(*) as count FROM products");
        $new_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total products in database: <strong>{$new_count}</strong></p>";
        
        // Check featured products
        $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE is_featured = 1");
        $featured_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Featured products: <strong>{$featured_count}</strong></p>";
        
    } else {
        echo "<p style='color: green;'>✓ Found {$product_count} existing products</p>";
        
        // Check featured products
        $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE is_featured = 1");
        $featured_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Featured products: <strong>{$featured_count}</strong></p>";
        
        if ($featured_count == 0) {
            echo "<p style='color: orange;'>⚠ No featured products found. Making some products featured...</p>";
            
            // Make first 6 products featured
            $update_sql = "UPDATE products SET is_featured = 1 WHERE id IN (SELECT id FROM (SELECT id FROM products ORDER BY id ASC LIMIT 6) as temp)";
            $db->exec($update_sql);
            
            echo "<p style='color: green;'>✓ Made 6 products featured</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Test Results</h3>";
    
    // Test the featured products query
    $stmt = $db->query("SELECT id, name, price, is_featured, status FROM products WHERE is_featured = 1 AND status = 'active' ORDER BY id DESC LIMIT 6");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($featured_products)) {
        echo "<p style='color: green;'>✓ Featured products query successful</p>";
        echo "<ul>";
        foreach ($featured_products as $product) {
            echo "<li>ID: {$product['id']} - Name: {$product['name']} - Price: ₹{$product['price']} - Featured: {$product['is_featured']} - Status: {$product['status']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ No featured products found</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='frontend/index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>
