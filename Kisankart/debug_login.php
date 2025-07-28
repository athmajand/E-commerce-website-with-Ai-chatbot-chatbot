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

// Get the test user's email from the URL parameter
$email = isset($_GET['email']) ? $_GET['email'] : 'testuser6156@example.com';
$password = isset($_GET['password']) ? $_GET['password'] : 'password123';

// Debug the login process
try {
    echo "<h2>Login Debug</h2>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    
    // Step 1: Check if the user exists
    $query = "SELECT * FROM customer_registrations WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>User Found</h3>";
        echo "<p><strong>ID:</strong> " . $row['id'] . "</p>";
        echo "<p><strong>Name:</strong> " . $row['first_name'] . " " . $row['last_name'] . "</p>";
        echo "<p><strong>Email:</strong> " . $row['email'] . "</p>";
        echo "<p><strong>Phone:</strong> " . $row['phone'] . "</p>";
        echo "<p><strong>Status:</strong> " . $row['status'] . "</p>";
        echo "<p><strong>Is Verified:</strong> " . ($row['is_verified'] ? "Yes" : "No") . "</p>";
        
        // Step 2: Check if the password field exists and has a value
        if (isset($row['password'])) {
            echo "<p><strong>Password Hash:</strong> " . (empty($row['password']) ? "EMPTY" : $row['password']) . "</p>";
            
            // Step 3: Test password verification
            $password_verified = password_verify($password, $row['password']);
            echo "<p><strong>Password Verification Result:</strong> " . ($password_verified ? "SUCCESS" : "FAILED") . "</p>";
            
            // If verification failed, let's check the hash algorithm
            if (!$password_verified) {
                echo "<p><strong>Hash Info:</strong> " . print_r(password_get_info($row['password']), true) . "</p>";
                
                // Try creating a new hash with the same password
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                echo "<p><strong>New Hash with Same Password:</strong> " . $new_hash . "</p>";
                echo "<p><strong>New Hash Verification:</strong> " . (password_verify($password, $new_hash) ? "SUCCESS" : "FAILED") . "</p>";
            }
        } else {
            echo "<p style='color:red;'><strong>Error:</strong> Password field does not exist in the database!</p>";
        }
        
        // Step 4: Check if the user meets the login criteria
        $meets_criteria = ($row['is_verified'] == 1 && $row['status'] == 'approved');
        echo "<p><strong>Meets Login Criteria:</strong> " . ($meets_criteria ? "Yes" : "No") . "</p>";
        
        // Step 5: Try to login using the CustomerRegistration class
        $customer_registration = new CustomerRegistration($db);
        $customer_registration->email = $email;
        $customer_registration->password = $password;
        
        $login_result = $customer_registration->loginWithEmail();
        echo "<p><strong>Login Result:</strong> " . ($login_result ? "SUCCESS" : "FAILED") . "</p>";
        
        if ($login_result) {
            echo "<h3>Login Successful</h3>";
            echo "<p>User ID: " . $customer_registration->id . "</p>";
            echo "<p>Name: " . $customer_registration->first_name . " " . $customer_registration->last_name . "</p>";
        } else {
            echo "<h3>Login Failed</h3>";
            echo "<p>Please check the following:</p>";
            echo "<ul>";
            echo "<li>Is the password correct?</li>";
            echo "<li>Is the user verified? (is_verified = 1)</li>";
            echo "<li>Is the user status 'approved'?</li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color:red;'><strong>Error:</strong> User with email '$email' not found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}
?>
