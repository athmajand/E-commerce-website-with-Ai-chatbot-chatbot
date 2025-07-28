<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$response = array();

// Add session debug info
$response['session_status'] = session_status();
$response['session_id'] = session_id();
$response['session_data'] = $_SESSION;

// Check if user is logged in (using customer_id or user_id)
if ((isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) ||
    (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']))) {

    // User is logged in
    $response['status'] = 'success';
    $response['logged_in'] = true;

    // Use customer_id if available, otherwise use user_id
    $customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : $_SESSION['user_id'];
    $response['customer_id'] = $customer_id;
    $response['user_id'] = $customer_id; // For backward compatibility

    $response['role'] = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $response['first_name'] = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
    $response['last_name'] = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
    $response['email'] = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    $response['message'] = 'User is logged in';
} else {
    // User is not logged in
    $response['status'] = 'error';
    $response['logged_in'] = false;
    $response['message'] = 'User is not logged in';
}

// Return response
echo json_encode($response);
