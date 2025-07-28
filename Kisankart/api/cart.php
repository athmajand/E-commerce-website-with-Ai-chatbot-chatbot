<?php
// Include database and authorization
include_once 'config/database.php';
include_once 'config/auth.php';
include_once 'models/User.php';
include_once 'models/Product.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check authorization
$auth = new Auth($db);
$auth_data = $auth->validateToken();

// If not authorized
if (!$auth_data) {
    // Set response code - 401 Unauthorized
    http_response_code(401);
    
    // Tell the user
    echo json_encode(array("message" => "Unauthorized. Please login."));
    exit;
}

// Get user ID from token
$user_id = $auth_data['id'];

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Handle based on request method
switch ($request_method) {
    // Get cart items
    case "GET":
        // Query to get cart items with correct table joins
        $query = "SELECT c.id, c.product_id, p.name, p.price, p.image, p.unit, c.quantity,
                  (p.price * c.quantity) as total, 
                  CONCAT(s.first_name, ' ', s.last_name) as seller_name
                  FROM cart c
                  JOIN products p ON c.product_id = p.id
                  JOIN seller_registrations s ON p.seller_id = s.id
                  WHERE c.customer_id = ?";

        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind customer ID
        $stmt->bindParam(1, $user_id);

        // Execute query
        $stmt->execute();

        // Check if any records found
        if ($stmt->rowCount() > 0) {
            // Cart array
            $cart_arr = array();
            $cart_arr["items"] = array();
            $cart_arr["itemCount"] = $stmt->rowCount();
            $total = 0;

            // Retrieve records
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cart_item = array(
                    "id" => $row['id'],
                    "product_id" => $row['product_id'],
                    "name" => $row['name'],
                    "price" => $row['price'],
                    "image" => $row['image'],
                    "unit" => $row['unit'],
                    "quantity" => $row['quantity'],
                    "total" => $row['total'],
                    "seller_name" => $row['seller_name']
                );

                // Add to cart array
                array_push($cart_arr["items"], $cart_item);
                
                // Add to total (fix the bug)
                $total += $row['total'];
            }

            // Add total to cart array
            $cart_arr["total"] = $total;

            // Set response code - 200 OK
            http_response_code(200);

            // Show cart data
            echo json_encode($cart_arr);
        } else {
            // Set response code - 200 OK (empty cart is not an error)
            http_response_code(200);

            // Tell the user cart is empty
            echo json_encode(array("message" => "Cart is empty.", "items" => array(), "total" => 0, "itemCount" => 0));
        }
        break;

    // Add item to cart
    case "POST":
        // Get posted data
        $data = json_decode(file_get_contents("php://input"));

        // Check if required fields are provided
        if (isset($data->product_id) && isset($data->quantity)) {
            // Check if product exists
            $product = new Product($db);
            $product->id = $data->product_id;
            
            if ($product->readOne()) {
                // Check if quantity is available
                if ($product->stock >= $data->quantity) {
                    // Check if product already in cart
                    $check_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->bindParam(1, $user_id);
                    $check_stmt->bindParam(2, $data->product_id);
                    $check_stmt->execute();

                    if ($check_stmt->rowCount() > 0) {
                        // Update quantity
                        $cart_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        $new_quantity = $cart_item['quantity'] + $data->quantity;

                        // Check if new quantity is available
                        if ($new_quantity > $product->stock) {
                            // Set response code - 400 bad request
                            http_response_code(400);

                            // Tell the user
                            echo json_encode(array(
                                "message" => "Requested quantity not available. Available: " . $product->stock
                            ));
                            exit;
                        }

                        // Update cart
                        $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                        $update_stmt = $db->prepare($update_query);
                        $update_stmt->bindParam(1, $new_quantity);
                        $update_stmt->bindParam(2, $cart_item['id']);

                        if ($update_stmt->execute()) {
                            // Set response code - 200 OK
                            http_response_code(200);

                            // Tell the user
                            echo json_encode(array(
                                "message" => "Item quantity updated in cart.",
                                "id" => $cart_item['id'],
                                "quantity" => $new_quantity
                            ));
                        } else {
                            // Set response code - 500 internal server error
                            http_response_code(500);

                            // Tell the user
                            echo json_encode(array("message" => "Unable to update cart."));
                        }
                    } else {
                        // Add to cart
                        $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
                        $insert_stmt = $db->prepare($insert_query);
                        $insert_stmt->bindParam(1, $user_id);
                        $insert_stmt->bindParam(2, $data->product_id);
                        $insert_stmt->bindParam(3, $data->quantity);

                        if ($insert_stmt->execute()) {
                            // Set response code - 201 created
                            http_response_code(201);

                            // Tell the user
                            echo json_encode(array(
                                "message" => "Item added to cart.",
                                "id" => $db->lastInsertId(),
                                "quantity" => $data->quantity
                            ));
                        } else {
                            // Set response code - 500 internal server error
                            http_response_code(500);

                            // Tell the user
                            echo json_encode(array("message" => "Unable to add item to cart."));
                        }
                    }
                } else {
                    // Set response code - 400 bad request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array(
                        "message" => "Requested quantity not available. Available: " . $product->stock
                    ));
                }
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user
                echo json_encode(array("message" => "Product not found."));
            }
        } else {
            // Set response code - 400 bad request
            http_response_code(400);

            // Tell the user
            echo json_encode(array("message" => "Unable to add item to cart. Data incomplete."));
        }
        break;

    // Update cart item quantity
    case "PUT":
        // Get posted data
        $data = json_decode(file_get_contents("php://input"));

        // Check if required fields are provided
        if (isset($data->item_id) && isset($data->quantity)) {
            // Check if cart item exists and belongs to user
            $check_query = "SELECT c.id, c.quantity, p.stock, p.name FROM cart c 
                           JOIN products p ON c.product_id = p.id 
                           WHERE c.id = ? AND c.customer_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $data->item_id);
            $check_stmt->bindParam(2, $user_id);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $cart_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Check if quantity is available
                if ($data->quantity <= $cart_item['stock']) {
                    // Update cart
                    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(1, $data->quantity);
                    $update_stmt->bindParam(2, $data->item_id);

                    if ($update_stmt->execute()) {
                        // Set response code - 200 OK
                        http_response_code(200);

                        // Tell the user
                        echo json_encode(array(
                            "message" => "Cart item updated successfully.",
                            "id" => $data->item_id,
                            "quantity" => $data->quantity
                        ));
                    } else {
                        // Set response code - 500 internal server error
                        http_response_code(500);

                        // Tell the user
                        echo json_encode(array("message" => "Unable to update cart item."));
                    }
                } else {
                    // Set response code - 400 bad request
                    http_response_code(400);

                    // Tell the user
                    echo json_encode(array(
                        "message" => "Requested quantity not available. Available: " . $cart_item['stock']
                    ));
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
            echo json_encode(array("message" => "Unable to update cart item. Data incomplete."));
        }
        break;

    // Delete cart item
    case "DELETE":
        // Get item ID from URL parameters
        $item_id = isset($_GET['id']) ? $_GET['id'] : null;

        if ($item_id) {
            // Check if cart item exists and belongs to user
            $check_query = "SELECT id FROM cart WHERE id = ? AND customer_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $item_id);
            $check_stmt->bindParam(2, $user_id);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                // Delete cart item
                $delete_query = "DELETE FROM cart WHERE id = ?";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->bindParam(1, $item_id);

                if ($delete_stmt->execute()) {
                    // Set response code - 200 OK
                    http_response_code(200);

                    // Tell the user
                    echo json_encode(array(
                        "message" => "Cart item removed successfully.",
                        "id" => $item_id
                    ));
                } else {
                    // Set response code - 500 internal server error
                    http_response_code(500);

                    // Tell the user
                    echo json_encode(array("message" => "Unable to remove cart item."));
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
            echo json_encode(array("message" => "Item ID is required."));
        }
        break;

    // Other methods not supported
    default:
        // Set response code - 405 method not allowed
        http_response_code(405);

        // Tell the user
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>
