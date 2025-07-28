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
    die("Database connection failed");
}

// Initialize variables
$success_message = '';
$error_message = '';
$log = [];

// Process the removal
try {
    // Get all foreign key constraints referencing users table
    $fk_query = "SELECT TABLE_NAME, CONSTRAINT_NAME
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE REFERENCED_TABLE_SCHEMA = 'kisan_kart'
                 AND REFERENCED_TABLE_NAME = 'users'
                 ORDER BY TABLE_NAME";
    $fk_stmt = $db->prepare($fk_query);
    $fk_stmt->execute();
    $fk_rows = $fk_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Drop foreign key constraints
    foreach ($fk_rows as $fk) {
        $table = $fk['TABLE_NAME'];
        $constraint = $fk['CONSTRAINT_NAME'];
        
        try {
            $drop_fk_query = "ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`";
            $drop_fk_stmt = $db->prepare($drop_fk_query);
            $drop_fk_stmt->execute();
            $log[] = "Dropped foreign key constraint '$constraint' from table '$table'";
        } catch (Exception $e) {
            $log[] = "Error dropping constraint '$constraint': " . $e->getMessage();
        }
    }
    
    // Get all tables that have a user_id column (potential related tables)
    $related_tables_query = "SELECT TABLE_NAME
                           FROM INFORMATION_SCHEMA.COLUMNS
                           WHERE TABLE_SCHEMA = 'kisan_kart'
                           AND COLUMN_NAME = 'user_id'
                           AND TABLE_NAME != 'users'";
    $related_tables_stmt = $db->prepare($related_tables_query);
    $related_tables_stmt->execute();
    $related_tables = $related_tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Drop related tables
    foreach ($related_tables as $table) {
        try {
            $drop_table_query = "DROP TABLE IF EXISTS `$table`";
            $drop_table_stmt = $db->prepare($drop_table_query);
            $drop_table_stmt->execute();
            $log[] = "Dropped related table '$table'";
        } catch (Exception $e) {
            $log[] = "Error dropping table '$table': " . $e->getMessage();
        }
    }
    
    // Finally, drop the users table
    try {
        $drop_users_query = "DROP TABLE IF EXISTS `users`";
        $drop_users_stmt = $db->prepare($drop_users_query);
        $drop_users_stmt->execute();
        $log[] = "Dropped users table";
        $success_message = "Users table and its connections have been successfully removed.";
    } catch (Exception $e) {
        $log[] = "Error dropping users table: " . $e->getMessage();
        $error_message = "Failed to drop users table: " . $e->getMessage();
    }
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Users Table - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Remove Users Table</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <h5 class="mt-4">Operation Log:</h5>
                        <pre class="bg-light p-3 rounded"><?php echo implode("\n", $log); ?></pre>
                        
                        <div class="mt-4">
                            <a href="http://localhost:8080/phpmyadmin/index.php?route=/database/structure&db=kisan_kart" class="btn btn-primary" target="_blank">View Database in phpMyAdmin</a>
                            <a href="index.php" class="btn btn-secondary ms-2">Go to Homepage</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
