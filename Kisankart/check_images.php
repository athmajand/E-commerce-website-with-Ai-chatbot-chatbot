<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h2>Product Database Check</h2>";

if ($db) {
    echo "<p style='color:green'>Database connection successful!</p>";

    // List all tables in the database
    echo "<h3>Tables in Database:</h3>";
    $tables_query = "SHOW TABLES";
    $tables_stmt = $db->query($tables_query);

    echo "<ul>";
    while ($row = $tables_stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";

    // Check if products table exists
    $check_products = $db->query("SHOW TABLES LIKE 'products'");
    if ($check_products->rowCount() > 0) {
        echo "<p style='color:green'>Products table exists.</p>";

        // Get all products
        $query = "SELECT id, name, image_url, additional_images FROM products";
        $stmt = $db->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "<h3>All Products:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Image URL</th><th>Additional Images</th></tr>";

            while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>" . $product['image_url'] . "</td>";
                echo "<td>" . $product['additional_images'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";

            // Create links to product details pages
            echo "<h3>Product Detail Links:</h3>";

            // Reset the statement to get products again
            $stmt = $db->prepare($query);
            $stmt->execute();

            echo "<ul>";
            while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<li><a href='frontend/product_details.php?id=" . $product['id'] . "' target='_blank'>" . $product['name'] . " (ID: " . $product['id'] . ")</a></li>";
            }
            echo "</ul>";
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
                echo "<p>Please refresh this page to see the new products.</p>";
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
            echo "<p>Please refresh this page to see the new products.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating products table: " . $e->getMessage() . "</p>";
        }
    }

    // Check uploads directory
    echo "<h3>Uploads Directory Check:</h3>";

    // Check if uploads directory exists
    if (!is_dir("uploads")) {
        echo "<p>Creating uploads directory...</p>";
        mkdir("uploads", 0755);
    }

    // Check if uploads/products directory exists
    if (!is_dir("uploads/products")) {
        echo "<p>Creating uploads/products directory...</p>";
        mkdir("uploads/products", 0755);
    }

    // List all files in uploads/products directory
    echo "<h4>Files in uploads/products directory:</h4>";
    if (is_dir("uploads/products")) {
        $files = scandir("uploads/products");
        if (count($files) > 2) { // More than . and ..
            echo "<ul>";
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    echo "<li>" . $file . "</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<p>No files found in uploads/products directory.</p>";
        }
    } else {
        echo "<p>uploads/products directory does not exist.</p>";
    }

    // Check server path
    echo "<h3>Server Path Information:</h3>";
    echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
    echo "<p><strong>Script Filename:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
    echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
} else {
    echo "<p style='color:red'>Database connection failed!</p>";
}
?>
