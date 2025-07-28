<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to make API request
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => $method,
            'ignore_errors' => true
        ]
    ];
    
    // Add token to header if provided
    if ($token) {
        $options['http']['header'] .= "Authorization: Bearer " . $token . "\r\n";
    }
    
    // Add data if provided
    if ($data) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    // Get status code
    $status = 0;
    if (isset($http_response_header[0])) {
        preg_match('/\d{3}/', $http_response_header[0], $matches);
        $status = intval($matches[0]);
    }
    
    return [
        'status' => $status,
        'response' => $response ? json_decode($response, true) : null,
        'raw_response' => $response,
        'error' => ''
    ];
}

// Base URL
$base_url = 'http://localhost:8080/Kisankart/api';

// Test credentials
$credentials = [
    'email' => 'admin@kisankart.com',
    'password' => 'admin123'
];

// Run tests
echo "<h1>API Error Handling Test</h1>";

// Step 1: Login to get token
echo "<h2>Step 1: Login to get token</h2>";
$login_result = makeRequest($base_url . '/auth/login.php', 'POST', $credentials);
echo "Status: " . $login_result['status'] . "<br>";

// If login successful, get token
$token = null;
if ($login_result['status'] == 200 && isset($login_result['response']['jwt'])) {
    $token = $login_result['response']['jwt'];
    echo "Token obtained successfully: " . $token . "<br>";
} else {
    echo "Failed to obtain token.<br>";
    echo "Response: <pre>" . print_r($login_result['response'], true) . "</pre>";
    exit;
}

// Test 1: No token
echo "<h2>Test 1: No token</h2>";
$no_token_result = makeRequest($base_url . '/simple_profile.php', 'GET');
echo "URL: " . $base_url . '/simple_profile.php' . "<br>";
echo "Status: " . $no_token_result['status'] . "<br>";
echo "Response: <pre>" . print_r($no_token_result['response'], true) . "</pre>";

// Test 2: Invalid token format
echo "<h2>Test 2: Invalid token format</h2>";
$invalid_token_result = makeRequest($base_url . '/simple_profile.php', 'GET', null, "invalid_token");
echo "URL: " . $base_url . '/simple_profile.php' . "<br>";
echo "Status: " . $invalid_token_result['status'] . "<br>";
echo "Response: <pre>" . print_r($invalid_token_result['response'], true) . "</pre>";

// Test 3: Expired token (simulate by modifying the token)
echo "<h2>Test 3: Expired token (simulated)</h2>";
$token_parts = explode('.', $token);
if (count($token_parts) == 3) {
    $payload = json_decode(base64_decode($token_parts[1]), true);
    $payload['exp'] = time() - 3600; // Set expiration to 1 hour ago
    $token_parts[1] = base64_encode(json_encode($payload));
    $expired_token = implode('.', $token_parts);
    
    $expired_token_result = makeRequest($base_url . '/simple_profile.php', 'GET', null, $expired_token);
    echo "URL: " . $base_url . '/simple_profile.php' . "<br>";
    echo "Status: " . $expired_token_result['status'] . "<br>";
    echo "Response: <pre>" . print_r($expired_token_result['response'], true) . "</pre>";
} else {
    echo "Could not modify token for expiration test.<br>";
}

// Test 4: Valid token
echo "<h2>Test 4: Valid token</h2>";
$valid_token_result = makeRequest($base_url . '/simple_profile.php', 'GET', null, $token);
echo "URL: " . $base_url . '/simple_profile.php' . "<br>";
echo "Status: " . $valid_token_result['status'] . "<br>";
echo "Response: <pre>" . print_r($valid_token_result['response'], true) . "</pre>";
?>
