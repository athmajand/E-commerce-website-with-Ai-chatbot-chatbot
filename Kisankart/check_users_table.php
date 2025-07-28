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

if (!$db) {
    die("Database connection failed. Please check your database configuration.");
}

// Check if users table exists
$stmt = $db->query("SHOW TABLES LIKE 'users'");
$usersTableExists = $stmt->rowCount() > 0;

if ($usersTableExists) {
    echo "Users table exists.<br>";
    
    // Check table structure
    $stmt = $db->query("DESCRIBE users");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Check if otp and otp_expiry columns exist
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'otp'");
    $otpColumnExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'otp_expiry'");
    $otpExpiryColumnExists = $stmt->rowCount() > 0;
    
    if ($otpColumnExists && $otpExpiryColumnExists) {
        echo "OTP columns already exist in the users table.<br>";
    } else {
        echo "OTP columns do not exist in the users table. Adding them now...<br>";
        
        // Add OTP columns
        $sql = "ALTER TABLE users 
                ADD COLUMN otp VARCHAR(10) NULL,
                ADD COLUMN otp_expiry DATETIME NULL";
        
        try {
            $db->exec($sql);
            echo "OTP columns added successfully.<br>";
        } catch (PDOException $e) {
            echo "Error adding OTP columns: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "Users table does not exist.<br>";
}
?>
