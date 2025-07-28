<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers
header("Content-Type: application/json; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Direct database connection
    $host = "localhost";
    $db_name = "kisan_kart";
    $username = "root";
    $password = "";

    // Try to connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        case 'price_asc':
            $query .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY p.price DESC";
            break;
        case 'name_asc':
            $query .= " ORDER BY p.name ASC";
            break;
        case 'name_desc':
            $query .= " ORDER BY p.name DESC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY p.id DESC";
            break;
    }

    // Add pagination
    $query .= " LIMIT :offset, :limit";

    // Prepare statement
    $stmt = $conn->prepare($query);

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

    $products = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Check if image_url exists and is valid
        if (isset($row['image_url']) && !empty($row['image_url'])) {
            // Make sure the image_url is properly formatted
            if (!strstr($row['image_url'], 'uploads/products/')) {
                // If it doesn't have the proper path, extract the filename and update it
                $filename = basename($row['image_url']);
                $row['image_url'] = 'uploads/products/' . $filename;
            }

            // Verify the file exists
            $image_path = __DIR__ . '/' . $row['image_url'];
            $image_exists = file_exists($image_path);

            // Add debug info
            $row['_debug_image_path'] = $image_path;
            $row['_debug_image_exists'] = $image_exists ? 'yes' : 'no';
        }

        $products[] = $row;
    }

    // Get total count of products for pagination
    $count_query = "SELECT COUNT(*) as total FROM products p WHERE 1=1";

    // Add the same filters to the count query
    if (!empty($search)) {
        $count_query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    }

    if ($category_id > 0) {
        $count_query .= " AND p.category_id = :category_id";
    }

    if ($min_price > 0) {
        $count_query .= " AND p.price >= :min_price";
    }

    if ($max_price > 0) {
        $count_query .= " AND p.price <= :max_price";
    }

    $count_stmt = $conn->prepare($count_query);

    // Bind parameters to count query
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

    $count_stmt->execute();
    $row = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_products = $row['total'];
    $total_pages = ceil($total_products / $limit);

    // Return response with pagination info
    echo json_encode(array(
        "success" => true,
        "products" => $products,
        "total_products" => $total_products,
        "total_pages" => $total_pages,
        "current_page" => $page,
        "message" => "Products retrieved successfully"
    ));

} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Database error: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ));
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ));
}
?>
