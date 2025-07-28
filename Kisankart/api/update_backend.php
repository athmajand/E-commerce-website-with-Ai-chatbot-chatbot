<?php
// Include database configuration
include_once __DIR__ . '/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Read SQL file
$sql = file_get_contents(__DIR__ . '/config/update_backend_schema.sql');

// Execute SQL queries
try {
    $db->exec($sql);
    echo "Database schema updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}
?>
