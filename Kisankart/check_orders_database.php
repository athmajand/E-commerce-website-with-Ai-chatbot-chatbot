<?php
// Database check script for orders and order_items tables

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

// Function to check if a table exists
function tableExists($db, $tableName) {
    $query = "SHOW TABLES LIKE '$tableName'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Function to get table structure
function getTableStructure($db, $tableName) {
    $query = "DESCRIBE $tableName";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $structure = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $structure[] = $row;
    }
    return $structure;
}

// Function to get sample data
function getSampleData($db, $tableName, $limit = 5) {
    $query = "SELECT * FROM $tableName LIMIT $limit";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

// Function to get count
function getCount($db, $tableName) {
    $query = "SELECT COUNT(*) as count FROM $tableName";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Check orders table
$ordersExists = tableExists($db, 'orders');
$ordersStructure = $ordersExists ? getTableStructure($db, 'orders') : [];
$ordersCount = $ordersExists ? getCount($db, 'orders') : 0;
$ordersSample = $ordersExists && $ordersCount > 0 ? getSampleData($db, 'orders') : [];

// Check order_items table
$orderItemsExists = tableExists($db, 'order_items');
$orderItemsStructure = $orderItemsExists ? getTableStructure($db, 'order_items') : [];
$orderItemsCount = $orderItemsExists ? getCount($db, 'order_items') : 0;
$orderItemsSample = $orderItemsExists && $orderItemsCount > 0 ? getSampleData($db, 'order_items') : [];

// Check customer_registrations table
$customerRegistrationsExists = tableExists($db, 'customer_registrations');
$customerRegistrationsCount = $customerRegistrationsExists ? getCount($db, 'customer_registrations') : 0;

// Check products table
$productsExists = tableExists($db, 'products');
$productsCount = $productsExists ? getCount($db, 'products') : 0;

// Create a sample order if none exists
if ($ordersExists && $ordersCount == 0 && $customerRegistrationsCount > 0) {
    try {
        // Get a customer ID
        $customerQuery = "SELECT id FROM customer_registrations LIMIT 1";
        $customerStmt = $db->prepare($customerQuery);
        $customerStmt->execute();
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $customerId = $customer['id'];
            
            // Insert a sample order
            $insertOrderQuery = "INSERT INTO orders (customer_id, total_amount, status, payment_method, payment_status, shipping_address) 
                                VALUES (?, 1000.00, 'pending', 'Credit Card', 'pending', '123 Test Street, Test City')";
            $insertOrderStmt = $db->prepare($insertOrderQuery);
            $insertOrderStmt->execute([$customerId]);
            
            $orderId = $db->lastInsertId();
            
            // Insert sample order items if products exist
            if ($productsCount > 0) {
                $productQuery = "SELECT id, price FROM products LIMIT 2";
                $productStmt = $db->prepare($productQuery);
                $productStmt->execute();
                $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($products as $product) {
                    $insertItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price, total) 
                                      VALUES (?, ?, 1, ?, ?)";
                    $insertItemStmt = $db->prepare($insertItemQuery);
                    $insertItemStmt->execute([$orderId, $product['id'], $product['price'], $product['price']]);
                }
            }
            
            // Refresh data
            $ordersCount = getCount($db, 'orders');
            $ordersSample = getSampleData($db, 'orders');
            $orderItemsCount = getCount($db, 'order_items');
            $orderItemsSample = getSampleData($db, 'order_items');
        }
    } catch (PDOException $e) {
        $sampleOrderError = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check - Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Database Check - Orders</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Database Tables Status</h2>
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
                            <td>order_items</td>
                            <td><?php echo $orderItemsExists ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?></td>
                            <td><?php echo $orderItemsCount; ?></td>
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
        
        <?php if ($ordersExists): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2>Orders Table Structure</h2>
            </div>
            <div class="card-body">
                <table class="table">
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
                        <?php foreach ($ordersStructure as $field): ?>
                        <tr>
                            <td><?php echo $field['Field']; ?></td>
                            <td><?php echo $field['Type']; ?></td>
                            <td><?php echo $field['Null']; ?></td>
                            <td><?php echo $field['Key']; ?></td>
                            <td><?php echo $field['Default']; ?></td>
                            <td><?php echo $field['Extra']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($ordersCount > 0): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2>Sample Orders</h2>
            </div>
            <div class="card-body">
                <pre><?php print_r($ordersSample); ?></pre>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($orderItemsExists): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2>Order Items Table Structure</h2>
            </div>
            <div class="card-body">
                <table class="table">
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
                        <?php foreach ($orderItemsStructure as $field): ?>
                        <tr>
                            <td><?php echo $field['Field']; ?></td>
                            <td><?php echo $field['Type']; ?></td>
                            <td><?php echo $field['Null']; ?></td>
                            <td><?php echo $field['Key']; ?></td>
                            <td><?php echo $field['Default']; ?></td>
                            <td><?php echo $field['Extra']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($orderItemsCount > 0): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2>Sample Order Items</h2>
            </div>
            <div class="card-body">
                <pre><?php print_r($orderItemsSample); ?></pre>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="frontend/customer_orders.php" class="btn btn-primary">Go to Orders Page</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
