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

echo "<h1>Test Add to Cart</h1>";

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    try {
        // Check if product exists
        $product_query = "SELECT id, name, price FROM products WHERE id = ?";
        $product_stmt = $db->prepare($product_query);
        $product_stmt->bindParam(1, $product_id);
        $product_stmt->execute();
        
        if ($product_stmt->rowCount() === 0) {
            echo "<p style='color:red;'>Product with ID $product_id does not exist!</p>";
        } else {
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if item already in cart
            $check_cart_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
            $check_cart_stmt = $db->prepare($check_cart_query);
            $check_cart_stmt->bindParam(1, $_SESSION['user_id']);
            $check_cart_stmt->bindParam(2, $product_id);
            $check_cart_stmt->execute();
            
            if ($check_cart_stmt->rowCount() > 0) {
                // Update quantity
                $cart_item = $check_cart_stmt->fetch(PDO::FETCH_ASSOC);
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $new_quantity);
                $update_stmt->bindParam(2, $cart_item['id']);
                $update_stmt->execute();
                
                echo "<p style='color:green;'>Updated quantity for product '{$product['name']}' in cart.</p>";
            } else {
                // Add new item to cart
                $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(1, $_SESSION['user_id']);
                $insert_stmt->bindParam(2, $product_id);
                $insert_stmt->bindParam(3, $quantity);
                $insert_stmt->execute();
                
                echo "<p style='color:green;'>Added product '{$product['name']}' to cart.</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
}

// Show available products
try {
    $query = "SELECT id, name, price FROM products";
    $stmt = $db->query($query);
    
    if ($stmt->rowCount() > 0) {
        echo "<h2>Available Products:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Action</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>₹" . $row['price'] . "</td>";
            echo "<td><form method='post' action='test_add_to_cart.php'>";
            echo "<input type='hidden' name='product_id' value='" . $row['id'] . "'>";
            echo "<input type='number' name='quantity' value='1' min='1' style='width:60px;'>";
            echo "<input type='submit' value='Add to Cart'>";
            echo "</form></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No products found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error fetching products: " . $e->getMessage() . "</p>";
}

// Show cart items
try {
    $cart_query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price
                  FROM cart c
                  JOIN products p ON c.product_id = p.id
                  WHERE c.customer_id = ?";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->bindParam(1, $_SESSION['user_id']);
    $cart_stmt->execute();
    
    echo "<h2>Your Cart:</h2>";
    
    if ($cart_stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr>";
        
        $total_amount = 0;
        
        while ($row = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
            $item_total = $row['price'] * $row['quantity'];
            $total_amount += $item_total;
            
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>₹" . $row['price'] . "</td>";
            echo "<td>" . $row['quantity'] . "</td>";
            echo "<td>₹" . $item_total . "</td>";
            echo "<td><form method='post' action='remove_from_cart.php'>";
            echo "<input type='hidden' name='cart_id' value='" . $row['id'] . "'>";
            echo "<input type='submit' value='Remove'>";
            echo "</form></td>";
            echo "</tr>";
        }
        
        echo "<tr><td colspan='4' align='right'><strong>Total:</strong></td><td>₹" . $total_amount . "</td><td></td></tr>";
        echo "</table>";
        
        echo "<p><a href='frontend/delivery_address.php' class='btn'>Proceed to Checkout</a></p>";
    } else {
        echo "<p>Your cart is empty.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error fetching cart items: " . $e->getMessage() . "</p>";
}
?>

<p><a href="test_buy_now.php">Back to Test Buy Now</a></p>
<p><a href="test_db_connection.php">Back to Test Page</a></p>
<p><a href="frontend/products.php">Go to Products Page</a></p>
<p><a href="login.php">Go to Login Page</a></p>
