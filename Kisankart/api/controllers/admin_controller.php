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
include_once __DIR__ . '/../includes/auth_middleware.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user object
$user = new User($db);

// Check if user is authenticated and is an admin
$auth = new AuthMiddleware();
$auth_data = $auth->validateToken();

if(!$auth_data) {
    // Set response code - 401 Unauthorized
    http_response_code(401);

    // Tell the user
    echo json_encode(array("message" => "Unauthorized."));
    exit;
}

if($auth_data['role'] !== 'admin') {
    // Set response code - 403 Forbidden
    http_response_code(403);

    // Tell the user
    echo json_encode(array("message" => "Access denied. Admin privileges required."));
    exit;
}

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Route based on request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $request_uri);
$endpoint = end($uri_segments);

// Get user ID from URL if provided
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

switch($endpoint) {
    case 'users':
        // Get all users
        if($request_method == "GET") {
            // Query to get all users
            $query = "SELECT id, username, email, phone, role, created_at FROM users ORDER BY created_at DESC";

            // Prepare statement
            $stmt = $db->prepare($query);

            // Execute query
            $stmt->execute();

            // Check if any users found
            if($stmt->rowCount() > 0) {
                // Users array
                $users_arr = array();
                $users_arr["records"] = array();

                // Retrieve table contents
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);

                    $user_item = array(
                        "id" => $id,
                        "username" => $username,
                        "email" => $email,
                        "phone" => $phone,
                        "role" => $role,
                        "created_at" => $created_at
                    );

                    array_push($users_arr["records"], $user_item);
                }

                // Set response code - 200 OK
                http_response_code(200);

                // Show users data
                echo json_encode($users_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user no users found
                echo json_encode(array("message" => "No users found."));
            }
        }
        // Create user
        else if($request_method == "POST") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if required data is provided
            if(
                !empty($data->username) &&
                !empty($data->password) &&
                !empty($data->email) &&
                !empty($data->role)
            ) {
                // Set user property values
                $user->username = $data->username;
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

    case 'user':
        // Get single user
        if($request_method == "GET" && $user_id) {
            // Set ID property of user to be read
            $user->id = $user_id;

            // Read the details of user
            if($user->readOne()) {
                // Create array
                $user_arr = array(
                    "id" => $user->id,
                    "username" => $user->username,
                    "email" => $user->email,
                    "phone" => $user->phone,
                    "role" => $user->role,
                    "created_at" => $user->created_at,
                    "updated_at" => $user->updated_at
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Make it json format
                echo json_encode($user_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user user does not exist
                echo json_encode(array("message" => "User does not exist."));
            }
        }
        // Update user
        else if($request_method == "PUT" && $user_id) {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Set ID property of user to be updated
            $user->id = $user_id;

            // Read the details of user
            if(!$user->readOne()) {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user user does not exist
                echo json_encode(array("message" => "User does not exist."));
                exit;
            }

            // Set user property values
            $user->username = isset($data->username) ? $data->username : $user->username;
            $user->email = isset($data->email) ? $data->email : $user->email;
            $user->phone = isset($data->phone) ? $data->phone : $user->phone;
            $user->role = isset($data->role) ? $data->role : $user->role;
            $user->password = isset($data->password) ? $data->password : "";

            // Update the user
            if($user->update()) {
                // Set response code - 200 OK
                http_response_code(200);

                // Tell the user
                echo json_encode(array("message" => "User was updated."));
            }
            // If unable to update the user
            else {
                // Set response code - 503 service unavailable
                http_response_code(503);

                // Tell the user
                echo json_encode(array("message" => "Unable to update user."));
            }
        }
        // Delete user
        else if($request_method == "DELETE" && $user_id) {
            // Set ID property of user to be deleted
            $user->id = $user_id;

            // Delete the user
            if($user->delete()) {
                // Set response code - 200 OK
                http_response_code(200);

                // Tell the user
                echo json_encode(array("message" => "User was deleted."));
            }
            // If unable to delete the user
            else {
                // Set response code - 503 service unavailable
                http_response_code(503);

                // Tell the user
                echo json_encode(array("message" => "Unable to delete user."));
            }
        } else {
            // Set response code - 400 bad request
            http_response_code(400);

            // Tell the user
            echo json_encode(array("message" => "Bad request."));
        }
        break;

    case 'dashboard':
        // Get dashboard statistics
        if($request_method == "GET") {
            // Get total users count
            $users_query = "SELECT COUNT(*) as total_users FROM users";
            $users_stmt = $db->prepare($users_query);
            $users_stmt->execute();
            $users_row = $users_stmt->fetch(PDO::FETCH_ASSOC);
            $total_users = $users_row['total_users'];

            // Get farmers count
            $farmers_query = "SELECT COUNT(*) as total_farmers FROM users WHERE role = 'farmer'";
            $farmers_stmt = $db->prepare($farmers_query);
            $farmers_stmt->execute();
            $farmers_row = $farmers_stmt->fetch(PDO::FETCH_ASSOC);
            $total_farmers = $farmers_row['total_farmers'];

            // Get customers count
            $customers_query = "SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'";
            $customers_stmt = $db->prepare($customers_query);
            $customers_stmt->execute();
            $customers_row = $customers_stmt->fetch(PDO::FETCH_ASSOC);
            $total_customers = $customers_row['total_customers'];

            // Get products count
            $products_query = "SELECT COUNT(*) as total_products FROM products";
            $products_stmt = $db->prepare($products_query);
            $products_stmt->execute();
            $products_row = $products_stmt->fetch(PDO::FETCH_ASSOC);
            $total_products = $products_row['total_products'];

            // Get orders count
            $orders_query = "SELECT COUNT(*) as total_orders FROM orders";
            $orders_stmt = $db->prepare($orders_query);
            $orders_stmt->execute();
            $orders_row = $orders_stmt->fetch(PDO::FETCH_ASSOC);
            $total_orders = $orders_row['total_orders'];

            // Get open complaints count
            $complaints_query = "SELECT COUNT(*) as open_complaints FROM complaints WHERE status = 'open'";
            $complaints_stmt = $db->prepare($complaints_query);
            $complaints_stmt->execute();
            $complaints_row = $complaints_stmt->fetch(PDO::FETCH_ASSOC);
            $open_complaints = $complaints_row['open_complaints'];

            // Create response array
            $dashboard = array(
                "total_users" => $total_users,
                "total_farmers" => $total_farmers,
                "total_customers" => $total_customers,
                "total_products" => $total_products,
                "total_orders" => $total_orders,
                "open_complaints" => $open_complaints
            );

            // Set response code - 200 OK
            http_response_code(200);

            // Return dashboard data
            echo json_encode($dashboard);
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
