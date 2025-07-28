<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Session Debug Information</h1>";

echo "<h2>All Session Variables:</h2>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>No session variables found!</p>";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h2>Common Session Variables Check:</h2>";
$commonVars = [
    'user_id',
    'firstName', 
    'lastName',
    'username',
    'email',
    'user_role',
    'is_logged_in'
];

foreach ($commonVars as $var) {
    if (isset($_SESSION[$var])) {
        echo "<p style='color: green;'>✓ \$_SESSION['$var'] = " . htmlspecialchars($_SESSION[$var]) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ \$_SESSION['$var'] is not set</p>";
    }
}

// Display session ID
echo "<h2>Session ID:</h2>";
echo "<p>Session ID: " . session_id() . "</p>";

echo "<h2>Session Status:</h2>";
echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>";

// Display cookie information
echo "<h2>Cookie Information</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Link to go back
echo "<p><a href='frontend/profile.php'>Go to profile page</a></p>";
echo "<p><a href='frontend/customer_dashboard.php'>Go to dashboard</a></p>";

echo "<h2>Test Links:</h2>";
echo "<p><a href='frontend/cart.php' target='_blank'>Test Cart Page</a></p>";
echo "<p><a href='login.php' target='_blank'>Login Page</a></p>";
echo "<p><a href='logout.php' target='_blank'>Logout</a></p>";

echo "<h2>How to Fix:</h2>";
echo "<ol>";
echo "<li>If no session variables exist, you need to login first</li>";
echo "<li>If session variables exist but firstName/lastName are missing, the login process needs to set them</li>";
echo "<li>Check the login.php file to see what session variables it sets</li>";
echo "</ol>";
?>
