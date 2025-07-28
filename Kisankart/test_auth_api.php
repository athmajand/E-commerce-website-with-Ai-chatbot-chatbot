<?php
// Test file to check if the auth API is working correctly

// Set up cURL request
$url = 'http://localhost:8080/Kisankart/api/auth/login.php';
$data = array(
    'username' => 'admin',
    'password' => 'admin123'
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
));
curl_setopt($ch, CURLOPT_POST, true);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Output results
echo "<h1>API Test Results</h1>";
echo "<h2>Request</h2>";
echo "<p>URL: $url</p>";
echo "<p>Data: " . json_encode($data) . "</p>";

echo "<h2>Response</h2>";
echo "<p>HTTP Code: $httpCode</p>";

if ($error) {
    echo "<p>Error: $error</p>";
} else {
    echo "<p>Response Body:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";

    // Try to decode JSON response
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<h2>Decoded Response</h2>";
        echo "<pre>" . print_r($decoded, true) . "</pre>";
    }
}
?>
