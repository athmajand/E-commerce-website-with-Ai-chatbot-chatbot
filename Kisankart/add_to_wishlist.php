<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers
header("Content-Type: application/json; charset=utf-8");

// Debug session information
error_log("Session data in add_to_wishlist.php: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(array("success" => false, "message" => "User not logged in"));
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Check if product_id is provided
if (!isset($data['product_id'])) {
    echo json_encode(array("success" => false, "message" => "Product ID is required"));
    exit;
}

// Validate product_id
$product_id = (int)$data['product_id'];

if ($product_id <= 0) {
    echo json_encode(array("success" => false, "message" => "Invalid product ID"));
    exit;
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(array("success" => false, "message" => "Database connection failed"));
    exit;
}

// Check if product exists
$product_query = "SELECT id, name FROM products WHERE id = ?";
$product_stmt = $db->prepare($product_query);
$product_stmt->bindParam(1, $product_id);
$product_stmt->execute();

if ($product_stmt->rowCount() === 0) {
    echo json_encode(array("success" => false, "message" => "Product not found"));
    exit;
}

$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

// Check if wishlist table exists
$check_table_query = "SHOW TABLES LIKE 'wishlist'";
$check_table_stmt = $db->prepare($check_table_query);
$check_table_stmt->execute();

if ($check_table_stmt->rowCount() === 0) {
    // Create wishlist table if it doesn't exist
    $create_table_query = "CREATE TABLE `wishlist` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `customer_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `added_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_customer_product` (`customer_id`, `product_id`),
        KEY `idx_wishlist_customer` (`customer_id`),
        KEY `idx_wishlist_product` (`product_id`),
        FOREIGN KEY (`customer_id`) REFERENCES `customer_registrations`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($create_table_query);
}

// Check if product is already in wishlist
$check_wishlist_query = "SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?";
$check_wishlist_stmt = $db->prepare($check_wishlist_query);
$check_wishlist_stmt->bindParam(1, $_SESSION['customer_id']);
$check_wishlist_stmt->bindParam(2, $product_id);
$check_wishlist_stmt->execute();

if ($check_wishlist_stmt->rowCount() > 0) {
    // Product is already in wishlist
    echo json_encode(array(
        "success" => true,
        "message" => "Product is already in your wishlist",
        "product" => array(
            "id" => $product['id'],
            "name" => $product['name']
        )
    ));
    exit;
}

// Add product to wishlist
try {
    $insert_query = "INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(1, $_SESSION['customer_id']);
    $insert_stmt->bindParam(2, $product_id);
    $insert_stmt->execute();

    // Return success response
    echo json_encode(array(
        "success" => true,
        "message" => "Product added to wishlist successfully",
        "product" => array(
            "id" => $product['id'],
            "name" => $product['name']
        )
    ));
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Wishlist error: " . $e->getMessage() . " - Code: " . $e->getCode());

    // Handle duplicate entry error
    if ($e->getCode() == 23000) {
        echo json_encode(array(
            "success" => true,
            "message" => "Product is already in your wishlist",
            "product" => array(
                "id" => $product['id'],
                "name" => $product['name']
            )
        ));
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Failed to add product to wishlist",
            "error" => $e->getMessage()
        ));
    }
}
?>
