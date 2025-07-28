<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=UTF-8");

// Include database configuration
include_once 'config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

echo "Updating users with default firstName and lastName values...\n\n";

try {
    // Get all users with empty firstName or lastName
    $query = "SELECT id, username, email FROM users WHERE firstName IS NULL OR firstName = '' OR lastName IS NULL OR lastName = ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $usersUpdated = 0;
    
    if ($stmt->rowCount() > 0) {
        echo "Found " . $stmt->rowCount() . " users to update:\n";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userId = $row['id'];
            $username = $row['username'];
            $email = $row['email'];
            
            // Generate firstName and lastName from username or email
            $firstName = $username;
            $lastName = '';
            
            // If email contains a dot, try to extract first and last name
            if (strpos($email, '@') !== false) {
                $emailParts = explode('@', $email);
                $emailName = $emailParts[0];
                
                if (strpos($emailName, '.') !== false) {
                    $nameParts = explode('.', $emailName);
                    $firstName = ucfirst($nameParts[0]);
                    $lastName = isset($nameParts[1]) ? ucfirst($nameParts[1]) : '';
                } else {
                    $firstName = ucfirst($emailName);
                }
            }
            
            // Update user
            $updateQuery = "UPDATE users SET firstName = :firstName, lastName = :lastName WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':firstName', $firstName);
            $updateStmt->bindParam(':lastName', $lastName);
            $updateStmt->bindParam(':id', $userId);
            
            if ($updateStmt->execute()) {
                echo "Updated user ID $userId ($username): Set firstName='$firstName', lastName='$lastName'\n";
                $usersUpdated++;
            } else {
                echo "Failed to update user ID $userId ($username)\n";
            }
        }
        
        echo "\nTotal users updated: $usersUpdated\n";
    } else {
        echo "No users found with empty firstName or lastName fields.\n";
    }
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
?>
