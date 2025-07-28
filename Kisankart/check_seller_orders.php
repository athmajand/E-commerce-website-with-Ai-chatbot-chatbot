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
include_once 'api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$error_message = '';
$success_message = '';
$current_seller_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$order_items = [];
$available_sellers = [];

// Get all sellers
try {
    $sellers_query = "SELECT id, first_name, last_name, business_name FROM seller_registrations";
    $sellers_stmt = $db->prepare($sellers_query);
    $sellers_stmt->execute();
    
    while ($row = $sellers_stmt->fetch(PDO::FETCH_ASSOC)) {
        $available_sellers[] = $row;
    }
    
    $success_message .= "Found " . count($available_sellers) . " sellers in the database.<br>";
} catch (PDOException $e) {
    $error_message .= "Error fetching sellers: " . $e->getMessage() . "<br>";
}

// Get all order items
try {
    $items_query = "SELECT oi.id, oi.order_id, oi.product_id, oi.seller_id, oi.quantity, oi.price,
                    p.name as product_name, o.status, o.order_date
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    JOIN products p ON oi.product_id = p.id
                    ORDER BY oi.id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute();
    
    while ($row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
        $order_items[] = $row;
    }
    
    $success_message .= "Found " . count($order_items) . " order items in the database.<br>";
} catch (PDOException $e) {
    $error_message .= "Error fetching order items: " . $e->getMessage() . "<br>";
}

// Check if we need to assign orders to the current seller
if (isset($_POST['assign_orders']) && $current_seller_id > 0) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Update some order items to be associated with the current seller
        $update_query = "UPDATE order_items SET seller_id = ? WHERE id IN (SELECT id FROM (SELECT id FROM order_items ORDER BY id LIMIT 2) as temp)";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(1, $current_seller_id);
        $update_stmt->execute();
        
        $affected_rows = $update_stmt->rowCount();
        
        // Commit transaction
        $db->commit();
        
        $success_message .= "Updated " . $affected_rows . " order items to be associated with seller ID " . $current_seller_id . ".<br>";
        
        // Refresh order items list
        $items_stmt->execute();
        $order_items = [];
        while ($row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
            $order_items[] = $row;
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message .= "Error updating order items: " . $e->getMessage() . "<br>";
    }
}

// Check if we need to assign orders to a specific seller
if (isset($_POST['assign_to_seller']) && isset($_POST['seller_id']) && $_POST['seller_id'] > 0) {
    $seller_id = intval($_POST['seller_id']);
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Update some order items to be associated with the selected seller
        $update_query = "UPDATE order_items SET seller_id = ? WHERE id IN (SELECT id FROM (SELECT id FROM order_items ORDER BY id LIMIT 2) as temp)";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(1, $seller_id);
        $update_stmt->execute();
        
        $affected_rows = $update_stmt->rowCount();
        
        // Commit transaction
        $db->commit();
        
        $success_message .= "Updated " . $affected_rows . " order items to be associated with seller ID " . $seller_id . ".<br>";
        
        // Refresh order items list
        $items_stmt->execute();
        $order_items = [];
        while ($row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
            $order_items[] = $row;
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message .= "Error updating order items: " . $e->getMessage() . "<br>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Seller Orders - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Check Seller Orders</h1>
        
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
                
                <?php if ($current_seller_id > 0): ?>
                    <form method="post" action="">
                        <button type="submit" name="assign_orders" class="btn btn-primary">Assign Orders to Current Seller</button>
                    </form>
                <?php else: ?>
                    <p class="text-warning">No seller is currently logged in. Please log in as a seller to assign orders.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Assign Orders to Specific Seller</h5>
            </div>
            <div class="card-body">
                <form method="post" action="" class="mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="seller_id" class="col-form-label">Seller:</label>
                        </div>
                        <div class="col-auto">
                            <select name="seller_id" id="seller_id" class="form-select">
                                <?php foreach ($available_sellers as $seller): ?>
                                    <option value="<?php echo $seller['id']; ?>">
                                        <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name'] . ' (' . $seller['business_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="assign_to_seller" class="btn btn-primary">Assign Orders</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">All Order Items</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($order_items)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Seller ID</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo $item['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo $item['seller_id']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['order_date'])); ?></td>
                                        <td><?php echo ucfirst($item['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No order items found in the database.</p>
                <?php endif; ?>
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
