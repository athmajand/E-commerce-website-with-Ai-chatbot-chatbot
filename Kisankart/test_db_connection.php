<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #1e8449; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>';

// SECTION 1: Check PHP Configuration
echo '<div class="section">
    <h2>1. PHP Configuration</h2>';

// Check PHP version
echo '<p>PHP Version: <strong>' . phpversion() . '</strong></p>';

// Check loaded extensions
$loaded_extensions = get_loaded_extensions();
echo '<p>Loaded Extensions: ' . implode(', ', $loaded_extensions) . '</p>';

// Check for MySQL extensions
echo '<p>MySQL Extensions: ';
if (in_array('mysqli', $loaded_extensions)) echo '<span class="success">mysqli ✓</span> ';
if (in_array('pdo_mysql', $loaded_extensions)) echo '<span class="success">pdo_mysql ✓</span> ';
if (!in_array('mysqli', $loaded_extensions) && !in_array('pdo_mysql', $loaded_extensions)) 
    echo '<span class="error">None ✗</span>';
echo '</p>';

// Check php.ini location
echo '<p>PHP Configuration File: ' . (php_ini_loaded_file() ? php_ini_loaded_file() : 'None') . '</p>';

echo '</div>';

// SECTION 2: Test MySQL Connection
echo '<div class="section">
    <h2>2. MySQL Connection Test</h2>';

// Database credentials
$host = "localhost";
$username = "root";
$password = "";
$database = "kisan_kart";

// Test 1: Connect to MySQL server
echo '<h3>Test 1: Connect to MySQL Server</h3>';
try {
    if (extension_loaded('mysqli')) {
        $mysqli = @mysqli_connect($host, $username, $password);
        if ($mysqli) {
            echo '<p class="success">✓ Successfully connected to MySQL server using mysqli!</p>';
            mysqli_close($mysqli);
        } else {
            echo '<p class="error">✗ mysqli Connection Error: ' . mysqli_connect_error() . '</p>';
        }
    } else {
        echo '<p class="warning">mysqli extension is not loaded. Skipping this test.</p>';
    }
    
    if (extension_loaded('pdo_mysql')) {
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo '<p class="success">✓ Successfully connected to MySQL server using PDO!</p>';
        $conn = null;
    } else {
        echo '<p class="warning">pdo_mysql extension is not loaded. Skipping this test.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="error">✗ PDO Connection Error: ' . $e->getMessage() . '</p>';
}

// Test 2: Connect to kisan_kart database
echo '<h3>Test 2: Connect to kisan_kart Database</h3>';
try {
    if (extension_loaded('mysqli')) {
        $mysqli = @mysqli_connect($host, $username, $password, $database);
        if ($mysqli) {
            echo '<p class="success">✓ Successfully connected to kisan_kart database using mysqli!</p>';
            mysqli_close($mysqli);
        } else {
            echo '<p class="error">✗ mysqli Connection Error: ' . mysqli_connect_error() . '</p>';
        }
    } else {
        echo '<p class="warning">mysqli extension is not loaded. Skipping this test.</p>';
    }
    
    if (extension_loaded('pdo_mysql')) {
        $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo '<p class="success">✓ Successfully connected to kisan_kart database using PDO!</p>';
        
        // Check tables
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo '<p>Tables in kisan_kart database: ' . implode(', ', $tables) . '</p>';
        
        // Check specifically for customer_registrations table
        if (in_array('customer_registrations', $tables)) {
            echo '<p class="success">✓ Table \'customer_registrations\' exists.</p>';
            
            // Count records in customer_registrations
            $count = $conn->query("SELECT COUNT(*) FROM customer_registrations")->fetchColumn();
            echo '<p>Number of records in customer_registrations: ' . $count . '</p>';
        } else {
            echo '<p class="error">✗ Table \'customer_registrations\' does not exist.</p>';
        }
        
        $conn = null;
    } else {
        echo '<p class="warning">pdo_mysql extension is not loaded. Skipping this test.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="error">✗ PDO Connection Error: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// SECTION 3: Test Database Class
echo '<div class="section">
    <h2>3. Test Database Class</h2>';

// Include database configuration
if (file_exists('api/config/database.php')) {
    include_once 'api/config/database.php';
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo '<p class="success">✓ Successfully connected using Database class!</p>';
        
        // Test a simple query
        try {
            $stmt = $db->query("SELECT 1");
            echo '<p class="success">✓ Successfully executed a test query.</p>';
        } catch (PDOException $e) {
            echo '<p class="error">✗ Query Error: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p class="error">✗ Failed to connect using Database class.</p>';
    }
} else {
    echo '<p class="error">✗ Database configuration file not found at api/config/database.php</p>';
}

echo '</div>';

// SECTION 4: Next Steps
echo '<div class="section">
    <h2>4. Next Steps</h2>';

echo '<p>Based on the test results above:</p>';
echo '<ol>
    <li>If any connection tests failed, make sure MySQL is running in XAMPP Control Panel</li>
    <li>If PHP extensions are missing, install XAMPP which includes all required extensions</li>
    <li>If the database doesn\'t exist, create it using phpMyAdmin</li>
    <li>If tables are missing, run your database creation scripts</li>
    <li>Once all issues are resolved, try logging in again</li>
</ol>';

echo '<p><a href="login.php"><button style="background: #1e8449; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Go to Login Page</button></a></p>';

echo '</div>
</body>
</html>';
?>
