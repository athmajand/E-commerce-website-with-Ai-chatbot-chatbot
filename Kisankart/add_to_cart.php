<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers
header("Content-Type: application/json; charset=UTF-8");

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(array("success" => false, "message" => "User not logged in"));
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Check if product_id and quantity are provided
if (!isset($data['product_id']) || !isset($data['quantity'])) {
    echo json_encode(array("success" => false, "message" => "Product ID and quantity are required"));
    exit;
}

// Validate product_id and quantity
$product_id = (int)$data['product_id'];
$quantity = (int)$data['quantity'];

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(array("success" => false, "message" => "Invalid product ID or quantity"));
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

// Check if product exists and is available
$product_query = "SELECT id, name, price, discount_price, stock_quantity FROM products WHERE id = ? AND status = 'active'";
$product_stmt = $db->prepare($product_query);
$product_stmt->bindParam(1, $product_id);
$product_stmt->execute();

if ($product_stmt->rowCount() === 0) {
    echo json_encode(array("success" => false, "message" => "Product not found or not available"));
    exit;
}

$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

// Check if quantity is available
if ($quantity > $product['stock_quantity']) {
    echo json_encode(array(
        "success" => false,
        "message" => "Not enough stock available. Only " . $product['stock_quantity'] . " items left."
    ));
    exit;
}

// Check if cart table exists
$check_table_query = "SHOW TABLES LIKE 'cart'";
$check_table_stmt = $db->prepare($check_table_query);
$check_table_stmt->execute();

if ($check_table_stmt->rowCount() === 0) {
    // Create cart table if it doesn't exist
    $create_table_query = "CREATE TABLE `cart` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `customer_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL DEFAULT 1,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($create_table_query);
}

// Check if product is already in cart
$check_cart_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
$check_cart_stmt = $db->prepare($check_cart_query);
$check_cart_stmt->bindParam(1, $_SESSION['customer_id']);
$check_cart_stmt->bindParam(2, $product_id);
$check_cart_stmt->execute();

if ($check_cart_stmt->rowCount() > 0) {
    // Update quantity if product is already in cart
    $cart_item = $check_cart_stmt->fetch(PDO::FETCH_ASSOC);
    $new_quantity = $cart_item['quantity'] + $quantity;

    // Check if new quantity exceeds stock
    if ($new_quantity > $product['stock_quantity']) {
        echo json_encode(array(
            "success" => false,
            "message" => "Cannot add more items. You already have " . $cart_item['quantity'] . " in your cart and only " . $product['stock_quantity'] . " are available."
        ));
        exit;
    }

    $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(1, $new_quantity);
    $update_stmt->bindParam(2, $cart_item['id']);
    $update_stmt->execute();
} else {
    // Add new item to cart
    $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(1, $_SESSION['customer_id']);
    $insert_stmt->bindParam(2, $product_id);
    $insert_stmt->bindParam(3, $quantity);
    $insert_stmt->execute();
}

// Return success response
echo json_encode(array(
    "success" => true,
    "message" => "Product added to cart successfully",
    "product" => array(
        "id" => $product['id'],
        "name" => $product['name'],
        "price" => $product['price'],
        "discount_price" => $product['discount_price'],
        "quantity" => $quantity
    )
));
?>
