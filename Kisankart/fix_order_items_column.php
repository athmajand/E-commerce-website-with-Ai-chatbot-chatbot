<?php
// To fix the 'Unknown column seller_id in order_items' error, run this script in your browser or via CLI.
// It will add or rename the seller_id column as needed.
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
$table_structure = [];
$farmer_id_exists = false;
$seller_id_exists = false;

// Check the current structure of the order_items table
try {
    $structure_query = "DESCRIBE order_items";
    $structure_stmt = $db->prepare($structure_query);
    $structure_stmt->execute();
    
    while ($row = $structure_stmt->fetch(PDO::FETCH_ASSOC)) {
        $table_structure[] = $row;
        if ($row['Field'] === 'farmer_id') {
            $farmer_id_exists = true;
        }
        if ($row['Field'] === 'seller_id') {
            $seller_id_exists = true;
        }
    }
    
    $success_message .= "Successfully retrieved order_items table structure.<br>";
    
    // Check if we need to add or rename columns
    if (!$farmer_id_exists && !$seller_id_exists) {
        // Neither column exists, we need to add seller_id
        try {
            $add_column_query = "ALTER TABLE order_items ADD COLUMN seller_id INT DEFAULT NULL";
            $db->exec($add_column_query);
            $success_message .= "Added seller_id column to order_items table.<br>";
            $seller_id_exists = true;
        } catch (PDOException $e) {
            $error_message .= "Error adding seller_id column: " . $e->getMessage() . "<br>";
        }
    } else if ($farmer_id_exists && !$seller_id_exists) {
        // Only farmer_id exists, we need to rename it to seller_id
        try {
            // Get the column details to preserve data type and constraints
            $column_details = null;
            foreach ($table_structure as $column) {
                if ($column['Field'] === 'farmer_id') {
                    $column_details = $column;
                    break;
                }
            }
            
            if ($column_details) {
                $rename_query = "ALTER TABLE order_items CHANGE COLUMN farmer_id seller_id " . 
                                $column_details['Type'] . 
                                ($column_details['Null'] === 'NO' ? ' NOT NULL' : '') . 
                                ($column_details['Default'] !== null ? " DEFAULT '" . $column_details['Default'] . "'" : '');
                
                $db->exec($rename_query);
                $success_message .= "Renamed farmer_id column to seller_id in order_items table.<br>";
                $seller_id_exists = true;
                $farmer_id_exists = false;
            } else {
                $error_message .= "Could not get column details for farmer_id.<br>";
            }
        } catch (PDOException $e) {
            $error_message .= "Error renaming farmer_id column: " . $e->getMessage() . "<br>";
        }
    } else if ($farmer_id_exists && $seller_id_exists) {
        // Both columns exist, we need to copy data from farmer_id to seller_id and then drop farmer_id
        try {
            // Copy data from farmer_id to seller_id where seller_id is NULL
            $copy_data_query = "UPDATE order_items SET seller_id = farmer_id WHERE seller_id IS NULL AND farmer_id IS NOT NULL";
            $db->exec($copy_data_query);
            $success_message .= "Copied data from farmer_id to seller_id in order_items table.<br>";
            
            // Drop the farmer_id column
            $drop_column_query = "ALTER TABLE order_items DROP COLUMN farmer_id";
            $db->exec($drop_column_query);
            $success_message .= "Dropped farmer_id column from order_items table.<br>";
            $farmer_id_exists = false;
        } catch (PDOException $e) {
            $error_message .= "Error copying data or dropping farmer_id column: " . $e->getMessage() . "<br>";
        }
    }
    
    // Get the updated structure
    $updated_structure = [];
    $structure_query = "DESCRIBE order_items";
    $structure_stmt = $db->prepare($structure_query);
    $structure_stmt->execute();
    
    while ($row = $structure_stmt->fetch(PDO::FETCH_ASSOC)) {
        $updated_structure[] = $row;
    }
    
} catch (PDOException $e) {
    $error_message .= "Error checking table structure: " . $e->getMessage() . "<br>";
}

