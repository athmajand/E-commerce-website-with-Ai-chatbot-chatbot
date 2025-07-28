<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: test_buy_now.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cart_id = intval($_POST['cart_id']);
    
    try {
        // Check if cart item belongs to the user
        $check_query = "SELECT id FROM cart WHERE id = ? AND customer_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $cart_id);
        $check_stmt->bindParam(2, $_SESSION['user_id']);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Delete cart item
            $delete_query = "DELETE FROM cart WHERE id = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindParam(1, $cart_id);
            $delete_stmt->execute();
            
            header("Location: test_add_to_cart.php");
            exit;
        } else {
            echo "<p>Invalid cart item or you don't have permission to remove it.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    header("Location: test_add_to_cart.php");
    exit;
}
?>
