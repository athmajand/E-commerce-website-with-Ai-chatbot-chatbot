<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection with buffered queries
$database = new Database();
$db = $database->getConnection();

// Double-check that buffered queries are enabled
if ($db) {
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
}

// Function to execute a query and handle errors
function executeQuery($db, $query, $description) {
    try {
        // For SELECT queries, use query() and fetchAll()
        if (stripos(trim($query), 'SELECT') === 0 || 
            stripos(trim($query), 'SHOW') === 0 || 
            stripos(trim($query), 'DESCRIBE') === 0) {
            $stmt = $db->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'success' => true,
                'description' => $description,
                'result' => $result
            ];
        } else {
            // For non-SELECT queries, use exec()
            $result = $db->exec($query);
            return [
                'success' => true,
                'description' => $description,
                'affected_rows' => $result
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'description' => $description,
            'error' => $e->getMessage()
        ];
    }
}

// Read SQL file
$sql_file = __DIR__ . '/update_seller_registrations_schema.sql';
$sql = file_get_contents($sql_file);

// Split SQL by semicolon
$queries = explode(';', $sql);
$results = [];

// Execute each query
foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        $description = "Executing: " . substr($query, 0, 50) . "...";
        $results[] = executeQuery($db, $query, $description);
    }
}

// Check if columns exist after update
$check_columns_query = "SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_COMMENT
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'kisan_kart' 
    AND TABLE_NAME = 'seller_registrations'
ORDER BY 
    ORDINAL_POSITION";

$columns_result = executeQuery($db, $check_columns_query, "Checking columns after update");

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Seller Registrations Schema</title>
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
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
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
        .back-btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Seller Registrations Schema</h1>
        
        <div class="section">
            <h2>About This Page</h2>
            <p>This page updates the seller_registrations table schema to add additional fields collected in the seller registration form.</p>
            <p>PDO buffered queries enabled: <?php echo $db && $db->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></p>
        </div>
        
        <div class="section">
            <h2>Execution Results</h2>
            <?php foreach ($results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <h3><?php echo htmlspecialchars($result['description']); ?>: <?php echo $result['success'] ? 'Success' : 'Error'; ?></h3>
                    
                    <?php if (!$result['success']): ?>
                        <p>Error message: <?php echo htmlspecialchars($result['error']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($columns_result['success'] && !empty($columns_result['result'])): ?>
            <div class="section">
                <h2>Table Columns After Update</h2>
                <table>
                    <tr>
                        <th>Column Name</th>
                        <th>Data Type</th>
                        <th>Nullable</th>
                        <th>Comment</th>
                    </tr>
                    <?php foreach ($columns_result['result'] as $column): ?>
                        <tr>
                            <td><?php echo $column['COLUMN_NAME']; ?></td>
                            <td><?php echo $column['DATA_TYPE']; ?></td>
                            <td><?php echo $column['IS_NULLABLE']; ?></td>
                            <td><?php echo $column['COLUMN_COMMENT']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Next Steps</h2>
            <p>The database schema has been updated. Now you need to:</p>
            <ol>
                <li>Update the SellerRegistration.php model to include the new fields</li>
                <li>Update the seller_registration.php form processing to map form fields to the new database columns</li>
                <li>Implement file upload handling for documents and images</li>
            </ol>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
