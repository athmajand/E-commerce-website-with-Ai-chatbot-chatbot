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
$orders = [];
$order_items = [];
$order_details = [];

// Check database connection
if (!$db) {
    $error_message = "Database connection failed. Please check your database configuration.";
} else {
    $success_message = "Database connection successful.";
    
    try {
        // Check if orders and order_items tables exist
        $check_orders_query = "SHOW TABLES LIKE 'orders'";
        $check_orders_stmt = $db->prepare($check_orders_query);
        $check_orders_stmt->execute();
        
        $check_items_query = "SHOW TABLES LIKE 'order_items'";
        $check_items_stmt = $db->prepare($check_items_query);
        $check_items_stmt->execute();
        
        if ($check_orders_stmt->rowCount() > 0 && $check_items_stmt->rowCount() > 0) {
            $success_message .= "<br>Orders and Order Items tables exist.";
            
            // Get order items structure
            $items_structure_query = "DESCRIBE order_items";
            $items_structure_stmt = $db->prepare($items_structure_query);
            $items_structure_stmt->execute();
            while ($row = $items_structure_stmt->fetch(PDO::FETCH_ASSOC)) {
                $items_structure[] = $row;
            }
            
            // Get all orders with customer info
            $orders_query = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status,
                            CONCAT(cr.first_name, ' ', cr.last_name) as customer_name
                            FROM orders o
                            LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                            ORDER BY o.order_date DESC
                            LIMIT 10";
            $orders_stmt = $db->prepare($orders_query);
            $orders_stmt->execute();
            
            while ($row = $orders_stmt->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = $row;
            }
            
            // Get detailed order items with product and seller info
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $order_id = $order['id'];
                    
                    // Get order items with product and seller info
                    $items_query = "SELECT oi.*, 
                                    p.name as product_name, 
                                    p.price as product_price,
                                    CONCAT(sr.first_name, ' ', sr.last_name) as seller_name
                                    FROM order_items oi
                                    LEFT JOIN products p ON oi.product_id = p.id
                                    LEFT JOIN seller_registrations sr ON oi.seller_id = sr.id
                                    WHERE oi.order_id = ?";
                    $items_stmt = $db->prepare($items_query);
                    $items_stmt->bindParam(1, $order_id);
                    $items_stmt->execute();
                    
                    $items = [];
                    while ($item = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
                        $items[] = $item;
                    }
                    
                    $order_details[$order_id] = [
                        'order' => $order,
                        'items' => $items
                    ];
                }
            }
        } else {
            $error_message = "Orders or Order Items table does not exist in the database.";
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
    <title>Test Order-Seller Relationship - Kisan Kart</title>
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
        .order-card {
            margin-bottom: 30px;
            border-left: 4px solid #4CAF50;
        }
        .order-items {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Test Order-Seller Relationship</h1>
        
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
                <h5 class="mb-0">Order Items Table Structure</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($items_structure)): ?>
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
                                <?php foreach ($items_structure as $column): ?>
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
        
        <h2 class="mb-3">Orders with Seller Information</h2>
        
        <?php if (!empty($order_details)): ?>
            <?php foreach ($order_details as $order_id => $details): ?>
                <div class="card order-card mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order #<?php echo $order_id; ?></h5>
                            <span class="badge bg-<?php echo strtolower($details['order']['status']) == 'delivered' ? 'success' : (strtolower($details['order']['status']) == 'cancelled' ? 'danger' : 'primary'); ?>">
                                <?php echo ucfirst($details['order']['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Customer:</strong> <?php echo $details['order']['customer_name']; ?></p>
                                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($details['order']['order_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Amount:</strong> ₹<?php echo number_format($details['order']['total_amount'], 2); ?></p>
                                <p><strong>Status:</strong> <?php echo ucfirst($details['order']['status']); ?></p>
                            </div>
                        </div>
                        
                        <h6 class="mb-3">Order Items:</h6>
                        <div class="table-container">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Seller/Farmer</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($details['items'])): ?>
                                        <?php foreach ($details['items'] as $item): ?>
                                            <tr>
                                                <td><?php echo $item['product_name'] ?? 'Unknown Product'; ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['seller_name'] ?? 'Unknown Seller'; ?></td>
                                                <td><?php echo ucfirst($item['status'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No items found for this order.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No orders found in the database or no seller information available.
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="frontend/customer_orders.php" class="btn btn-primary">Go to Customer Orders Page</a>
            <a href="index.php" class="btn btn-secondary ms-2">Back to Home</a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
