<?php
// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if we need to add the payment_details column
$add_column = isset($_GET['add_column']) && $_GET['add_column'] === 'true';

// Check if orders table exists
$check_table_query = "SHOW TABLES LIKE 'orders'";
$check_table_stmt = $db->prepare($check_table_query);
$check_table_stmt->execute();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Orders Table Structure</title>
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
        }
    </style>
</head>
<body>";

if ($check_table_stmt->rowCount() > 0) {
    echo "<h1>Orders Table Structure</h1>";

    // Get table structure
    $structure_query = "DESCRIBE orders";
    $structure_stmt = $db->prepare($structure_query);
    $structure_stmt->execute();

    // Check if payment_details column exists
    $payment_details_exists = false;
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
        if ($row['Field'] === 'payment_details') {
            $payment_details_exists = true;
        }
    }

    echo "</table>";

    // Add payment_details column if it doesn't exist and requested
    if (!$payment_details_exists) {
        echo "<div class='message warning'>The 'payment_details' column does not exist in the orders table.</div>";

        if ($add_column) {
            try {
                $alter_query = "ALTER TABLE orders ADD COLUMN payment_details VARCHAR(255) DEFAULT NULL AFTER payment_status";
                $db->exec($alter_query);
                echo "<div class='message success'>Successfully added 'payment_details' column to the orders table.</div>";
                echo "<a href='check_orders_table.php' class='action-btn'>Refresh</a>";
            } catch (PDOException $e) {
                echo "<div class='message error'>Error adding column: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<a href='check_orders_table.php?add_column=true' class='action-btn'>Add payment_details Column</a>";
        }
    } else {
        echo "<div class='message success'>The 'payment_details' column exists in the orders table.</div>";
    }
} else {
    echo "<div class='message error'>Orders table does not exist.</div>";
}

echo "</body></html>";
?>
