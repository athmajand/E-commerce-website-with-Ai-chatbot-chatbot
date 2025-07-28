<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Database Structure Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #1e8449; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn { background: #1e8449; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; }
        .btn:hover { background: #166938; }
    </style>
</head>
<body>
    <h1>Database Structure Check</h1>';

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '<div class="section">
        <p class="error">Database connection failed. Please check your MySQL connection.</p>
    </div>';
} else {
    echo '<div class="section">
        <p class="success">Database connection successful.</p>
    </div>';
    
    // Check database tables
    try {
        // Get all tables
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo '<div class="section">
            <h2>Database Tables</h2>';
        
        if (count($tables) > 0) {
            echo '<p>Found ' . count($tables) . ' tables in the database:</p>';
            echo '<ul>';
            foreach ($tables as $table) {
                echo '<li>' . $table . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="warning">No tables found in the database.</p>';
        }
        
        echo '</div>';
        
        // Check customer_registrations table structure
        if (in_array('customer_registrations', $tables)) {
            echo '<div class="section">
                <h2>customer_registrations Table Structure</h2>';
            
            // Get table structure
            $stmt = $db->query("DESCRIBE customer_registrations");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                    <th>Extra</th>
                </tr>';
            
            $hasIsVerified = false;
            $hasStatus = false;
            
            foreach ($columns as $column) {
                echo '<tr>';
                foreach ($column as $key => $value) {
                    echo '<td>' . ($value === null ? 'NULL' : $value) . '</td>';
                }
                echo '</tr>';
                
                if ($column['Field'] === 'is_verified') {
                    $hasIsVerified = true;
                }
                
                if ($column['Field'] === 'status') {
                    $hasStatus = true;
                }
            }
            
            echo '</table>';
            
            // Check for required fields
            $requiredFields = ['id', 'first_name', 'last_name', 'email', 'phone', 'password'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                $found = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === $field) {
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $missingFields[] = $field;
                }
            }
            
            if (count($missingFields) > 0) {
                echo '<p class="error">Missing required fields: ' . implode(', ', $missingFields) . '</p>';
                
                // Generate SQL to add missing fields
                echo '<p>SQL to add missing fields:</p>';
                echo '<div style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;">';
                
                foreach ($missingFields as $field) {
                    $sql = "ALTER TABLE customer_registrations ADD COLUMN ";
                    
                    switch ($field) {
                        case 'id':
                            $sql .= "id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
                            break;
                        case 'first_name':
                        case 'last_name':
                            $sql .= "$field VARCHAR(100) NOT NULL";
                            break;
                        case 'email':
                            $sql .= "email VARCHAR(255) NOT NULL UNIQUE";
                            break;
                        case 'phone':
                            $sql .= "phone VARCHAR(20) NOT NULL UNIQUE";
                            break;
                        case 'password':
                            $sql .= "password VARCHAR(255) NOT NULL";
                            break;
                        default:
                            $sql .= "$field VARCHAR(255)";
                    }
                    
                    echo $sql . ";<br>";
                }
                
                echo '</div>';
            } else {
                echo '<p class="success">All required fields are present.</p>';
            }
            
            // Check for verification fields
            if (!$hasIsVerified) {
                echo '<p class="warning">The is_verified field is missing.</p>';
                echo '<p>SQL to add is_verified field:</p>';
                echo '<div style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;">';
                echo 'ALTER TABLE customer_registrations ADD COLUMN is_verified TINYINT(1) DEFAULT 0;';
                echo '</div>';
                
                // Add button to add the field
                echo '<form method="post" action="check_db_structure.php">
                    <button type="submit" name="add_is_verified" class="btn">Add is_verified Field</button>
                </form>';
            } else {
                echo '<p class="success">The is_verified field is present.</p>';
            }
            
            if (!$hasStatus) {
                echo '<p class="warning">The status field is missing.</p>';
                echo '<p>SQL to add status field:</p>';
                echo '<div style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;">';
                echo 'ALTER TABLE customer_registrations ADD COLUMN status ENUM(\'pending\', \'approved\', \'rejected\') DEFAULT \'pending\';';
                echo '</div>';
                
                // Add button to add the field
                echo '<form method="post" action="check_db_structure.php">
                    <button type="submit" name="add_status" class="btn">Add status Field</button>
                </form>';
            } else {
                echo '<p class="success">The status field is present.</p>';
            }
            
            // Add button to update all records to verified and approved
            echo '<form method="post" action="check_db_structure.php">
                <button type="submit" name="update_all_verified" class="btn">Set All Records to Verified and Approved</button>
            </form>';
            
            echo '</div>';
            
            // Check sample data
            echo '<div class="section">
                <h2>Sample Data</h2>';
            
            $stmt = $db->query("SELECT * FROM customer_registrations LIMIT 5");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($records) > 0) {
                echo '<table>
                    <tr>';
                
                // Get column names
                $columns = array_keys($records[0]);
                foreach ($columns as $column) {
                    echo '<th>' . $column . '</th>';
                }
                
                echo '</tr>';
                
                // Output data
                foreach ($records as $record) {
                    echo '<tr>';
                    foreach ($record as $key => $value) {
                        if ($key === 'password') {
                            echo '<td>[HIDDEN]</td>';
                        } else {
                            echo '<td>' . ($value === null ? 'NULL' : $value) . '</td>';
                        }
                    }
                    echo '</tr>';
                }
                
                echo '</table>';
            } else {
                echo '<p class="warning">No records found in the customer_registrations table.</p>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="section">
                <h2>customer_registrations Table</h2>
                <p class="error">The customer_registrations table does not exist.</p>
                <p>SQL to create the table:</p>
                <div style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;">
CREATE TABLE `customer_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `registration_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum(\'pending\',\'approved\',\'rejected\') DEFAULT \'pending\',
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT \'0\',
  `last_login` datetime DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                </div>
                
                <form method="post" action="check_db_structure.php">
                    <button type="submit" name="create_table" class="btn">Create Table</button>
                </form>
            </div>';
        }
    } catch (PDOException $e) {
        echo '<div class="section">
            <p class="error">Database error: ' . $e->getMessage() . '</p>
        </div>';
    }
    
    // Process form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if (isset($_POST['add_is_verified'])) {
                $db->exec('ALTER TABLE customer_registrations ADD COLUMN is_verified TINYINT(1) DEFAULT 0');
                echo '<div class="section">
                    <p class="success">Added is_verified field successfully.</p>
                    <p><a href="check_db_structure.php" class="btn">Refresh Page</a></p>
                </div>';
            }
            
            if (isset($_POST['add_status'])) {
                $db->exec('ALTER TABLE customer_registrations ADD COLUMN status ENUM(\'pending\', \'approved\', \'rejected\') DEFAULT \'pending\'');
                echo '<div class="section">
                    <p class="success">Added status field successfully.</p>
                    <p><a href="check_db_structure.php" class="btn">Refresh Page</a></p>
                </div>';
            }
            
            if (isset($_POST['update_all_verified'])) {
                $db->exec('UPDATE customer_registrations SET is_verified = 1, status = \'approved\'');
                echo '<div class="section">
                    <p class="success">Updated all records to verified and approved.</p>
                    <p><a href="check_db_structure.php" class="btn">Refresh Page</a></p>
                </div>';
            }
            
            if (isset($_POST['create_table'])) {
                $db->exec('CREATE TABLE `customer_registrations` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `first_name` varchar(100) NOT NULL,
                  `last_name` varchar(100) NOT NULL,
                  `email` varchar(255) NOT NULL,
                  `phone` varchar(20) NOT NULL,
                  `password` varchar(255) NOT NULL,
                  `address` text,
                  `city` varchar(100) DEFAULT NULL,
                  `state` varchar(100) DEFAULT NULL,
                  `postal_code` varchar(20) DEFAULT NULL,
                  `registration_date` datetime DEFAULT CURRENT_TIMESTAMP,
                  `status` enum(\'pending\',\'approved\',\'rejected\') DEFAULT \'pending\',
                  `verification_token` varchar(255) DEFAULT NULL,
                  `is_verified` tinyint(1) DEFAULT \'0\',
                  `last_login` datetime DEFAULT NULL,
                  `notes` text,
                  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `email` (`email`),
                  UNIQUE KEY `phone` (`phone`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
                
                echo '<div class="section">
                    <p class="success">Created customer_registrations table successfully.</p>
                    <p><a href="check_db_structure.php" class="btn">Refresh Page</a></p>
                </div>';
            }
        } catch (PDOException $e) {
            echo '<div class="section">
                <p class="error">Error: ' . $e->getMessage() . '</p>
            </div>';
        }
    }
}

echo '<div class="section">
    <h2>Next Steps</h2>
    <p><a href="login_tester.php" class="btn">Go to Login Tester</a></p>
    <p><a href="login.php" class="btn">Go to Login Page</a></p>
</div>';

echo '</body>
</html>';
?>
