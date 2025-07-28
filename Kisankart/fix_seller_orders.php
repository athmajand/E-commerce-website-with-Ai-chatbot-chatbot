<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$error_message = '';
$success_message = '';
$current_seller_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$seller_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Unknown';
$sellers = [];

// Get all sellers
try {
    $sellers_query = "SELECT id, first_name, last_name, business_name FROM seller_registrations";
    $sellers_stmt = $db->prepare($sellers_query);
    $sellers_stmt->execute();

    while ($row = $sellers_stmt->fetch(PDO::FETCH_ASSOC)) {
        $sellers[] = $row;
    }
} catch (PDOException $e) {
    $error_message .= "Error fetching sellers: " . $e->getMessage() . "<br>";
}

// Check if we need to assign orders to the current seller
if (isset($_POST['assign_orders']) && $current_seller_id > 0) {
    try {
        // Start transaction
        $db->beginTransaction();

        // Get some product IDs to use for orders
        $products_query = "SELECT id FROM products LIMIT 5";
        $products_stmt = $db->prepare($products_query);
        $products_stmt->execute();
        $product_ids = [];

        while ($row = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
            $product_ids[] = $row['id'];
        }

        if (count($product_ids) > 0) {
            // Create a sample order if none exists
            $check_orders_query = "SELECT COUNT(*) as count FROM orders";
            $check_orders_stmt = $db->prepare($check_orders_query);
            $check_orders_stmt->execute();
            $orders_count = $check_orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $order_id = 0;

            if ($orders_count == 0) {
                // Create a new order
                $create_order_query = "INSERT INTO orders (customer_id, total_amount, status, payment_method, payment_status,
                                      shipping_address, shipping_city, shipping_state, shipping_postal_code)
                                      VALUES (1, 500.00, 'processing', 'Credit Card', 'completed',
                                      '123 Test Street', 'Test City', 'Test State', '123456')";
                $db->exec($create_order_query);
                $order_id = $db->lastInsertId();
                $success_message .= "Created a new order with ID: " . $order_id . "<br>";
            } else {
                // Get an existing order ID
                $order_query = "SELECT id FROM orders LIMIT 1";
                $order_stmt = $db->prepare($order_query);
                $order_stmt->execute();
                $order_id = $order_stmt->fetch(PDO::FETCH_ASSOC)['id'];
            }

            if ($order_id > 0) {
                // Check if order_items table has seller_id column
                $check_column_query = "SHOW COLUMNS FROM order_items LIKE 'seller_id'";
                $check_column_stmt = $db->prepare($check_column_query);
                $check_column_stmt->execute();

                if ($check_column_stmt->rowCount() == 0) {
                    // Add seller_id column
                    $add_column_query = "ALTER TABLE order_items ADD COLUMN seller_id INT NOT NULL AFTER product_id";
                    $db->exec($add_column_query);
                    $success_message .= "Added seller_id column to order_items table.<br>";
                }

                // Create sample order items for the current seller
                foreach ($product_ids as $index => $product_id) {
                    $price = 100 + ($index * 50);
                    $quantity = $index + 1;

                    $create_item_query = "INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, created_at)
                                         VALUES (:order_id, :product_id, :seller_id, :quantity, :price, NOW())";
                    $create_item_stmt = $db->prepare($create_item_query);
                    $create_item_stmt->bindParam(':order_id', $order_id);
                    $create_item_stmt->bindParam(':product_id', $product_id);
                    $create_item_stmt->bindParam(':seller_id', $current_seller_id);
                    $create_item_stmt->bindParam(':quantity', $quantity);
                    $create_item_stmt->bindParam(':price', $price);
                    $create_item_stmt->execute();
                }

                $success_message .= "Created " . count($product_ids) . " order items for seller ID " . $current_seller_id . ".<br>";
            }
        } else {
            $error_message = "No products found in the database. Please add some products first.";
            $db->rollBack();
        }

        // Commit transaction
        $db->commit();

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}

