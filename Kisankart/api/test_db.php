<?php
// Include database configuration
include_once 'config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Query to check if firstName and lastName columns exist
$query = "SHOW COLUMNS FROM users LIKE 'firstName'";
$stmt = $db->prepare($query);
$stmt->execute();

$firstNameExists = $stmt->rowCount() > 0;

$query = "SHOW COLUMNS FROM users LIKE 'lastName'";
$stmt = $db->prepare($query);
$stmt->execute();

$lastNameExists = $stmt->rowCount() > 0;

// Output results
echo "Database Schema Check:\n";
echo "firstName column exists: " . ($firstNameExists ? "Yes" : "No") . "\n";
echo "lastName column exists: " . ($lastNameExists ? "Yes" : "No") . "\n";

// If columns don't exist, try to add them
if (!$firstNameExists || !$lastNameExists) {
    echo "Attempting to add missing columns...\n";
    
    try {
        if (!$firstNameExists) {
            $query = "ALTER TABLE users ADD COLUMN firstName VARCHAR(100) AFTER username";
            $db->exec($query);
            echo "Added firstName column.\n";
        }
        
        if (!$lastNameExists) {
            $query = "ALTER TABLE users ADD COLUMN lastName VARCHAR(100) AFTER firstName";
            $db->exec($query);
            echo "Added lastName column.\n";
        }
        
        echo "Schema update completed.\n";
    } catch (PDOException $e) {
        echo "Error updating schema: " . $e->getMessage() . "\n";
    }
}

// Check if there are any users in the database
$query = "SELECT id, username, firstName, lastName, email FROM users LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();

echo "\nUsers in database:\n";
if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . ", Username: " . $row['username'] . 
             ", Name: " . $row['firstName'] . " " . $row['lastName'] . 
             ", Email: " . $row['email'] . "\n";
    }
} else {
    echo "No users found.\n";
}
?>