// Check if there are any orders with seller_id
$orders_count = 0;
if ($seller_id_exists) {
    try {
        $count_query = "SELECT COUNT(*) as total FROM order_items WHERE seller_id IS NOT NULL";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $orders_count = $count_result['total'];
        
        $success_message .= "Found " . $orders_count . " order items with seller_id.<br>";
    } catch (PDOException $e) {
        $error_message .= "Error counting orders: " . $e->getMessage() . "<br>";
    }
}

// Add sample data if needed
if (isset($_POST['add_sample']) && $seller_id_exists && $orders_count == 0) {
    try {
        // Get a seller ID
        $seller_query = "SELECT id FROM seller_registrations LIMIT 1";
        $seller_stmt = $db->prepare($seller_query);
        $seller_stmt->execute();
        $seller_row = $seller_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seller_row) {
            $seller_id = $seller_row['id'];
            
            // Get a customer ID
            $customer_query = "SELECT id FROM customer_registrations LIMIT 1";
            $customer_stmt = $db->prepare($customer_query);
            $customer_stmt->execute();
            $customer_row = $customer_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer_row) {
                $customer_id = $customer_row['id'];
                
                // Get a product ID
                $product_query = "SELECT id FROM products LIMIT 1";
                $product_stmt = $db->prepare($product_query);
                $product_stmt->execute();
                $product_row = $product_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product_row) {
                    $product_id = $product_row['id'];
                    
                    // Start transaction
                    $db->beginTransaction();
                    
                    // Create a new order
                    $order_query = "INSERT INTO orders (customer_id, total_amount, status, payment_method, payment_status, 
                                    shipping_address, shipping_city, shipping_state, shipping_postal_code)
                                    VALUES (?, 100.00, 'processing', 'Credit Card', 'completed', 
                                    '123 Test Street', 'Test City', 'Test State', '123456')";
                    $order_stmt = $db->prepare($order_query);
                    $order_stmt->bindParam(1, $customer_id);
                    $order_stmt->execute();
                    
                    // Get the new order ID
                    $order_id = $db->lastInsertId();
                    
                    // Create order item with seller_id
                    $item_query = "INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, status)
                                  VALUES (?, ?, ?, 1, 100.00, 'processing')";
                    $item_stmt = $db->prepare($item_query);
                    $item_stmt->bindParam(1, $order_id);
                    $item_stmt->bindParam(2, $product_id);
                    $item_stmt->bindParam(3, $seller_id);
                    $item_stmt->execute();
                    
                    // Commit transaction
                    $db->commit();
                    
                    $success_message .= "Added sample order with seller_id = " . $seller_id . "<br>";
                } else {
                    $error_message .= "No products found in the database.<br>";
                }
            } else {
                $error_message .= "No customers found in the database.<br>";
            }
        } else {
            $error_message .= "No sellers found in the database.<br>";
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message .= "Error adding sample data: " . $e->getMessage() . "<br>";
    }
}

// --- Add delivery_instructions column to orders table if missing ---
try {
    $orders_structure_query = "DESCRIBE orders";
    $orders_structure_stmt = $db->prepare($orders_structure_query);
    $orders_structure_stmt->execute();
    $orders_columns = $orders_structure_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('delivery_instructions', $orders_columns)) {
        $add_column_query = "ALTER TABLE orders ADD COLUMN delivery_instructions TEXT NULL";
        $db->exec($add_column_query);
        $success_message .= "Added delivery_instructions column to orders table.<br>";
    }
} catch (PDOException $e) {
    $error_message .= "Error adding delivery_instructions column: " . $e->getMessage() . "<br>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Order Items Column - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Fix Order Items Column</h1>
        
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
                <?php if (!empty($updated_structure)): ?>
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
                                <?php foreach ($updated_structure as $column): ?>
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
        
        <?php if ($seller_id_exists && $orders_count == 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add Sample Data</h5>
                </div>
                <div class="card-body">
                    <p>No order items with seller_id found. You can add a sample order to test the functionality.</p>
                    <form method="post" action="">
                        <button type="submit" name="add_sample" class="btn btn-primary">Add Sample Order</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
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
