<?php
// Enable all error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set text headers for easier debugging
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=UTF-8");

// Output PHP info
echo "PHP Version: " . phpversion() . "\n\n";

// Check if JSON functions are available
echo "JSON Support: " . (function_exists('json_encode') ? "Available" : "Not Available") . "\n\n";

// Test JSON encoding
$test_array = array("test" => "value", "number" => 123);
echo "JSON Encode Test: " . json_encode($test_array) . "\n\n";

// Check for JSON errors
echo "JSON Last Error: " . json_last_error() . "\n";
echo "JSON Last Error Message: " . json_last_error_msg() . "\n\n";

// Check database connection
echo "Testing Database Connection:\n";
try {
    include_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "Database Connection: Success\n";

    // Check if firstName and lastName columns exist
    echo "\nChecking Database Schema:\n";
    $query = "SHOW COLUMNS FROM users LIKE 'firstName'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $firstNameExists = $stmt->rowCount() > 0;
    echo "firstName column exists: " . ($firstNameExists ? "Yes" : "No") . "\n";

    $query = "SHOW COLUMNS FROM users LIKE 'lastName'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $lastNameExists = $stmt->rowCount() > 0;
    echo "lastName column exists: " . ($lastNameExists ? "Yes" : "No") . "\n";

    // If columns don't exist, add them
    if (!$firstNameExists || !$lastNameExists) {
        echo "\nAdding missing columns...\n";

        if (!$firstNameExists) {
            $query = "ALTER TABLE users ADD COLUMN firstName VARCHAR(100) AFTER username";
            $db->exec($query);
            echo "Added firstName column.\n";
        }

        if (!$lastNameExists) {
            $query = "ALTER TABLE users ADD COLUMN lastName VARCHAR(100) AFTER firstName";
            $db->exec($query);
            echo "Added lastName column.\n";
        }

        echo "Schema update completed.\n";
    }

    // Check users in database
    echo "\nUsers in database:\n";
    $query = "SELECT id, username, firstName, lastName, email FROM users LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: " . $row['id'] . ", Username: " . $row['username'] .
                 ", Name: " . ($row['firstName'] ? $row['firstName'] : '[empty]') . " " .
                 ($row['lastName'] ? $row['lastName'] : '[empty]') .
                 ", Email: " . $row['email'] . "\n";
        }
    } else {
        echo "No users found.\n";
    }
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

// Output server information
echo "\nServer Information:\n";
echo "PHP_SAPI: " . PHP_SAPI . "\n";
echo "SERVER_SOFTWARE: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
?>
