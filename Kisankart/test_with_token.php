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
echo "<h1>API Test Results with Token</h1>";

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

// Step 5: Test addresses endpoint again to see the new address
echo "<h2>Step 5: Test addresses endpoint again to see the new address</h2>";
$addresses_result2 = makeRequest($base_url . '/simple_addresses.php', 'GET', null, $token);
echo "URL: " . $base_url . '/simple_addresses.php' . "<br>";
echo "Status: " . $addresses_result2['status'] . "<br>";
echo "Response: <pre>" . print_r($addresses_result2['response'], true) . "</pre>";

// Step 6: Test seller registration
echo "<h2>Step 6: Test seller registration</h2>";
$seller_data = [
    'business_name' => 'Test Seller Business',
    'business_description' => 'This is a test seller business',
    'business_address' => '456 Seller St, Bangalore, Karnataka',
    'gst_number' => 'GST123456789',
    'pan_number' => 'PAN123456789'
];
$seller_result = makeRequest($base_url . '/seller/register.php', 'POST', $seller_data, $token);
echo "URL: " . $base_url . '/seller/register.php' . "<br>";
echo "Status: " . $seller_result['status'] . "<br>";
echo "Response: <pre>" . print_r($seller_result['response'], true) . "</pre>";
?>
