<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h2>Kisan Kart Database Setup</h2>";

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

    // Step 1: Create categories table if it doesn't exist
    echo "<h3>Step 1: Setting up Categories Table</h3>";
    $check_categories = $db->query("SHOW TABLES LIKE 'categories'");
    if ($check_categories->rowCount() === 0) {
        echo "<p>Categories table does not exist. Creating it now...</p>";

        try {
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
            echo "<p style='color:green'>Categories table created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating categories table: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:green'>Categories table already exists.</p>";
    }

    // Check if categories table has data
    $count_categories = $db->query("SELECT COUNT(*) as count FROM categories");
    $category_count = $count_categories->fetch(PDO::FETCH_ASSOC);

    if ($category_count['count'] == 0) {
        echo "<p>No categories found. Adding sample categories...</p>";

        try {
            $sample_categories_sql = "INSERT INTO `categories`
                (`name`, `description`, `is_active`)
                VALUES
                ('Vegetables', 'Fresh vegetables from local farms', 1),
                ('Fruits', 'Fresh and seasonal fruits', 1),
                ('Grains', 'Rice, wheat and other grains', 1),
                ('Dairy', 'Milk, cheese and other dairy products', 1),
                ('Spices', 'Fresh and dried spices', 1),
                ('Organic', 'Certified organic products', 1)";

            $db->exec($sample_categories_sql);
            echo "<p style='color:green'>Sample categories added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error adding sample categories: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Categories table already has " . $category_count['count'] . " records.</p>";
    }

    // Display categories
    $categories_query = "SELECT id, name FROM categories";
    $categories_stmt = $db->query($categories_query);

    echo "<h4>Available Categories:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th></tr>";

    while ($category = $categories_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $category['id'] . "</td>";
        echo "<td>" . $category['name'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Step 2: Create seller_registrations table if it doesn't exist
    echo "<h3>Step 2: Setting up Seller Registrations Table</h3>";
    $check_seller_registrations = $db->query("SHOW TABLES LIKE 'seller_registrations'");
    if ($check_seller_registrations->rowCount() === 0) {
        echo "<p>Seller registrations table does not exist. Creating it now...</p>";

        try {
            $create_seller_registrations_sql = "CREATE TABLE `seller_registrations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `first_name` varchar(100) NOT NULL,
                `last_name` varchar(100) NOT NULL,
                `email` varchar(100) NOT NULL,
                `phone` varchar(20) NOT NULL,
                `password` varchar(255) NOT NULL,
                `address` text,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `pincode` varchar(20) DEFAULT NULL,
                `date_of_birth` date DEFAULT NULL,
                `id_type` varchar(50) DEFAULT NULL,
                `id_number` varchar(50) DEFAULT NULL,
                `id_front_image` varchar(255) DEFAULT NULL,
                `id_back_image` varchar(255) DEFAULT NULL,
                `bank_name` varchar(100) DEFAULT NULL,
                `account_number` varchar(50) DEFAULT NULL,
                `ifsc_code` varchar(20) DEFAULT NULL,
                `product_categories` text,
                `status` enum('pending','approved','rejected') DEFAULT 'pending',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`),
                UNIQUE KEY `phone` (`phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $db->exec($create_seller_registrations_sql);
            echo "<p style='color:green'>Seller registrations table created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating seller registrations table: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:green'>Seller registrations table already exists.</p>";
    }

    // Check if seller_registrations table has data
    $count_sellers = $db->query("SELECT COUNT(*) as count FROM seller_registrations");
    $seller_count = $count_sellers->fetch(PDO::FETCH_ASSOC);

    if ($seller_count['count'] == 0) {
        echo "<p>No sellers found. Adding a sample seller...</p>";

        try {
            $sample_seller_sql = "INSERT INTO `seller_registrations`
                (`first_name`, `last_name`, `email`, `phone`, `password`, `address`, `city`, `state`, `pincode`, `product_categories`, `status`)
                VALUES
                ('Farmer', 'Kumar', 'farmer@example.com', '9876543210', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'Sample Address', 'Sample City', 'Sample State', '123456', '[\"Vegetables\",\"Fruits\"]', 'approved')";

            $db->exec($sample_seller_sql);
            echo "<p style='color:green'>Sample seller added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error adding sample seller: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Seller registrations table already has " . $seller_count['count'] . " records.</p>";
    }

    // Display sellers
    $sellers_query = "SELECT id, first_name, last_name, email FROM seller_registrations";
    $sellers_stmt = $db->query($sellers_query);

    echo "<h4>Available Sellers:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";

    while ($seller = $sellers_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $seller['id'] . "</td>";
        echo "<td>" . $seller['first_name'] . " " . $seller['last_name'] . "</td>";
        echo "<td>" . $seller['email'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Step 3: Create seller_profiles table if it doesn't exist
    echo "<h3>Step 3: Setting up Seller Profiles Table</h3>";
    $check_seller_profiles = $db->query("SHOW TABLES LIKE 'seller_profiles'");
    if ($check_seller_profiles->rowCount() === 0) {
        echo "<p>Seller profiles table does not exist. Creating it now...</p>";

        try {
            $create_seller_profiles_sql = "CREATE TABLE `seller_profiles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `seller_registration_id` int(11) NOT NULL,
                `shop_name` varchar(255) DEFAULT NULL,
                `description` text,
                `logo_url` varchar(255) DEFAULT NULL,
                `banner_url` varchar(255) DEFAULT NULL,
                `rating` decimal(3,2) DEFAULT 0.00,
                `total_sales` int(11) DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `seller_registration_id` (`seller_registration_id`),
                CONSTRAINT `seller_profiles_ibfk_1` FOREIGN KEY (`seller_registration_id`) REFERENCES `seller_registrations` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $db->exec($create_seller_profiles_sql);
            echo "<p style='color:green'>Seller profiles table created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating seller profiles table: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:green'>Seller profiles table already exists.</p>";
    }

    // Check if seller_profiles table has data
    $count_profiles = $db->query("SELECT COUNT(*) as count FROM seller_profiles");
    $profile_count = $count_profiles->fetch(PDO::FETCH_ASSOC);

    if ($profile_count['count'] == 0) {
        echo "<p>No seller profiles found. Adding a sample profile...</p>";

        try {
            // Get the first seller registration ID
            $seller_query = "SELECT id FROM seller_registrations LIMIT 1";
            $seller_stmt = $db->query($seller_query);
            $seller = $seller_stmt->fetch(PDO::FETCH_ASSOC);

            if ($seller) {
                $sample_profile_sql = "INSERT INTO `seller_profiles`
                    (`seller_registration_id`, `shop_name`, `description`)
                    VALUES
                    (" . $seller['id'] . ", 'Farmer\'s Fresh Produce', 'We sell fresh vegetables and fruits directly from our farm.')";

                $db->exec($sample_profile_sql);
                echo "<p style='color:green'>Sample seller profile added successfully!</p>";
            } else {
                echo "<p style='color:red'>No seller registrations found to create a profile.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error adding sample seller profile: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Seller profiles table already has " . $profile_count['count'] . " records.</p>";
    }

    // Step 4: Create products table if it doesn't exist
    echo "<h3>Step 4: Setting up Products Table</h3>";
    $check_products = $db->query("SHOW TABLES LIKE 'products'");
    if ($check_products->rowCount() === 0) {
        echo "<p>Products table does not exist. Creating it now...</p>";

        try {
            $create_products_sql = "CREATE TABLE `products` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `description` text,
                `price` decimal(10,2) NOT NULL,
                `discount_price` decimal(10,2) DEFAULT NULL,
                `category_id` int(11) DEFAULT NULL,
                `seller_id` int(11) DEFAULT NULL,
                `farmer_id` int(11) DEFAULT NULL,
                `stock_quantity` int(11) DEFAULT 0,
                `image_url` varchar(255) DEFAULT NULL,
                `additional_images` TEXT,
                `is_featured` tinyint(1) DEFAULT 0,
                `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `category_id` (`category_id`),
                KEY `seller_id` (`seller_id`),
                KEY `farmer_id` (`farmer_id`),
                CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_farmer_id` FOREIGN KEY (`farmer_id`) REFERENCES `seller_registrations` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $db->exec($create_products_sql);
            echo "<p style='color:green'>Products table created successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating products table: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:green'>Products table already exists.</p>";

        // Check if foreign key constraints exist
        $check_fk_query = "SELECT * FROM information_schema.TABLE_CONSTRAINTS
                          WHERE CONSTRAINT_SCHEMA = DATABASE()
                          AND TABLE_NAME = 'products'
                          AND CONSTRAINT_NAME = 'products_ibfk_2'";
        $check_fk_stmt = $db->query($check_fk_query);

        if ($check_fk_stmt->rowCount() > 0) {
            echo "<p>Removing foreign key constraint on seller_id...</p>";

            try {
                $db->exec("ALTER TABLE products DROP FOREIGN KEY products_ibfk_2");
                echo "<p style='color:green'>Foreign key constraint removed successfully!</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error removing foreign key constraint: " . $e->getMessage() . "</p>";
            }
        }
    }

    // Step 5: Set up uploads directory
    echo "<h3>Step 5: Setting up Uploads Directory</h3>";

    // Check if uploads directory exists
    if (!is_dir("uploads")) {
        echo "<p>Creating uploads directory...</p>";
        if (mkdir("uploads", 0755)) {
            echo "<p style='color:green'>Uploads directory created successfully!</p>";
        } else {
            echo "<p style='color:red'>Failed to create uploads directory.</p>";
        }
    } else {
        echo "<p>Uploads directory already exists.</p>";
    }

    // Check if uploads/products directory exists
    if (!is_dir("uploads/products")) {
        echo "<p>Creating uploads/products directory...</p>";
        if (mkdir("uploads/products", 0755)) {
            echo "<p style='color:green'>Uploads/products directory created successfully!</p>";
        } else {
            echo "<p style='color:red'>Failed to create uploads/products directory.</p>";
        }
    } else {
        echo "<p>Uploads/products directory already exists.</p>";
    }

    echo "<h3>Database Setup Complete!</h3>";
    echo "<p>You can now <a href='add_sample_product.php'>add sample products</a>.</p>";
} else {
    echo "<p style='color:red'>Database connection failed!</p>";
}
?>
