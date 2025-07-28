<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and seller registration model
include_once '../config/database.php';
include_once '../models/SellerRegistration.php';
include_once '../utils/ErrorHandler.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Double-check that buffered queries are enabled
if ($db && defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if data is not empty
if (
    !empty($data) &&
    (
        (!empty($data->email) && !empty($data->password)) ||
        (!empty($data->phone) && !empty($data->password))
    )
) {
    // Create seller registration object
    $seller = new SellerRegistration($db);

    // Set properties
    if (!empty($data->email)) {
        $seller->email = $data->email;
        $seller->password = $data->password;
        $login_success = $seller->loginWithEmail();
    } else {
        $seller->phone = $data->phone;
        $seller->password = $data->password;
        $login_success = $seller->loginWithPhone();
    }

    if ($login_success) {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Set session variables
        $_SESSION['user_id'] = $seller->id;
        $_SESSION['username'] = $seller->first_name . ' ' . $seller->last_name;
        $_SESSION['firstName'] = $seller->first_name;
        $_SESSION['lastName'] = $seller->last_name;
        $_SESSION['email'] = $seller->email;
        $_SESSION['phone'] = $seller->phone;
        $_SESSION['user_role'] = 'seller';
        $_SESSION['seller_id'] = $seller->id;
        $_SESSION['seller_name'] = $seller->first_name . ' ' . $seller->last_name;
        $_SESSION['seller_email'] = $seller->email;
        $_SESSION['seller_phone'] = $seller->phone;
        $_SESSION['seller_business_name'] = $seller->business_name;
        $_SESSION['seller_status'] = $seller->status;
        $_SESSION['seller_is_verified'] = $seller->is_verified;

        // Generate JWT token (if you're using JWT)
        $jwt = bin2hex(random_bytes(32)); // Simple token for demonstration

        // Set response
        $response['success'] = true;
        $response['message'] = 'Login successful';
        $response['data'] = [
            'id' => $seller->id,
            'firstName' => $seller->first_name,
            'lastName' => $seller->last_name,
            'email' => $seller->email,
            'phone' => $seller->phone,
            'role' => 'seller',
            'status' => $seller->status,
            'is_verified' => $seller->is_verified,
            'business_name' => $seller->business_name,
            'jwt' => $jwt
        ];

        // Set JWT cookie
        setcookie('jwt', $jwt, time() + 3600, '/');
    } else {
        // Set error response
        $response['message'] = $seller->error ?: 'Invalid login credentials';

        // If not verified, include verification token in response
        if (strpos($seller->error, 'not verified') !== false) {
            $response['data'] = [
                'email' => $seller->email,
                'needs_verification' => true
            ];
        }
    }
} else {
    // Set error response for missing data
    $response['message'] = 'Missing required parameters';
}

// Return response
echo json_encode($response);
?>
