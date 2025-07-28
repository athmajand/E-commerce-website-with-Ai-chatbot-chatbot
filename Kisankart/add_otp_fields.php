<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed. Please check your database configuration.");
}

// SQL to add OTP fields to users table
$sql = "
ALTER TABLE users
ADD COLUMN IF NOT EXISTS otp VARCHAR(10) NULL,
ADD COLUMN IF NOT EXISTS otp_expiry DATETIME NULL;
";

// Execute the query
if ($conn->multi_query($sql)) {
    echo "OTP fields added to users table successfully!";
} else {
    echo "Error adding OTP fields: " . $conn->error;
}

// Close the connection
$conn->close();
?>
