<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Login Flow Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #1e8449; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
        pre { white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Login Flow Test</h1>';

// Include database and models
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/CustomerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection was successful
if (!$db) {
    echo '<div class="section">
        <h2>Database Connection</h2>
        <p class="error">Database connection failed. Please check your MySQL connection.</p>
    </div>';
    exit;
}

echo '<div class="section">
    <h2>Database Connection</h2>
    <p class="success">Database connection successful.</p>
</div>';

// Test CustomerRegistration model
echo '<div class="section">
    <h2>CustomerRegistration Model Test</h2>';

// Get the most recent customer registration for testing
try {
    $query = "SELECT * FROM customer_registrations ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $test_user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<p>Found test user: ' . $test_user['first_name'] . ' ' . $test_user['last_name'] . ' (' . $test_user['email'] . ')</p>';

        // Test email login
        $customer_registration = new CustomerRegistration($db);
        $customer_registration->email = $test_user['email'];
        $customer_registration->password = 'password123'; // This is a test password, replace with a known password

        echo '<h3>Testing Email Login</h3>';
        if ($customer_registration->loginWithEmail()) {
            echo '<p class="success">Email login successful!</p>';
            echo '<pre>';
            echo 'ID: ' . $customer_registration->id . "\n";
            echo 'Name: ' . $customer_registration->first_name . ' ' . $customer_registration->last_name . "\n";
            echo 'Email: ' . $customer_registration->email . "\n";
            echo 'Phone: ' . $customer_registration->phone . "\n";
            echo '</pre>';

            // Generate JWT token
            $jwt = generateJWT($customer_registration->id, $customer_registration->email, 'customer');
            echo '<p>Generated JWT Token:</p>';
            echo '<div class="code">' . $jwt . '</div>';
        } else {
            echo '<p class="error">Email login failed. Check if the password is correct.</p>';
        }

        // Test phone login if phone exists
        if (!empty($test_user['phone'])) {
            $customer_registration = new CustomerRegistration($db);
            $customer_registration->phone = $test_user['phone'];
            $customer_registration->password = 'password123'; // This is a test password, replace with a known password

            echo '<h3>Testing Phone Login</h3>';
            if ($customer_registration->loginWithPhone()) {
                echo '<p class="success">Phone login successful!</p>';
                echo '<pre>';
                echo 'ID: ' . $customer_registration->id . "\n";
                echo 'Name: ' . $customer_registration->first_name . ' ' . $customer_registration->last_name . "\n";
                echo 'Email: ' . $customer_registration->email . "\n";
                echo 'Phone: ' . $customer_registration->phone . "\n";
                echo '</pre>';
            } else {
                echo '<p class="error">Phone login failed. Check if the password is correct.</p>';
            }
        } else {
            echo '<p class="warning">No phone number available for testing phone login.</p>';
        }
    } else {
        echo '<p class="error">No customer registrations found for testing.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Function to generate JWT token
function generateJWT($user_id, $username, $role) {
    $secret_key = "kisan_kart_jwt_secret";
    $issuer_claim = "kisan_kart_api"; // this can be the servername
    $audience_claim = "kisan_kart_client";
    $issuedat_claim = time(); // issued at
    $notbefore_claim = $issuedat_claim; // not before
    $expire_claim = $issuedat_claim + 3600; // expire time (1 hour)

    $token = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "nbf" => $notbefore_claim,
        "exp" => $expire_claim,
        "data" => array(
            "id" => $user_id,
            "username" => $username,
            "role" => $role
        )
    );

    // Create JWT parts
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode($token));

    // Create signature
    $signature_data = $header . '.' . $payload;
    $signature = base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));

    // Combine all parts to form the JWT
    $jwt = $header . '.' . $payload . '.' . $signature;

    return $jwt;
}

echo '<div class="section">
    <h2>Next Steps</h2>
    <p>Now that you have simplified the login flow to use only the CustomerRegistration model:</p>
    <ol>
        <li>Try logging in using the regular login form</li>
        <li>Verify that the JWT token is generated correctly</li>
        <li>Check that you can access protected API endpoints</li>
    </ol>
    <p><a href="login.php"><button style="background: #1e8449; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Go to Login Page</button></a></p>
</div>';

echo '</body>
</html>';
?>
