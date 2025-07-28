<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to the unified payment page
if (isset($_POST['payment_method'])) {
    // Forward the POST data
    header("Location: payment.php");
    exit;
} elseif (isset($_POST['process_payment'])) {
    // Forward the payment processing
    header("Location: payment.php");
    exit;
} else {
    // Redirect to checkout if accessed directly
    header("Location: checkout.php");
    exit;
}


