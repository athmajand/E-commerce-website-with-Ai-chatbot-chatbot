<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Check Customer Registrations</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #1e8449; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn-group { margin-top: 20px; }
        button { background: #1e8449; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #166938; }
    </style>
</head>
<body>
    <h1>Customer Registrations</h1>';

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '<div class="section">
        <p class="error">Database connection failed. Please check your MySQL connection.</p>
        <p><a href="mysql_diagnostic.php"><button>Run MySQL Diagnostics</button></a></p>
    </div>';
} else {
    echo '<div class="section">';
    
    try {
        // Check if customer_registrations table exists
        $stmt = $db->query("SHOW TABLES LIKE 'customer_registrations'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p class="warning">The customer_registrations table does not exist.</p>';
            echo '<p><a href="create_database.php"><button>Create Database Tables</button></a></p>';
        } else {
            // Get all customer registrations
            $query = "SELECT * FROM customer_registrations";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $count = $stmt->rowCount();
            
            if ($count > 0) {
                echo '<h2>Found ' . $count . ' customer registrations:</h2>';
                echo '<table>';
                echo '<tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Last Login</th>
                </tr>';
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>';
                    echo '<td>' . $row['email'] . '</td>';
                    echo '<td>' . $row['phone'] . '</td>';
                    echo '<td>' . $row['status'] . '</td>';
                    echo '<td>' . ($row['is_verified'] ? 'Yes' : 'No') . '</td>';
                    echo '<td>' . ($row['last_login'] ? $row['last_login'] : 'Never') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            } else {
                echo '<p class="warning">No customer registrations found.</p>';
                echo '<p>You need to register at least one customer to test the login functionality.</p>';
                echo '<p><a href="customer_registration.php"><button>Register a Customer</button></a></p>';
            }
        }
    } catch (PDOException $e) {
        echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">
        <h2>Next Steps</h2>
        <div class="btn-group">
            <a href="customer_registration.php"><button>Register a Customer</button></a>
            <a href="login.php" style="margin-left: 10px;"><button>Go to Login Page</button></a>
        </div>
    </div>';
}

echo '</body>
</html>';
?>
