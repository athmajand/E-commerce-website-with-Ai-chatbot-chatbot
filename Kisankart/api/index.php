<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($request_uri, '/'));

// Remove the first segment (usually 'api')
if(isset($uri_segments[0]) && $uri_segments[0] == 'api') {
    array_shift($uri_segments);
}

// Route to appropriate controller based on first segment
if(isset($uri_segments[0])) {
    $controller = $uri_segments[0];

    // Special test endpoints
    if($controller == 'test') {
        include_once 'test.php';
        exit;
    }

    if($controller == 'debug') {
        include_once 'debug.php';
        exit;
    }

    try {
        switch($controller) {
            case 'auth':
                include_once 'controllers/auth_controller.php';
                break;

            case 'admin':
                include_once 'controllers/admin_controller.php';
                break;

            case 'farmer':
                include_once 'controllers/farmer_controller.php';
                break;

            case 'seller':
                include_once 'controllers/seller_controller.php';
                break;

            case 'customer':
                include_once 'controllers/customer_controller.php';
                break;

            case 'products':
            case 'product':
            case 'categories':
            case 'category':
                include_once 'controllers/product_controller.php';
                break;

            case 'users':
                include_once 'controllers/user_controller.php';
                break;

            case 'cart':
                include_once 'cart.php';
                break;

            default:
                // Set response code - 404 Not found
                http_response_code(404);

                // Tell the user
                echo json_encode(array("message" => "Endpoint not found."));
                break;
        }
    } catch (Exception $e) {
        // Set response code - 500 server error
        http_response_code(500);

        // Return error message
        echo json_encode(array(
            "status" => "error",
            "message" => "Server error occurred",
            "error" => $e->getMessage()
        ));
    }
} else {
    // API info
    $api_info = array(
        "name" => "Kisankart API",
        "version" => "1.0.0",
        "description" => "API for Kisankart - A platform connecting farmers and customers",
        "endpoints" => array(
            "/api/auth/register" => "Register a new user",
            "/api/auth/login" => "Login and get JWT token",
            "/api/users/profile" => "Get or update user profile (requires authentication)",
            "/api/users/change-password" => "Change user password (requires authentication)",
            "/api/users/addresses" => "Get or manage user addresses (requires authentication)",
            "/api/admin/*" => "Admin endpoints (requires admin role)",
            "/api/farmer/*" => "Farmer endpoints (requires farmer role)",
            "/api/seller/*" => "Seller endpoints (requires seller role)",
            "/api/seller/register" => "Register as a seller (requires authentication)",
            "/api/seller/profile" => "Get or update seller profile (requires seller role)",
            "/api/customer/*" => "Customer endpoints (requires customer role)",
            "/api/products" => "Get all products (public)",
            "/api/product?id=X" => "Get product details (public)",
            "/api/categories" => "Get all categories (public)",
            "/api/category?id=X" => "Get category details (public)",
            "/api/simple_profile.php" => "Get user profile (requires authentication)",
            "/api/simple_addresses.php" => "Get or manage user addresses (requires authentication)"
        )
    );

    // Set response code - 200 OK
    http_response_code(200);

    // Show API info
    echo json_encode($api_info);
}
?>
