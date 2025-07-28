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
$orders_table_exists = false;
$order_items_table_exists = false;
$order_items_structure = [];
$seller_id = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 1; // Default to seller ID 1 if not provided

// Check database connection
if (!$db) {
    $error_message = "Database connection failed. Please check your database configuration.";
} else {
    $success_message = "Database connection successful.";

    try {
        // Check if orders table exists
        $check_orders_query = "SHOW TABLES LIKE 'orders'";
        $check_orders_stmt = $db->prepare($check_orders_query);
        $check_orders_stmt->execute();
        $orders_table_exists = $check_orders_stmt->rowCount() > 0;

        // Check if order_items table exists
        $check_items_query = "SHOW TABLES LIKE 'order_items'";
        $check_items_stmt = $db->prepare($check_items_query);
        $check_items_stmt->execute();
        $order_items_table_exists = $check_items_stmt->rowCount() > 0;

        if ($orders_table_exists && $order_items_table_exists) {
            $success_message .= "<br>Orders and Order Items tables exist.";

            // Get order items structure
            $items_structure_query = "DESCRIBE order_items";
            $items_structure_stmt = $db->prepare($items_structure_query);
            $items_structure_stmt->execute();
            while ($row = $items_structure_stmt->fetch(PDO::FETCH_ASSOC)) {
                $order_items_structure[] = $row;
            }

            // Check if seller_id exists in order_items
            $seller_id_exists = false;
            foreach ($order_items_structure as $column) {
                if ($column['Field'] === 'seller_id') {
                    $seller_id_exists = true;
                    break;
                }
            }

            if (!$seller_id_exists) {
                $error_message = "The 'seller_id' column does not exist in the order_items table.";
            } else {
                // Get all sellers
                $sellers_query = "SELECT id, first_name, last_name, business_name FROM seller_registrations";
                $sellers_stmt = $db->prepare($sellers_query);
                $sellers_stmt->execute();
                $sellers = [];
                while ($row = $sellers_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $sellers[] = $row;
                }

                // Get orders for the selected seller
                $orders_query = "SELECT oi.id, oi.order_id, oi.product_id, oi.seller_id, oi.quantity, oi.price,
                                o.status, o.order_date, p.name as product_name,
                                CONCAT(cr.first_name, ' ', cr.last_name) as customer_name
                         FROM order_items oi
                         JOIN orders o ON oi.order_id = o.id
                         JOIN products p ON oi.product_id = p.id
                         LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                         WHERE oi.seller_id = ?
                         ORDER BY o.order_date DESC";
                $orders_stmt = $db->prepare($orders_query);
                $orders_stmt->bindParam(1, $seller_id);
                $orders_stmt->execute();

                $orders = [];
                while ($row = $orders_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $orders[] = $row;
                }

                // Get all order items to check if any have seller_id
                $all_items_query = "SELECT COUNT(*) as total FROM order_items WHERE seller_id IS NOT NULL";
                $all_items_stmt = $db->prepare($all_items_query);
                $all_items_stmt->execute();
                $all_items_count = $all_items_stmt->fetch(PDO::FETCH_ASSOC)['total'];

                $success_message .= "<br>Total order items with seller_id: " . $all_items_count;
            }
        } else {
            if (!$orders_table_exists) {
                $error_message .= "Orders table does not exist in the database. ";
            }
            if (!$order_items_table_exists) {
                $error_message .= "Order Items table does not exist in the database.";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Seller Orders - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Test Seller Orders Connection</h1>

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

        <!-- Seller Selection Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Select Seller</h5>
            </div>
            <div class="card-body">
                <form method="get" class="mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="seller_id" class="col-form-label">Seller:</label>
                        </div>
                        <div class="col-auto">
                            <select name="seller_id" id="seller_id" class="form-select">
                                <?php foreach ($sellers as $seller): ?>
                                    <option value="<?php echo $seller['id']; ?>" <?php echo $seller_id == $seller['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name'] . ' (' . $seller['business_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">View Orders</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Order Items Table Structure</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($order_items_structure)): ?>
                    <div class="table-container">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Null</th>
                                    <th>Key</th>
                                    <th>Default</th>
                                    <th>Extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items_structure as $column): ?>
                                    <tr>
                                        <td><?php echo $column['Field']; ?></td>
                                        <td><?php echo $column['Type']; ?></td>
                                        <td><?php echo $column['Null']; ?></td>
                                        <td><?php echo $column['Key']; ?></td>
                                        <td><?php echo $column['Default']; ?></td>
                                        <td><?php echo $column['Extra']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No table structure information available.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Orders for Selected Seller</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($orders)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order Item ID</th>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Customer</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo $order['quantity']; ?></td>
                                        <td>₹<?php echo number_format($order['price'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td><?php echo ucfirst($order['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-bag-x text-muted" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="text-muted">No Orders Found</h5>
                        <p class="text-muted">No orders found for this seller.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <a href="frontend/seller/orders.php" class="btn btn-primary">Go to Seller Orders Page</a>
            <a href="index.php" class="btn btn-secondary ms-2">Back to Home</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
