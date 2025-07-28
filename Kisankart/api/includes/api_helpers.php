<?php
/**
 * API Helper Functions for Kisan Kart
 * 
 * These functions help with API responses and headers
 */

/**
 * Set proper API headers
 * 
 * @param string $charset Character set (default: utf-8)
 * @return void
 */
function set_api_headers($charset = 'utf-8') {
    // Set content type header for JSON
    header("Content-Type: application/json; charset={$charset}");
    
    // Set CORS headers
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    // Set cache control headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

/**
 * Handle preflight OPTIONS requests for API endpoints
 * 
 * @return bool True if this was an OPTIONS request and it was handled
 */
function handle_preflight_request() {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit;
        return true;
    }
    return false;
}

/**
 * Send a JSON response
 * 
 * @param mixed $data The data to send
 * @param int $status_code HTTP status code (default: 200)
 * @param string $charset Character set (default: utf-8)
 * @return void
 */
function send_json_response($data, $status_code = 200, $charset = 'utf-8') {
    // Set headers
    set_api_headers($charset);
    
    // Set HTTP status code
    http_response_code($status_code);
    
    // Output JSON
    echo json_encode($data);
    exit;
}

/**
 * Send a success response
 * 
 * @param mixed $data The data to send
 * @param string $message Success message (default: 'Success')
 * @param int $status_code HTTP status code (default: 200)
 * @return void
 */
function send_success_response($data = null, $message = 'Success', $status_code = 200) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    send_json_response($response, $status_code);
}

/**
 * Send an error response
 * 
 * @param string $message Error message
 * @param int $status_code HTTP status code (default: 400)
 * @param mixed $errors Additional error details (optional)
 * @return void
 */
function send_error_response($message, $status_code = 400, $errors = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    
    send_json_response($response, $status_code);
}
?>
