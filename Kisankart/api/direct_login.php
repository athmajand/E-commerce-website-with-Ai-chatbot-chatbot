<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database configuration
include_once 'config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Default response
$success = false;
$message = 'Invalid request';
$user_data = null;

// Check if email and password are provided
if (isset($data->email) && isset($data->password)) {
    $email = $data->email;
    $password = $data->password;
    
    // Query to find user by email
    $query = "SELECT id, username, firstName, lastName, password, email, role FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Generate a simple token
            $token = bin2hex(random_bytes(32));
            
            // Success message
            $message = 'Login successful! Redirecting...';
            $success = true;
            
            // User data to return
            $user_data = [
                'id' => $user['id'],
                'username' => $user['username'],
                'firstName' => $user['firstName'],
                'lastName' => $user['lastName'],
                'email' => $user['email'],
                'role' => $user['role'],
                'token' => $token
            ];
        } else {
            $message = 'Invalid password';
        }
    } else {
        $message = 'User not found';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $message,
    'user' => $success ? $user_data : null
]);
exit;
?>
