<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h2>Database Connection Test</h2>";
if ($db) {
    echo "<p style='color:green'>Database connection successful!</p>";

    // Check tables
    echo "<h3>Tables in Database:</h3>";
    $tables_query = "SHOW TABLES";
    $tables_stmt = $db->query($tables_query);

    echo "<ul>";
    while ($row = $tables_stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";

    // Check products table
    echo "<h3>Products Table:</h3>";
    $check_products = $db->query("SHOW TABLES LIKE 'products'");
    if ($check_products->rowCount() > 0) {
        echo "<p>Products table exists.</p>";

        // Count products
        $count_query = "SELECT COUNT(*) as count FROM products";
        $count_stmt = $db->query($count_query);
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC);

        echo "<p>Number of products: " . $count['count'] . "</p>";

        // List products
        if ($count['count'] > 0) {
            echo "<h4>Product List:</h4>";
            $products_query = "SELECT id, name, price, discount_price, category_id, seller_id, stock_quantity FROM products";
            $products_stmt = $db->query($products_query);

            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Discount Price</th><th>Category ID</th><th>Seller ID</th><th>Stock</th></tr>";

            while ($product = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>" . $product['price'] . "</td>";
                echo "<td>" . $product['discount_price'] . "</td>";
                echo "<td>" . $product['category_id'] . "</td>";
                echo "<td>" . $product['seller_id'] . "</td>";
                echo "<td>" . $product['stock_quantity'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No products found in the database.</p>";

            // Create sample products
            echo "<h4>Creating Sample Products:</h4>";
            try {
                $sample_products_sql = "INSERT INTO `products`
                    (`name`, `description`, `price`, `discount_price`, `stock_quantity`, `is_featured`, `status`)
                    VALUES
                    ('Organic Tomatoes', 'Fresh organic tomatoes from local farms', 120.00, 99.00, 50, 1, 'active'),
                    ('Premium Rice', 'High-quality basmati rice', 350.00, 320.00, 100, 1, 'active'),
                    ('Fresh Apples', 'Crisp and juicy apples', 180.00, 150.00, 75, 1, 'active')";

                $db->exec($sample_products_sql);
                echo "<p style='color:green'>Sample products created successfully!</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error creating sample products: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p>Products table does not exist.</p>";

        // Create products table
        echo "<h4>Creating Products Table:</h4>";
        try {
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
            echo "<p style='color:green'>Products table created successfully!</p>";

            // Insert sample products
            $sample_products_sql = "INSERT INTO `products`
                (`name`, `description`, `price`, `discount_price`, `stock_quantity`, `is_featured`, `status`)
                VALUES
                ('Organic Tomatoes', 'Fresh organic tomatoes from local farms', 120.00, 99.00, 50, 1, 'active'),
                ('Premium Rice', 'High-quality basmati rice', 350.00, 320.00, 100, 1, 'active'),
                ('Fresh Apples', 'Crisp and juicy apples', 180.00, 150.00, 75, 1, 'active')";

            $db->exec($sample_products_sql);
            echo "<p style='color:green'>Sample products created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating products table: " . $e->getMessage() . "</p>";
        }
    }

    // Check categories table
    echo "<h3>Categories Table:</h3>";
    $check_categories = $db->query("SHOW TABLES LIKE 'categories'");
    if ($check_categories->rowCount() > 0) {
        echo "<p>Categories table exists.</p>";

        // Count categories
        $count_query = "SELECT COUNT(*) as count FROM categories";
        $count_stmt = $db->query($count_query);
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC);

        echo "<p>Number of categories: " . $count['count'] . "</p>";

        if ($count['count'] == 0) {
            // Create sample categories
            echo "<h4>Creating Sample Categories:</h4>";
            try {
                $sample_categories_sql = "INSERT INTO `categories`
                    (`name`, `description`, `is_active`)
                    VALUES
                    ('Vegetables', 'Fresh vegetables from local farms', 1),
                    ('Fruits', 'Fresh and seasonal fruits', 1),
                    ('Grains', 'Rice, wheat and other grains', 1),
                    ('Dairy', 'Milk, cheese and other dairy products', 1)";

                $db->exec($sample_categories_sql);
                echo "<p style='color:green'>Sample categories created successfully!</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error creating sample categories: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p>Categories table does not exist.</p>";

        // Create categories table
        echo "<h4>Creating Categories Table:</h4>";
        try {
            $create_table_sql = "CREATE TABLE `categories` (
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

            $db->exec($create_table_sql);
            echo "<p style='color:green'>Categories table created successfully!</p>";

            // Insert sample categories
            $sample_categories_sql = "INSERT INTO `categories`
                (`name`, `description`, `is_active`)
                VALUES
                ('Vegetables', 'Fresh vegetables from local farms', 1),
                ('Fruits', 'Fresh and seasonal fruits', 1),
                ('Grains', 'Rice, wheat and other grains', 1),
                ('Dairy', 'Milk, cheese and other dairy products', 1)";

            $db->exec($sample_categories_sql);
            echo "<p style='color:green'>Sample categories created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating categories table: " . $e->getMessage() . "</p>";
        }
    }

    // Update product categories
    echo "<h3>Updating Product Categories:</h3>";
    try {
        $update_query = "UPDATE products SET category_id =
                        CASE
                            WHEN name LIKE '%Tomato%' THEN (SELECT id FROM categories WHERE name = 'Vegetables' LIMIT 1)
                            WHEN name LIKE '%Rice%' THEN (SELECT id FROM categories WHERE name = 'Grains' LIMIT 1)
                            WHEN name LIKE '%Apple%' THEN (SELECT id FROM categories WHERE name = 'Fruits' LIMIT 1)
                            ELSE NULL
                        END
                        WHERE category_id IS NULL";
        $db->exec($update_query);
        echo "<p style='color:green'>Product categories updated successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error updating product categories: " . $e->getMessage() . "</p>";
    }

} else {
    echo "<p style='color:red'>Database connection failed!</p>";
}
?>

<p><a href="frontend/product_details.php?id=1">View Product 1</a></p>
<p><a href="frontend/product_details.php?id=2">View Product 2</a></p>
<p><a href="frontend/product_details.php?id=3">View Product 3</a></p>
