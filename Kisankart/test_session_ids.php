<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/helpers/session_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Session ID Test</h1>";

// Display current session
echo "<h2>Current Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test setting session variables
echo "<h2>Test Setting Session Variables</h2>";

// Get customer ID from form submission
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : null;

if ($customer_id) {
    // Check if customer exists
    try {
        $query = "SELECT id, first_name, last_name, email FROM customer_registrations WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $customer_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set session variables
            $_SESSION['user_id'] = $customer['id'];
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['first_name'] = $customer['first_name'];
            $_SESSION['last_name'] = $customer['last_name'];
            $_SESSION['email'] = $customer['email'];
            $_SESSION['role'] = 'customer';
            
            echo "<p style='color:green;'>Session variables set successfully!</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>Customer not found with ID: $customer_id</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
    }
}

// Test helper functions
echo "<h2>Test Helper Functions</h2>";

echo "<p>isLoggedIn(): " . (isLoggedIn() ? "Yes" : "No") . "</p>";
echo "<p>isCustomer(): " . (isCustomer() ? "Yes" : "No") . "</p>";
echo "<p>isSeller(): " . (isSeller() ? "Yes" : "No") . "</p>";
echo "<p>getCustomerIdFromSession(): " . (getCustomerIdFromSession() ?: "Not found") . "</p>";

// Form to set session variables
echo "<h2>Set Session Variables</h2>";
echo "<form method='post' action='test_session_ids.php'>";
echo "<label>Customer ID: <input type='number' name='customer_id' required></label><br>";
echo "<input type='submit' value='Set Session'>";
echo "</form>";

// Form to test with only user_id
echo "<h2>Test with only user_id</h2>";
echo "<form method='post' action='test_session_ids.php'>";
echo "<input type='hidden' name='test_type' value='user_id_only'>";
echo "<label>Customer ID: <input type='number' name='customer_id' required></label><br>";
echo "<input type='submit' value='Set user_id only'>";
echo "</form>";

// Form to test with only customer_id
echo "<h2>Test with only customer_id</h2>";
echo "<form method='post' action='test_session_ids.php'>";
echo "<input type='hidden' name='test_type' value='customer_id_only'>";
echo "<label>Customer ID: <input type='number' name='customer_id' required></label><br>";
echo "<input type='submit' value='Set customer_id only'>";
echo "</form>";

// Process test type
if (isset($_POST['test_type']) && isset($_POST['customer_id'])) {
    $test_type = $_POST['test_type'];
    $customer_id = intval($_POST['customer_id']);
    
    // Check if customer exists
    try {
        $query = "SELECT id, first_name, last_name, email FROM customer_registrations WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $customer_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Clear existing session variables
            unset($_SESSION['user_id']);
            unset($_SESSION['customer_id']);
            
            // Set session variables based on test type
            if ($test_type === 'user_id_only') {
                $_SESSION['user_id'] = $customer['id'];
                echo "<p style='color:blue;'>Set user_id only: " . $customer['id'] . "</p>";
            } else if ($test_type === 'customer_id_only') {
                $_SESSION['customer_id'] = $customer['id'];
                echo "<p style='color:blue;'>Set customer_id only: " . $customer['id'] . "</p>";
            }
            
            // Set other session variables
            $_SESSION['first_name'] = $customer['first_name'];
            $_SESSION['last_name'] = $customer['last_name'];
            $_SESSION['email'] = $customer['email'];
            $_SESSION['role'] = 'customer';
            
            echo "<p>Session after setting:</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            // Test synchronization
            echo "<p>Synchronizing session IDs...</p>";
            $result = synchronizeSessionIds();
            echo "<p>Synchronization " . ($result ? "successful" : "failed") . "</p>";
            
            echo "<p>Session after synchronization:</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>Customer not found with ID: $customer_id</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
    }
}

// Link to clear session
echo "<p><a href='logout.php'>Clear Session (Logout)</a></p>";

// Link to go back to home page
echo "<p><a href='frontend/index.html'>Go back to home page</a></p>";
