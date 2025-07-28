<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    // Database credentials
    private $host = "localhost";
    private $db_name = "kisan_kart";
    private $username = "root";
    private $password = "";
    public $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // Try different connection methods
            $connected = false;
            $lastError = "";

            // Method 1: Standard connection with buffered queries
            try {
                // Enable buffered queries by default to prevent "Cannot execute queries while other unbuffered queries are active" errors
                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                );

                // Check if the buffered query attribute is available
                if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                    $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
                }
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password, $options);
                $connected = true;
            } catch (PDOException $e) {
                $lastError = $e->getMessage();
                // Failed, try next method
            }

            // Method 2: Connect without database first
            if (!$connected) {
                try {
                    // Enable buffered queries
                    $options = array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    );

                    // Check if the buffered query attribute is available
                    if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
                    }
                    $serverConn = new PDO("mysql:host=" . $this->host, $this->username, $this->password, $options);

                    // Check if database exists
                    $stmt = $serverConn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->db_name}'");
                    $databaseExists = $stmt->rowCount() > 0;

                    if (!$databaseExists) {
                        // Create database if it doesn't exist
                        $serverConn->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}`");
                        error_log("Created database {$this->db_name}");
                    }

                    // Now connect to the database with buffered queries
                    $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password, $options);
                    $connected = true;
                } catch (PDOException $e) {
                    $lastError = $e->getMessage();
                    // Failed, try next method
                }
            }

            // Method 3: Try with explicit port
            if (!$connected) {
                try {
                    // Enable buffered queries
                    $options = array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    );

                    // Check if the buffered query attribute is available
                    if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
                    }
                    $this->conn = new PDO("mysql:host=" . $this->host . ";port=3306;dbname=" . $this->db_name, $this->username, $this->password, $options);
                    $connected = true;
                } catch (PDOException $e) {
                    $lastError = $e->getMessage();
                    // Failed, try next method
                }
            }

            // Method 4: Try with socket (Unix/Linux only)
            if (!$connected && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                try {
                    // Enable buffered queries
                    $options = array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    );

                    // Check if the buffered query attribute is available
                    if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
                    }
                    $this->conn = new PDO("mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=" . $this->db_name, $this->username, $this->password, $options);
                    $connected = true;
                } catch (PDOException $e) {
                    $lastError = $e->getMessage();
                    // Failed, all methods exhausted
                }
            }

            // If we connected successfully
            if ($connected && $this->conn) {
                // Set connection attributes
                $this->conn->exec("set names utf8");
                // PDO::ATTR_ERRMODE is already set in the connection options

                // Ensure buffered queries are enabled if the attribute is available
                if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                    $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                }

                // Check if customer_registrations table exists
                $stmt = $this->conn->query("SHOW TABLES LIKE 'customer_registrations'");
                $tableExists = $stmt->rowCount() > 0;

                if (!$tableExists) {
                    // Create the customer_registrations table
                    $sql = "CREATE TABLE `customer_registrations` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `first_name` varchar(100) NOT NULL,
                      `last_name` varchar(100) NOT NULL,
                      `email` varchar(255) NOT NULL,
                      `phone` varchar(20) NOT NULL,
                      `password` varchar(255) NOT NULL,
                      `address` text,
                      `city` varchar(100) DEFAULT NULL,
                      `state` varchar(100) DEFAULT NULL,
                      `postal_code` varchar(20) DEFAULT NULL,
                      `registration_date` datetime DEFAULT CURRENT_TIMESTAMP,
                      `status` enum('pending','approved','rejected') DEFAULT 'pending',
                      `verification_token` varchar(255) DEFAULT NULL,
                      `is_verified` tinyint(1) DEFAULT '0',
                      `last_login` datetime DEFAULT NULL,
                      `notes` text,
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `email` (`email`),
                      UNIQUE KEY `phone` (`phone`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                    $this->conn->exec($sql);
                    error_log("Created table customer_registrations");
                }

                // Check if seller_registrations table exists
                $stmt = $this->conn->query("SHOW TABLES LIKE 'seller_registrations'");
                $sellerTableExists = $stmt->rowCount() > 0;

                if (!$sellerTableExists) {
                    // Create the seller_registrations table
                    $sql = "CREATE TABLE `seller_registrations` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `first_name` varchar(100) NOT NULL,
                      `last_name` varchar(100) NOT NULL,
                      `date_of_birth` date DEFAULT NULL,
                      `email` varchar(255) NOT NULL,
                      `phone` varchar(20) NOT NULL,
                      `password` varchar(255) NOT NULL,
                      `business_name` varchar(100) NOT NULL,
                      `business_description` text,
                      `business_logo` varchar(255) DEFAULT NULL,
                      `business_address` text NOT NULL,
                      `business_country` varchar(50) DEFAULT NULL,
                      `business_state` varchar(100) DEFAULT NULL,
                      `business_city` varchar(100) DEFAULT NULL,
                      `business_postal_code` varchar(20) DEFAULT NULL,
                      `gst_number` varchar(50) DEFAULT NULL,
                      `pan_number` varchar(50) DEFAULT NULL,
                      `id_type` varchar(50) DEFAULT NULL,
                      `id_document_path` varchar(255) DEFAULT NULL,
                      `tax_classification` varchar(50) DEFAULT NULL,
                      `tax_document_path` varchar(255) DEFAULT NULL,
                      `bank_account_details` text,
                      `bank_account_number` varchar(50) DEFAULT NULL,
                      `account_holder_name` varchar(100) DEFAULT NULL,
                      `ifsc_code` varchar(20) DEFAULT NULL,
                      `bank_document_path` varchar(255) DEFAULT NULL,
                      `store_display_name` varchar(100) DEFAULT NULL,
                      `product_categories` text,
                      `marketplace` varchar(10) DEFAULT NULL,
                      `store_logo_path` varchar(255) DEFAULT NULL,
                      `verification_token` varchar(255) DEFAULT NULL,
                      `is_verified` tinyint(1) DEFAULT '0',
                      `status` enum('pending','approved','rejected') DEFAULT 'pending',
                      `last_login` datetime DEFAULT NULL,
                      `notes` text,
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `email` (`email`),
                      UNIQUE KEY `phone` (`phone`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                    $this->conn->exec($sql);
                    error_log("Created table seller_registrations");
                }

                // Check if orders table exists
                $stmt = $this->conn->query("SHOW TABLES LIKE 'orders'");
                $ordersTableExists = $stmt->rowCount() > 0;

                if (!$ordersTableExists) {
                    // Create the orders table
                    $sql = "CREATE TABLE `orders` (
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
                      `tracking_number` varchar(100) DEFAULT NULL,
                      `notes` text,
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `customer_id` (`customer_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                    $this->conn->exec($sql);
                    error_log("Created table orders");

                    // Create a sample order for testing
                    if (isset($this->conn)) {
                        try {
                            // Check if there are any customer registrations
                            $stmt = $this->conn->query("SELECT id FROM customer_registrations LIMIT 1");
                            if ($stmt->rowCount() > 0) {
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $customer_id = $row['id'];

                                // Insert a sample order
                                $sample_order_sql = "INSERT INTO `orders`
                                    (`customer_id`, `total_amount`, `status`, `payment_method`, `payment_status`,
                                    `shipping_address`, `shipping_city`, `shipping_state`, `shipping_postal_code`)
                                    VALUES
                                    ($customer_id, 1250.00, 'processing', 'Credit Card', 'completed',
                                    '123 Main St', 'Mumbai', 'Maharashtra', '400001')";

                                $this->conn->exec($sample_order_sql);
                                error_log("Created sample order for customer ID: $customer_id");
                            }
                        } catch (PDOException $e) {
                            error_log("Error creating sample order: " . $e->getMessage());
                        }
                    }
                }

                // Check if products table exists
                $stmt = $this->conn->query("SHOW TABLES LIKE 'products'");
                $productsTableExists = $stmt->rowCount() > 0;

                if (!$productsTableExists) {
                    // Create the products table
                    $sql = "CREATE TABLE `products` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) NOT NULL,
                      `description` text,
                      `price` decimal(10,2) NOT NULL,
                      `discount_price` decimal(10,2) DEFAULT NULL,
                      `category_id` int(11) DEFAULT NULL,
                      `seller_id` int(11) DEFAULT NULL,
                      `stock_quantity` int(11) DEFAULT 0,
                      `image_url` varchar(255) DEFAULT NULL,
                      `is_featured` tinyint(1) DEFAULT 0,
                      `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `category_id` (`category_id`),
                      KEY `seller_id` (`seller_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                    $this->conn->exec($sql);
                    error_log("Created table products");

                    // Insert sample products
                    try {
                        $sample_products_sql = "INSERT INTO `products`
                            (`name`, `description`, `price`, `discount_price`, `stock_quantity`, `is_featured`, `status`)
                            VALUES
                            ('Organic Tomatoes', 'Fresh organic tomatoes from local farms', 120.00, 99.00, 50, 1, 'active'),
                            ('Premium Rice', 'High-quality basmati rice', 350.00, 320.00, 100, 1, 'active'),
                            ('Fresh Apples', 'Crisp and juicy apples', 180.00, 150.00, 75, 1, 'active')";

                        $this->conn->exec($sample_products_sql);
                        error_log("Created sample products");
                    } catch (PDOException $e) {
                        error_log("Error creating sample products: " . $e->getMessage());
                    }
                }

                // Check if order_items table exists
                $stmt = $this->conn->query("SHOW TABLES LIKE 'order_items'");
                $orderItemsTableExists = $stmt->rowCount() > 0;

                if (!$orderItemsTableExists) {
                    // Create the order_items table
                    $sql = "CREATE TABLE `order_items` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `order_id` int(11) NOT NULL,
                      `product_id` int(11) NOT NULL,
                      `farmer_id` int(11) DEFAULT NULL,
                      `quantity` int(11) NOT NULL,
                      `price` decimal(10,2) NOT NULL,
                      `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `order_id` (`order_id`),
                      KEY `product_id` (`product_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                    $this->conn->exec($sql);
                    error_log("Created table order_items");

                    // Create sample order items if we have orders
                    try {
                        $stmt = $this->conn->query("SELECT id FROM orders LIMIT 1");
                        if ($stmt->rowCount() > 0) {
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            $order_id = $row['id'];

                            // Insert sample order items
                            $sample_items_sql = "INSERT INTO `order_items`
                                (`order_id`, `product_id`, `farmer_id`, `quantity`, `price`, `status`)
                                VALUES
                                ($order_id, 1, 1, 2, 450.00, 'processing'),
                                ($order_id, 2, 1, 1, 350.00, 'processing')";

                            $this->conn->exec($sample_items_sql);
                            error_log("Created sample order items for order ID: $order_id");
                        }
                    } catch (PDOException $e) {
                        error_log("Error creating sample order items: " . $e->getMessage());
                    }
                }
            } else {
                // All connection methods failed
                error_log("All database connection methods failed. Last error: " . $lastError);
            }
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            // Don't echo the error message directly to avoid exposing sensitive information
        }

        return $this->conn;
    }
}
?>
