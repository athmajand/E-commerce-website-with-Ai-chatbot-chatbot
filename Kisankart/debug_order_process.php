<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Order Processing Debug</h1>";

// Display session information
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
echo "<p>Is logged in: " . ($is_logged_in ? 'Yes' : 'No') . "</p>";

if (!$is_logged_in) {
    echo "<p>You need to login first.</p>";
    echo "<p><a href='test_buy_now.php'>Go to Test Buy Now page to login</a></p>";
    exit;
}

// Check database tables
try {
    // Check orders table
    $check_table_query = "SHOW TABLES LIKE 'orders'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();
    
    echo "<h2>Database Tables Check:</h2>";
    echo "<p>Orders table exists: " . ($check_table_stmt->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
    
    if ($check_table_stmt->rowCount() > 0) {
        // Check orders table structure
        $table_structure_query = "SHOW CREATE TABLE orders";
        $table_structure_stmt = $db->prepare($table_structure_query);
        $table_structure_stmt->execute();
        $table_structure = $table_structure_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Orders Table Structure:</h3>";
        echo "<pre>" . $table_structure['Create Table'] . "</pre>";
    }
    
    // Check order_items table
    $check_table_query = "SHOW TABLES LIKE 'order_items'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();
    
    echo "<p>Order Items table exists: " . ($check_table_stmt->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
    
    if ($check_table_stmt->rowCount() > 0) {
        // Check order_items table structure
        $table_structure_query = "SHOW CREATE TABLE order_items";
        $table_structure_stmt = $db->prepare($table_structure_query);
        $table_structure_stmt->execute();
        $table_structure = $table_structure_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Order Items Table Structure:</h3>";
        echo "<pre>" . $table_structure['Create Table'] . "</pre>";
    }
    
    // Check addresses table
    $check_table_query = "SHOW TABLES LIKE 'addresses'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();
    
    echo "<p>Addresses table exists: " . ($check_table_stmt->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
    
    if ($check_table_stmt->rowCount() > 0) {
        // Check if user has addresses
        $address_query = "SELECT * FROM addresses WHERE user_id = ?";
        $address_stmt = $db->prepare($address_query);
        $address_stmt->bindParam(1, $_SESSION['user_id']);
        $address_stmt->execute();
        
        echo "<p>User has addresses: " . ($address_stmt->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
        
        if ($address_stmt->rowCount() > 0) {
            $addresses = $address_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>User Addresses:</h3>";
            echo "<pre>";
            print_r($addresses);
            echo "</pre>";
        }
    }
    
    // Check cart items
    $cart_query = "SELECT c.*, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.customer_id = ?";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->bindParam(1, $_SESSION['user_id']);
    $cart_stmt->execute();
    
    echo "<h2>Cart Items:</h2>";
    echo "<p>User has items in cart: " . ($cart_stmt->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
    
    if ($cart_stmt->rowCount() > 0) {
        $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($cart_items);
        echo "</pre>";
    }
    
    // Check checkout session variables
    echo "<h2>Checkout Session Variables:</h2>";
    echo "<p>checkout_address_id: " . (isset($_SESSION['checkout_address_id']) ? $_SESSION['checkout_address_id'] : 'Not set') . "</p>";
    echo "<p>checkout_delivery_slot: " . (isset($_SESSION['checkout_delivery_slot']) ? $_SESSION['checkout_delivery_slot'] : 'Not set') . "</p>";
    
    // Check recent orders
    $orders_query = "SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 5";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->bindParam(1, $_SESSION['user_id']);
    $orders_stmt->execute();
    
    echo "<h2>Recent Orders:</h2>";
    echo "<p>User has recent orders: " . ($orders_stmt->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
    
    if ($orders_stmt->rowCount() > 0) {
        $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($orders);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test order creation form
echo "<h2>Test Order Creation</h2>";
echo "<p>This form will simulate the order creation process.</p>";
echo "<form method='post' action='test_create_order.php'>";
echo "<input type='hidden' name='payment_method' value='cod'>";
echo "<input type='submit' name='place_order' value='Test Create Order'>";
echo "</form>";
?>

<p><a href="test_buy_now.php">Back to Test Buy Now</a></p>
<p><a href="test_add_to_cart.php">Back to Test Add to Cart</a></p>
<p><a href="frontend/delivery_address.php">Go to Delivery Address</a></p>
<p><a href="frontend/payment_method.php">Go to Payment Method</a></p>
