<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$error_message = '';
$success_message = '';
$seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : null;
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$price = isset($_POST['price']) ? floatval($_POST['price']) : 100.00;

// Get all sellers
$sellers = [];
try {
    $sellers_query = "SELECT id, first_name, last_name, business_name FROM seller_registrations";
    $sellers_stmt = $db->prepare($sellers_query);
    $sellers_stmt->execute();
    while ($row = $sellers_stmt->fetch(PDO::FETCH_ASSOC)) {
        $sellers[] = $row;
    }
} catch (PDOException $e) {
    $error_message = "Error fetching sellers: " . $e->getMessage();
}

// Get all products
$products = [];
try {
    $products_query = "SELECT id, name, price FROM products";
    $products_stmt = $db->prepare($products_query);
    $products_stmt->execute();
    while ($row = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = $row;
    }
} catch (PDOException $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
}

// Get all customers
$customers = [];
try {
    $customers_query = "SELECT id, first_name, last_name FROM customer_registrations";
    $customers_stmt = $db->prepare($customers_query);
    $customers_stmt->execute();
    while ($row = $customers_stmt->fetch(PDO::FETCH_ASSOC)) {
        $customers[] = $row;
    }
} catch (PDOException $e) {
    $error_message = "Error fetching customers: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    if (!$seller_id || !$product_id || !$customer_id) {
        $error_message = "Please select a seller, product, and customer.";
    } else {
        try {
            // Start transaction
            $db->beginTransaction();

            // 1. Create a new order
            $order_query = "INSERT INTO orders (customer_id, total_amount, status, payment_method, payment_status,
                            shipping_address, shipping_city, shipping_state, shipping_postal_code)
                            VALUES (?, ?, 'processing', 'Credit Card', 'completed',
                            '123 Test Street', 'Test City', 'Test State', '123456')";
            $order_stmt = $db->prepare($order_query);
            $total_amount = $price * $quantity;
            $order_stmt->bindParam(1, $customer_id);
            $order_stmt->bindParam(2, $total_amount);
            $order_stmt->execute();

            // Get the new order ID
            $order_id = $db->lastInsertId();

            // 2. Create order item with seller_id
            $item_query = "INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, status)
                          VALUES (?, ?, ?, ?, ?, 'processing')";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(1, $order_id);
            $item_stmt->bindParam(2, $product_id);
            $item_stmt->bindParam(3, $seller_id);
            $item_stmt->bindParam(4, $quantity);
            $item_stmt->bindParam(5, $price);
            $item_stmt->execute();

            // Commit transaction
            $db->commit();

            $success_message = "Sample order created successfully! Order ID: " . $order_id;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $db->rollBack();
            $error_message = "Error creating order: " . $e->getMessage();
        }
    }
}

// Get recent orders
$recent_orders = [];
try {
    $recent_query = "SELECT o.id as order_id, o.order_date, o.total_amount, o.status,
                    CONCAT(cr.first_name, ' ', cr.last_name) as customer_name,
                    oi.id as item_id, oi.product_id, oi.seller_id, oi.quantity, oi.price,
                    p.name as product_name,
                    CONCAT(sr.first_name, ' ', sr.last_name) as seller_name
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                    LEFT JOIN seller_registrations sr ON oi.seller_id = sr.id
                    ORDER BY o.order_date DESC
                    LIMIT 10";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->execute();
    while ($row = $recent_stmt->fetch(PDO::FETCH_ASSOC)) {
        $recent_orders[] = $row;
    }
} catch (PDOException $e) {
    $error_message = "Error fetching recent orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sample Seller Orders - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Add Sample Seller Orders</h1>

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

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Create Sample Order</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="seller_id" class="form-label">Seller</label>
                                <select name="seller_id" id="seller_id" class="form-select" required>
                                    <option value="">Select Seller</option>
                                    <?php foreach ($sellers as $seller): ?>
                                        <option value="<?php echo $seller['id']; ?>" <?php echo $seller_id == $seller['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name'] . ' (' . $seller['business_name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="product_id" class="form-label">Product</label>
                                <select name="product_id" id="product_id" class="form-select" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" <?php echo $product_id == $product['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['name'] . ' (₹' . number_format($product['price'], 2) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer</label>
                                <select name="customer_id" id="customer_id" class="form-select" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" <?php echo $customer_id == $customer['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" value="<?php echo $quantity; ?>" min="1" required>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price (₹)</label>
                                <input type="number" name="price" id="price" class="form-control" value="<?php echo $price; ?>" min="0.01" step="0.01" required>
                            </div>

                            <button type="submit" name="create_order" class="btn btn-success">Create Sample Order</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No recent orders found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Product</th>
                                            <th>Seller</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo $order['order_id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['seller_name'] ?? 'N/A'); ?></td>
                                                <td>₹<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="test_seller_orders.php" class="btn btn-primary">Test Seller Orders</a>
            <a href="frontend/seller/orders.php" class="btn btn-success ms-2">Go to Seller Orders Page</a>
            <a href="index.php" class="btn btn-secondary ms-2">Back to Home</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
