<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include database configuration
    $database_file = __DIR__ . '/api/config/database.php';
    if (!file_exists($database_file)) {
        throw new Exception("Database file not found at: " . $database_file);
    }
    
    include_once $database_file;
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Simple query to get all products
    $query = "SELECT * FROM products LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $products = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = $row;
    }
    
    // Return response
    echo json_encode(array(
        "success" => true,
        "products" => $products,
        "total_products" => count($products),
        "message" => "Products retrieved successfully"
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
