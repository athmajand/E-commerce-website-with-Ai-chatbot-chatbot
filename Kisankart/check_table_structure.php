<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check customer_registrations table structure
try {
    echo "<h2>Customer Registrations Table Structure</h2>";
    
    $query = "DESCRIBE customer_registrations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error checking table structure: " . $e->getMessage() . "</p>";
}
?>
