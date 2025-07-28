<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set text headers for easier debugging
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=UTF-8");

// Include database
include_once 'api/config/database.php';

echo "Updating Admin Password\n";
echo "----------------------\n\n";

try {
    // Get database connection
    echo "Connecting to database...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: " . ($db ? "Success" : "Failed") . "\n\n";
    
    // Create new password hash
    $password = "admin123";
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    echo "New password hash for 'admin123': " . $password_hash . "\n\n";
    
    // Update admin password
    $query = "UPDATE users SET password = ? WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $password_hash);
    
    if($stmt->execute()) {
        echo "Admin password updated successfully!\n";
        
        // Verify the update
        $verify_query = "SELECT password FROM users WHERE username = 'admin'";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->execute();
        $row = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Updated password hash: " . $row['password'] . "\n\n";
        
        // Test password verification
        $password_verified = password_verify($password, $row['password']);
        echo "Password verification test: " . ($password_verified ? "Success" : "Failed") . "\n";
    } else {
        echo "Failed to update admin password.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
