<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Generate a unique email and phone
$email = "testuser" . rand(1000, 9999) . "@example.com";
$phone = "9" . rand(100000000, 999999999);
$password = "password123";

// Hash the password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert the test user directly into the database
try {
    // Begin transaction
    $db->beginTransaction();
    
    // Insert query
    $query = "INSERT INTO customer_registrations 
              (first_name, last_name, email, phone, password, address, city, state, postal_code, status, is_verified) 
              VALUES 
              (:first_name, :last_name, :email, :phone, :password, :address, :city, :state, :postal_code, :status, :is_verified)";
    
    // Prepare query
    $stmt = $db->prepare($query);
    
    // Bind values
    $first_name = "Test";
    $last_name = "User";
    $address = "123 Test Street";
    $city = "Test City";
    $state = "Test State";
    $postal_code = "123456";
    $status = "approved";
    $is_verified = 1;
    
    $stmt->bindParam(":first_name", $first_name);
    $stmt->bindParam(":last_name", $last_name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":password", $password_hash);
    $stmt->bindParam(":address", $address);
    $stmt->bindParam(":city", $city);
    $stmt->bindParam(":state", $state);
    $stmt->bindParam(":postal_code", $postal_code);
    $stmt->bindParam(":status", $status);
    $stmt->bindParam(":is_verified", $is_verified);
    
    // Execute query
    $result = $stmt->execute();
    
    if ($result) {
        $user_id = $db->lastInsertId();
        
        // Commit transaction
        $db->commit();
        
        echo "<h2>Test User Created Successfully</h2>";
        echo "<p>A verified and approved test user has been created with the following details:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $user_id . "</li>";
        echo "<li><strong>Name:</strong> " . $first_name . " " . $last_name . "</li>";
        echo "<li><strong>Email:</strong> " . $email . "</li>";
        echo "<li><strong>Phone:</strong> " . $phone . "</li>";
        echo "<li><strong>Password:</strong> " . $password . "</li>";
        echo "<li><strong>Password Hash:</strong> " . $password_hash . "</li>";
        echo "<li><strong>Status:</strong> " . $status . "</li>";
        echo "<li><strong>Is Verified:</strong> " . ($is_verified ? "Yes" : "No") . "</li>";
        echo "</ul>";
        
        echo "<p>You can now test the login functionality using these credentials.</p>";
        
        echo "<div>";
        echo "<a href='login.php' class='btn btn-primary'>Go to Login Page</a> ";
        echo "<a href='debug_login.php?email=" . urlencode($email) . "&password=" . urlencode($password) . "' class='btn btn-success'>Debug Login</a>";
        echo "</div>";
    } else {
        // Rollback transaction
        $db->rollBack();
        
        echo "<h2>Error Creating Test User</h2>";
        echo "<p>There was an error creating the test user. Please check the logs for more information.</p>";
        echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
    }
} catch (PDOException $e) {
    // Rollback transaction
    $db->rollBack();
    
    echo "<h2>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
