<?php
// Script to create a sample order for testing

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = '';
$error = '';
$orderId = null;

// Function to check if a table exists
function tableExists($db, $tableName) {
    $query = "SHOW TABLES LIKE '$tableName'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Function to get count
function getCount($db, $tableName) {
    $query = "SELECT COUNT(*) as count FROM $tableName";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Check required tables
$ordersExists = tableExists($db, 'orders');
$orderItemsExists = tableExists($db, 'order_items');
$customerRegistrationsExists = tableExists($db, 'customer_registrations');
$productsExists = tableExists($db, 'products');

// Get counts
$ordersCount = $ordersExists ? getCount($db, 'orders') : 0;
$customerRegistrationsCount = $customerRegistrationsExists ? getCount($db, 'customer_registrations') : 0;
$productsCount = $productsExists ? getCount($db, 'products') : 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    try {
        // Check if we have customers and products
        if (!$customerRegistrationsExists) {
            throw new Exception("Customer registrations table does not exist.");
        }
        
        if ($customerRegistrationsCount == 0) {
            throw new Exception("No customers found in the database.");
        }
        
        if (!$productsExists) {
            throw new Exception("Products table does not exist.");
        }
        
        if ($productsCount == 0) {
            throw new Exception("No products found in the database.");
        }
        
        // Create orders table if it doesn't exist
        if (!$ordersExists) {
            $createOrdersTable = "CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                shipping_address TEXT NOT NULL,
                billing_address TEXT,
                payment_method VARCHAR(50) NOT NULL,
                payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
                status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                tracking_number VARCHAR(100),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $db->exec($createOrdersTable);
            $message .= "Orders table created successfully.<br>";
        }
        
        // Create order_items table if it doesn't exist
        if (!$orderItemsExists) {
            $createOrderItemsTable = "CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                price DECIMAL(10, 2) NOT NULL,
                discount DECIMAL(10, 2) DEFAULT 0.00,
                total DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $db->exec($createOrderItemsTable);
            $message .= "Order items table created successfully.<br>";
        }
        
        // Get a customer ID
        $customerQuery = "SELECT id FROM customer_registrations LIMIT 1";
        $customerStmt = $db->prepare($customerQuery);
        $customerStmt->execute();
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception("Failed to retrieve customer information.");
        }
        
        $customerId = $customer['id'];
        
        // Get products
        $productQuery = "SELECT id, name, price FROM products LIMIT 3";
        $productStmt = $db->prepare($productQuery);
        $productStmt->execute();
        $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($products)) {
            throw new Exception("Failed to retrieve product information.");
        }
        
        // Calculate total amount
        $totalAmount = 0;
        foreach ($products as $product) {
            $totalAmount += $product['price'];
        }
        
        // Insert order
        $insertOrderQuery = "INSERT INTO orders (customer_id, total_amount, status, payment_method, payment_status, shipping_address, notes) 
                            VALUES (?, ?, 'pending', 'Credit Card', 'pending', '123 Test Street, Test City', 'Sample order created for testing')";
        $insertOrderStmt = $db->prepare($insertOrderQuery);
        $insertOrderStmt->execute([$customerId, $totalAmount]);
        
        $orderId = $db->lastInsertId();
        
        // Insert order items
        foreach ($products as $product) {
            $insertItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price, total) 
                              VALUES (?, ?, 1, ?, ?)";
            $insertItemStmt = $db->prepare($insertItemQuery);
            $insertItemStmt->execute([$orderId, $product['id'], $product['price'], $product['price']]);
        }
        
        $message = "Sample order #$orderId created successfully with " . count($products) . " items.";
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Sample Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Create Sample Order</h1>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Database Status</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Exists</th>
                            <th>Record Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>orders</td>
                            <td><?php echo $ordersExists ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?></td>
                            <td><?php echo $ordersCount; ?></td>
                        </tr>
                        <tr>
                            <td>customer_registrations</td>
                            <td><?php echo $customerRegistrationsExists ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?></td>
                            <td><?php echo $customerRegistrationsCount; ?></td>
                        </tr>
                        <tr>
                            <td>products</td>
                            <td><?php echo $productsExists ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?></td>
                            <td><?php echo $productsCount; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Create Sample Order</h2>
            </div>
            <div class="card-body">
                <?php if ($customerRegistrationsCount > 0 && $productsCount > 0): ?>
                <form method="post">
                    <p>This will create a sample order with items for testing purposes.</p>
                    <button type="submit" name="create_order" class="btn btn-primary">Create Sample Order</button>
                </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    <p>Cannot create sample order because:</p>
                    <ul>
                        <?php if ($customerRegistrationsCount == 0): ?>
                        <li>No customers found in the database.</li>
                        <?php endif; ?>
                        <?php if ($productsCount == 0): ?>
                        <li>No products found in the database.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($orderId): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2>View Created Order</h2>
            </div>
            <div class="card-body">
                <p>Your sample order has been created. You can view it using the links below:</p>
                <a href="frontend/order_details.php?id=<?php echo $orderId; ?>" class="btn btn-success">View Order Details</a>
                <a href="frontend/customer_orders.php" class="btn btn-primary">View All Orders</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="check_orders_database.php" class="btn btn-info">Check Database</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
