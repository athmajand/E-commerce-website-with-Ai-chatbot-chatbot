<?php
class Auth {
    private $conn;
    private $table_name = "customer_registrations";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validateToken() {
        // Get HTTP Authorization header
        $headers = getallheaders();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        // Check if Authorization header exists and starts with 'Bearer '
        if (!$auth_header) {
            return false;
        }

        if (strpos($auth_header, 'Bearer ') !== 0) {
            return false;
        }

        // Extract token from header
        $token = substr($auth_header, 7);

        // Split token into parts
        $token_parts = explode('.', $token);

        // Check if token has three parts (header, payload, signature)
        if (count($token_parts) != 3) {
            return false;
        }

        // Extract parts
        $header = $token_parts[0];
        $payload = $token_parts[1];
        $signature = $token_parts[2];

        // Decode payload
        $decoded_payload = json_decode(base64_decode($payload), true);

        // Check if payload is valid
        if (!$decoded_payload) {
            return false;
        }

        // Check if token has expired
        if (isset($decoded_payload['exp']) && $decoded_payload['exp'] < time()) {
            return false;
        }

        // Verify signature
        $secret_key = "kisan_kart_jwt_secret";
        $signature_data = $header . '.' . $payload;
        $expected_signature = base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));

        // If signatures don't match, token is invalid
        if ($signature !== $expected_signature) {
            return false;
        }

        // Check if user data exists in token
        if (!isset($decoded_payload['data']) || !isset($decoded_payload['data']['id'])) {
            return false;
        }

        // Verify user exists in database
        $user_id = $decoded_payload['data']['id'];
        $query = "SELECT id, first_name, last_name, email FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return array_merge($decoded_payload['data'], $user);
        }

        return false;
    }

    public function generateToken($user_data) {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        
        $payload_data = [
            'data' => $user_data,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ];
        
        $payload = base64_encode(json_encode($payload_data));
        
        $secret_key = "kisan_kart_jwt_secret";
        $signature_data = $header . '.' . $payload;
        $signature = base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));
        
        return $header . '.' . $payload . '.' . $signature;
    }
}
?> 