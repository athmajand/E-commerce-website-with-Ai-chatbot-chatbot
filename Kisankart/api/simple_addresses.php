<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
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
include_once __DIR__ . '/models/Address.php';
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
    // Instantiate address object
    $address = new Address($db);
    $address->user_id = $user_id;

    // Read addresses
    $stmt = $address->readAll();
    $num = $stmt->rowCount();

    // Check if any addresses found
    if ($num > 0) {
        // Addresses array
        $addresses_arr = array();

        // Retrieve table contents
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $address_item = array(
                "id" => $id,
                "name" => $name,
                "phone" => $phone,
                "street" => $street,
                "city" => $city,
                "state" => $state,
                "postalCode" => $postal_code,
                "isDefault" => $is_default == 1,
                "address_type" => $address_type
            );

            array_push($addresses_arr, $address_item);
        }

        // Set response code - 200 OK
        http_response_code(200);

        // Return addresses data
        echo json_encode($addresses_arr);
    } else {
        // Set response code - 200 OK
        http_response_code(200);

        // No addresses found
        echo json_encode(array());
    }
}
// Handle POST request
else if ($request_method == "POST") {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Check if required data is provided
    if (
        isset($data->name) &&
        isset($data->phone) &&
        isset($data->street) &&
        isset($data->city) &&
        isset($data->state) &&
        isset($data->postalCode)
    ) {
        // Instantiate address object
        $address = new Address($db);

        // Set address properties
        $address->user_id = $user_id;
        $address->name = $data->name;
        $address->phone = $data->phone;
        $address->street = $data->street;
        $address->city = $data->city;
        $address->state = $data->state;
        $address->postal_code = $data->postalCode;
        $address->is_default = isset($data->isDefault) && $data->isDefault ? 1 : 0;

        // Create address
        if ($address->create()) {
            // Set response code - 201 Created
            http_response_code(201);

            // Tell the user
            echo json_encode(array("message" => "Address was created."));
        } else {
            // Set response code - 503 Service Unavailable
            http_response_code(503);

            // Tell the user
            echo json_encode(array("message" => "Unable to create address."));
        }
    } else {
        // Set response code - 400 Bad Request
        http_response_code(400);

        // Tell the user
        echo json_encode(array("message" => "Unable to create address. Data is incomplete."));
    }
} else {
    // Set response code - 405 Method not allowed
    http_response_code(405);

    // Tell the user
    echo json_encode(array("message" => "Method not allowed."));
}
?>
