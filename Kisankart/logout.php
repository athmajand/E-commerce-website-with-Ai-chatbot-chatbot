<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear cookies
setcookie("jwt", "", time() - 3600, "/");
setcookie("user_id", "", time() - 3600, "/");
setcookie("username", "", time() - 3600, "/");
setcookie("email", "", time() - 3600, "/");
setcookie("role", "", time() - 3600, "/");

// Redirect to login page
header("Location: login.php");
exit;
?>
