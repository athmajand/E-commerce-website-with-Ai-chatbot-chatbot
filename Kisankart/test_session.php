<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Link to go back
echo "<p><a href='frontend/customer_profile.php'>Try to go to profile page</a></p>";
?>
