<?php
/**
 * Header Helper Functions for Kisankart
 *
 * These functions help set proper HTTP headers
 */

/**
 * Set proper HTML content type headers
 *
 * @param string $charset Character set (default: utf-8)
 * @return void
 */
function set_html_headers($charset = 'utf-8') {
    // Set content type header for HTML
    header("Content-Type: text/html; charset={$charset}");

    // Set cache control headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Set security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
}

/**
 * Set proper JSON content type headers
 *
 * @param string $charset Character set (default: utf-8)
 * @param bool $allow_cors Whether to allow CORS (default: true)
 * @return void
 */
function set_json_headers($charset = 'utf-8', $allow_cors = true) {
    // Set content type header for JSON
    header("Content-Type: application/json; charset={$charset}");

    // Set CORS headers if allowed
    if ($allow_cors) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    }

    // Set cache control headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Set security headers
    header("X-Content-Type-Options: nosniff");
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
 * Set headers for static assets with proper caching
 *
 * @param string $content_type Content type of the asset
 * @param int $max_age Maximum age in seconds (default: 604800 = 1 week)
 * @return void
 */
function set_asset_headers($content_type, $max_age = 604800) {
    header("Content-Type: {$content_type}");
    header("Cache-Control: public, max-age={$max_age}");
    header("Pragma: public");

    // Set expiration date
    $expiration = gmdate("D, d M Y H:i:s", time() + $max_age) . " GMT";
    header("Expires: {$expiration}");
}
?>
