<?php
// Script to create the customer_registrations table

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Read SQL file
$sql = file_get_contents(__DIR__ . '/create_customer_registrations_table.sql');

// Execute SQL
try {
    // Execute the SQL directly
    $result = $db->exec($sql);

    // Check if table exists
    $check_query = "SHOW TABLES LIKE 'customer_registrations'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "Customer registrations table created or already exists!";

        // Check table structure
        $describe_query = "DESCRIBE customer_registrations";
        $stmt = $db->prepare($describe_query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<br><br>Table structure:<br>";
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
    } else {
        echo "Error: Table was not created successfully.";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
