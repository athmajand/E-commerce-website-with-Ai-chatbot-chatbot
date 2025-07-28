<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration and models
include_once 'api/config/database.php';
include_once 'api/models/CustomerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create a test user
try {
    // Instantiate CustomerRegistration object
    $customer_registration = new CustomerRegistration($db);
    
    // Set test user data
    $customer_registration->first_name = "Test";
    $customer_registration->last_name = "User";
    $customer_registration->email = "testuser" . rand(1000, 9999) . "@example.com";
    $customer_registration->phone = "9" . rand(100000000, 999999999);
    $customer_registration->password = "password123";
    $customer_registration->address = "123 Test Street";
    $customer_registration->city = "Test City";
    $customer_registration->state = "Test State";
    $customer_registration->postal_code = "123456";
    $customer_registration->status = "approved";
    $customer_registration->is_verified = 1;
    
    // Create the test user
    $result = $customer_registration->create();
    
    if ($result) {
        echo "<h2>Test User Created Successfully</h2>";
        echo "<p>A test user has been created with the following details:</p>";
        echo "<ul>";
        echo "<li><strong>Name:</strong> " . $customer_registration->first_name . " " . $customer_registration->last_name . "</li>";
        echo "<li><strong>Email:</strong> " . $customer_registration->email . "</li>";
        echo "<li><strong>Phone:</strong> " . $customer_registration->phone . "</li>";
        echo "<li><strong>Password:</strong> password123</li>";
        echo "</ul>";
        
        echo "<p>You can now test the login functionality using these credentials.</p>";
        
        echo "<div>";
        echo "<a href='login.php' class='btn btn-primary'>Go to Login Page</a> ";
        echo "<a href='test_customer_registration_login.php' class='btn btn-success'>Run Login Tests</a>";
        echo "</div>";
    } else {
        echo "<h2>Error Creating Test User</h2>";
        echo "<p>There was an error creating the test user. Please check the logs for more information.</p>";
    }
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>Exception: " . $e->getMessage() . "</p>";
}
?>
