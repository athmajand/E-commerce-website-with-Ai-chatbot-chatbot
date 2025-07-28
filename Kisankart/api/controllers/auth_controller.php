<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user model
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/User.php';

// Generate JWT token
function generateJWT($user_id, $username, $role) {
    $secret_key = "kisan_kart_jwt_secret";
    $issuer_claim = "kisan_kart_api"; // this can be the servername
    $audience_claim = "kisan_kart_client";
    $issuedat_claim = time(); // issued at
    $notbefore_claim = $issuedat_claim; // not before
    $expire_claim = $issuedat_claim + 3600; // expire time (1 hour)

    $token = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "nbf" => $notbefore_claim,
        "exp" => $expire_claim,
        "data" => array(
            "id" => $user_id,
            "username" => $username,
            "role" => $role
        )
    );

    // Create JWT parts
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode($token));

    // Create signature
    $signature_data = $header . '.' . $payload;
    $signature = base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));

    // Combine all parts to form the JWT
    $jwt = $header . '.' . $payload . '.' . $signature;

    return $jwt;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user object
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Route based on request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($request_uri, '/'));

// Find the endpoint (login or register) in the URI segments
$endpoint = '';
if (in_array('login', $uri_segments)) {
    $endpoint = 'login';
} elseif (in_array('register', $uri_segments)) {
    $endpoint = 'register';
} else {
    // Default to the last segment if no specific endpoint is found
    $endpoint = end($uri_segments);
}

switch($endpoint) {
    case 'register':
        // Check if request method is POST
        if($request_method == "POST") {
            // Check if required data is provided
            if(
                !empty($data->username) &&
                !empty($data->password) &&
                !empty($data->email) &&
                !empty($data->role)
            ) {
                // Set user property values
                $user->username = $data->username;
                $user->firstName = isset($data->firstName) ? $data->firstName : "";
                $user->lastName = isset($data->lastName) ? $data->lastName : "";
                $user->password = $data->password;
                $user->email = $data->email;
                $user->phone = isset($data->phone) ? $data->phone : "";
                $user->role = $data->role;

                // Check if username already exists
                if($user->usernameExists()) {
                    // Set response code - 400 bad request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array("message" => "Username already exists."));
                    exit;
                }

                // Check if email already exists
                if($user->emailExists()) {
                    // Set response code - 400 bad request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array("message" => "Email already exists."));
                    exit;
                }

                // Create the user
                if($user->create()) {
                    // Set response code - 201 created
                    http_response_code(201);

                    // Tell the user
                    echo json_encode(array("message" => "User was created."));
                }
                // If unable to create the user
                else {
                    // Set response code - 503 service unavailable
                    http_response_code(503);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to create user."));
                }
            }
            // Tell the user data is incomplete
            else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to create user. Data is incomplete."));
            }
        } else {
            // Set response code - 405 method not allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'login':
        // Check if request method is POST
        if($request_method == "POST") {
            // Check if login credential (username, email, or phone) and password are provided
            if((!empty($data->username) || !empty($data->email) || !empty($data->phone)) && !empty($data->password)) {
                // Set user property values
                // If email is provided but username is not, use email as the login credential
                if(empty($data->username) && !empty($data->email)) {
                    $user->username = $data->email; // Using username property to store email input
                } else if(!empty($data->username)) {
                    $user->username = $data->username;
                }

                // Set phone if provided
                if(!empty($data->phone)) {
                    $user->phone = $data->phone;
                }

                $user->password = $data->password;

                // Attempt to login
                if($user->login()) {
                    // Generate JWT
                    $jwt = generateJWT($user->id, $user->username, $user->role);

                    // Set response code - 200 OK
                    http_response_code(200);

                    // Return JWT token and user data
                    echo json_encode(
                        array(
                            "message" => "Login successful.",
                            "jwt" => $jwt,
                            "id" => $user->id,
                            "username" => $user->username,
                            "firstName" => $user->firstName,
                            "lastName" => $user->lastName,
                            "email" => $user->email,
                            "phone" => $user->phone,
                            "role" => $user->role
                        )
                    );
                }
                // Login failed
                else {
                    // Set response code - 401 Unauthorized
                    http_response_code(401);

                    // Tell the user login failed
                    echo json_encode(array("message" => "Invalid username/email or password."));
                }
            }
            // Tell the user data is incomplete
            else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to login. Username/email or password is missing."));
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
