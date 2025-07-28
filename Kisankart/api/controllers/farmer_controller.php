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
include_once __DIR__ . '/../models/Product.php';
include_once __DIR__ . '/../models/Order.php';
include_once __DIR__ . '/../includes/auth_middleware.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is authenticated and is a farmer
$auth = new AuthMiddleware();
$auth_data = $auth->validateToken();

if(!$auth_data) {
    // Set response code - 401 Unauthorized
    http_response_code(401);

    // Tell the user
    echo json_encode(array("message" => "Unauthorized."));
    exit;
}

if($auth_data['role'] !== 'farmer') {
    // Set response code - 403 Forbidden
    http_response_code(403);

    // Tell the user
    echo json_encode(array("message" => "Access denied. Farmer privileges required."));
    exit;
}

// Get farmer ID from token
$farmer_id = $auth_data['id'];

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Route based on request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $request_uri);
$endpoint = end($uri_segments);

// Get product ID from URL if provided
$product_id = isset($_GET['id']) ? $_GET['id'] : null;

switch($endpoint) {
    case 'profile':
        // Get farmer profile
        if($request_method == "GET") {
            // Query to get farmer profile
            $query = "SELECT u.id, u.username, u.email, u.phone, fp.farm_name, fp.farm_location,
                      fp.farm_description, fp.profile_image
                      FROM users u
                      LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
                      WHERE u.id = ? AND u.role = 'farmer'";

            // Prepare statement
            $stmt = $db->prepare($query);

            // Bind farmer ID
            $stmt->bindParam(1, $farmer_id);

            // Execute query
            $stmt->execute();

            // Check if farmer found
            if($stmt->rowCount() > 0) {
                // Get farmer data
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Create farmer array
                $farmer = array(
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "phone" => $row['phone'],
                    "farm_name" => $row['farm_name'],
                    "farm_location" => $row['farm_location'],
                    "farm_description" => $row['farm_description'],
                    "profile_image" => $row['profile_image']
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Return farmer data
                echo json_encode($farmer);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user farmer not found
                echo json_encode(array("message" => "Farmer not found."));
            }
        }
        // Update farmer profile
        else if($request_method == "PUT") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if farmer profile exists
            $check_query = "SELECT id FROM farmer_profiles WHERE user_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $farmer_id);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                // Update existing profile
                $query = "UPDATE farmer_profiles
                          SET farm_name = ?, farm_location = ?, farm_description = ?, profile_image = ?
                          WHERE user_id = ?";

                // Prepare statement
                $stmt = $db->prepare($query);

                // Sanitize data
                $farm_name = htmlspecialchars(strip_tags($data->farm_name ?? ''));
                $farm_location = htmlspecialchars(strip_tags($data->farm_location ?? ''));
                $farm_description = htmlspecialchars(strip_tags($data->farm_description ?? ''));
                $profile_image = htmlspecialchars(strip_tags($data->profile_image ?? ''));

                // Bind data
                $stmt->bindParam(1, $farm_name);
                $stmt->bindParam(2, $farm_location);
                $stmt->bindParam(3, $farm_description);
                $stmt->bindParam(4, $profile_image);
                $stmt->bindParam(5, $farmer_id);
            } else {
                // Create new profile
                $query = "INSERT INTO farmer_profiles (user_id, farm_name, farm_location, farm_description, profile_image)
                          VALUES (?, ?, ?, ?, ?)";

                // Prepare statement
                $stmt = $db->prepare($query);

                // Sanitize data
                $farm_name = htmlspecialchars(strip_tags($data->farm_name ?? ''));
                $farm_location = htmlspecialchars(strip_tags($data->farm_location ?? ''));
                $farm_description = htmlspecialchars(strip_tags($data->farm_description ?? ''));
                $profile_image = htmlspecialchars(strip_tags($data->profile_image ?? ''));

                // Bind data
                $stmt->bindParam(1, $farmer_id);
                $stmt->bindParam(2, $farm_name);
                $stmt->bindParam(3, $farm_location);
                $stmt->bindParam(4, $farm_description);
                $stmt->bindParam(5, $profile_image);
            }

            // Execute query
            if($stmt->execute()) {
                // Update user data if provided
                if(isset($data->email) || isset($data->phone)) {
                    // Instantiate user object
                    $user = new User($db);
                    $user->id = $farmer_id;

                    // Read current user data
                    $user->readOne();

                    // Update user properties
                    $user->email = isset($data->email) ? $data->email : $user->email;
                    $user->phone = isset($data->phone) ? $data->phone : $user->phone;

                    // Update user
                    $user->update();
                }

                // Set response code - 200 OK
                http_response_code(200);

                // Tell the user
                echo json_encode(array("message" => "Profile updated successfully."));
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

    case 'products':
        // Get all products of the farmer
        if($request_method == "GET") {
            // Instantiate product object
            $product = new Product($db);
            $product->farmer_id = $farmer_id;

            // Get products
            $stmt = $product->readByFarmer();
            $num = $stmt->rowCount();

            // Check if any products found
            if($num > 0) {
                // Products array
                $products_arr = array();
                $products_arr["records"] = array();

                // Retrieve table contents
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);

                    $product_item = array(
                        "id" => $id,
                        "category_id" => $category_id,
                        "category_name" => $category_name,
                        "name" => $name,
                        "description" => $description,
                        "price" => $price,
                        "stock_quantity" => $stock_quantity,
                        "unit" => $unit,
                        "image" => $image,
                        "is_organic" => $is_organic,
                        "is_available" => $is_available,
                        "created_at" => $created_at
                    );

                    array_push($products_arr["records"], $product_item);
                }

                // Set response code - 200 OK
                http_response_code(200);

                // Show products data
                echo json_encode($products_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user no products found
                echo json_encode(array("message" => "No products found."));
            }
        }
        // Create new product
        else if($request_method == "POST") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if required data is provided
            if(
                !empty($data->category_id) &&
                !empty($data->name) &&
                !empty($data->price) &&
                !empty($data->stock_quantity) &&
                !empty($data->unit)
            ) {
                // Instantiate product object
                $product = new Product($db);

                // Set product property values
                $product->farmer_id = $farmer_id;
                $product->category_id = $data->category_id;
                $product->name = $data->name;
                $product->description = $data->description ?? '';
                $product->price = $data->price;
                $product->stock_quantity = $data->stock_quantity;
                $product->unit = $data->unit;
                $product->image = $data->image ?? '';
                $product->is_organic = $data->is_organic ?? 0;
                $product->is_available = $data->is_available ?? 1;

                // Create the product
                if($product->create()) {
                    // Set response code - 201 created
                    http_response_code(201);

                    // Tell the user
                    echo json_encode(array("message" => "Product was created."));
                }
                // If unable to create the product
                else {
                    // Set response code - 503 service unavailable
                    http_response_code(503);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to create product."));
                }
            }
            // Tell the user data is incomplete
            else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to create product. Data is incomplete."));
            }
        } else {
            // Set response code - 405 method not allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'product':
        // Get single product
        if($request_method == "GET" && $product_id) {
            // Instantiate product object
            $product = new Product($db);
            $product->id = $product_id;

            // Read the details of product
            if($product->readOne()) {
                // Check if product belongs to the farmer
                if($product->farmer_id != $farmer_id) {
                    // Set response code - 403 Forbidden
                    http_response_code(403);

                    // Tell the user
                    echo json_encode(array("message" => "Access denied. You don't own this product."));
                    exit;
                }

                // Create array
                $product_arr = array(
                    "id" => $product->id,
                    "farmer_id" => $product->farmer_id,
                    "category_id" => $product->category_id,
                    "name" => $product->name,
                    "description" => $product->description,
                    "price" => $product->price,
                    "stock_quantity" => $product->stock_quantity,
                    "unit" => $product->unit,
                    "image" => $product->image,
                    "is_organic" => $product->is_organic,
                    "is_available" => $product->is_available,
                    "created_at" => $product->created_at,
                    "updated_at" => $product->updated_at
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Make it json format
                echo json_encode($product_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user product does not exist
                echo json_encode(array("message" => "Product does not exist."));
            }
        }
        // Update product
        else if($request_method == "PUT" && $product_id) {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Instantiate product object
            $product = new Product($db);
            $product->id = $product_id;
            $product->farmer_id = $farmer_id;

            // Read the details of product
            if(!$product->readOne()) {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user product does not exist
                echo json_encode(array("message" => "Product does not exist."));
                exit;
            }

            // Check if product belongs to the farmer
            if($product->farmer_id != $farmer_id) {
                // Set response code - 403 Forbidden
                http_response_code(403);

                // Tell the user
                echo json_encode(array("message" => "Access denied. You don't own this product."));
                exit;
            }

            // Set product property values
            $product->category_id = isset($data->category_id) ? $data->category_id : $product->category_id;
            $product->name = isset($data->name) ? $data->name : $product->name;
            $product->description = isset($data->description) ? $data->description : $product->description;
            $product->price = isset($data->price) ? $data->price : $product->price;
            $product->stock_quantity = isset($data->stock_quantity) ? $data->stock_quantity : $product->stock_quantity;
            $product->unit = isset($data->unit) ? $data->unit : $product->unit;
            $product->image = isset($data->image) ? $data->image : $product->image;
            $product->is_organic = isset($data->is_organic) ? $data->is_organic : $product->is_organic;
            $product->is_available = isset($data->is_available) ? $data->is_available : $product->is_available;

            // Update the product
            if($product->update()) {
                // Set response code - 200 OK
                http_response_code(200);

                // Tell the user
                echo json_encode(array("message" => "Product was updated."));
            }
            // If unable to update the product
            else {
                // Set response code - 503 service unavailable
                http_response_code(503);

                // Tell the user
                echo json_encode(array("message" => "Unable to update product."));
            }
        }
        // Delete product
        else if($request_method == "DELETE" && $product_id) {
            // Instantiate product object
            $product = new Product($db);
            $product->id = $product_id;
            $product->farmer_id = $farmer_id;

            // Delete the product
            if($product->delete()) {
                // Set response code - 200 OK
                http_response_code(200);

                // Tell the user
                echo json_encode(array("message" => "Product was deleted."));
            }
            // If unable to delete the product
            else {
                // Set response code - 503 service unavailable
                http_response_code(503);

                // Tell the user
                echo json_encode(array("message" => "Unable to delete product."));
            }
        } else {
            // Set response code - 400 bad request
            http_response_code(400);

            // Tell the user
            echo json_encode(array("message" => "Bad request."));
        }
        break;

    case 'orders':
        // Get all orders for the farmer
        if($request_method == "GET") {
            // Instantiate order object
            $order = new Order($db);

            // Get orders
            $stmt = $order->readFarmerOrders($farmer_id);
            $num = $stmt->rowCount();

            // Check if any orders found
            if($num > 0) {
                // Orders array
                $orders_arr = array();
                $orders_arr["records"] = array();

                // Retrieve table contents
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);

                    $order_item = array(
                        "id" => $id,
                        "customer_id" => $customer_id,
                        "product_name" => $product_name,
                        "quantity" => $quantity,
                        "price" => $price,
                        "total" => $quantity * $price,
                        "status" => $status,
                        "created_at" => $created_at
                    );

                    array_push($orders_arr["records"], $order_item);
                }

                // Set response code - 200 OK
                http_response_code(200);

                // Show orders data
                echo json_encode($orders_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user no orders found
                echo json_encode(array("message" => "No orders found."));
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
