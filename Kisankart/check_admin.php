<?php
// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if admin user exists
$query = "SELECT id, username, email, password, role FROM users WHERE username = 'admin' OR email = 'admin@kisankart.com'";
$stmt = $db->prepare($query);
$stmt->execute();
$num = $stmt->rowCount();

echo "Checking for admin user...\n";
echo "Found: " . ($num > 0 ? "Yes" : "No") . "\n\n";

if ($num > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "User details:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Role: " . $row['role'] . "\n";
    echo "Password hash: " . $row['password'] . "\n";
    
    // Check if the default password works
    $default_password = "admin123";
    $password_verified = password_verify($default_password, $row['password']);
    echo "Default password 'admin123' works: " . ($password_verified ? "Yes" : "No") . "\n";
}
?>
