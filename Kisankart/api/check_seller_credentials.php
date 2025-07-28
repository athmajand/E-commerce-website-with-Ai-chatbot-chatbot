<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database and seller registration model
include_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate seller registration object
$seller = new SellerRegistration($db);

// Check if request method is GET or POST
if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from request
    $data = [];
    
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        // Get data from query parameters
        $data = $_GET;
    } else {
        // Get posted data
        $data = json_decode(file_get_contents("php://input"), true);
        
        // If JSON parsing failed, try POST data
        if (is_null($data)) {
            $data = $_POST;
        }
    }
    
    // Initialize response
    $response = [
        "exists" => false,
        "field" => "",
        "message" => ""
    ];
    
    // Check if email is provided
    if (!empty($data['email'])) {
        // Set seller email property
        $seller->email = $data['email'];
        
        // Check if email exists
        if ($seller->emailExists()) {
            // Set response
            $response["exists"] = true;
            $response["field"] = "email";
            $response["message"] = "Email already exists. Please use a different email address.";
        }
    }
    
    // Check if phone is provided
    if (!empty($data['phone'])) {
        // Set seller phone property
        $seller->phone = $data['phone'];
        
        // Check if phone exists
        if ($seller->phoneExists()) {
            // Set response
            $response["exists"] = true;
            $response["field"] = "phone";
            $response["message"] = "Phone number already exists. Please use a different phone number.";
        }
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Return response
    echo json_encode($response);
} else {
    // Set response code - 405 method not allowed
    http_response_code(405);
    
    // Tell the user
    echo json_encode(array("message" => "Method not allowed."));
}
?>
