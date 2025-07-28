<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database and models
include_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/models/CustomerRegistration.php';
include_once __DIR__ . '/middleware/auth.php';

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

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Handle GET request
if ($request_method == "GET") {
    // Instantiate customer registration object
    $customer = new CustomerRegistration($db);
    $customer->id = $user_id;

    // Read customer data
    if($customer->readOne()) {
        // Create customer array
        $customer_arr = array(
            "id" => $customer->id,
            "firstName" => $customer->first_name,
            "lastName" => $customer->last_name,
            "email" => $customer->email,
            "phone" => $customer->phone,
            "role" => "customer",
            "address" => $customer->address,
            "city" => $customer->city,
            "state" => $customer->state,
            "postal_code" => $customer->postal_code
        );

        // Set response code - 200 OK
        http_response_code(200);

        // Return customer data
        echo json_encode($customer_arr);
    } else {
        // Set response code - 404 Not found
        http_response_code(404);

        // Tell the user customer not found
        echo json_encode(array("message" => "Customer not found."));
    }
}
// Handle PUT request
else if ($request_method == "PUT") {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Instantiate customer registration object
    $customer = new CustomerRegistration($db);
    $customer->id = $user_id;

    // Read current customer data
    if($customer->readOne()) {
        // Update customer properties
        $customer->first_name = isset($data->firstName) ? $data->firstName : $customer->first_name;
        $customer->last_name = isset($data->lastName) ? $data->lastName : $customer->last_name;
        $customer->phone = isset($data->phone) ? $data->phone : $customer->phone;
        $customer->address = isset($data->address) ? $data->address : $customer->address;
        $customer->city = isset($data->city) ? $data->city : $customer->city;
        $customer->state = isset($data->state) ? $data->state : $customer->state;
        $customer->postal_code = isset($data->postal_code) ? $data->postal_code : $customer->postal_code;

        // Update customer
        if($customer->update()) {
            // Create updated customer array
            $customer_arr = array(
                "id" => $customer->id,
                "firstName" => $customer->first_name,
                "lastName" => $customer->last_name,
                "email" => $customer->email,
                "phone" => $customer->phone,
                "role" => "customer",
                "address" => $customer->address,
                "city" => $customer->city,
                "state" => $customer->state,
                "postal_code" => $customer->postal_code
            );

            // Set response code - 200 OK
            http_response_code(200);

            // Return updated customer data
            echo json_encode($customer_arr);
        } else {
            // Set response code - 503 service unavailable
            http_response_code(503);

            // Tell the user
            echo json_encode(array("message" => "Unable to update profile."));
        }
    } else {
        // Set response code - 404 Not found
        http_response_code(404);

        // Tell the user
        echo json_encode(array("message" => "Customer not found."));
    }
} else {
    // Set response code - 405 Method not allowed
    http_response_code(405);

    // Tell the user
    echo json_encode(array("message" => "Method not allowed."));
}
?>
