<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if buffered queries are enabled
$buffered = $db && $db->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);

// Function to test multiple queries
function testMultipleQueries($db) {
    try {
        // First query
        $query1 = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'kisan_kart'";
        $stmt1 = $db->query($query1);
        
        // Second query without fetching results from first
        $query2 = "SHOW TABLES FROM kisan_kart";
        $stmt2 = $db->query($query2);
        
        // Now fetch results from both
        $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $tables = [];
        while ($row = $stmt2->fetch(PDO::FETCH_COLUMN)) {
            $tables[] = $row;
        }
        
        return [
            'success' => true,
            'table_count' => $result1['count'],
            'tables' => $tables
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Test the queries
$test_result = testMultipleQueries($db);

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Buffered Queries</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #4CAF50;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #F44336;
            font-weight: bold;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Buffered Queries</h1>
        
        <h2>Database Connection</h2>
        <p>Connection status: <?php echo $db ? '<span class="success">Connected</span>' : '<span class="error">Failed</span>'; ?></p>
        <p>Buffered queries: <?php echo $buffered ? '<span class="success">Enabled</span>' : '<span class="error">Disabled</span>'; ?></p>
        
        <h2>Multiple Queries Test</h2>
        <?php if ($test_result['success']): ?>
            <p class="success">Test passed! Multiple queries executed successfully.</p>
            <p>Number of tables in database: <?php echo $test_result['table_count']; ?></p>
            <p>Tables:</p>
            <ul>
                <?php foreach ($test_result['tables'] as $table): ?>
                    <li><?php echo htmlspecialchars($table); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="error">Test failed!</p>
            <p>Error: <?php echo htmlspecialchars($test_result['error']); ?></p>
        <?php endif; ?>
        
        <h2>Next Steps</h2>
        <p>If the test passed, your database connection is now correctly configured to use buffered queries.</p>
        <p>This should resolve the "Cannot execute queries while other unbuffered queries are active" error.</p>
        
        <p><a href="fix_database.php" style="display: inline-block; background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Go to Fix Database Page</a></p>
    </div>
</body>
</html>
