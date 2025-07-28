<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get product ID and quantity from POST request
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);

if ($is_logged_in) {
    // Customer is logged in, redirect to delivery address page
    header("Location: delivery_address.php?product_id=$product_id&quantity=$quantity");
    exit;
} else {
    // Check if it's an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    } else {
        // Customer is not logged in, redirect to login page with a message
        header("Location: ../login.php?redirect=frontend/products.php&message=Please+log+in+to+continue");
        exit;
    }
}
