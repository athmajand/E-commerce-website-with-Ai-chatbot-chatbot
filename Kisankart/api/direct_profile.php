<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database configuration
include_once 'config/database.php';
include_once 'models/User.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Get user ID from request
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "User ID is required. Use ?id=X in the URL."
    ]);
    exit;
}

// Get user data
$user = new User($db);
$user->id = $user_id;

if ($user->readOne()) {
    // Create user array
    $user_data = [
        "id" => $user->id,
        "username" => $user->username,
        "firstName" => $user->firstName,
        "lastName" => $user->lastName,
        "email" => $user->email,
        "phone" => $user->phone,
        "role" => $user->role
    ];
    
    echo json_encode([
        "status" => "success",
        "message" => "User profile retrieved successfully",
        "user" => $user_data
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
}
?>
