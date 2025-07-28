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

echo "<h1>Test Place Order</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, set a test user ID
    $_SESSION['user_id'] = 3; // Hemanth Kumar
    $_SESSION['first_name'] = 'Hemanth';
    $_SESSION['last_name'] = 'Kumar';
    
    echo "<p>Set test user: Hemanth Kumar (ID: 3)</p>";
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

echo "<p>Logged in as: $userName (ID: $userId)</p>";

// Check if cart has items
$cart_query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.discount_price
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.customer_id = ?";
$cart_stmt = $db->prepare($cart_query);
$cart_stmt->bindParam(1, $userId);
$cart_stmt->execute();

$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    echo "<p>Your cart is empty. Adding a test product...</p>";
    
    // Get a valid product
    $product_query = "SELECT id, name, price FROM products WHERE id = 10 LIMIT 1"; // Carrot
    $product_stmt = $db->prepare($product_query);
    $product_stmt->execute();
    
    if ($product_stmt->rowCount() === 0) {
        echo "<p style='color:red;'>No products found in the database!</p>";
        exit;
    }
    
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add to cart
    try {
        // Check if already in cart
        $check_cart_query = "SELECT id FROM cart WHERE customer_id = ? AND product_id = ?";
        $check_cart_stmt = $db->prepare($check_cart_query);
        $check_cart_stmt->bindParam(1, $userId);
        $check_cart_stmt->bindParam(2, $product['id']);
        $check_cart_stmt->execute();
        
        if ($check_cart_stmt->rowCount() > 0) {
            // Update quantity
            $cart_item = $check_cart_stmt->fetch(PDO::FETCH_ASSOC);
            $update_query = "UPDATE cart SET quantity = quantity + 1 WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $cart_item['id']);
            $update_stmt->execute();
            
            echo "<p>Updated quantity for product '{$product['name']}' in cart.</p>";
        } else {
            // Add new item to cart
            $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, 1)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $userId);
            $insert_stmt->bindParam(2, $product['id']);
            $insert_stmt->execute();
            
            echo "<p>Added product '{$product['name']}' to cart.</p>";
        }
        
        // Refresh cart items
        $cart_stmt->execute();
        $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error adding product to cart: " . $e->getMessage() . "</p>";
        exit;
    }
}

// Display cart items
echo "<h2>Cart Items:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr>";

$total_amount = 0;

foreach ($cart_items as $item) {
    $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
    $item_total = $price * $item['quantity'];
    $total_amount += $item_total;
    
    echo "<tr>";
    echo "<td>" . $item['id'] . "</td>";
    echo "<td>" . $item['name'] . "</td>";
    echo "<td>₹" . $price . "</td>";
    echo "<td>" . $item['quantity'] . "</td>";
    echo "<td>₹" . $item_total . "</td>";
    echo "</tr>";
}

echo "<tr><td colspan='4' align='right'><strong>Total:</strong></td><td>₹" . $total_amount . "</td></tr>";
echo "</table>";

// Check if user has an address
$address_query = "SELECT * FROM addresses WHERE user_id = ? LIMIT 1";
$address_stmt = $db->prepare($address_query);
$address_stmt->bindParam(1, $userId);
$address_stmt->execute();

$address = null;

if ($address_stmt->rowCount() === 0) {
    echo "<p>No address found. Creating a test address...</p>";
    
    // Create a test address
    try {
        $insert_query = "INSERT INTO addresses (user_id, name, phone, street, city, state, postal_code, is_default)
                        VALUES (?, ?, '1234567890', 'Test Street', 'Test City', 'Test State', '123456', 1)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(1, $userId);
        $insert_stmt->bindParam(2, $userName);
        $insert_stmt->execute();
        
        $addressId = $db->lastInsertId();
        
        // Get the created address
        $address_query = "SELECT * FROM addresses WHERE id = ?";
        $address_stmt = $db->prepare($address_query);
        $address_stmt->bindParam(1, $addressId);
        $address_stmt->execute();
        $address = $address_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Created test address with ID: $addressId</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error creating test address: " . $e->getMessage() . "</p>";
        exit;
    }
} else {
    $address = $address_stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Found existing address with ID: " . $address['id'] . "</p>";
}

// Display address
echo "<h2>Delivery Address:</h2>";
echo "<p><strong>" . $address['name'] . "</strong><br>";
echo $address['street'] . "<br>";
echo $address['city'] . ", " . $address['state'] . " - " . $address['postal_code'] . "<br>";
echo "Phone: " . $address['phone'] . "</p>";

// Set session variables for checkout
$_SESSION['checkout_address_id'] = $address['id'];
$_SESSION['checkout_delivery_slot'] = 'morning-' . date('Y-m-d', strtotime('+1 day'));

echo "<p>Set checkout session variables:</p>";
echo "<ul>";
echo "<li>checkout_address_id: " . $_SESSION['checkout_address_id'] . "</li>";
echo "<li>checkout_delivery_slot: " . $_SESSION['checkout_delivery_slot'] . "</li>";
echo "</ul>";

// Place order form
echo "<h2>Place Order</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='action' value='place_order'>";
echo "<label>Payment Method: <select name='payment_method'>";
echo "<option value='cod'>Cash on Delivery</option>";
echo "<option value='upi'>UPI</option>";
echo "<option value='card'>Credit/Debit Card</option>";
echo "<option value='netbanking'>Net Banking</option>";
echo "</select></label><br>";
echo "<input type='submit' value='Place Order'>";
echo "</form>";

// Process order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $payment_method = $_POST['payment_method'];
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Create order
        $order_query = "INSERT INTO orders (customer_id, total_amount, payment_method, payment_status,
                        shipping_address, shipping_city, shipping_state, shipping_postal_code, delivery_slot, status)
                        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, 'pending')";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->bindParam(1, $userId);
        $order_stmt->bindParam(2, $total_amount);
        $order_stmt->bindParam(3, $payment_method);
        $order_stmt->bindParam(4, $address['street']);
        $order_stmt->bindParam(5, $address['city']);
        $order_stmt->bindParam(6, $address['state']);
        $order_stmt->bindParam(7, $address['postal_code']);
        $order_stmt->bindParam(8, $_SESSION['checkout_delivery_slot']);
        $order_stmt->execute();
        
        $order_id = $db->lastInsertId();
        
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
            $item_stmt->execute();
        }
        
        // Clear cart
        $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->bindParam(1, $userId);
        $clear_cart_stmt->execute();
        
        // Clear checkout session variables
        unset($_SESSION['checkout_address_id']);
        unset($_SESSION['checkout_delivery_slot']);
        
        // Commit transaction
        $db->commit();
        
        echo "<p style='color:green;'>Order placed successfully with ID: $order_id</p>";
        echo "<p><a href='frontend/order_confirmation.php?order_id=$order_id'>View Order Confirmation</a></p>";
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        echo "<p style='color:red;'>Error placing order: " . $e->getMessage() . "</p>";
    }
}
?>
