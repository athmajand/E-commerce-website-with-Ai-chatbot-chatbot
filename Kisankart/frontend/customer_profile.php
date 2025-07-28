<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to the new profile page
header("Location: new_profile.php");
exit;
?>
