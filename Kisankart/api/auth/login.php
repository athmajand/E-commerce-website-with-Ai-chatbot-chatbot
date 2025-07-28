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

// Include database and user models
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../models/SellerLogin.php';

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

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Instantiate user object
    $user = new User($db);

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    // Check if login with phone number
    if (!empty($data->phone) && !empty($data->password)) {
        // Check if seller login is requested
        $isSeller = isset($data->seller) && $data->seller === true;

        if ($isSeller) {
            // Instantiate seller login object
            $seller = new SellerLogin($db);

            // Set seller property values for phone login
            $seller->phone = $data->phone;
            $seller->password = $data->password;

            // Attempt to login with phone
            if ($seller->login()) {
                // Generate JWT
                $jwt = generateJWT($seller->id, $seller->username, $seller->role);

                // Set response code - 200 OK
                http_response_code(200);

                // Return JWT token and seller data
                echo json_encode(
                    array(
                        "message" => "Seller login successful.",
                        "jwt" => $jwt,
                        "id" => $seller->id,
                        "username" => $seller->username,
                        "firstName" => $seller->firstName,
                        "lastName" => $seller->lastName,
                        "email" => $seller->email,
                        "phone" => $seller->phone,
                        "role" => $seller->role
                    )
                );
            } else {
                // Set response code - 401 Unauthorized
                http_response_code(401);

                // Tell the user login failed
                echo json_encode(array("message" => "Invalid seller phone number or password."));
            }
        } else {
            // Set user property values for phone login
            $user->phone = $data->phone;
            $user->password = $data->password;

            // Attempt to login with phone
            if ($user->login()) {
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
            } else {
                // Set response code - 401 Unauthorized
                http_response_code(401);

                // Tell the user login failed
                echo json_encode(array("message" => "Invalid phone number or password."));
            }
        }
    }
    // Check if login credential (username or email) and password are provided
    else if ((!empty($data->username) || !empty($data->email)) && !empty($data->password)) {
        // Check if seller login is requested
        $isSeller = isset($data->seller) && $data->seller === true;

        if ($isSeller) {
            // Instantiate seller login object
            $seller = new SellerLogin($db);

            // Set seller property values
            if (empty($data->username) && !empty($data->email)) {
                $seller->email = $data->email;
            } else if (!empty($data->username)) {
                $seller->username = $data->username;
            }
            $seller->password = $data->password;

            // Attempt to login
            if ($seller->login()) {
                // Generate JWT
                $jwt = generateJWT($seller->id, $seller->username, $seller->role);

                // Set response code - 200 OK
                http_response_code(200);

                // Return JWT token and seller data
                echo json_encode(
                    array(
                        "message" => "Seller login successful.",
                        "jwt" => $jwt,
                        "id" => $seller->id,
                        "username" => $seller->username,
                        "firstName" => $seller->firstName,
                        "lastName" => $seller->lastName,
                        "email" => $seller->email,
                        "phone" => $seller->phone,
                        "role" => $seller->role
                    )
                );
            } else {
                // Set response code - 401 Unauthorized
                http_response_code(401);

                // Tell the user login failed
                echo json_encode(array("message" => "Invalid seller username/email or password."));
            }
        } else {
            // Set user property values
            // If email is provided but username is not, use email as the login credential
            if (empty($data->username) && !empty($data->email)) {
                $user->username = $data->email; // Using username property to store email input
            } else {
                $user->username = $data->username;
            }
            $user->password = $data->password;

            // Attempt to login
            if ($user->login()) {
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
            } else {
                // Set response code - 401 Unauthorized
                http_response_code(401);

                // Tell the user login failed
                echo json_encode(array("message" => "Invalid username/email or password."));
            }
        }
    }
    // Tell the user data is incomplete
    else {
        // Set response code - 400 bad request
        http_response_code(400);

        // Tell the user
        echo json_encode(array("message" => "Unable to login. Username/email/phone or password is missing."));
    }
} else {
    // Set response code - 405 method not allowed
    http_response_code(405);

    // Tell the user
    echo json_encode(array("message" => "Method not allowed."));
}
?>
