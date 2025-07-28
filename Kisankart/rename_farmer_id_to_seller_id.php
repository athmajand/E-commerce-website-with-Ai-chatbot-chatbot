<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$error_message = '';
$success_message = '';
$column_exists = false;
$column_renamed = false;
$affected_files = [];

// Check if the column exists
try {
    $check_column_query = "SHOW COLUMNS FROM `order_items` LIKE 'farmer_id'";
    $check_column_stmt = $db->prepare($check_column_query);
    $check_column_stmt->execute();
    $column_exists = $check_column_stmt->rowCount() > 0;
    
    if ($column_exists) {
        $success_message .= "The 'farmer_id' column exists in the order_items table.<br>";
    } else {
        // Check if seller_id already exists
        $check_seller_id_query = "SHOW COLUMNS FROM `order_items` LIKE 'seller_id'";
        $check_seller_id_stmt = $db->prepare($check_seller_id_query);
        $check_seller_id_stmt->execute();
        
        if ($check_seller_id_stmt->rowCount() > 0) {
            $success_message .= "The 'seller_id' column already exists in the order_items table. No need to rename.<br>";
            $column_renamed = true;
        } else {
            $error_message .= "The 'farmer_id' column does not exist in the order_items table and neither does 'seller_id'.<br>";
        }
    }
} catch (PDOException $e) {
    $error_message .= "Error checking column: " . $e->getMessage() . "<br>";
}

// Process the rename if requested
if (isset($_POST['rename_column']) && $column_exists && !$column_renamed) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Get column details to preserve data type and constraints
        $column_details_query = "SHOW COLUMNS FROM `order_items` WHERE Field = 'farmer_id'";
        $column_details_stmt = $db->prepare($column_details_query);
        $column_details_stmt->execute();
        $column_details = $column_details_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Rename the column
        $rename_query = "ALTER TABLE `order_items` CHANGE COLUMN `farmer_id` `seller_id` " . 
                        $column_details['Type'] . 
                        ($column_details['Null'] === 'NO' ? ' NOT NULL' : '') . 
                        ($column_details['Default'] !== null ? " DEFAULT '" . $column_details['Default'] . "'" : '');
        
        $db->exec($rename_query);
        
        // Commit transaction
        $db->commit();
        
        $success_message .= "Successfully renamed 'farmer_id' to 'seller_id' in the order_items table.<br>";
        $column_renamed = true;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $error_message .= "Error renaming column: " . $e->getMessage() . "<br>";
    }
}

// Find all PHP files that might reference farmer_id
$affected_files = [];
$directories = ['api', 'frontend', 'backend'];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                if (strpos($content, 'farmer_id') !== false) {
                    $affected_files[] = [
                        'path' => $file->getPathname(),
                        'relative_path' => str_replace('\\', '/', substr($file->getPathname(), strlen(getcwd()) + 1))
                    ];
                }
            }
        }
    }
}

// Update affected files if requested
$updated_files = [];
if (isset($_POST['update_files']) && $column_renamed && !empty($affected_files)) {
    foreach ($affected_files as $file) {
        $content = file_get_contents($file['path']);
        $updated_content = str_replace('farmer_id', 'seller_id', $content);
        
        if ($content !== $updated_content) {
            file_put_contents($file['path'], $updated_content);
            $updated_files[] = $file['relative_path'];
        }
    }
    
    if (!empty($updated_files)) {
        $success_message .= "Updated " . count($updated_files) . " files to use 'seller_id' instead of 'farmer_id'.<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rename farmer_id to seller_id - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .file-list {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Rename farmer_id to seller_id</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Database Column Rename</h5>
            </div>
            <div class="card-body">
                <?php if ($column_exists && !$column_renamed): ?>
                    <p>The 'farmer_id' column exists in the order_items table and needs to be renamed to 'seller_id'.</p>
                    <form method="post" action="">
                        <button type="submit" name="rename_column" class="btn btn-primary">Rename Column</button>
                    </form>
                <?php elseif ($column_renamed): ?>
                    <p class="text-success">The column has been successfully renamed to 'seller_id'.</p>
                <?php else: ?>
                    <p class="text-warning">The 'farmer_id' column does not exist in the order_items table.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($affected_files)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Affected PHP Files</h5>
                </div>
                <div class="card-body">
                    <p>The following PHP files contain references to 'farmer_id' and need to be updated:</p>
                    <div class="file-list mb-3">
                        <ul class="list-group">
                            <?php foreach ($affected_files as $file): ?>
                                <li class="list-group-item">
                                    <?php echo htmlspecialchars($file['relative_path']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if ($column_renamed && empty($updated_files)): ?>
                        <form method="post" action="">
                            <div class="alert alert-warning">
                                <p><strong>Warning:</strong> This will replace all instances of 'farmer_id' with 'seller_id' in the files listed above. Make sure you have a backup before proceeding.</p>
                            </div>
                            <button type="submit" name="update_files" class="btn btn-warning">Update Files</button>
                        </form>
                    <?php elseif (!empty($updated_files)): ?>
                        <div class="alert alert-success">
                            <p>The following files have been updated:</p>
                            <ul>
                                <?php foreach ($updated_files as $file): ?>
                                    <li><?php echo htmlspecialchars($file); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="test_seller_orders.php" class="btn btn-primary">Test Seller Orders</a>
            <a href="frontend/seller/orders.php" class="btn btn-success ms-2">Go to Seller Orders Page</a>
            <a href="index.php" class="btn btn-secondary ms-2">Back to Home</a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
