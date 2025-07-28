<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database, user model, and seller profile model
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../models/SellerProfile.php';
include_once __DIR__ . '/../auth/validate_token.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get JWT token from the request header
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? trim(str_replace('Bearer', '', $headers['Authorization'])) : '';

// If JWT is not empty
if($jwt) {
    // Validate JWT and get user data
    $auth_data = validateToken($jwt);
    
    // If token is valid
    if($auth_data) {
        // Get user ID from token
        $user_id = $auth_data['id'];
        
        // Instantiate user object
        $user = new User($db);
        $user->id = $user_id;
        
        // Read user data
        if($user->readOne()) {
            // Check if user is a seller
            if($user->role != 'seller') {
                // Update user role to seller
                $user->role = 'seller';
                $user->update();
            }
            
            // Instantiate seller profile object
            $seller_profile = new SellerProfile($db);
            $seller_profile->user_id = $user_id;
            
            // Handle different HTTP methods
            $request_method = $_SERVER["REQUEST_METHOD"];
            
            switch($request_method) {
                // Get seller profile
                case 'GET':
                    // Check if seller profile exists
                    if($seller_profile->readOne()) {
                        // Create seller profile array
                        $seller_profile_arr = array(
                            "id" => $seller_profile->id,
                            "user_id" => $seller_profile->user_id,
                            "business_name" => $seller_profile->business_name,
                            "business_description" => $seller_profile->business_description,
                            "business_logo" => $seller_profile->business_logo,
                            "business_address" => $seller_profile->business_address,
                            "gst_number" => $seller_profile->gst_number,
                            "pan_number" => $seller_profile->pan_number,
                            "bank_account_details" => $seller_profile->bank_account_details,
                            "is_verified" => $seller_profile->is_verified,
                            "verification_documents" => $seller_profile->verification_documents,
                            "rating" => $seller_profile->rating,
                            "total_reviews" => $seller_profile->total_reviews,
                            "commission_rate" => $seller_profile->commission_rate
                        );
                        
                        // Set response code - 200 OK
                        http_response_code(200);
                        
                        // Return seller profile data
                        echo json_encode($seller_profile_arr);
                    } else {
                        // Set response code - 404 Not found
                        http_response_code(404);
                        
                        // Tell the user seller profile not found
                        echo json_encode(array("message" => "Seller profile not found."));
                    }
                    break;
                
                // Create seller profile
                case 'POST':
                    // Get posted data
                    $data = json_decode(file_get_contents("php://input"));
                    
                    // Check if seller profile already exists
                    if($seller_profile->exists()) {
                        // Set response code - 400 bad request
                        http_response_code(400);
                        
                        // Tell the user
                        echo json_encode(array("message" => "Seller profile already exists."));
                        exit;
                    }
                    
                    // Check if required data is provided
                    if(
                        !empty($data->business_name) &&
                        !empty($data->business_address)
                    ) {
                        // Set seller profile property values
                        $seller_profile->business_name = $data->business_name;
                        $seller_profile->business_description = isset($data->business_description) ? $data->business_description : "";
                        $seller_profile->business_logo = isset($data->business_logo) ? $data->business_logo : "";
                        $seller_profile->business_address = $data->business_address;
                        $seller_profile->gst_number = isset($data->gst_number) ? $data->gst_number : "";
                        $seller_profile->pan_number = isset($data->pan_number) ? $data->pan_number : "";
                        $seller_profile->bank_account_details = isset($data->bank_account_details) ? json_encode($data->bank_account_details) : "{}";
                        $seller_profile->verification_documents = isset($data->verification_documents) ? json_encode($data->verification_documents) : "[]";
                        
                        // Create the seller profile
                        if($seller_profile->create()) {
                            // Set response code - 201 created
                            http_response_code(201);
                            
                            // Tell the user
                            echo json_encode(array("message" => "Seller profile was created."));
                        }
                        // If unable to create the seller profile
                        else {
                            // Set response code - 503 service unavailable
                            http_response_code(503);
                            
                            // Tell the user
                            echo json_encode(array("message" => "Unable to create seller profile."));
                        }
                    }
                    // Tell the user data is incomplete
                    else {
                        // Set response code - 400 bad request
                        http_response_code(400);
                        
                        // Tell the user
                        echo json_encode(array("message" => "Unable to create seller profile. Data is incomplete."));
                    }
                    break;
                
                // Update seller profile
                case 'PUT':
                    // Get posted data
                    $data = json_decode(file_get_contents("php://input"));
                    
                    // Check if seller profile exists
                    if(!$seller_profile->readOne()) {
                        // Set response code - 404 Not found
                        http_response_code(404);
                        
                        // Tell the user seller profile not found
                        echo json_encode(array("message" => "Seller profile not found."));
                        exit;
                    }
                    
                    // Update seller profile property values
                    if(isset($data->business_name)) $seller_profile->business_name = $data->business_name;
                    if(isset($data->business_description)) $seller_profile->business_description = $data->business_description;
                    if(isset($data->business_logo)) $seller_profile->business_logo = $data->business_logo;
                    if(isset($data->business_address)) $seller_profile->business_address = $data->business_address;
                    if(isset($data->gst_number)) $seller_profile->gst_number = $data->gst_number;
                    if(isset($data->pan_number)) $seller_profile->pan_number = $data->pan_number;
                    if(isset($data->bank_account_details)) $seller_profile->bank_account_details = json_encode($data->bank_account_details);
                    if(isset($data->verification_documents)) $seller_profile->verification_documents = json_encode($data->verification_documents);
                    
                    // Update the seller profile
                    if($seller_profile->update()) {
                        // Set response code - 200 OK
                        http_response_code(200);
                        
                        // Tell the user
                        echo json_encode(array("message" => "Seller profile was updated."));
                    }
                    // If unable to update the seller profile
                    else {
                        // Set response code - 503 service unavailable
                        http_response_code(503);
                        
                        // Tell the user
                        echo json_encode(array("message" => "Unable to update seller profile."));
                    }
                    break;
                
                default:
                    // Set response code - 405 method not allowed
                    http_response_code(405);
                    
                    // Tell the user
                    echo json_encode(array("message" => "Method not allowed."));
                    break;
            }
        } else {
            // Set response code - 404 Not found
            http_response_code(404);
            
            // Tell the user user not found
            echo json_encode(array("message" => "User not found."));
        }
    } else {
        // Set response code - 401 Unauthorized
        http_response_code(401);
        
        // Tell the user access denied
        echo json_encode(array("message" => "Access denied."));
    }
} else {
    // Set response code - 401 Unauthorized
    http_response_code(401);
    
    // Tell the user access denied
    echo json_encode(array("message" => "Access denied."));
}
?>
