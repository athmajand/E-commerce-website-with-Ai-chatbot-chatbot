<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Include database configuration
include_once 'api/config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Check if connection was successful
if (!$db) {
    die("<div class='alert alert-danger'>Database connection failed</div>");
}

// Initialize variables
$table_exists = false;
$table_structure = [];
$table_records = [];
$error = null;

try {
    // Check if customer_logins table exists
    $query = "SHOW TABLES LIKE 'customer_logins'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $table_exists = ($stmt->rowCount() > 0);
    
    if ($table_exists) {
        // Get table structure
        $query = "DESCRIBE customer_logins";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get record count
        $query = "SELECT COUNT(*) as count FROM customer_logins";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $count_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $record_count = $count_result['count'];
        
        // Get sample records (limited to 5)
        if ($record_count > 0) {
            $query = "SELECT * FROM customer_logins LIMIT 5";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $table_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Logins Table Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 1000px;
        }
        h1 {
            color: #1e8449;
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #1e8449;
            color: white;
            font-weight: 600;
        }
        .table {
            margin-bottom: 0;
        }
        .btn-success {
            background-color: #1e8449;
            border-color: #1e8449;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Customer Logins Table Check</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                Table Status
            </div>
            <div class="card-body">
                <?php if ($table_exists): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> The customer_logins table exists in the database.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <strong>Warning!</strong> The customer_logins table does not exist in the database.
                    </div>
                    <p>You need to create the table by importing the SQL script:</p>
                    <ol>
                        <li>Open phpMyAdmin (usually at http://localhost/phpmyadmin)</li>
                        <li>Select the "kisan_kart" database</li>
                        <li>Go to the "Import" tab</li>
                        <li>Choose the customer_logins.sql file and click "Go"</li>
                    </ol>
                    <a href="customer_logins.sql" class="btn btn-primary" download>Download SQL Script</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($table_exists): ?>
            <div class="card">
                <div class="card-header">
                    Table Structure
                </div>
                <div class="card-body">
                    <div class="table-responsive">
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
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Record Count
                </div>
                <div class="card-body">
                    <p>The customer_logins table contains <strong><?php echo $record_count; ?></strong> records.</p>
                    
                    <?php if ($record_count == 0): ?>
                        <div class="alert alert-warning">
                            <strong>Warning!</strong> No records found in the customer_logins table.
                        </div>
                        <p>You may need to migrate existing customer data to the new table.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($table_records)): ?>
                <div class="card">
                    <div class="card-header">
                        Sample Records
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($table_records[0]) as $column): ?>
                                            <th><?php echo $column; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table_records as $record): ?>
                                        <tr>
                                            <?php foreach ($record as $value): ?>
                                                <td><?php echo $value; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="login.php" class="btn btn-success">Go to Login Page</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
