<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include session helper
include_once __DIR__ . '/api/helpers/session_helper.php';

echo "<h1>Session ID Synchronization</h1>";

echo "<h2>Current Session Status</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Synchronize session IDs
$result = synchronizeSessionIds();

echo "<h2>Synchronization Result</h2>";
echo "<p>Synchronization " . ($result ? "successful" : "failed") . "</p>";

echo "<h2>Updated Session Status</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
echo "<h2>Login Status</h2>";
echo "<p>User is " . (isLoggedIn() ? "logged in" : "not logged in") . "</p>";

// Check if user is a customer
echo "<h2>Customer Status</h2>";
echo "<p>User is " . (isCustomer() ? "a customer" : "not a customer") . "</p>";

// Check if user is a seller
echo "<h2>Seller Status</h2>";
echo "<p>User is " . (isSeller() ? "a seller" : "not a seller") . "</p>";

// Get customer ID
echo "<h2>Customer ID</h2>";
$customer_id = getCustomerIdFromSession();
echo "<p>Customer ID: " . ($customer_id ? $customer_id : "Not found") . "</p>";

// Add a link to go back to the home page
echo "<p><a href='frontend/index.html'>Go back to home page</a></p>";
