<?php
// Headers
header("Content-Type: text/plain; charset=UTF-8");

// Include database configuration
include_once 'api/config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Check if connection was successful
if (!$db) {
    die("Database connection failed");
}

// Get all tables
$tables_query = "SHOW TABLES";
$tables_stmt = $db->prepare($tables_query);
$tables_stmt->execute();
$tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);

// Output schema
echo "-- Kisan Kart Database Schema Backup\n";
echo "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

// Get schema for each table
foreach ($tables as $table) {
    echo "-- Table structure for table `$table`\n";
    
    // Get create table statement
    $create_query = "SHOW CREATE TABLE `$table`";
    $create_stmt = $db->prepare($create_query);
    $create_stmt->execute();
    $create_row = $create_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo $create_row['Create Table'] . ";\n\n";
    
    // Get sample data (first 5 rows)
    echo "-- Sample data for table `$table` (first 5 rows)\n";
    $data_query = "SELECT * FROM `$table` LIMIT 5";
    $data_stmt = $db->prepare($data_query);
    $data_stmt->execute();
    $data_rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($data_rows)) {
        $columns = array_keys($data_rows[0]);
        $column_list = "`" . implode("`, `", $columns) . "`";
        
        echo "INSERT INTO `$table` ($column_list) VALUES\n";
        
        $row_values = [];
        foreach ($data_rows as $row) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            $row_values[] = "(" . implode(", ", $values) . ")";
        }
        
        echo implode(",\n", $row_values) . ";\n\n";
    } else {
        echo "-- No data found\n\n";
    }
}

// Get foreign key constraints
echo "-- Foreign key constraints\n";
$fk_query = "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_SCHEMA = 'kisan_kart'
             AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY TABLE_NAME, COLUMN_NAME";
$fk_stmt = $db->prepare($fk_query);
$fk_stmt->execute();
$fk_rows = $fk_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($fk_rows as $fk) {
    echo "-- {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']} (Constraint: {$fk['CONSTRAINT_NAME']})\n";
}
?>
