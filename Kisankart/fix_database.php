<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Database Fix Script</h1>";

if ($db) {
    echo "<p style='color:green;'>Database connection successful!</p>";

    try {
        // Begin transaction
        $db->beginTransaction();

        // Check if cart table exists
        $query = "SHOW TABLES LIKE 'cart'";
        $stmt = $db->query($query);

        if ($stmt->rowCount() > 0) {
            echo "<p>Cart table exists. Checking structure...</p>";

            // Check if customer_id column exists
            $query = "SHOW COLUMNS FROM cart LIKE 'customer_id'";
            $stmt = $db->query($query);

            if ($stmt->rowCount() > 0) {
                echo "<p>customer_id column exists in cart table.</p>";
            } else {
                echo "<p style='color:orange;'>customer_id column does not exist in cart table. Creating it...</p>";

                // Add customer_id column
                $query = "ALTER TABLE cart ADD COLUMN customer_id INT(11) NOT NULL AFTER id";
                $db->exec($query);

                echo "<p style='color:green;'>customer_id column added to cart table.</p>";
            }
        } else {
            echo "<p style='color:orange;'>Cart table does not exist. Creating it...</p>";

            // Create cart table
            $query = "CREATE TABLE `cart` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `product_id` int(11) NOT NULL,
                `quantity` int(11) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `customer_id` (`customer_id`),
                KEY `product_id` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $db->exec($query);

            echo "<p style='color:green;'>Cart table created.</p>";
        }

        // Check if addresses table exists
        $query = "SHOW TABLES LIKE 'addresses'";
        $stmt = $db->query($query);

        if ($stmt->rowCount() > 0) {
            echo "<p>Addresses table exists. Checking structure...</p>";

            // Check if user_id column exists
            $query = "SHOW COLUMNS FROM addresses LIKE 'user_id'";
            $stmt = $db->query($query);

            if ($stmt->rowCount() > 0) {
                echo "<p>user_id column exists in addresses table.</p>";
            } else {
                echo "<p style='color:orange;'>user_id column does not exist in addresses table. Creating it...</p>";

                // Add user_id column
                $query = "ALTER TABLE addresses ADD COLUMN user_id INT(11) NOT NULL AFTER id";
                $db->exec($query);

                echo "<p style='color:green;'>user_id column added to addresses table.</p>";
            }
        } else {
            echo "<p style='color:orange;'>Addresses table does not exist. Creating it...</p>";

            // Create addresses table
            $query = "CREATE TABLE `addresses` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `name` varchar(100) NOT NULL,
                `phone` varchar(20) NOT NULL,
                `street` text NOT NULL,
                `city` varchar(100) NOT NULL,
                `state` varchar(100) NOT NULL,
                `postal_code` varchar(20) NOT NULL,
                `is_default` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $db->exec($query);

            echo "<p style='color:green;'>Addresses table created.</p>";
        }

        // Check if orders table exists
        $query = "SHOW TABLES LIKE 'orders'";
        $stmt = $db->query($query);

        if ($stmt->rowCount() > 0) {
            echo "<p>Orders table exists. Checking structure...</p>";

            // Check if customer_id column exists
            $query = "SHOW COLUMNS FROM orders LIKE 'customer_id'";
            $stmt = $db->query($query);

            if ($stmt->rowCount() > 0) {
                echo "<p>customer_id column exists in orders table.</p>";
            } else {
                echo "<p style='color:orange;'>customer_id column does not exist in orders table. Creating it...</p>";

                // Add customer_id column
                $query = "ALTER TABLE orders ADD COLUMN customer_id INT(11) NOT NULL AFTER id";
                $db->exec($query);

                echo "<p style='color:green;'>customer_id column added to orders table.</p>";
            }
        } else {
            echo "<p style='color:orange;'>Orders table does not exist. Creating it...</p>";

            // Create orders table
            $query = "CREATE TABLE `orders` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
                `total_amount` decimal(10,2) NOT NULL,
                `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
                `payment_method` varchar(50) DEFAULT NULL,
                `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
                `shipping_address` text,
                `shipping_city` varchar(100) DEFAULT NULL,
                `shipping_state` varchar(100) DEFAULT NULL,
                `shipping_postal_code` varchar(20) DEFAULT NULL,
                `delivery_slot` varchar(50) DEFAULT NULL,
                `tracking_number` varchar(100) DEFAULT NULL,
                `notes` text,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `customer_id` (`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $db->exec($query);

            echo "<p style='color:green;'>Orders table created.</p>";
        }

        // Commit transaction
        $db->commit();

        echo "<p style='color:green;'>Database fix completed successfully!</p>";
    } catch (PDOException $e) {
        // Rollback transaction on error if there is an active transaction
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "<p style='color:red;'>Error fixing database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>Database connection failed!</p>";
}

echo "<h2>Actions</h2>";
echo "<p><a href='check_tables.php'>Check Tables</a></p>";
echo "<p><a href='session_debug.php'>Check Session</a></p>";
echo "<p><a href='test_buy_now.php'>Test Buy Now</a></p>";
echo "<p><a href='frontend/products.php'>Go to Products Page</a></p>";
?>
