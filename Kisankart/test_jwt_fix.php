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
echo "<h1>JWT Fix Test Results</h1>";

// Step 1: Login to get token
echo "<h2>Step 1: Login to get token</h2>";
$login_result = makeRequest($base_url . '/auth/login.php', 'POST', $credentials);
echo "Status: " . $login_result['status'] . "<br>";

// If login successful, get token
$token = null;
if ($login_result['status'] == 200 && isset($login_result['response']['jwt'])) {
    $token = $login_result['response']['jwt'];
    echo "Token obtained successfully: " . $token . "<br>";

    // Display token parts
    $token_parts = explode('.', $token);
    if (count($token_parts) == 3) {
        echo "<h3>Token Structure (3 parts as expected)</h3>";
        echo "Header: " . $token_parts[0] . "<br>";
        echo "Payload: " . $token_parts[1] . "<br>";
        echo "Signature: " . $token_parts[2] . "<br>";

        // Decode header and payload
        echo "<h3>Decoded Token Parts</h3>";
        echo "Header: <pre>" . print_r(json_decode(base64_decode($token_parts[0]), true), true) . "</pre>";
        echo "Payload: <pre>" . print_r(json_decode(base64_decode($token_parts[1]), true), true) . "</pre>";
    } else {
        echo "<p style='color: red;'>Token does not have the expected 3-part structure!</p>";
    }
} else {
    echo "Failed to obtain token.<br>";
    echo "Response: <pre>" . print_r($login_result['response'], true) . "</pre>";
    exit;
}

// Step 2: Test profile endpoint with token
echo "<h2>Step 2: Test profile endpoint with token</h2>";
$profile_result = makeRequest($base_url . '/simple_profile.php', 'GET', null, $token);
echo "URL: " . $base_url . '/simple_profile.php' . "<br>";
echo "Status: " . $profile_result['status'] . "<br>";
echo "Response: <pre>" . print_r($profile_result['response'], true) . "</pre>";

// Step 3: Test addresses endpoint with token
echo "<h2>Step 3: Test addresses endpoint with token</h2>";
$addresses_result = makeRequest($base_url . '/simple_addresses.php', 'GET', null, $token);
echo "URL: " . $base_url . '/simple_addresses.php' . "<br>";
echo "Status: " . $addresses_result['status'] . "<br>";
echo "Response: <pre>" . print_r($addresses_result['response'], true) . "</pre>";

// Step 4: Test creating an address
echo "<h2>Step 4: Test creating an address</h2>";
$new_address = [
    'name' => 'Home',
    'phone' => '9876543210',
    'street' => '123 Main St',
    'city' => 'Bangalore',
    'state' => 'Karnataka',
    'postalCode' => '560001',
    'isDefault' => true
];
$create_address_result = makeRequest($base_url . '/simple_addresses.php', 'POST', $new_address, $token);
echo "URL: " . $base_url . '/simple_addresses.php' . "<br>";
echo "Status: " . $create_address_result['status'] . "<br>";
echo "Response: <pre>" . print_r($create_address_result['response'], true) . "</pre>";
?>
