<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/middleware/auth.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Verify token and get user data or error message
$auth_data = verifyToken(true);

// If token is invalid
if (isset($auth_data['error'])) {
    // Set response code - 401 Unauthorized
    http_response_code(401);

    // Tell the user with specific error message
    echo json_encode(array("message" => "Access denied. " . $auth_data['error']));
    exit;
}

// Get user ID from token
$user_id = $auth_data['id'];

// Fetch user data from customer_registrations table
$query = "SELECT * FROM customer_registrations WHERE id = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    // Fetch the user data
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create response array
    $response = array(
        "id" => $user_data['id'],
        "firstName" => $user_data['first_name'],
        "lastName" => $user_data['last_name'],
        "email" => $user_data['email'],
        "phone" => $user_data['phone'],
        "address" => $user_data['address'],
        "city" => $user_data['city'],
        "state" => $user_data['state'],
        "postal_code" => $user_data['postal_code'],
        "status" => $user_data['status'],
        "is_verified" => $user_data['is_verified'],
        "registration_date" => $user_data['registration_date'],
        "last_login" => $user_data['last_login']
    );
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Return user data
    echo json_encode($response);
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user user not found
    echo json_encode(array("message" => "User not found."));
}
?>
