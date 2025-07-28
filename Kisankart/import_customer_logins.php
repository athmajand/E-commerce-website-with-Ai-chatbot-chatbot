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
    die("<div class='alert alert-danger'>Database connection failed</div>");
}

// Initialize variables
$success = false;
$error = null;
$message = "";
$migrated_count = 0;

// SQL to create the customer_logins table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS customer_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) UNIQUE,
    password VARCHAR(255) NOT NULL,
    customer_profile_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE
);

-- Add index for faster login queries
CREATE INDEX idx_customer_logins_email ON customer_logins(email);
CREATE INDEX idx_customer_logins_phone ON customer_logins(phone);
";

// SQL to migrate existing customer data
$migrate_data_sql = "
INSERT IGNORE INTO customer_logins (email, phone, password, customer_profile_id, is_active)
SELECT u.email, u.phone, u.password, cp.id, 1
FROM users u
JOIN customer_profiles cp ON u.id = cp.user_id
WHERE u.role = 'customer';
";

try {
    // Execute create table SQL
    $db->exec($create_table_sql);
    $success = true;
    $message .= "Customer logins table created successfully.<br>";
    
    // Check if there are already records in the table
    $query = "SELECT COUNT(*) as count FROM customer_logins";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Execute migrate data SQL
        $db->exec($migrate_data_sql);
        
        // Count migrated records
        $query = "SELECT COUNT(*) as count FROM customer_logins";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $migrated_count = $result['count'];
        
        $message .= "Migrated $migrated_count customer records to the new table.<br>";
    } else {
        $message .= "Data migration skipped as records already exist in the table.<br>";
    }
} catch (Exception $e) {
    $success = false;
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Customer Logins Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 800px;
        }
        h1 {
            color: #1e8449;
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #1e8449;
            color: white;
            font-weight: 600;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .btn-success {
            background-color: #1e8449;
            border-color: #1e8449;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Import Customer Logins Table</h1>
        
        <div class="card">
            <div class="card-header">
                Import Result
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> <?php echo $message; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Error!</strong> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                SQL Executed
            </div>
            <div class="card-body">
                <h5>Create Table SQL:</h5>
                <pre><?php echo htmlspecialchars($create_table_sql); ?></pre>
                
                <h5>Migrate Data SQL:</h5>
                <pre><?php echo htmlspecialchars($migrate_data_sql); ?></pre>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="check_customer_logins_table.php" class="btn btn-success">Check Table Status</a>
            <a href="login.php" class="btn btn-primary">Go to Login Page</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