// Check if we need to assign orders to a specific seller
if (isset($_POST['assign_to_seller']) && isset($_POST['seller_id']) && $_POST['seller_id'] > 0) {
    $seller_id = intval($_POST['seller_id']);

    try {
        // Start transaction
        $db->beginTransaction();

        // Get some product IDs to use for orders
        $products_query = "SELECT id FROM products LIMIT 5";
        $products_stmt = $db->prepare($products_query);
        $products_stmt->execute();
        $product_ids = [];

        while ($row = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
            $product_ids[] = $row['id'];
        }

        if (count($product_ids) > 0) {
            // Create a sample order if none exists
            $check_orders_query = "SELECT COUNT(*) as count FROM orders";
            $check_orders_stmt = $db->prepare($check_orders_query);
            $check_orders_stmt->execute();
            $orders_count = $check_orders_stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $order_id = 0;

            if ($orders_count == 0) {
                // Create a new order
                $create_order_query = "INSERT INTO orders (customer_id, total_amount, status, payment_method, payment_status,
                                      shipping_address, shipping_city, shipping_state, shipping_postal_code)
                                      VALUES (1, 500.00, 'processing', 'Credit Card', 'completed',
                                      '123 Test Street', 'Test City', 'Test State', '123456')";
                $db->exec($create_order_query);
                $order_id = $db->lastInsertId();
                $success_message .= "Created a new order with ID: " . $order_id . "<br>";
            } else {
                // Get an existing order ID
                $order_query = "SELECT id FROM orders LIMIT 1";
                $order_stmt = $db->prepare($order_query);
                $order_stmt->execute();
                $order_id = $order_stmt->fetch(PDO::FETCH_ASSOC)['id'];
            }

            if ($order_id > 0) {
                // Check if order_items table has seller_id column
                $check_column_query = "SHOW COLUMNS FROM order_items LIKE 'seller_id'";
                $check_column_stmt = $db->prepare($check_column_query);
                $check_column_stmt->execute();

                if ($check_column_stmt->rowCount() == 0) {
                    // Add seller_id column
                    $add_column_query = "ALTER TABLE order_items ADD COLUMN seller_id INT NOT NULL AFTER product_id";
                    $db->exec($add_column_query);
                    $success_message .= "Added seller_id column to order_items table.<br>";
                }

                // Create sample order items for the specified seller
                foreach ($product_ids as $index => $product_id) {
                    $price = 100 + ($index * 50);
                    $quantity = $index + 1;

                    $create_item_query = "INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, created_at)
                                         VALUES (:order_id, :product_id, :seller_id, :quantity, :price, NOW())";
                    $create_item_stmt = $db->prepare($create_item_query);
                    $create_item_stmt->bindParam(':order_id', $order_id);
                    $create_item_stmt->bindParam(':product_id', $product_id);
                    $create_item_stmt->bindParam(':seller_id', $seller_id);
                    $create_item_stmt->bindParam(':quantity', $quantity);
                    $create_item_stmt->bindParam(':price', $price);
                    $create_item_stmt->execute();
                }

                // Get the seller name for the success message
                $seller_name_query = "SELECT CONCAT(first_name, ' ', last_name) as name FROM seller_registrations WHERE id = ?";
                $seller_name_stmt = $db->prepare($seller_name_query);
                $seller_name_stmt->bindParam(1, $seller_id);
                $seller_name_stmt->execute();
                $seller_name_row = $seller_name_stmt->fetch(PDO::FETCH_ASSOC);
                $seller_name_for_message = $seller_name_row ? $seller_name_row['name'] : "Seller ID " . $seller_id;

                $success_message .= "Created " . count($product_ids) . " order items for " . $seller_name_for_message . ".<br>";
            }
        } else {
            $error_message = "No products found in the database. Please add some products first.";
            $db->rollBack();
        }

        // Commit transaction
        $db->commit();

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}

// Check if we need to clear all orders for testing
if (isset($_POST['clear_orders'])) {
    try {
        // Start transaction
        $db->beginTransaction();

        // Delete all order items
        $delete_items_query = "DELETE FROM order_items";
        $db->exec($delete_items_query);

        // Reset auto increment
        $reset_items_query = "ALTER TABLE order_items AUTO_INCREMENT = 1";
        $db->exec($reset_items_query);

        // Commit transaction
        $db->commit();

        $success_message = "All order items have been cleared for testing.";

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Seller Orders - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Fix Seller Orders</h1>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Current Session Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Current Seller ID:</strong> <?php echo $current_seller_id; ?></p>
                <p><strong>Seller Name:</strong> <?php echo htmlspecialchars($seller_name); ?></p>

                <?php if ($current_seller_id > 0): ?>
                    <form method="post" action="">
                        <button type="submit" name="assign_orders" class="btn btn-primary">Create Sample Orders for Current Seller</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <p>No seller is currently logged in. Please log in as a seller to create sample orders.</p>
                        <a href="seller_login.php" class="btn btn-success">Go to Seller Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Create Orders for Specific Seller</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="seller_id" class="form-label">Select Seller:</label>
                            <select name="seller_id" id="seller_id" class="form-select">
                                <?php foreach ($sellers as $seller): ?>
                                    <option value="<?php echo $seller['id']; ?>">
                                        <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name'] . ' (' . $seller['business_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="assign_to_seller" class="btn btn-success">Create Sample Orders for Selected Seller</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Testing Tools</h5>
            </div>
            <div class="card-body">
                <form method="post" action="" onsubmit="return confirm('Are you sure you want to clear all order items? This cannot be undone.')">
                    <button type="submit" name="clear_orders" class="btn btn-danger">Clear All Order Items (For Testing)</button>
                </form>
            </div>
        </div>

        <div class="mt-4">
            <a href="frontend/seller/orders.php" class="btn btn-success">Go to Seller Orders Page</a>
            <a href="test_seller_orders.php" class="btn btn-primary ms-2">Test Seller Orders</a>
            <a href="index.php" class="btn btn-secondary ms-2">Back to Home</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
