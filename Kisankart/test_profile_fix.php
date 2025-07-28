<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Display current session data
echo "<h2>Current Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Get customer data from database
    $query = "SELECT * FROM customer_registrations WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer_data) {
        echo "<h2>Customer Data from Database</h2>";
        echo "<pre>";
        print_r($customer_data);
        echo "</pre>";
    } else {
        echo "<p>No customer data found for user_id: " . $_SESSION['user_id'] . "</p>";
    }
} else {
    echo "<p>No user_id found in session.</p>";
}

// Links to test pages
echo "<h2>Test Links</h2>";
echo "<ul>";
echo "<li><a href='frontend/profile.php'>Go to Profile Page</a></li>";
echo "<li><a href='frontend/customer_dashboard.php'>Go to Dashboard</a></li>";
echo "<li><a href='logout.php'>Logout</a></li>";
echo "</ul>";
?>
