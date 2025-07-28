<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database and user model
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/User.php';

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Instantiate user object
    $user = new User($db);

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // User registration functionality has been removed

    // Set response code - 503 service unavailable
    http_response_code(503);

    // Tell the user
    echo json_encode(array(
        "message" => "User registration is currently disabled. Please contact the administrator.",
        "status" => "registration_disabled"
    ));
} else {
    // Set response code - 405 method not allowed
    http_response_code(405);

    // Tell the user
    echo json_encode(array("message" => "Method not allowed."));
}
?>
