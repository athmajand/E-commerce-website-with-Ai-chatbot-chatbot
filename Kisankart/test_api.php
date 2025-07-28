<?php
// Turn off all error reporting for this test file
error_reporting(0);
ini_set('display_errors', 0);

// Set proper JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Create a simple JSON response
$response = array(
    "status" => "success",
    "message" => "This is a test JSON response",
    "timestamp" => time()
);

// Output as JSON
echo json_encode($response);
?>
