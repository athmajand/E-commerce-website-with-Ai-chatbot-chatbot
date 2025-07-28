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
include_once __DIR__ . '/../models/Address.php';
include_once __DIR__ . '/../middleware/auth.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Verify token and get user data
$auth_data = verifyToken();

// If token is invalid
if (!$auth_data) {
    // Set response code - 401 Unauthorized
    http_response_code(401);

    // Tell the user
    echo json_encode(array("message" => "Access denied. Invalid or missing token."));
    exit;
}

// Get user ID from token
$user_id = $auth_data['id'];

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Route based on request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $request_uri);
$endpoint = end($uri_segments);

// Check if we're dealing with an address ID
$address_id = null;
if (isset($uri_segments[count($uri_segments) - 2]) && $uri_segments[count($uri_segments) - 2] == 'addresses') {
    $address_id = $endpoint;
    $endpoint = 'address';
}

switch($endpoint) {
    case 'profile':
        // Get user profile
        if($request_method == "GET") {
            // Instantiate user object
            $user = new User($db);
            $user->id = $user_id;

            // Read user data
            if($user->readOne()) {
                // Create user array
                $user_arr = array(
                    "id" => $user->id,
                    "username" => $user->username,
                    "firstName" => $user->firstName,
                    "lastName" => $user->lastName,
                    "email" => $user->email,
                    "phone" => $user->phone,
                    "role" => $user->role
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Return user data
                echo json_encode($user_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user user not found
                echo json_encode(array("message" => "User not found."));
            }
        }
        // Update user profile
        else if($request_method == "PUT") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Instantiate user object
            $user = new User($db);
            $user->id = $user_id;

            // Read current user data
            if(!$user->readOne()) {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user user not found
                echo json_encode(array("message" => "User not found."));
                exit;
            }

            // Update user properties
            $user->firstName = isset($data->firstName) ? $data->firstName : $user->firstName;
            $user->lastName = isset($data->lastName) ? $data->lastName : $user->lastName;
            $user->email = isset($data->email) ? $data->email : $user->email;
            $user->phone = isset($data->phone) ? $data->phone : $user->phone;

            // Update user
            if($user->update()) {
                // Create updated user array
                $user_arr = array(
                    "id" => $user->id,
                    "username" => $user->username,
                    "firstName" => $user->firstName,
                    "lastName" => $user->lastName,
                    "email" => $user->email,
                    "phone" => $user->phone,
                    "role" => $user->role
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Return updated user data
                echo json_encode($user_arr);
            } else {
                // Set response code - 503 service unavailable
                http_response_code(503);

                // Tell the user
                echo json_encode(array("message" => "Unable to update profile."));
            }
        } else {
            // Set response code - 405 method not allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'change-password':
        // Update password
        if($request_method == "PUT") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if required data is provided
            if(!empty($data->currentPassword) && !empty($data->newPassword)) {
                // Instantiate user object
                $user = new User($db);
                $user->id = $user_id;

                // Read current user data
                if(!$user->readOne()) {
                    // Set response code - 404 Not found
                    http_response_code(404);

                    // Tell the user user not found
                    echo json_encode(array("message" => "User not found."));
                    exit;
                }

                // Verify current password
                if(password_verify($data->currentPassword, $user->password)) {
                    // Set new password
                    $user->password = $data->newPassword;

                    // Update user
                    if($user->update()) {
                        // Set response code - 200 OK
                        http_response_code(200);

                        // Tell the user
                        echo json_encode(array("message" => "Password updated successfully."));
                    } else {
                        // Set response code - 503 service unavailable
                        http_response_code(503);

                        // Tell the user
                        echo json_encode(array("message" => "Unable to update password."));
                    }
                } else {
                    // Set response code - 401 Unauthorized
                    http_response_code(401);

                    // Tell the user
                    echo json_encode(array("message" => "Current password is incorrect."));
                }
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to update password. Data is incomplete."));
            }
        } else {
            // Set response code - 405 method not allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'addresses':
        // Get user addresses
        if($request_method == "GET") {
            // Instantiate address object
            $address = new Address($db);
            $address->user_id = $user_id;

            // Read addresses
            $stmt = $address->readAll();
            $num = $stmt->rowCount();

            // Check if any addresses found
            if($num > 0) {
                // Addresses array
                $addresses_arr = array();

                // Retrieve records
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);

                    $address_item = array(
                        "id" => $id,
                        "name" => $name,
                        "phone" => $phone,
                        "street" => $street,
                        "city" => $city,
                        "state" => $state,
                        "postalCode" => $postal_code,
                        "isDefault" => $is_default == 1
                    );

                    array_push($addresses_arr, $address_item);
                }

                // Set response code - 200 OK
                http_response_code(200);

                // Return addresses
                echo json_encode($addresses_arr);
            } else {
                // Set response code - 200 OK
                http_response_code(200);

                // No addresses found
                echo json_encode(array());
            }
        }
        // Create new address
        else if($request_method == "POST") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if required data is provided
            if(!empty($data->name) && !empty($data->phone) && !empty($data->street) &&
               !empty($data->city) && !empty($data->state) && !empty($data->postalCode)) {

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
                if($address->create()) {
                    // Create address array
                    $address_arr = array(
                        "id" => $address->id,
                        "name" => $address->name,
                        "phone" => $address->phone,
                        "street" => $address->street,
                        "city" => $address->city,
                        "state" => $address->state,
                        "postalCode" => $address->postal_code,
                        "isDefault" => $address->is_default == 1
                    );

                    // Set response code - 201 created
                    http_response_code(201);

                    // Return created address
                    echo json_encode($address_arr);
                } else {
                    // Set response code - 503 service unavailable
                    http_response_code(503);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to create address."));
                }
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to create address. Data is incomplete."));
            }
        } else {
            // Set response code - 405 method not allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'address':
        // Update address
        if($request_method == "PUT") {
            // Check if address ID is provided
            if($address_id) {
                // Get posted data
                $data = json_decode(file_get_contents("php://input"));

                // Check if required data is provided
                if(!empty($data->name) && !empty($data->phone) && !empty($data->street) &&
                   !empty($data->city) && !empty($data->state) && !empty($data->postalCode)) {

                    // Instantiate address object
                    $address = new Address($db);
                    $address->id = $address_id;
                    $address->user_id = $user_id;

                    // Check if address exists and belongs to user
                    if($address->readOne()) {
                        // Set address properties
                        $address->name = $data->name;
                        $address->phone = $data->phone;
                        $address->street = $data->street;
                        $address->city = $data->city;
                        $address->state = $data->state;
                        $address->postal_code = $data->postalCode;
                        $address->is_default = isset($data->isDefault) && $data->isDefault ? 1 : 0;

                        // Update address
                        if($address->update()) {
                            // Create address array
                            $address_arr = array(
                                "id" => $address->id,
                                "name" => $address->name,
                                "phone" => $address->phone,
                                "street" => $address->street,
                                "city" => $address->city,
                                "state" => $address->state,
                                "postalCode" => $address->postal_code,
                                "isDefault" => $address->is_default == 1
                            );

                            // Set response code - 200 OK
                            http_response_code(200);

                            // Return updated address
                            echo json_encode($address_arr);
                        } else {
                            // Set response code - 503 service unavailable
                            http_response_code(503);

                            // Tell the user
                            echo json_encode(array("message" => "Unable to update address."));
                        }
                    } else {
                        // Set response code - 404 Not found
                        http_response_code(404);

                        // Tell the user
                        echo json_encode(array("message" => "Address not found."));
                    }
                } else {
                    // Set response code - 400 bad request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to update address. Data is incomplete."));
                }
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to update address. Address ID is missing."));
            }
        }
        // Delete address
        else if($request_method == "DELETE") {
            // Check if address ID is provided
            if($address_id) {
                // Instantiate address object
                $address = new Address($db);
                $address->id = $address_id;
                $address->user_id = $user_id;

                // Check if address exists and belongs to user
                if($address->readOne()) {
                    // Delete address
                    if($address->delete()) {
                        // Set response code - 200 OK
                        http_response_code(200);

                        // Tell the user
                        echo json_encode(array("message" => "Address deleted."));
                    } else {
                        // Set response code - 503 service unavailable
                        http_response_code(503);

                        // Tell the user
                        echo json_encode(array("message" => "Unable to delete address."));
                    }
                } else {
                    // Set response code - 404 Not found
                    http_response_code(404);

                    // Tell the user
                    echo json_encode(array("message" => "Address not found."));
                }
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to delete address. Address ID is missing."));
            }
        } else {
            // Set response code - 405 method not allowed
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
