<?php
// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if admin user exists
$query = "SELECT id FROM users WHERE username = 'admin' OR email = 'admin@kisankart.com'";
$stmt = $db->prepare($query);
$stmt->execute();
$num = $stmt->rowCount();

if ($num > 0) {
    echo "Admin user already exists. Updating password...\n";
    
    // Update admin password
    $password_hash = password_hash("admin123", PASSWORD_BCRYPT);
    $update_query = "UPDATE users SET password = :password WHERE username = 'admin' OR email = 'admin@kisankart.com'";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':password', $password_hash);
    
    if ($update_stmt->execute()) {
        echo "Admin password updated successfully.\n";
    } else {
        echo "Failed to update admin password.\n";
    }
} else {
    echo "Admin user does not exist. Creating new admin user...\n";
    
    // Create admin user
    $password_hash = password_hash("admin123", PASSWORD_BCRYPT);
    $create_query = "INSERT INTO users (username, password, email, role) VALUES ('admin', :password, 'admin@kisankart.com', 'admin')";
    $create_stmt = $db->prepare($create_query);
    $create_stmt->bindParam(':password', $password_hash);
    
    if ($create_stmt->execute()) {
        echo "Admin user created successfully.\n";
    } else {
        echo "Failed to create admin user.\n";
    }
}

// Verify admin user
$verify_query = "SELECT id, username, email, role FROM users WHERE username = 'admin' OR email = 'admin@kisankart.com'";
$verify_stmt = $db->prepare($verify_query);
$verify_stmt->execute();

if ($verify_stmt->rowCount() > 0) {
    $row = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nAdmin user details:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Role: " . $row['role'] . "\n";
    echo "\nYou can now log in with:\n";
    echo "Username: admin@kisankart.com\n";
    echo "Password: admin123\n";
} else {
    echo "Failed to verify admin user.\n";
}
?>
