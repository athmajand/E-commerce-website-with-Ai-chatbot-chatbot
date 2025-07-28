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

// Get the test user's email from the URL parameter
$email = isset($_GET['email']) ? $_GET['email'] : 'testuser6156@example.com';

// Check the password hash in the database
try {
    $query = "SELECT id, email, phone, password FROM customer_registrations WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Password Hash Check</h2>";
        echo "<p><strong>User ID:</strong> " . $row['id'] . "</p>";
        echo "<p><strong>Email:</strong> " . $row['email'] . "</p>";
        echo "<p><strong>Phone:</strong> " . $row['phone'] . "</p>";
        
        // Check if password field exists and has a value
        if (isset($row['password'])) {
            echo "<p><strong>Password Hash:</strong> " . (empty($row['password']) ? "EMPTY" : $row['password']) . "</p>";
            
            // Test password verification
            $test_password = "password123";
            $password_verified = password_verify($test_password, $row['password']);
            
            echo "<p><strong>Test Password:</strong> " . $test_password . "</p>";
            echo "<p><strong>Password Verification Result:</strong> " . ($password_verified ? "SUCCESS" : "FAILED") . "</p>";
            
            // If verification failed, let's check the hash algorithm
            if (!$password_verified) {
                echo "<p><strong>Hash Info:</strong> " . print_r(password_get_info($row['password']), true) . "</p>";
                
                // Try creating a new hash with the same password
                $new_hash = password_hash($test_password, PASSWORD_BCRYPT);
                echo "<p><strong>New Hash with Same Password:</strong> " . $new_hash . "</p>";
                echo "<p><strong>New Hash Verification:</strong> " . (password_verify($test_password, $new_hash) ? "SUCCESS" : "FAILED") . "</p>";
            }
        } else {
            echo "<p style='color:red;'><strong>Error:</strong> Password field does not exist in the database!</p>";
        }
    } else {
        echo "<p style='color:red;'><strong>Error:</strong> User with email '$email' not found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}
?>
