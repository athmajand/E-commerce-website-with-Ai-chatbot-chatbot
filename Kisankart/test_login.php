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

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_id'])) {
    $customer_id = intval($_POST['customer_id']);

    // Verify the customer exists
    try {
        $query = "SELECT id, first_name, last_name, email FROM customer_registrations WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $customer_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            // Set session variables
            $_SESSION['user_id'] = $customer['id'];
            $_SESSION['first_name'] = $customer['first_name'];
            $_SESSION['last_name'] = $customer['last_name'];
            $_SESSION['email'] = $customer['email'];
            $_SESSION['role'] = 'customer';

            echo "<h1>Test Login Successful</h1>";
            echo "<p>You are now logged in as:</p>";
            echo "<pre>";
            print_r($customer);
            echo "</pre>";
            echo "<p>Session data:</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
        } else {
            echo "<h1>Test Login Failed</h1>";
            echo "<p>No customer found with ID: " . $customer_id . "</p>";
        }
    } catch (PDOException $e) {
        echo "<h1>Database Error</h1>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h1>Invalid Request</h1>";
    echo "<p>Please submit the form with a valid customer ID.</p>";
}
?>

<p><a href="test_db_connection.php">Back to Test Page</a></p>
<p><a href="frontend/products.php">Go to Products Page</a></p>
<p><a href="login.php">Go to Login Page</a></p>
