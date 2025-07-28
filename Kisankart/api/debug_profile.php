<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database and models
include_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/models/User.php';
include_once __DIR__ . '/middleware/auth.php';

// Output array
$output = array(
    "status" => "debug_info",
    "message" => "Profile debug information",
    "token_info" => null,
    "user_data" => null,
    "customer_profile_data" => null,
    "error" => null
);

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        $output["error"] = "Database connection failed";
        echo json_encode($output);
        exit;
    }
    
    // Get token from request
    $headers = getallheaders();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    $token = null;
    
    if (!empty($auth_header) && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
        $output["token_info"] = array(
            "token_exists" => true,
            "token_value" => substr($token, 0, 10) . '...' // Only show first 10 chars for security
        );
    } else {
        $output["token_info"] = array(
            "token_exists" => false
        );
    }
    
    // Verify token and get user data
    if ($token) {
        $auth_data = verifyToken(true);
        
        if (isset($auth_data['error'])) {
            $output["token_info"]["is_valid"] = false;
            $output["token_info"]["error"] = $auth_data['error'];
        } else {
            $output["token_info"]["is_valid"] = true;
            $output["token_info"]["user_id"] = $auth_data['id'];
            $output["token_info"]["role"] = $auth_data['role'];
            
            // Get user data
            $user = new User($db);
            $user->id = $auth_data['id'];
            
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
                
                // Check if customer profile exists
                $query = "SELECT * FROM customer_profiles WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $user->id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $customer_profile = $stmt->fetch(PDO::FETCH_ASSOC);
                    $output["customer_profile_data"] = $customer_profile;
                } else {
                    $output["customer_profile_data"] = "No customer profile found for this user";
                }
            } else {
                $output["error"] = "User not found";
            }
        }
    } else {
        $output["error"] = "No token provided";
    }
} catch (Exception $e) {
    $output["error"] = "Exception: " . $e->getMessage();
}

// Return debug info
echo json_encode($output);
?>
