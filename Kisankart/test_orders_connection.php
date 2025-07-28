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
$table_structure = [];

// Check database connection
if (!$db) {
    $error_message = "Database connection failed. Please check your database configuration.";
} else {
    $success_message = "Database connection successful.";
    
    try {
        // Check if orders table exists
        $check_table_query = "SHOW TABLES LIKE 'orders'";
        $check_table_stmt = $db->prepare($check_table_query);
        $check_table_stmt->execute();
        
        if ($check_table_stmt->rowCount() > 0) {
            $success_message .= "<br>Orders table exists.";
            
            // Get table structure
            $table_structure_query = "DESCRIBE orders";
            $table_structure_stmt = $db->prepare($table_structure_query);
            $table_structure_stmt->execute();
            while ($row = $table_structure_stmt->fetch(PDO::FETCH_ASSOC)) {
                $table_structure[] = $row;
            }
            
            // Count orders
            $count_query = "SELECT COUNT(*) as total FROM orders";
            $count_stmt = $db->prepare($count_query);
            $count_stmt->execute();
            $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $total_orders = $count_result['total'];
            
            $success_message .= "<br>Total orders in database: " . $total_orders;
            
            // Get all orders
            $orders_query = "SELECT o.*, CONCAT(cr.first_name, ' ', cr.last_name) as customer_name 
                            FROM orders o
                            LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                            ORDER BY o.order_date DESC
                            LIMIT 10";
            $orders_stmt = $db->prepare($orders_query);
            $orders_stmt->execute();
            
            while ($row = $orders_stmt->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = $row;
            }
        } else {
            $error_message = "Orders table does not exist in the database.";
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
    <title>Test Orders Connection - Kisan Kart</title>
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
        <h1 class="mb-4">Test Orders Connection</h1>
        
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
                <h5 class="mb-0">Orders Table Structure</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($table_structure)): ?>
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
                                <?php foreach ($table_structure as $column): ?>
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
                <h5 class="mb-0">Orders Data (Limited to 10 records)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($orders)): ?>
                    <div class="table-container">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo $order['customer_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo $order['order_date']; ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo ucfirst($order['status']); ?></td>
                                        <td><?php echo $order['payment_method']; ?></td>
                                        <td><?php echo ucfirst($order['payment_status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No orders found in the database.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="frontend/customer_orders.php" class="btn btn-primary">Go to Customer Orders Page</a>
            <a href="index.php" class="btn btn-secondary ms-2">Back to Home</a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
