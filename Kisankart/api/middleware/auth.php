<?php
// Function to verify JWT token
function verifyToken($return_error_message = false) {
    // Get HTTP Authorization header
    $headers = getallheaders();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    // Check if Authorization header exists and starts with 'Bearer '
    if (!$auth_header) {
        return $return_error_message ? array('error' => 'Authorization header is missing') : false;
    }

    if (strpos($auth_header, 'Bearer ') !== 0) {
        return $return_error_message ? array('error' => 'Authorization header must start with Bearer') : false;
    }

    // Extract token from header
    $token = substr($auth_header, 7);

    // Split token into parts
    $token_parts = explode('.', $token);

    // Check if token has three parts (header, payload, signature)
    if (count($token_parts) != 3) {
        return $return_error_message ? array('error' => 'Invalid token format. Token must have three parts') : false;
    }

    // Extract parts
    $header = $token_parts[0];
    $payload = $token_parts[1];
    $signature = $token_parts[2];

    // Decode payload
    $decoded_payload = json_decode(base64_decode($payload), true);

    // Check if payload is valid
    if (!$decoded_payload) {
        return $return_error_message ? array('error' => 'Invalid token payload') : false;
    }

    // Check if token has expired
    if (isset($decoded_payload['exp']) && $decoded_payload['exp'] < time()) {
        return $return_error_message ? array('error' => 'Token has expired') : false;
    }

    // Verify signature
    $secret_key = "kisan_kart_jwt_secret";
    $signature_data = $header . '.' . $payload;
    $expected_signature = base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));

    // If signatures don't match, token is invalid
    if ($signature !== $expected_signature) {
        return $return_error_message ? array('error' => 'Invalid token signature') : false;
    }

    // Check if user data exists in token
    if (!isset($decoded_payload['data']) || !isset($decoded_payload['data']['id'])) {
        return $return_error_message ? array('error' => 'Token missing user data') : false;
    }

    // Return user data from token
    return $decoded_payload['data'];
}
?>
