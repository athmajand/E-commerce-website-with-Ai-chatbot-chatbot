<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>MySQL Diagnostic Tool</title>
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
    </style>
</head>
<body>
    <h1>MySQL Diagnostic Tool for Kisan Kart</h1>';

// SECTION 1: Check if MySQL is running
echo '<div class="section">
    <h2>1. MySQL Service Status</h2>';

$connection = @fsockopen('localhost', 3306, $errno, $errstr, 5);
if (is_resource($connection)) {
    echo '<p class="success">✓ MySQL is running on localhost:3306</p>';
    fclose($connection);
} else {
    echo '<p class="error">✗ MySQL is NOT running on localhost:3306</p>';
    echo '<p>Error: ' . $errstr . ' (Error #' . $errno . ')</p>';
    echo '<p class="warning">Please start MySQL using XAMPP Control Panel:</p>';
    echo '<ol>
        <li>Open XAMPP Control Panel</li>
        <li>Click the "Start" button next to MySQL</li>
        <li>Wait for the service to start (status should turn green)</li>
        <li>Refresh this page</li>
    </ol>';
    echo '<img src="https://www.apachefriends.org/images/xampp-panel.jpg" alt="XAMPP Control Panel" style="max-width: 100%; height: auto;">';
}

// SECTION 2: Try different connection methods
echo '</div><div class="section">
    <h2>2. Testing MySQL Connection Methods</h2>';

// Method 1: PDO with default settings
echo '<h3>Method 1: PDO with default settings</h3>';
try {
    $conn = new PDO("mysql:host=localhost", "root", "");
    echo '<p class="success">✓ Successfully connected to MySQL server using PDO!</p>';
    $conn = null;
} catch (PDOException $e) {
    echo '<p class="error">✗ PDO Connection Error: ' . $e->getMessage() . '</p>';
}

// Method 2: PDO with explicit port
echo '<h3>Method 2: PDO with explicit port</h3>';
try {
    $conn = new PDO("mysql:host=localhost;port=3306", "root", "");
    echo '<p class="success">✓ Successfully connected to MySQL server using PDO with explicit port!</p>';
    $conn = null;
} catch (PDOException $e) {
    echo '<p class="error">✗ PDO Connection Error (explicit port): ' . $e->getMessage() . '</p>';
}

// Method 3: PDO with socket
echo '<h3>Method 3: PDO with socket (Unix/Linux only)</h3>';
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    try {
        $conn = new PDO("mysql:unix_socket=/var/run/mysqld/mysqld.sock", "root", "");
        echo '<p class="success">✓ Successfully connected to MySQL server using PDO with socket!</p>';
        $conn = null;
    } catch (PDOException $e) {
        echo '<p class="error">✗ PDO Connection Error (socket): ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p class="warning">Socket connection not applicable on Windows</p>';
}

// Method 4: mysqli
echo '<h3>Method 4: mysqli</h3>';
if (function_exists('mysqli_connect')) {
    $mysqli = @mysqli_connect('localhost', 'root', '');
    if ($mysqli) {
        echo '<p class="success">✓ Successfully connected to MySQL server using mysqli!</p>';
        mysqli_close($mysqli);
    } else {
        echo '<p class="error">✗ mysqli Connection Error: ' . mysqli_connect_error() . '</p>';
    }
} else {
    echo '<p class="error">✗ mysqli extension is not available</p>';
}

// SECTION 3: Check database existence
echo '</div><div class="section">
    <h2>3. Database Check</h2>';

try {
    $conn = new PDO("mysql:host=localhost", "root", "");
    
    // Check if kisan_kart database exists
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'kisan_kart'");
    $databaseExists = $stmt->rowCount() > 0;
    
    if ($databaseExists) {
        echo '<p class="success">✓ Database \'kisan_kart\' exists.</p>';
        
        // Try to connect to the database
        try {
            $dbConn = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
            echo '<p class="success">✓ Successfully connected to \'kisan_kart\' database!</p>';
            
            // Check tables
            $tables = $dbConn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo '<p>Tables in kisan_kart database: ' . implode(', ', $tables) . '</p>';
            
            // Check specifically for customer_registrations table
            if (in_array('customer_registrations', $tables)) {
                echo '<p class="success">✓ Table \'customer_registrations\' exists.</p>';
            } else {
                echo '<p class="error">✗ Table \'customer_registrations\' does not exist.</p>';
            }
            
            $dbConn = null;
        } catch (PDOException $e) {
            echo '<p class="error">✗ Error connecting to \'kisan_kart\' database: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p class="error">✗ Database \'kisan_kart\' does not exist.</p>';
        echo '<p>Would you like to create it? <button onclick="window.location.href=\'create_database.php\'">Create Database</button></p>';
    }
    
    $conn = null;
} catch (PDOException $e) {
    echo '<p class="error">✗ Error checking database: ' . $e->getMessage() . '</p>';
}

// SECTION 4: System Information
echo '</div><div class="section">
    <h2>4. System Information</h2>';
echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
echo '<p><strong>Server Software:</strong> ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
echo '<p><strong>PDO Drivers:</strong> ' . implode(', ', PDO::getAvailableDrivers()) . '</p>';
echo '<p><strong>Operating System:</strong> ' . PHP_OS . '</p>';

// Check for loaded extensions
$loaded_extensions = get_loaded_extensions();
echo '<p><strong>MySQL Extensions:</strong> ';
if (in_array('mysqli', $loaded_extensions)) echo 'mysqli ✓ ';
if (in_array('pdo_mysql', $loaded_extensions)) echo 'pdo_mysql ✓ ';
if (!in_array('mysqli', $loaded_extensions) && !in_array('pdo_mysql', $loaded_extensions)) echo 'None ✗';
echo '</p>';

// SECTION 5: Next Steps
echo '</div><div class="section">
    <h2>5. Next Steps</h2>';
echo '<p>Based on the diagnostics above:</p>';
echo '<ol>
    <li>Make sure MySQL is running in XAMPP Control Panel</li>
    <li>If MySQL is running but connection fails, check your MySQL configuration</li>
    <li>If the database doesn\'t exist, create it using the button above</li>
    <li>Once all issues are resolved, try logging in again</li>
</ol>';

echo '<p><a href="login.php"><button>Go to Login Page</button></a></p>';

echo '</div>
</body>
</html>';
?>
