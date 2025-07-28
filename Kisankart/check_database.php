<?php
// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Function to check database tables
function checkTable($db, $table_name) {
    try {
        $query = "SHOW TABLES LIKE '{$table_name}'";
        $stmt = $db->query($query);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get table structure
function getTableStructure($db, $table_name) {
    try {
        $query = "DESCRIBE {$table_name}";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to count records in a table
function countRecords($db, $table_name) {
    try {
        $query = "SELECT COUNT(*) as count FROM {$table_name}";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to get all records from a table
function getAllRecords($db, $table_name, $limit = 10) {
    try {
        $query = "SELECT * FROM {$table_name} LIMIT {$limit}";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Check if seller_registrations table exists
$seller_reg_exists = checkTable($db, 'seller_registrations');
$seller_reg_structure = $seller_reg_exists ? getTableStructure($db, 'seller_registrations') : [];
$seller_reg_count = $seller_reg_exists ? countRecords($db, 'seller_registrations') : 0;
$seller_reg_records = $seller_reg_exists ? getAllRecords($db, 'seller_registrations') : [];

// Check if customer_registrations table exists
$customer_reg_exists = checkTable($db, 'customer_registrations');
$customer_reg_structure = $customer_reg_exists ? getTableStructure($db, 'customer_registrations') : [];
$customer_reg_count = $customer_reg_exists ? countRecords($db, 'customer_registrations') : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #4CAF50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-exists {
            background-color: #c8e6c9;
            color: #2e7d32;
        }
        .status-not-exists {
            background-color: #ffcdd2;
            color: #c62828;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Kisan Kart Database Check</h1>
        
        <div class="section">
            <h2>Seller Registrations Table</h2>
            <p>
                Status: 
                <span class="status <?php echo $seller_reg_exists ? 'status-exists' : 'status-not-exists'; ?>">
                    <?php echo $seller_reg_exists ? 'EXISTS' : 'DOES NOT EXIST'; ?>
                </span>
            </p>
            
            <?php if ($seller_reg_exists): ?>
                <p>Total Records: <?php echo $seller_reg_count; ?></p>
                
                <h3>Table Structure</h3>
                <table>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                    <?php foreach ($seller_reg_structure as $column): ?>
                        <tr>
                            <td><?php echo $column['Field']; ?></td>
                            <td><?php echo $column['Type']; ?></td>
                            <td><?php echo $column['Null']; ?></td>
                            <td><?php echo $column['Key']; ?></td>
                            <td><?php echo $column['Default']; ?></td>
                            <td><?php echo $column['Extra']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <?php if (!empty($seller_reg_records)): ?>
                    <h3>Recent Records</h3>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Business Name</th>
                            <th>Status</th>
                        </tr>
                        <?php foreach ($seller_reg_records as $record): ?>
                            <tr>
                                <td><?php echo $record['id']; ?></td>
                                <td><?php echo $record['first_name']; ?></td>
                                <td><?php echo $record['last_name']; ?></td>
                                <td><?php echo $record['email']; ?></td>
                                <td><?php echo $record['phone']; ?></td>
                                <td><?php echo $record['business_name']; ?></td>
                                <td><?php echo $record['status']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No records found in the seller_registrations table.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Customer Registrations Table</h2>
            <p>
                Status: 
                <span class="status <?php echo $customer_reg_exists ? 'status-exists' : 'status-not-exists'; ?>">
                    <?php echo $customer_reg_exists ? 'EXISTS' : 'DOES NOT EXIST'; ?>
                </span>
            </p>
            
            <?php if ($customer_reg_exists): ?>
                <p>Total Records: <?php echo $customer_reg_count; ?></p>
                
                <h3>Table Structure</h3>
                <table>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                    <?php foreach ($customer_reg_structure as $column): ?>
                        <tr>
                            <td><?php echo $column['Field']; ?></td>
                            <td><?php echo $column['Type']; ?></td>
                            <td><?php echo $column['Null']; ?></td>
                            <td><?php echo $column['Key']; ?></td>
                            <td><?php echo $column['Default']; ?></td>
                            <td><?php echo $column['Extra']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
