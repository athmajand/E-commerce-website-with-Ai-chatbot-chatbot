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

// Get the email from the URL parameter or use the default
$email = isset($_GET['email']) ? $_GET['email'] : 'testuser6156@example.com';

// Update the user's verification status
try {
    // Begin transaction
    $db->beginTransaction();
    
    // Update query
    $query = "UPDATE customer_registrations 
              SET is_verified = 1 
              WHERE email = :email";
    
    // Prepare query
    $stmt = $db->prepare($query);
    
    // Bind values
    $stmt->bindParam(":email", $email);
    
    // Execute query
    $result = $stmt->execute();
    
    if ($result && $stmt->rowCount() > 0) {
        // Commit transaction
        $db->commit();
        
        echo "<h2>User Verified Successfully</h2>";
        echo "<p>The user with email <strong>$email</strong> has been verified.</p>";
        
        // Get the updated user details
        $query = "SELECT * FROM customer_registrations WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Updated User Details</h3>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . $user['id'] . "</li>";
            echo "<li><strong>Name:</strong> " . $user['first_name'] . " " . $user['last_name'] . "</li>";
            echo "<li><strong>Email:</strong> " . $user['email'] . "</li>";
            echo "<li><strong>Phone:</strong> " . $user['phone'] . "</li>";
            echo "<li><strong>Status:</strong> " . $user['status'] . "</li>";
            echo "<li><strong>Is Verified:</strong> " . ($user['is_verified'] ? "Yes" : "No") . "</li>";
            echo "</ul>";
        }
        
        echo "<div>";
        echo "<a href='login.php' class='btn btn-primary'>Go to Login Page</a> ";
        echo "<a href='debug_login.php?email=" . urlencode($email) . "' class='btn btn-success'>Debug Login</a>";
        echo "</div>";
    } else {
        // Rollback transaction
        $db->rollBack();
        
        echo "<h2>Error Verifying User</h2>";
        echo "<p>There was an error verifying the user with email <strong>$email</strong>. The user may not exist or is already verified.</p>";
    }
} catch (PDOException $e) {
    // Rollback transaction
    $db->rollBack();
    
    echo "<h2>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
