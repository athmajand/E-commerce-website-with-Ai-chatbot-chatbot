<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Checking Database Tables ===\n";
    
    // Show all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\n=== Checking for admin_users table ===\n";
    
    // Check if admin_users table exists
    if (in_array('admin_users', $tables)) {
        echo "admin_users table EXISTS\n";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE admin_users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nTable structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
        }
        
        // Check for admin users
        $stmt = $conn->query("SELECT * FROM admin_users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nAdmin users count: " . count($users) . "\n";
        
        if (count($users) > 0) {
            echo "\nAdmin users:\n";
            foreach ($users as $user) {
                echo "- ID: {$user['id']}, Username: {$user['username']}, Status: " . (isset($user['status']) ? $user['status'] : 'N/A') . "\n";
            }
        }
        
    } else {
        echo "admin_users table DOES NOT EXIST\n";
        
        // Check if there's a users table with admin role
        if (in_array('users', $tables)) {
            echo "\nChecking users table for admin role...\n";
            $stmt = $conn->query("SELECT * FROM users WHERE role = 'admin' OR username = 'admin'");
            $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Admin users in 'users' table: " . count($admin_users) . "\n";
            
            if (count($admin_users) > 0) {
                foreach ($admin_users as $user) {
                    echo "- ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}\n";
                }
            }
        }
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
