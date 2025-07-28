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

// Check customer_registrations table
try {
    echo "<h2>Customer Registrations Table Data</h2>";
    
    // Get the most recent registration
    $query = "SELECT * FROM customer_registrations ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Most Recent Registration</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        
        foreach ($row as $field => $value) {
            // Don't display the actual password hash for security
            if ($field === 'password') {
                echo "<tr><td>password</td><td>" . (empty($value) ? "EMPTY" : "Password hash exists: " . substr($value, 0, 10) . "...") . "</td></tr>";
            } else {
                echo "<tr><td>" . $field . "</td><td>" . $value . "</td></tr>";
            }
        }
        
        echo "</table>";
        
        // Check if password field exists and has a value
        if (isset($row['password']) && !empty($row['password'])) {
            echo "<p style='color:green;'>Password field exists and contains a hash value.</p>";
        } else {
            echo "<p style='color:red;'>Password field is empty or does not exist!</p>";
        }
    } else {
        echo "<p>No registrations found in the database.</p>";
    }
    
    echo "<p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error checking customer_registrations table: " . $e->getMessage() . "</p>";
}
?>
