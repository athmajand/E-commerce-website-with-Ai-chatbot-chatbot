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

// Output array
$output = array(
    "table_exists" => false,
    "table_structure" => null,
    "has_delivery_instructions" => false,
    "error" => null
);

try {
    // Check if orders table exists
    $check_table_query = "SHOW TABLES LIKE 'orders'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();
    
    $output["table_exists"] = ($check_table_stmt->rowCount() > 0);
    
    if ($output["table_exists"]) {
        // Get table structure
        $table_structure_query = "DESCRIBE orders";
        $table_structure_stmt = $db->prepare($table_structure_query);
        $table_structure_stmt->execute();
        
        $output["table_structure"] = $table_structure_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if delivery_instructions column exists
        foreach ($output["table_structure"] as $column) {
            if ($column['Field'] === 'delivery_instructions') {
                $output["has_delivery_instructions"] = true;
                break;
            }
        }
        
        // If delivery_instructions column doesn't exist, add it
        if (!$output["has_delivery_instructions"]) {
            $alter_table_query = "ALTER TABLE orders ADD COLUMN delivery_instructions TEXT AFTER shipping_postal_code";
            $db->exec($alter_table_query);
            $output["column_added"] = true;
            
            // Verify column was added
            $table_structure_query = "DESCRIBE orders";
            $table_structure_stmt = $db->prepare($table_structure_query);
            $table_structure_stmt->execute();
            $output["updated_table_structure"] = $table_structure_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($output["updated_table_structure"] as $column) {
                if ($column['Field'] === 'delivery_instructions') {
                    $output["has_delivery_instructions"] = true;
                    break;
                }
            }
        }
    }
} catch (PDOException $e) {
    $output["error"] = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Delivery Instructions - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Verify Delivery Instructions Column</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($output["error"]): ?>
                            <div class="alert alert-danger">
                                <strong>Error:</strong> <?php echo $output["error"]; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert <?php echo $output["table_exists"] ? 'alert-success' : 'alert-warning'; ?>">
                                <strong>Orders Table:</strong> <?php echo $output["table_exists"] ? 'Exists' : 'Does not exist'; ?>
                            </div>
                            
                            <?php if ($output["table_exists"]): ?>
                                <div class="alert <?php echo $output["has_delivery_instructions"] ? 'alert-success' : 'alert-warning'; ?>">
                                    <strong>Delivery Instructions Column:</strong> <?php echo $output["has_delivery_instructions"] ? 'Exists' : 'Does not exist'; ?>
                                    <?php if (isset($output["column_added"]) && $output["column_added"]): ?>
                                        <br><strong>Action:</strong> Column was added successfully.
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="mt-4">Table Structure:</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Field</th>
                                                <th>Type</th>
                                                <th>Null</th>
                                                <th>Key</th>
                                                <th>Default</th>
                                                <th>Extra</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $structure = isset($output["updated_table_structure"]) ? $output["updated_table_structure"] : $output["table_structure"];
                                            foreach ($structure as $column): 
                                            ?>
                                                <tr <?php echo ($column['Field'] === 'delivery_instructions') ? 'class="table-success"' : ''; ?>>
                                                    <td><?php echo $column['Field']; ?></td>
                                                    <td><?php echo $column['Type']; ?></td>
                                                    <td><?php echo $column['Null']; ?></td>
                                                    <td><?php echo $column['Key']; ?></td>
                                                    <td><?php echo $column['Default']; ?></td>
                                                    <td><?php echo $column['Extra']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="frontend/checkout.php" class="btn btn-primary">Go to Checkout Page</a>
                            <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
