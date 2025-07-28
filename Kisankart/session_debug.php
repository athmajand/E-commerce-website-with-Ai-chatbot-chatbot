<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Session Debug</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Session ID</h2>";
echo "<p>Session ID: " . session_id() . "</p>";

echo "<h2>Session Status</h2>";
echo "<p>Session Status: " . session_status() . "</p>";

echo "<h2>User ID Check</h2>";
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    echo "<p style='color:green;'>user_id is set: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color:red;'>user_id is NOT set</p>";
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h2>Database Connection</h2>";
if ($db) {
    echo "<p style='color:green;'>Database connection successful!</p>";
    
    // Test query to check if customer_registrations table exists
    try {
        $query = "SHOW TABLES LIKE 'customer_registrations'";
        $stmt = $db->query($query);
        
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green;'>customer_registrations table exists!</p>";
            
            // Check if there are any records in the table
            $query = "SELECT COUNT(*) as count FROM customer_registrations";
            $stmt = $db->query($query);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p>Number of customer records: " . $row['count'] . "</p>";
            
            // If user_id is set, check if it exists in the database
            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $query = "SELECT id, first_name, last_name, email FROM customer_registrations WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $_SESSION['user_id']);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<p style='color:green;'>User ID exists in customer_registrations table!</p>";
                    echo "<pre>";
                    print_r($customer);
                    echo "</pre>";
                } else {
                    echo "<p style='color:red;'>User ID does NOT exist in customer_registrations table!</p>";
                }
            }
        } else {
            echo "<p style='color:red;'>customer_registrations table does not exist!</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error querying database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>Database connection failed!</p>";
}

echo "<h2>Actions</h2>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
echo "<p><a href='frontend/products.php'>Go to Products Page</a></p>";
echo "<p><a href='test_buy_now.php'>Test Buy Now</a></p>";
echo "<p><a href='logout.php'>Logout</a></p>";
?>
