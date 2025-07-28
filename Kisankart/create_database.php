<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Create Kisan Kart Database</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #1e8449; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
        button { background: #1e8449; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #166938; }
        .btn-group { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Create Kisan Kart Database</h1>';

// Database credentials
$host = "localhost";
$username = "root";
$password = "";
$database = "kisan_kart";

// SECTION 1: Check MySQL Connection
echo '<div class="section">
    <h2>1. Checking MySQL Connection</h2>';

try {
    // Try to connect to MySQL server
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo '<p class="success">✓ Connected to MySQL server successfully.</p>';

    // SECTION 2: Create Database
    echo '</div><div class="section">
        <h2>2. Creating Database</h2>';

    // Check if database exists
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
    $databaseExists = $stmt->rowCount() > 0;

    if ($databaseExists) {
        echo '<p class="warning">⚠ Database \'' . $database . '\' already exists.</p>';
    } else {
        // Create database
        $conn->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        echo '<p class="success">✓ Database \'' . $database . '\' created successfully!</p>';
    }

    // SECTION 3: Create Tables
    echo '</div><div class="section">
        <h2>3. Creating Tables</h2>';

    // Connect to the kisan_kart database
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo '<p class="success">✓ Connected to \'' . $database . '\' database successfully.</p>';

    // Check if customer_registrations table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'customer_registrations'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo '<p class="warning">⚠ Table \'customer_registrations\' already exists.</p>';
    } else {
        // Create the customer_registrations table
        $sql = "CREATE TABLE `customer_registrations` (
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
          `status` enum('pending','approved','rejected') DEFAULT 'pending',
          `verification_token` varchar(255) DEFAULT NULL,
          `is_verified` tinyint(1) DEFAULT '0',
          `last_login` datetime DEFAULT NULL,
          `notes` text,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `email` (`email`),
          UNIQUE KEY `phone` (`phone`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $conn->exec($sql);
        echo '<p class="success">✓ Table \'customer_registrations\' created successfully!</p>';
    }

    // SECTION 4: Next Steps
    echo '</div><div class="section">
        <h2>4. Next Steps</h2>';
    echo '<p>The database and required tables have been created successfully. You can now:</p>';
    echo '<ol>
        <li>Register a new customer account</li>
        <li>Login with your customer credentials</li>
        <li>Explore the Kisan Kart application</li>
    </ol>';

    echo '<div class="btn-group">
        <a href="login.php"><button>Go to Login Page</button></a>
        <a href="customer_registration.php" style="margin-left: 10px;"><button>Go to Registration Page</button></a>
    </div>';

} catch(PDOException $e) {
    echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';

    echo '<p class="warning">Please make sure:</p>';
    echo '<ol>
        <li>MySQL service is running in XAMPP Control Panel</li>
        <li>MySQL username "root" has no password (default XAMPP configuration)</li>
        <li>MySQL is listening on the default port (3306)</li>
    </ol>';

    echo '<p>You can run the MySQL diagnostic tool to check for issues:</p>';
    echo '<a href="mysql_diagnostic.php"><button>Run MySQL Diagnostics</button></a>';
}

echo '</div>
</body>
</html>';
?>
