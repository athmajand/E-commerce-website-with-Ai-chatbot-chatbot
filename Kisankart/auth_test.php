<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database and user model
include_once 'api/config/database.php';
include_once 'api/models/User.php';

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Create test response
    $response = array(
        "status" => "success",
        "message" => "Database connection successful",
        "database_info" => array(
            "connection" => ($db ? "Connected" : "Failed")
        )
    );

    // Output as JSON
    echo json_encode($response);
} catch (Exception $e) {
    // Create error response
    $error_response = array(
        "status" => "error",
        "message" => "Database connection failed",
        "error" => $e->getMessage()
    );

    // Output as JSON
    echo json_encode($error_response);
}
?>
