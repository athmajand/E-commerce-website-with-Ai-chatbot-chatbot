<?php
class AuthMiddleware {
    private $secret_key = "kisan_kart_jwt_secret";

    // Validate JWT token
    public function validateToken($return_error_message = false) {
        // Get all headers
        $headers = getallheaders();

        // Check if Authorization header exists
        if(!isset($headers['Authorization'])) {
            return $return_error_message ? array('error' => 'Authorization header is missing') : false;
        }

        // Get the token
        $authHeader = $headers['Authorization'];
        $arr = explode(" ", $authHeader);

        // Check if token format is valid
        if(count($arr) != 2) {
            return $return_error_message ? array('error' => 'Invalid Authorization header format') : false;
        }

        if($arr[0] != 'Bearer') {
            return $return_error_message ? array('error' => 'Authorization header must start with Bearer') : false;
        }

        $jwt = $arr[1];

        // Split token into parts
        $token_parts = explode('.', $jwt);

        // Check if token has three parts (header, payload, signature)
        if (count($token_parts) != 3) {
            return $return_error_message ? array('error' => 'Invalid token format. Token must have three parts') : false;
        }

        // Extract parts
        $header = $token_parts[0];
        $payload = $token_parts[1];
        $signature = $token_parts[2];

        // Decode payload
        try {
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
            $signature_data = $header . '.' . $payload;
            $expected_signature = base64_encode(hash_hmac('sha256', $signature_data, $this->secret_key, true));

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
        } catch(Exception $e) {
            return $return_error_message ? array('error' => 'Exception: ' . $e->getMessage()) : false;
        }
    }

    // Check if user has admin role
    public function isAdmin($return_error_message = false) {
        $auth_data = $this->validateToken($return_error_message);

        if(isset($auth_data['error'])) {
            return $return_error_message ? $auth_data : false;
        }

        if($auth_data['role'] !== 'admin') {
            return $return_error_message ? array('error' => 'User is not an admin') : false;
        }

        return true;
    }

    // Check if user has farmer role
    public function isFarmer($return_error_message = false) {
        $auth_data = $this->validateToken($return_error_message);

        if(isset($auth_data['error'])) {
            return $return_error_message ? $auth_data : false;
        }

        if($auth_data['role'] !== 'farmer') {
            return $return_error_message ? array('error' => 'User is not a farmer') : false;
        }

        return true;
    }

    // Check if user has customer role
    public function isCustomer($return_error_message = false) {
        $auth_data = $this->validateToken($return_error_message);

        if(isset($auth_data['error'])) {
            return $return_error_message ? $auth_data : false;
        }

        if($auth_data['role'] !== 'customer') {
            return $return_error_message ? array('error' => 'User is not a customer') : false;
        }

        return true;
    }

    // Check if user has seller role
    public function isSeller($return_error_message = false) {
        $auth_data = $this->validateToken($return_error_message);

        if(isset($auth_data['error'])) {
            return $return_error_message ? $auth_data : false;
        }

        if($auth_data['role'] !== 'seller') {
            return $return_error_message ? array('error' => 'User is not a seller') : false;
        }

        return true;
    }

    // Get user ID from token
    public function getUserId($return_error_message = false) {
        $auth_data = $this->validateToken($return_error_message);

        if(isset($auth_data['error'])) {
            return $return_error_message ? $auth_data : null;
        }

        return $auth_data['id'];
    }
}
?>
