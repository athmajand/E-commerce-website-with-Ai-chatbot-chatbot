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

// Check if user is authenticated and is a customer
$auth = new AuthMiddleware();
$auth_data = $auth->validateToken();

if(!$auth_data) {
    // Set response code - 401 Unauthorized
    http_response_code(401);

    // Tell the user
    echo json_encode(array("message" => "Unauthorized."));
    exit;
}

if($auth_data['role'] !== 'customer') {
    // Set response code - 403 Forbidden
    http_response_code(403);

    // Tell the user
    echo json_encode(array("message" => "Access denied. Customer privileges required."));
    exit;
}

// Get customer ID from token
$customer_id = $auth_data['id'];

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Route based on request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $request_uri);
$endpoint = end($uri_segments);

// Get item ID from URL if provided
$item_id = isset($_GET['id']) ? $_GET['id'] : null;

switch($endpoint) {
    case 'profile':
        // Get customer profile
        if($request_method == "GET") {
            // Query to get customer profile
            $query = "SELECT u.id, u.username, u.email, u.phone, cp.address, cp.city,
                      cp.state, cp.postal_code, cp.profile_image
                      FROM users u
                      LEFT JOIN customer_profiles cp ON u.id = cp.user_id
                      WHERE u.id = ? AND u.role = 'customer'";

            // Prepare statement
            $stmt = $db->prepare($query);

            // Bind customer ID
            $stmt->bindParam(1, $customer_id);

            // Execute query
            $stmt->execute();

            // Check if customer found
            if($stmt->rowCount() > 0) {
                // Get customer data
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Create customer array
                $customer = array(
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "phone" => $row['phone'],
                    "address" => $row['address'],
                    "city" => $row['city'],
                    "state" => $row['state'],
                    "postal_code" => $row['postal_code'],
                    "profile_image" => $row['profile_image']
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Return customer data
                echo json_encode($customer);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user customer not found
                echo json_encode(array("message" => "Customer not found."));
            }
        }
        // Update customer profile
        else if($request_method == "PUT") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if customer profile exists
            $check_query = "SELECT id FROM customer_profiles WHERE user_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $customer_id);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                // Update existing profile
                $query = "UPDATE customer_profiles
                          SET address = ?, city = ?, state = ?, postal_code = ?, profile_image = ?
                          WHERE user_id = ?";

                // Prepare statement
                $stmt = $db->prepare($query);

                // Sanitize data
                $address = htmlspecialchars(strip_tags($data->address ?? ''));
                $city = htmlspecialchars(strip_tags($data->city ?? ''));
                $state = htmlspecialchars(strip_tags($data->state ?? ''));
                $postal_code = htmlspecialchars(strip_tags($data->postal_code ?? ''));
                $profile_image = htmlspecialchars(strip_tags($data->profile_image ?? ''));

                // Bind data
                $stmt->bindParam(1, $address);
                $stmt->bindParam(2, $city);
                $stmt->bindParam(3, $state);
                $stmt->bindParam(4, $postal_code);
                $stmt->bindParam(5, $profile_image);
                $stmt->bindParam(6, $customer_id);
            } else {
                // Create new profile
                $query = "INSERT INTO customer_profiles (user_id, address, city, state, postal_code, profile_image)
                          VALUES (?, ?, ?, ?, ?, ?)";

                // Prepare statement
                $stmt = $db->prepare($query);

                // Sanitize data
                $address = htmlspecialchars(strip_tags($data->address ?? ''));
                $city = htmlspecialchars(strip_tags($data->city ?? ''));
                $state = htmlspecialchars(strip_tags($data->state ?? ''));
                $postal_code = htmlspecialchars(strip_tags($data->postal_code ?? ''));
                $profile_image = htmlspecialchars(strip_tags($data->profile_image ?? ''));

                // Bind data
                $stmt->bindParam(1, $customer_id);
                $stmt->bindParam(2, $address);
                $stmt->bindParam(3, $city);
                $stmt->bindParam(4, $state);
                $stmt->bindParam(5, $postal_code);
                $stmt->bindParam(6, $profile_image);
            }

            // Execute query
            if($stmt->execute()) {
                // Update user data if provided
                if(isset($data->email) || isset($data->phone)) {
                    // Instantiate user object
                    $user = new User($db);
                    $user->id = $customer_id;

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

    case 'cart':
        // Get cart items
        if($request_method == "GET") {
            // Query to get cart items
            $query = "SELECT c.id, c.product_id, p.name, p.price, p.image, p.unit, c.quantity,
                      (p.price * c.quantity) as total, u.username as farmer_name
                      FROM cart c
                      JOIN products p ON c.product_id = p.id
                      JOIN users u ON p.farmer_id = u.id
                      WHERE c.customer_id = ?";

            // Prepare statement
            $stmt = $db->prepare($query);

            // Bind customer ID
            $stmt->bindParam(1, $customer_id);

            // Execute query
            $stmt->execute();

            // Check if any cart items found
            if($stmt->rowCount() > 0) {
                // Cart array
                $cart_arr = array();
                $cart_arr["records"] = array();
                $cart_arr["total"] = 0;

                // Retrieve table contents
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);

                    $cart_item = array(
                        "id" => $id,
                        "product_id" => $product_id,
                        "name" => $name,
                        "price" => $price,
                        "image" => $image,
                        "unit" => $unit,
                        "quantity" => $quantity,
                        "total" => $total,
                        "farmer_name" => $farmer_name
                    );

                    array_push($cart_arr["records"], $cart_item);
                    $cart_arr["total"] += $total;
                }

                // Set response code - 200 OK
                http_response_code(200);

                // Show cart data
                echo json_encode($cart_arr);
            } else {
                // Set response code - 200 OK (empty cart is not an error)
                http_response_code(200);

                // Tell the user cart is empty
                echo json_encode(array("message" => "Cart is empty.", "records" => array(), "total" => 0));
            }
        }
        // Add item to cart
        else if($request_method == "POST") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if required data is provided
            if(!empty($data->product_id) && !empty($data->quantity)) {
                // Check if product exists and is available
                $product_query = "SELECT id, stock_quantity FROM products WHERE id = ? AND is_available = 1";
                $product_stmt = $db->prepare($product_query);
                $product_stmt->bindParam(1, $data->product_id);
                $product_stmt->execute();

                if($product_stmt->rowCount() > 0) {
                    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

                    // Check if requested quantity is available
                    if($data->quantity > $product['stock_quantity']) {
                        // Set response code - 400 bad request
                        http_response_code(400);

                        // Tell the user
                        echo json_encode(array(
                            "message" => "Requested quantity not available. Available: " . $product['stock_quantity']
                        ));
                        exit;
                    }

                    // Check if product already in cart
                    $check_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->bindParam(1, $customer_id);
                    $check_stmt->bindParam(2, $data->product_id);
                    $check_stmt->execute();

                    if($check_stmt->rowCount() > 0) {
                        // Update quantity
                        $cart_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        $new_quantity = $cart_item['quantity'] + $data->quantity;

                        // Check if new quantity is available
                        if($new_quantity > $product['stock_quantity']) {
                            // Set response code - 400 bad request
                            http_response_code(400);

                            // Tell the user
                            echo json_encode(array(
                                "message" => "Requested quantity not available. Available: " . $product['stock_quantity']
                            ));
                            exit;
                        }

                        $query = "UPDATE cart SET quantity = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(1, $new_quantity);
                        $stmt->bindParam(2, $cart_item['id']);
                    } else {
                        // Add new item to cart
                        $query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(1, $customer_id);
                        $stmt->bindParam(2, $data->product_id);
                        $stmt->bindParam(3, $data->quantity);
                    }

                    // Execute query
                    if($stmt->execute()) {
                        // Set response code - 201 created
                        http_response_code(201);

                        // Tell the user
                        echo json_encode(array("message" => "Product added to cart."));
                    } else {
                        // Set response code - 503 service unavailable
                        http_response_code(503);

                        // Tell the user
                        echo json_encode(array("message" => "Unable to add product to cart."));
                    }
                } else {
                    // Set response code - 404 Not found
                    http_response_code(404);

                    // Tell the user
                    echo json_encode(array("message" => "Product not found or not available."));
                }
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to add to cart. Data is incomplete."));
            }
        }
        // Update cart item
        else if($request_method == "PUT" && $item_id) {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if quantity is provided
            if(!empty($data->quantity)) {
                // Check if cart item exists and belongs to the customer
                $check_query = "SELECT c.id, c.product_id, p.stock_quantity
                               FROM cart c
                               JOIN products p ON c.product_id = p.id
                               WHERE c.id = ? AND c.customer_id = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(1, $item_id);
                $check_stmt->bindParam(2, $customer_id);
                $check_stmt->execute();

                if($check_stmt->rowCount() > 0) {
                    $cart_item = $check_stmt->fetch(PDO::FETCH_ASSOC);

                    // Check if requested quantity is available
                    if($data->quantity > $cart_item['stock_quantity']) {
                        // Set response code - 400 bad request
                        http_response_code(400);

                        // Tell the user
                        echo json_encode(array(
                            "message" => "Requested quantity not available. Available: " . $cart_item['stock_quantity']
                        ));
                        exit;
                    }

                    // If quantity is 0, remove item from cart
                    if($data->quantity <= 0) {
                        $query = "DELETE FROM cart WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(1, $item_id);

                        if($stmt->execute()) {
                            // Set response code - 200 OK
                            http_response_code(200);

                            // Tell the user
                            echo json_encode(array("message" => "Item removed from cart."));
                        } else {
                            // Set response code - 503 service unavailable
                            http_response_code(503);

                            // Tell the user
                            echo json_encode(array("message" => "Unable to remove item from cart."));
                        }
                    } else {
                        // Update quantity
                        $query = "UPDATE cart SET quantity = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(1, $data->quantity);
                        $stmt->bindParam(2, $item_id);

                        if($stmt->execute()) {
                            // Set response code - 200 OK
                            http_response_code(200);

                            // Tell the user
                            echo json_encode(array("message" => "Cart item updated."));
                        } else {
                            // Set response code - 503 service unavailable
                            http_response_code(503);

                            // Tell the user
                            echo json_encode(array("message" => "Unable to update cart item."));
                        }
                    }
                } else {
                    // Set response code - 404 Not found
                    http_response_code(404);

                    // Tell the user
                    echo json_encode(array("message" => "Cart item not found."));
                }
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to update cart. Quantity is required."));
            }
        }
        // Remove item from cart
        else if($request_method == "DELETE" && $item_id) {
            // Check if cart item exists and belongs to the customer
            $check_query = "SELECT id FROM cart WHERE id = ? AND customer_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $item_id);
            $check_stmt->bindParam(2, $customer_id);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                // Delete cart item
                $query = "DELETE FROM cart WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $item_id);

                if($stmt->execute()) {
                    // Set response code - 200 OK
                    http_response_code(200);

                    // Tell the user
                    echo json_encode(array("message" => "Item removed from cart."));
                } else {
                    // Set response code - 503 service unavailable
                    http_response_code(503);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to remove item from cart."));
                }
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user
                echo json_encode(array("message" => "Cart item not found."));
            }
        } else {
            // Set response code - 405 method not allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'orders':
        // Get all orders for the customer
        if($request_method == "GET") {
            // Instantiate order object
            $order = new Order($db);
            $order->customer_id = $customer_id;

            // Get orders
            $stmt = $order->readCustomerOrders();
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
                        "total_amount" => $total_amount,
                        "status" => $status,
                        "payment_method" => $payment_method,
                        "payment_status" => $payment_status,
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
        }
        // Create new order
        else if($request_method == "POST") {
            // Get posted data
            $data = json_decode(file_get_contents("php://input"));

            // Check if required data is provided
            if(
                !empty($data->payment_method) &&
                !empty($data->shipping_address) &&
                !empty($data->shipping_city) &&
                !empty($data->shipping_state) &&
                !empty($data->shipping_postal_code)
            ) {
                // Check if cart is not empty
                $cart_query = "SELECT c.product_id, c.quantity, p.price, p.farmer_id, p.stock_quantity
                              FROM cart c
                              JOIN products p ON c.product_id = p.id
                              WHERE c.customer_id = ?";
                $cart_stmt = $db->prepare($cart_query);
                $cart_stmt->bindParam(1, $customer_id);
                $cart_stmt->execute();

                if($cart_stmt->rowCount() > 0) {
                    // Calculate total amount and prepare order items
                    $total_amount = 0;
                    $order_items = array();

                    while($cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Check if requested quantity is available
                        if($cart_item['quantity'] > $cart_item['stock_quantity']) {
                            // Set response code - 400 bad request
                            http_response_code(400);

                            // Tell the user
                            echo json_encode(array(
                                "message" => "Product out of stock or insufficient quantity."
                            ));
                            exit;
                        }

                        $item_total = $cart_item['quantity'] * $cart_item['price'];
                        $total_amount += $item_total;

                        $order_items[] = array(
                            "product_id" => $cart_item['product_id'],
                            "farmer_id" => $cart_item['farmer_id'],
                            "quantity" => $cart_item['quantity'],
                            "price" => $cart_item['price']
                        );
                    }

                    // Create order
                    $order = new Order($db);
                    $order->customer_id = $customer_id;
                    $order->total_amount = $total_amount;
                    $order->status = "pending";
                    $order->payment_method = $data->payment_method;
                    $order->payment_status = $data->payment_method == "cod" ? "pending" : "completed";
                    $order->shipping_address = $data->shipping_address;
                    $order->shipping_city = $data->shipping_city;
                    $order->shipping_state = $data->shipping_state;
                    $order->shipping_postal_code = $data->shipping_postal_code;
                    $order->items = $order_items;

                    if($order->create()) {
                        // Set response code - 201 created
                        http_response_code(201);

                        // Tell the user
                        echo json_encode(array(
                            "message" => "Order was created.",
                            "order_id" => $order->id,
                            "total_amount" => $total_amount
                        ));
                    } else {
                        // Set response code - 503 service unavailable
                        http_response_code(503);

                        // Tell the user
                        echo json_encode(array("message" => "Unable to create order."));
                    }
                } else {
                    // Set response code - 400 bad request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array("message" => "Cart is empty."));
                }
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to create order. Data is incomplete."));
            }
        } else {
            // Set response code - 405 method not allowed
            http_response_code(405);

            // Tell the user
            echo json_encode(array("message" => "Method not allowed."));
        }
        break;

    case 'order':
        // Get single order
        if($request_method == "GET" && $item_id) {
            // Instantiate order object
            $order = new Order($db);
            $order->id = $item_id;

            // Read the details of order
            if($order->readOne()) {
                // Check if order belongs to the customer
                if($order->customer_id != $customer_id) {
                    // Set response code - 403 Forbidden
                    http_response_code(403);

                    // Tell the user
                    echo json_encode(array("message" => "Access denied. You don't own this order."));
                    exit;
                }

                // Create array
                $order_arr = array(
                    "id" => $order->id,
                    "customer_id" => $order->customer_id,
                    "total_amount" => $order->total_amount,
                    "status" => $order->status,
                    "payment_method" => $order->payment_method,
                    "payment_status" => $order->payment_status,
                    "shipping_address" => $order->shipping_address,
                    "shipping_city" => $order->shipping_city,
                    "shipping_state" => $order->shipping_state,
                    "shipping_postal_code" => $order->shipping_postal_code,
                    "created_at" => $order->created_at,
                    "updated_at" => $order->updated_at,
                    "items" => $order->items
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Make it json format
                echo json_encode($order_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user order does not exist
                echo json_encode(array("message" => "Order does not exist."));
            }
        }
        // Cancel order
        else if($request_method == "DELETE" && $item_id) {
            // Instantiate order object
            $order = new Order($db);
            $order->id = $item_id;
            $order->customer_id = $customer_id;

            // Cancel the order
            if($order->cancel()) {
                // Set response code - 200 OK
                http_response_code(200);

                // Tell the user
                echo json_encode(array("message" => "Order was cancelled."));
            } else {
                // Set response code - 400 bad request
                http_response_code(400);

                // Tell the user
                echo json_encode(array("message" => "Unable to cancel order. Order may not be in pending status."));
            }
        } else {
            // Set response code - 400 bad request
            http_response_code(400);

            // Tell the user
            echo json_encode(array("message" => "Bad request."));
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
