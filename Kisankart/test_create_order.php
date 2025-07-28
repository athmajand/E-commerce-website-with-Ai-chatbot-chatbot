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

echo "<h1>Test Order Creation</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>You need to login first.</p>";
    echo "<p><a href='test_buy_now.php'>Go to Test Buy Now page to login</a></p>";
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = $_POST['payment_method'];
    $userId = $_SESSION['user_id'];
    $error_message = '';
    
    try {
        // Get cart items
        $cart_query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.discount_price
                      FROM cart c
                      JOIN products p ON c.product_id = p.id
                      WHERE c.customer_id = ?";
        $cart_stmt = $db->prepare($cart_query);
        $cart_stmt->bindParam(1, $userId);
        $cart_stmt->execute();
        
        $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cart_items)) {
            echo "<p style='color:red;'>Your cart is empty. Please add items to your cart first.</p>";
            echo "<p><a href='test_add_to_cart.php'>Go to Add to Cart</a></p>";
            exit;
        }
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
            $total_amount += $price * $item['quantity'];
        }
        
        // Get or create a test address
        $address_query = "SELECT * FROM addresses WHERE user_id = ? LIMIT 1";
        $address_stmt = $db->prepare($address_query);
        $address_stmt->bindParam(1, $userId);
        $address_stmt->execute();
        
        if ($address_stmt->rowCount() === 0) {
            // Create a test address
            $insert_query = "INSERT INTO addresses (user_id, name, phone, street, city, state, postal_code, is_default)
                            VALUES (?, 'Test User', '1234567890', 'Test Street', 'Test City', 'Test State', '123456', 1)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $userId);
            $insert_stmt->execute();
            
            $addressId = $db->lastInsertId();
            
            $address_query = "SELECT * FROM addresses WHERE id = ?";
            $address_stmt = $db->prepare($address_query);
            $address_stmt->bindParam(1, $addressId);
            $address_stmt->execute();
        }
        
        $address = $address_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Begin transaction
        $db->beginTransaction();
        
        // Create order
        $order_query = "INSERT INTO orders (customer_id, total_amount, payment_method, payment_status,
                        shipping_address, shipping_city, shipping_state, shipping_postal_code, delivery_slot, status)
                        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, 'morning-" . date('Y-m-d', strtotime('+1 day')) . "', 'pending')";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->bindParam(1, $userId);
        $order_stmt->bindParam(2, $total_amount);
        $order_stmt->bindParam(3, $payment_method);
        $order_stmt->bindParam(4, $address['street']);
        $order_stmt->bindParam(5, $address['city']);
        $order_stmt->bindParam(6, $address['state']);
        $order_stmt->bindParam(7, $address['postal_code']);
        
        echo "<h2>Executing Order Query:</h2>";
        echo "<pre>" . $order_query . "</pre>";
        echo "<p>Parameters:</p>";
        echo "<ul>";
        echo "<li>userId: " . $userId . "</li>";
        echo "<li>total_amount: " . $total_amount . "</li>";
        echo "<li>payment_method: " . $payment_method . "</li>";
        echo "<li>street: " . $address['street'] . "</li>";
        echo "<li>city: " . $address['city'] . "</li>";
        echo "<li>state: " . $address['state'] . "</li>";
        echo "<li>postal_code: " . $address['postal_code'] . "</li>";
        echo "</ul>";
        
        $order_stmt->execute();
        
        $order_id = $db->lastInsertId();
        
        echo "<p style='color:green;'>Order created with ID: " . $order_id . "</p>";
        
        // Add order items
        foreach ($cart_items as $item) {
            $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
            
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price)
                          VALUES (?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(1, $order_id);
            $item_stmt->bindParam(2, $item['product_id']);
            $item_stmt->bindParam(3, $item['quantity']);
            $item_stmt->bindParam(4, $price);
            
            echo "<p>Adding order item: Product ID " . $item['product_id'] . ", Quantity " . $item['quantity'] . ", Price " . $price . "</p>";
            
            $item_stmt->execute();
        }
        
        // Clear cart
        $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->bindParam(1, $userId);
        $clear_cart_stmt->execute();
        
        echo "<p style='color:green;'>Cart cleared successfully.</p>";
        
        // Commit transaction
        $db->commit();
        
        echo "<p style='color:green;'>Order created successfully!</p>";
        echo "<p><a href='frontend/order_confirmation.php?order_id=" . $order_id . "'>View Order Confirmation</a></p>";
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        echo "<h2>Error Creating Order</h2>";
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        
        // Get more detailed error information
        echo "<h3>Error Details:</h3>";
        echo "<pre>";
        print_r($e);
        echo "</pre>";
        
        // Check if there's an issue with the SQL query
        if (strpos($e->getMessage(), 'syntax error') !== false) {
            echo "<p>There might be a syntax error in the SQL query.</p>";
        }
        
        // Check if there's an issue with foreign key constraints
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            echo "<p>There might be an issue with foreign key constraints.</p>";
        }
        
        // Check if there's an issue with data types
        if (strpos($e->getMessage(), 'data type mismatch') !== false) {
            echo "<p>There might be a data type mismatch in the SQL query.</p>";
        }
    }
} else {
    echo "<p>No form submission detected.</p>";
}
?>

<p><a href="debug_order_process.php">Back to Debug Order Process</a></p>
<p><a href="test_add_to_cart.php">Back to Test Add to Cart</a></p>
