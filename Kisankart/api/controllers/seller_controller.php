<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and models
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../models/SellerLogin.php';
include_once __DIR__ . '/../models/SellerRegistration.php';
include_once __DIR__ . '/../models/SellerProfile.php';
include_once __DIR__ . '/../models/Product.php';
include_once __DIR__ . '/../middleware/auth.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Verify token and get user data
$auth_data = verifyToken();

// Check if token is valid
if (!$auth_data) {
    // Set response code - 401 Unauthorized
    http_response_code(401);

    // Tell the user access denied
    echo json_encode(array("message" => "Access denied. Token is invalid or missing."));
    exit;
}

// Get user ID and role from token
$user_id = $auth_data['id'];
$user_role = $auth_data['role'];

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get request URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($request_uri, '/'));

// Find the endpoint in the URI segments
$endpoint = end($uri_segments);

// Route based on endpoint
switch($endpoint) {
    case 'register':
        // Register as a seller
        if($request_method == "POST") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if data is complete
            if(
                !empty($data->business_name) &&
                !empty($data->business_address)
            ) {
                // Create user object
                $user = new User($db);
                $user->id = $user_id;

                // Read user data
                if($user->readOne()) {
                    // Check if user is already a seller
                    if($user->role == 'seller') {
                        // Set response code - 400 Bad Request
                        http_response_code(400);

                        // Tell the user
                        echo json_encode(array("message" => "User is already registered as a seller."));
                        exit;
                    }

                    // Create seller profile object
                    $seller = new SellerProfile($db);

                    // Check if seller profile already exists
                    $seller->seller_id = $user_id;
                    if($seller->exists()) {
                        // Set response code - 400 Bad Request
                        http_response_code(400);

                        // Tell the user
                        echo json_encode(array("message" => "Seller profile already exists."));
                        exit;
                    }

                    // Set seller profile properties
                    $seller->seller_id = $user_id;
                    $seller->business_name = $data->business_name;
                    $seller->business_description = $data->business_description ?? '';
                    $seller->business_logo = $data->business_logo ?? '';
                    $seller->business_address = $data->business_address;
                    $seller->gst_number = $data->gst_number ?? '';
                    $seller->pan_number = $data->pan_number ?? '';
                    $seller->bank_account_details = $data->bank_account_details ?? '';
                    $seller->verification_documents = $data->verification_documents ?? '';

                    // Create seller profile
                    if($seller->create()) {
                        // Update user role to seller
                        $user->role = 'seller';
                        if($user->update()) {
                            // Set response code - 201 Created
                            http_response_code(201);

                            // Tell the user
                            echo json_encode(array("message" => "Seller profile created successfully."));
                        } else {
                            // Set response code - 500 Internal Server Error
                            http_response_code(500);

                            // Tell the user
                            echo json_encode(array("message" => "Unable to update user role."));
                        }
                    } else {
                        // Set response code - 500 Internal Server Error
                        http_response_code(500);

                        // Tell the user
                        echo json_encode(array("message" => "Unable to create seller profile."));
                    }
                } else {
                    // Set response code - 404 Not found
                    http_response_code(404);

                    // Tell the user
                    echo json_encode(array("message" => "User not found."));
                }
            } else {
                // Set response code - 400 Bad Request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to create seller profile. Data is incomplete."));
            }
        } else {
            // Set response code - 405 Method Not Allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'profile':
        // Get seller profile
        if($request_method == "GET") {
            // Check if user is a seller
            if($user_role != 'seller') {
                // Set response code - 403 Forbidden
                http_response_code(403);

                // Tell the user
                echo json_encode(array("message" => "Access denied. User is not a seller."));
                exit;
            }

            // Create seller profile object
            $seller = new SellerProfile($db);
            $seller->seller_id = $user_id;

            // Read seller profile
            if($seller->readOne()) {
                // Create seller profile array
                $seller_arr = array(
                    "id" => $seller->id,
                    "seller_id" => $seller->seller_id,
                    "business_name" => $seller->business_name,
                    "business_description" => $seller->business_description,
                    "business_logo" => $seller->business_logo,
                    "business_address" => $seller->business_address,
                    "gst_number" => $seller->gst_number,
                    "pan_number" => $seller->pan_number,
                    "bank_account_details" => $seller->bank_account_details,
                    "is_verified" => $seller->is_verified,
                    "verification_documents" => $seller->verification_documents,
                    "rating" => $seller->rating,
                    "total_reviews" => $seller->total_reviews,
                    "commission_rate" => $seller->commission_rate,
                    "created_at" => $seller->created_at,
                    "updated_at" => $seller->updated_at
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Return seller profile
                echo json_encode($seller_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user
                echo json_encode(array("message" => "Seller profile not found."));
            }
        }
        // Update seller profile
        else if($request_method == "PUT") {
            // Check if user is a seller
            if($user_role != 'seller') {
                // Set response code - 403 Forbidden
                http_response_code(403);

                // Tell the user
                echo json_encode(array("message" => "Access denied. User is not a seller."));
                exit;
            }

            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Create seller profile object
            $seller = new SellerProfile($db);
            $seller->seller_id = $user_id;

            // Check if seller profile exists
            if($seller->readOne()) {
                // Set seller profile properties
                if(isset($data->business_name)) $seller->business_name = $data->business_name;
                if(isset($data->business_description)) $seller->business_description = $data->business_description;
                if(isset($data->business_logo)) $seller->business_logo = $data->business_logo;
                if(isset($data->business_address)) $seller->business_address = $data->business_address;
                if(isset($data->gst_number)) $seller->gst_number = $data->gst_number;
                if(isset($data->pan_number)) $seller->pan_number = $data->pan_number;
                if(isset($data->bank_account_details)) $seller->bank_account_details = $data->bank_account_details;
                if(isset($data->verification_documents)) $seller->verification_documents = $data->verification_documents;

                // Update seller profile
                if($seller->update()) {
                    // Set response code - 200 OK
                    http_response_code(200);

                    // Tell the user
                    echo json_encode(array("message" => "Seller profile updated successfully."));
                } else {
                    // Set response code - 500 Internal Server Error
                    http_response_code(500);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to update seller profile."));
                }
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user
                echo json_encode(array("message" => "Seller profile not found."));
            }
        } else {
            // Set response code - 405 Method Not Allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    default:
        // Set response code - 404 Not found
        http_response_code(404);

        // Tell the user
        echo json_encode(array("message" => "Endpoint not found."));
        break;
}
?>
