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

// Output array
$output = array(
    "status" => "success",
    "message" => "Profile test information",
    "localStorage" => array(),
    "token" => null,
    "decoded_token" => null,
    "user_data" => null
);

// Get token from request
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
    $token = substr($auth_header, 7);
    $output["token"] = $token;
    
    // Decode token
    $decoded_token = json_decode(base64_decode($token), true);
    $output["decoded_token"] = $decoded_token;
    
    // If token is valid
    if ($decoded_token && isset($decoded_token['data']) && isset($decoded_token['data']['id'])) {
        $user_id = $decoded_token['data']['id'];
        
        // Get user data
        $user = new User($db);
        $user->id = $user_id;
        
        if ($user->readOne()) {
            $output["user_data"] = array(
                "id" => $user->id,
                "username" => $user->username,
                "firstName" => $user->firstName,
                "lastName" => $user->lastName,
                "email" => $user->email,
                "phone" => $user->phone,
                "role" => $user->role
            );
        } else {
            $output["status"] = "error";
            $output["message"] = "User not found";
        }
    } else {
        $output["status"] = "error";
        $output["message"] = "Invalid token format";
    }
} else {
    $output["status"] = "error";
    $output["message"] = "No token provided";
}

// Output JSON
echo json_encode($output, JSON_PRETTY_PRINT);
?>
