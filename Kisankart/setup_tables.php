<?php
// Script to set up all required tables for Kisan Kart

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Function to execute SQL file
function executeSqlFile($db, $file) {
    echo "Executing SQL file: $file<br>";
    
    try {
        // Read the SQL file
        $sql = file_get_contents($file);
        
        // Execute the SQL commands
        $result = $db->exec($sql);
        
        echo "SQL file executed successfully.<br>";
        return true;
    } catch (PDOException $e) {
        echo "Error executing SQL file: " . $e->getMessage() . "<br>";
        return false;
    }
}

// List of SQL files to execute
$sqlFiles = [
    'create_products_table.sql',
    'create_orders_table.sql',
    'create_wishlist_table.sql'
];

// Execute each SQL file
$success = true;
foreach ($sqlFiles as $file) {
    if (!executeSqlFile($db, $file)) {
        $success = false;
    }
}

// Display result
if ($success) {
    echo "<h2>All tables created successfully!</h2>";
    echo "<p>You can now use the Kisan Kart application with all required tables.</p>";
} else {
    echo "<h2>Some tables could not be created.</h2>";
    echo "<p>Please check the error messages above and fix any issues.</p>";
}

// Add a link to go back to the dashboard
echo "<p><a href='frontend/customer_dashboard.php'>Go to Customer Dashboard</a></p>";
?>
