<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database configuration
$database_file = __DIR__ . '/api/config/database.php';

// Check if file exists
if (!file_exists($database_file)) {
    // Try alternative path
    $database_file = 'api/config/database.php';

    if (!file_exists($database_file)) {
        http_response_code(500);
        echo json_encode(array(
            "message" => "Database configuration file not found.",
            "paths_checked" => [
                __DIR__ . '/api/config/database.php',
                'api/config/database.php'
            ]
        ));
        exit;
    }
}

// Include the database file
include_once $database_file;

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if database connection was successful
if (!$db) {
    // Return error response
    http_response_code(500);
    echo json_encode(array("message" => "Database connection failed."));
    exit;
}

// Log the successful connection
error_log("Database connection successful in get_products.php");

// Get request parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 9; // Default 9 products per page
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest';

// Calculate offset for pagination
$offset = ($page - 1) * $limit;

// Build the base query
$query = "SELECT p.*, c.name as category_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE 1=1";

// Add search condition if provided
if (!empty($search)) {
    $search = "%{$search}%";
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
}

// Add category filter if provided
if ($category_id > 0) {
    $query .= " AND p.category_id = :category_id";
}

// Add price range filter if provided
if ($min_price > 0) {
    $query .= " AND p.price >= :min_price";
}

if ($max_price > 0) {
    $query .= " AND p.price <= :max_price";
}

// Add sorting
switch ($sort_by) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY p.created_at ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

// Count total products (for pagination)
$count_query = str_replace("SELECT p.*, c.name as category_name", "SELECT COUNT(*) as total", $query);
$count_query = preg_replace('/ORDER BY.*$/', '', $count_query);

// Prepare count statement
$count_stmt = $db->prepare($count_query);

// Bind parameters for count query
if (!empty($search)) {
    $count_stmt->bindParam(':search', $search);
}

if ($category_id > 0) {
    $count_stmt->bindParam(':category_id', $category_id);
}

if ($min_price > 0) {
    $count_stmt->bindParam(':min_price', $min_price);
}

if ($max_price > 0) {
    $count_stmt->bindParam(':max_price', $max_price);
}

// Execute count query
$count_stmt->execute();
$row = $count_stmt->fetch(PDO::FETCH_ASSOC);
$total_products = $row['total'];
$total_pages = ceil($total_products / $limit);

// Add pagination to the main query
$query .= " LIMIT :offset, :limit";

// Prepare statement
$stmt = $db->prepare($query);

// Bind parameters
if (!empty($search)) {
    $stmt->bindParam(':search', $search);
}

if ($category_id > 0) {
    $stmt->bindParam(':category_id', $category_id);
}

if ($min_price > 0) {
    $stmt->bindParam(':min_price', $min_price);
}

if ($max_price > 0) {
    $stmt->bindParam(':max_price', $max_price);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

// Execute query
$stmt->execute();

// Check if any products found
$num = $stmt->rowCount();

if ($num > 0) {
    // Products array
    $products_arr = array();
    $products_arr["products"] = array();
    $products_arr["total_pages"] = $total_pages;
    $products_arr["current_page"] = $page;
    $products_arr["total_products"] = $total_products;

    // Retrieve table contents
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Extract row
        extract($row);

        $product_item = array(
            "id" => $id,
            "name" => $name,
            "description" => $description,
            "price" => $price,
            "discount_price" => $discount_price,
            "category_id" => $category_id,
            "category_name" => $category_name,
            "seller_id" => $seller_id,
            "stock_quantity" => $stock_quantity,
            "image_url" => $image_url,
            "is_featured" => $is_featured,
            "status" => $status,
            "created_at" => $created_at
        );

        // Push to "products" array
        array_push($products_arr["products"], $product_item);
    }

    // Set response code - 200 OK
    http_response_code(200);

    // Return products data
    echo json_encode($products_arr);
} else {
    // No products found
    // Set response code - 200 OK (still a valid response)
    http_response_code(200);

    // Return empty products array with pagination info
    echo json_encode(array(
        "products" => array(),
        "total_pages" => 0,
        "current_page" => $page,
        "total_products" => 0,
        "message" => "No products found."
    ));
}
?>
