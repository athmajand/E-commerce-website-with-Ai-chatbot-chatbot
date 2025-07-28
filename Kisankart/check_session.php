<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set a test session variable
$_SESSION['test_variable'] = 'This is a test value set at ' . date('Y-m-d H:i:s');

// Display all session variables
echo "<h2>Current Session Variables</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Display session ID
echo "<p>Session ID: " . session_id() . "</p>";

// Display cookie information
echo "<h2>Cookie Information</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Link to test the session in another page
echo "<p><a href='test_session.php'>Check if session is maintained</a></p>";

// Link to try to go to profile page
echo "<p><a href='frontend/customer_profile.php'>Try to go to profile page</a></p>";

// Link to login page
echo "<p><a href='login.php'>Go to login page</a></p>";
?>
