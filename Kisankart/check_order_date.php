<?php
// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if we need to add the order_date column
$add_column = isset($_GET['add_column']) && $_GET['add_column'] === 'true';
$rename_column = isset($_GET['rename_column']) && $_GET['rename_column'] === 'true';

// Check if orders table exists
$check_table_query = "SHOW TABLES LIKE 'orders'";
$check_table_stmt = $db->prepare($check_table_query);
$check_table_stmt->execute();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Orders Table Date Field Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .warning { background-color: #fff3cd; color: #856404; }
        .action-btn { 
            display: inline-block; 
            padding: 8px 16px; 
            background-color: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin-top: 20px; 
            margin-right: 10px;
        }
    </style>
</head>
<body>";

if ($check_table_stmt->rowCount() > 0) {
    echo "<h1>Orders Table Date Fields</h1>";
    
    // Get table structure
    $structure_query = "DESCRIBE orders";
    $structure_stmt = $db->prepare($structure_query);
    $structure_stmt->execute();
    
    // Check if order_date column exists
    $order_date_exists = false;
    $created_at_exists = false;
    $columns = [];
    
    echo "<table>
        <tr>
            <th>Field</th>
            <th>Type</th>
            <th>Null</th>
            <th>Key</th>
            <th>Default</th>
            <th>Extra</th>
        </tr>";
    
    while ($row = $structure_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
            <td>{$row['Field']}</td>
            <td>{$row['Type']}</td>
            <td>{$row['Null']}</td>
            <td>{$row['Key']}</td>
            <td>{$row['Default']}</td>
            <td>{$row['Extra']}</td>
        </tr>";
        
        $columns[] = $row['Field'];
        if ($row['Field'] === 'order_date') {
            $order_date_exists = true;
        }
        if ($row['Field'] === 'created_at') {
            $created_at_exists = true;
        }
    }
    
    echo "</table>";
    
    // Add or rename columns based on what exists
    if (!$order_date_exists && !$created_at_exists) {
        echo "<div class='message warning'>Neither 'order_date' nor 'created_at' columns exist in the orders table.</div>";
        
        if ($add_column) {
            try {
                $alter_query = "ALTER TABLE orders ADD COLUMN order_date DATETIME DEFAULT CURRENT_TIMESTAMP AFTER id";
                $db->exec($alter_query);
                echo "<div class='message success'>Successfully added 'order_date' column to the orders table.</div>";
                echo "<a href='check_order_date.php' class='action-btn'>Refresh</a>";
            } catch (PDOException $e) {
                echo "<div class='message error'>Error adding column: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<a href='check_order_date.php?add_column=true' class='action-btn'>Add order_date Column</a>";
        }
    } elseif (!$order_date_exists && $created_at_exists) {
        echo "<div class='message warning'>The 'order_date' column does not exist, but 'created_at' does. You can use 'created_at' or rename it to 'order_date'.</div>";
        
        if ($rename_column) {
            try {
                $alter_query = "ALTER TABLE orders CHANGE COLUMN created_at order_date DATETIME DEFAULT CURRENT_TIMESTAMP";
                $db->exec($alter_query);
                echo "<div class='message success'>Successfully renamed 'created_at' column to 'order_date'.</div>";
                echo "<a href='check_order_date.php' class='action-btn'>Refresh</a>";
            } catch (PDOException $e) {
                echo "<div class='message error'>Error renaming column: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<a href='check_order_date.php?rename_column=true' class='action-btn'>Rename created_at to order_date</a>";
        }
    } elseif ($order_date_exists) {
        echo "<div class='message success'>The 'order_date' column exists in the orders table.</div>";
    }
    
    // Check if there are orders with null order_date
    if ($order_date_exists) {
        $check_null_query = "SELECT COUNT(*) as null_count FROM orders WHERE order_date IS NULL";
        $check_null_stmt = $db->prepare($check_null_query);
        $check_null_stmt->execute();
        $null_count = $check_null_stmt->fetch(PDO::FETCH_ASSOC)['null_count'];
        
        if ($null_count > 0) {
            echo "<div class='message warning'>There are {$null_count} orders with NULL order_date values.</div>";
            
            if (isset($_GET['update_null']) && $_GET['update_null'] === 'true') {
                try {
                    $update_query = "UPDATE orders SET order_date = NOW() WHERE order_date IS NULL";
                    $db->exec($update_query);
                    echo "<div class='message success'>Successfully updated NULL order_date values.</div>";
                    echo "<a href='check_order_date.php' class='action-btn'>Refresh</a>";
                } catch (PDOException $e) {
                    echo "<div class='message error'>Error updating NULL values: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<a href='check_order_date.php?update_null=true' class='action-btn'>Update NULL order_date Values</a>";
            }
        }
    }
} else {
    echo "<div class='message error'>Orders table does not exist.</div>";
}

echo "<div style='margin-top: 20px;'>
    <a href='check_orders_table.php' class='action-btn'>Back to Orders Table Structure</a>
    <a href='index.php' class='action-btn'>Back to Home</a>
</div>";

echo "</body></html>";
?>
