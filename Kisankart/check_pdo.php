<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output as plain text
header('Content-Type: text/plain');

// Check PHP version
echo "PHP Version: " . phpversion() . "\n\n";

// Check if PDO is available
echo "Checking PDO availability:\n";
if (extension_loaded('pdo')) {
    echo "PDO extension is loaded.\n";
    
    // Check available PDO drivers
    echo "Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
    
    // Check if MySQL driver is available
    if (in_array('mysql', PDO::getAvailableDrivers())) {
        echo "PDO MySQL driver is available.\n";
    } else {
        echo "PDO MySQL driver is NOT available.\n";
    }
} else {
    echo "PDO extension is NOT loaded.\n";
}

// Check loaded extensions
echo "\nLoaded Extensions:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $extension) {
    echo "- $extension\n";
}

// Check MySQL connection using mysqli
echo "\nTrying mysqli connection:\n";
try {
    $mysqli = new mysqli("localhost", "root", "", "kisan_kart");
    if ($mysqli->connect_error) {
        echo "MySQLi connection failed: " . $mysqli->connect_error . "\n";
    } else {
        echo "MySQLi connection successful.\n";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "MySQLi connection error: " . $e->getMessage() . "\n";
}

// Check session status
echo "\nChecking session status:\n";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "Session ID: " . session_id() . "\n";
echo "Session variables:\n";
if (empty($_SESSION)) {
    echo "No session variables set.\n";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "- $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
}
?>
