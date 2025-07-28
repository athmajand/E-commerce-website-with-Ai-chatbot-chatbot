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
$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $confirmed) {
    try {
        // Start transaction
        $db->beginTransaction();
        
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
            
            $drop_fk_query = "ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`";
            $drop_fk_stmt = $db->prepare($drop_fk_query);
            
            if ($drop_fk_stmt->execute()) {
                $log[] = "Dropped foreign key constraint '$constraint' from table '$table'";
            } else {
                throw new Exception("Failed to drop foreign key constraint '$constraint' from table '$table'");
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
            $drop_table_query = "DROP TABLE IF EXISTS `$table`";
            $drop_table_stmt = $db->prepare($drop_table_query);
            
            if ($drop_table_stmt->execute()) {
                $log[] = "Dropped related table '$table'";
            } else {
                throw new Exception("Failed to drop related table '$table'");
            }
        }
        
        // Finally, drop the users table
        $drop_users_query = "DROP TABLE IF EXISTS `users`";
        $drop_users_stmt = $db->prepare($drop_users_query);
        
        if ($drop_users_stmt->execute()) {
            $log[] = "Dropped users table";
            $success_message = "Users table and its connections have been successfully removed.";
            
            // Commit transaction
            $db->commit();
        } else {
            throw new Exception("Failed to drop users table");
        }
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop Users Table - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Drop Users Table and Its Connections</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                            
                            <h5 class="mt-4">Operation Log:</h5>
                            <pre class="bg-light p-3 rounded"><?php echo implode("\n", $log); ?></pre>
                            
                            <div class="mt-4">
                                <a href="recreate_tables.php" class="btn btn-primary">Recreate Tables</a>
                                <a href="http://localhost:8080/phpmyadmin/index.php?route=/database/structure&db=kisan_kart" class="btn btn-info ms-2" target="_blank">View Database in phpMyAdmin</a>
                            </div>
                        <?php elseif ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            
                            <div class="mt-4">
                                <a href="drop_users_table.php" class="btn btn-primary">Try Again</a>
                                <a href="http://localhost:8080/phpmyadmin/index.php?route=/database/structure&db=kisan_kart" class="btn btn-info ms-2" target="_blank">View Database in phpMyAdmin</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5 class="alert-heading">⚠️ WARNING: This action cannot be undone!</h5>
                                <p>You are about to remove the users table and all related tables from your database. This will:</p>
                                <ul>
                                    <li>Delete ALL user accounts</li>
                                    <li>Delete ALL customer profiles</li>
                                    <li>Delete ALL seller profiles</li>
                                    <li>Delete ALL other data related to users</li>
                                </ul>
                                <p>Please make sure you have a backup before proceeding.</p>
                            </div>
                            
                            <form method="post" action="drop_users_table.php" onsubmit="return confirm('Are you absolutely sure you want to delete the users table and all related data? This cannot be undone!');">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="confirm" name="confirm" value="yes" required>
                                    <label class="form-check-label" for="confirm">I understand that this action will permanently delete all user data and cannot be undone.</label>
                                </div>
                                <button type="submit" class="btn btn-danger">Drop Users Table and Its Connections</button>
                                <a href="http://localhost:8080/phpmyadmin/index.php?route=/database/structure&db=kisan_kart" class="btn btn-secondary ms-2" target="_blank">Cancel and Go to phpMyAdmin</a>
                            </form>
                            
                            <div class="mt-4">
                                <a href="backup_schema.php" class="btn btn-primary" target="_blank">Create Database Backup First</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
