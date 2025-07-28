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

// Include database and models
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../models/SellerRegistration.php';
include_once __DIR__ . '/../models/SellerProfile.php';
include_once __DIR__ . '/../middleware/auth.php';

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

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Check if required data is provided
    if (
        !empty($data->business_name) &&
        !empty($data->business_address)
    ) {
        // Create user object to update role
        $user = new User($db);
        $user->id = $user_id;

        // Read user data
        if ($user->readOne()) {
            // Update user role to seller
            $user->role = 'seller';

            // Update user
            if ($user->update()) {
                // Create seller profile object
                $seller = new SellerProfile($db);

                // First create a seller registration
                $seller_reg = new SellerRegistration($db);

                // Check if seller registration already exists with this email
                $seller_reg->email = $user->email;
                if ($seller_reg->emailExists()) {
                    // Set response code - 400 Bad Request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array("message" => "Seller registration already exists with this email."));
                    exit;
                }

                // Check if seller registration already exists with this phone
                $seller_reg->phone = $user->phone;
                if ($seller_reg->phoneExists()) {
                    // Set response code - 400 Bad Request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array("message" => "Seller registration already exists with this phone number."));
                    exit;
                }

                // Get user data to populate seller registration
                $seller_reg->first_name = $user->firstName;
                $seller_reg->last_name = $user->lastName;
                $seller_reg->password = $user->password; // This is already hashed
                $seller_reg->business_name = htmlspecialchars(strip_tags($data->business_name));
                $seller_reg->business_description = !empty($data->business_description) ? htmlspecialchars(strip_tags($data->business_description)) : "";
                $seller_reg->business_logo = !empty($data->business_logo) ? htmlspecialchars(strip_tags($data->business_logo)) : "";
                $seller_reg->business_address = htmlspecialchars(strip_tags($data->business_address));
                $seller_reg->business_country = !empty($data->business_country) ? htmlspecialchars(strip_tags($data->business_country)) : "";
                $seller_reg->business_state = !empty($data->business_state) ? htmlspecialchars(strip_tags($data->business_state)) : "";
                $seller_reg->business_city = !empty($data->business_city) ? htmlspecialchars(strip_tags($data->business_city)) : "";
                $seller_reg->business_postal_code = !empty($data->business_postal_code) ? htmlspecialchars(strip_tags($data->business_postal_code)) : "";
                $seller_reg->gst_number = !empty($data->gst_number) ? htmlspecialchars(strip_tags($data->gst_number)) : "";
                $seller_reg->pan_number = !empty($data->pan_number) ? htmlspecialchars(strip_tags($data->pan_number)) : "";
                $seller_reg->bank_account_details = !empty($data->bank_account_details) ? htmlspecialchars(strip_tags($data->bank_account_details)) : "";
                $seller_reg->status = 'pending';

                // Create seller registration
                if ($seller_reg->create()) {
                    // Now create seller profile
                    $seller = new SellerProfile($db);

                    // Set seller profile properties
                    $seller->seller_id = $seller_reg->id;
                    $seller->business_name = htmlspecialchars(strip_tags($data->business_name));
                    $seller->business_description = !empty($data->business_description) ? htmlspecialchars(strip_tags($data->business_description)) : "";
                    $seller->business_logo = !empty($data->business_logo) ? htmlspecialchars(strip_tags($data->business_logo)) : "";
                    $seller->business_address = htmlspecialchars(strip_tags($data->business_address));
                    $seller->gst_number = !empty($data->gst_number) ? htmlspecialchars(strip_tags($data->gst_number)) : "";
                    $seller->pan_number = !empty($data->pan_number) ? htmlspecialchars(strip_tags($data->pan_number)) : "";
                    $seller->bank_account_details = !empty($data->bank_account_details) ? htmlspecialchars(strip_tags($data->bank_account_details)) : "";
                    $seller->verification_documents = !empty($data->verification_documents) ? htmlspecialchars(strip_tags($data->verification_documents)) : "";

                    // Create seller profile
                    if ($seller->create()) {
                        // Set response code - 201 created
                        http_response_code(201);

                        // Tell the user
                        echo json_encode(array(
                            "message" => "Seller profile created successfully.",
                            "seller_id" => $seller->id,
                            "seller_registration_id" => $seller->seller_id,
                            "business_name" => $seller->business_name
                        ));
                    } else {
                        // Set response code - 503 service unavailable
                        http_response_code(503);

                        // Tell the user
                        echo json_encode(array("message" => "Unable to create seller profile."));
                    }
                } else {
                    // Set response code - 503 service unavailable
                    http_response_code(503);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to create seller registration."));
                }
            } else {
                // Set response code - 503 service unavailable
                http_response_code(503);

                // Tell the user
                echo json_encode(array("message" => "Unable to update user role."));
            }
        } else {
            // Set response code - 404 Not found
            http_response_code(404);

            // Tell the user
            echo json_encode(array("message" => "User not found."));
        }
    } else {
        // Set response code - 400 bad request
        http_response_code(400);

        // Tell the user
        echo json_encode(array("message" => "Unable to create seller profile. Data is incomplete."));
    }
} else {
    // Set response code - 405 method not allowed
    http_response_code(405);

    // Tell the user
    echo json_encode(array("message" => "Method not allowed."));
}
?>
