<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Display PHP session configuration
echo "<h2>PHP Session Configuration</h2>";
echo "<pre>";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "\n";
echo "session.use_trans_sid: " . ini_get('session.use_trans_sid') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "</pre>";

// Display current session ID
echo "<p>Current Session ID: " . session_id() . "</p>";

// Set a test session variable
$_SESSION['test_variable'] = 'This is a test value set at ' . date('Y-m-d H:i:s');

// Display all session variables
echo "<h2>Current Session Variables</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

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

// Link to set session variables
echo "<p><a href='set_session.php'>Set session variables</a></p>";
?>
