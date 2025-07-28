<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set text headers for easier debugging
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=UTF-8");

// Include database and user model
include_once 'api/config/database.php';
include_once 'api/models/User.php';

echo "Login Debug Test\n";
echo "----------------\n\n";

try {
    // Get database connection
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: " . ($db ? "Success" : "Failed") . "\n\n";
    
    // Check if admin user exists
    echo "Checking if admin user exists...\n";
    $query = "SELECT id, username, password, email, role FROM users WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $num = $stmt->rowCount();
    
    echo "Admin user found: " . ($num > 0 ? "Yes" : "No") . "\n";
    
    if($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Admin details:\n";
        echo "- ID: " . $row['id'] . "\n";
        echo "- Username: " . $row['username'] . "\n";
        echo "- Email: " . $row['email'] . "\n";
        echo "- Role: " . $row['role'] . "\n";
        echo "- Password hash: " . substr($row['password'], 0, 10) . "...\n\n";
        
        // Test password verification
        echo "Testing password verification...\n";
        $test_password = "admin123";
        $password_verified = password_verify($test_password, $row['password']);
        echo "Password 'admin123' verification: " . ($password_verified ? "Success" : "Failed") . "\n\n";
        
        // If verification failed, try to create a new hash for comparison
        if(!$password_verified) {
            echo "Creating new hash for 'admin123'...\n";
            $new_hash = password_hash($test_password, PASSWORD_BCRYPT);
            echo "New hash: " . $new_hash . "\n";
            echo "Original hash: " . $row['password'] . "\n\n";
        }
    }
    
    // Test login using User model
    echo "Testing login using User model...\n";
    $user = new User($db);
    $user->username = "admin";
    $user->password = "admin123";
    
    $login_result = $user->login();
    echo "Login result: " . ($login_result ? "Success" : "Failed") . "\n";
    
    if($login_result) {
        echo "User details after login:\n";
        echo "- ID: " . $user->id . "\n";
        echo "- Email: " . $user->email . "\n";
        echo "- Role: " . $user->role . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
