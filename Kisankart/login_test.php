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
    
    // Instantiate user object
    $user = new User($db);
    
    // Set user properties for login
    $user->username = "admin";
    $user->password = "admin123";
    
    // Attempt to login
    if($user->login()) {
        // Create success response
        $response = array(
            "status" => "success",
            "message" => "Login successful",
            "user_info" => array(
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "role" => $user->role
            )
        );
    } else {
        // Create failure response
        $response = array(
            "status" => "error",
            "message" => "Login failed. Invalid username or password."
        );
    }
    
    // Output as JSON
    echo json_encode($response);
} catch (Exception $e) {
    // Create error response
    $error_response = array(
        "status" => "error",
        "message" => "An error occurred",
        "error" => $e->getMessage()
    );
    
    // Output as JSON
    echo json_encode($error_response);
}
?>
