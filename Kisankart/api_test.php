<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to make API request
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set method
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($method != 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Set data if provided
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    // Set headers
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'status' => $http_code,
        'response' => $response ? json_decode($response, true) : null,
        'raw_response' => $response,
        'error' => $error
    ];
}

// Base URL
$base_url = 'http://localhost:8080/Kisankart/api';

// Test endpoints
$endpoints = [
    'Root' => '/',
    'Auth Login' => '/auth/login.php',
    'Simple Profile' => '/simple_profile.php',
    'Simple Addresses' => '/simple_addresses.php',
    'Debug' => '/debug.php'
];

// Test credentials
$credentials = [
    'email' => 'admin@kisankart.com',
    'password' => 'admin123'
];

// Run tests
echo "<h1>API Test Results</h1>";

// Test login first to get token
echo "<h2>Testing Login</h2>";
$login_result = makeRequest($base_url . '/auth/login.php', 'POST', $credentials);
echo "Status: " . $login_result['status'] . "<br>";
echo "Response: <pre>" . print_r($login_result['response'], true) . "</pre>";
echo "Raw Response: <pre>" . htmlspecialchars($login_result['raw_response']) . "</pre>";

// If login successful, get token
$token = null;
if ($login_result['status'] == 200 && isset($login_result['response']['jwt'])) {
    $token = $login_result['response']['jwt'];
    echo "Token obtained successfully.<br>";
} else {
    echo "Failed to obtain token.<br>";
}

// Test other endpoints
foreach ($endpoints as $name => $endpoint) {
    echo "<h2>Testing $name</h2>";
    $result = makeRequest($base_url . $endpoint, 'GET', null, $token);
    echo "URL: " . $base_url . $endpoint . "<br>";
    echo "Status: " . $result['status'] . "<br>";
    echo "Response: <pre>" . print_r($result['response'], true) . "</pre>";
    echo "Raw Response: <pre>" . htmlspecialchars($result['raw_response']) . "</pre>";
    if ($result['error']) {
        echo "Error: " . $result['error'] . "<br>";
    }
    echo "<hr>";
}
?>
