<?php
// This file is for backward compatibility
// Redirect to the new customer_orders.php file

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to customer_orders.php
header("Location: /Kisankart/frontend/customer_orders.php");
exit;
?>
