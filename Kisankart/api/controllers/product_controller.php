<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and models
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/Product.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get request method
$request_method = $_SERVER["REQUEST_METHOD"];

// Route based on request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $request_uri);
$endpoint = end($uri_segments);

// Get product ID or category ID from URL if provided
$id = isset($_GET['id']) ? $_GET['id'] : null;
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Only allow GET requests
if($request_method != "GET") {
    // Set response code - 405 method not allowed
    http_response_code(405);

    // Tell the user
    echo json_encode(array("message" => "Method not allowed."));
    exit;
}

switch($endpoint) {
    case 'products':
        // Get all products
        // Instantiate product object
        $product = new Product($db);

        // If search parameter is provided
        if($search) {
            // Search products
            $stmt = $product->search($search);
        }
        // If category ID is provided
        else if($category_id) {
            // Set category ID
            $product->category_id = $category_id;

            // Get products by category
            $stmt = $product->readByCategory();
        }
        // Otherwise get all products
        else {
            // Get products
            $stmt = $product->read();
        }

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
                    "farmer_id" => $farmer_id,
                    "farmer_name" => $farmer_name,
                    "category_id" => $category_id,
                    "category_name" => $category_name,
                    "name" => $name,
                    "description" => $description,
                    "price" => $price,
                    "stock_quantity" => $stock_quantity,
                    "unit" => $unit,
                    "image" => $image,
                    "is_organic" => $is_organic,
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
        break;

    case 'product':
        // Get single product
        if($id) {
            // Instantiate product object
            $product = new Product($db);
            $product->id = $id;

            // Read the details of product
            if($product->readOne()) {
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
        } else {
            // Set response code - 400 bad request
            http_response_code(400);

            // Tell the user
            echo json_encode(array("message" => "Product ID is required."));
        }
        break;

    case 'categories':
        // Get all categories
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $num = $stmt->rowCount();

        // Check if any categories found
        if($num > 0) {
            // Categories array
            $categories_arr = array();
            $categories_arr["records"] = array();

            // Retrieve table contents
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $category_item = array(
                    "id" => $id,
                    "name" => $name,
                    "description" => $description
                );

                array_push($categories_arr["records"], $category_item);
            }

            // Set response code - 200 OK
            http_response_code(200);

            // Show categories data
            echo json_encode($categories_arr);
        } else {
            // Set response code - 404 Not found
            http_response_code(404);

            // Tell the user no categories found
            echo json_encode(array("message" => "No categories found."));
        }
        break;

    case 'category':
        // Get single category
        if($id) {
            $query = "SELECT * FROM categories WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Create array
                $category_arr = array(
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "description" => $row['description']
                );

                // Set response code - 200 OK
                http_response_code(200);

                // Make it json format
                echo json_encode($category_arr);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user category does not exist
                echo json_encode(array("message" => "Category does not exist."));
            }
        } else {
            // Set response code - 400 bad request
            http_response_code(400);

            // Tell the user
            echo json_encode(array("message" => "Category ID is required."));
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
