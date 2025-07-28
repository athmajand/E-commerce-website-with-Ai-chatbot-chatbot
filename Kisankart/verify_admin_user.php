<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

try {
    // Create connection using PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Checking admin_users table ===\n\n";
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() == 0) {
        echo "admin_users table does not exist!\n";
        exit;
    }
    
    // Get table structure
    echo "Table Structure:\n";
    $stmt = $conn->query("DESCRIBE admin_users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
    echo "\n";
    
    // Check admin user
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute(['admin']);
    
    if ($stmt->rowCount() > 0) {
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Admin user found:\n";
        echo "ID: {$admin['id']}\n";
        echo "Username: {$admin['username']}\n";
        echo "Status: " . (isset($admin['status']) ? $admin['status'] : 'N/A') . "\n";
        echo "Password Hash Length: " . strlen($admin['password']) . "\n";
        
        // Verify if default password works
        if (password_verify('admin123', $admin['password'])) {
            echo "\nDefault password 'admin123' is valid\n";
        } else {
            echo "\nDefault password 'admin123' is NOT valid\n";
        }
    } else {
        echo "No admin user found!\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
