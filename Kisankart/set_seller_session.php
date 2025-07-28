<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set seller session variables
$_SESSION['user_id'] = 1;
$_SESSION['seller_id'] = 1;
$_SESSION['user_role'] = 'seller';
$_SESSION['first_name'] = 'Test';
$_SESSION['last_name'] = 'Seller';
$_SESSION['email'] = 'seller@example.com';

// Output result
echo "Seller session variables set successfully:<br>";
echo "- user_id: " . $_SESSION['user_id'] . "<br>";
echo "- seller_id: " . $_SESSION['seller_id'] . "<br>";
echo "- user_role: " . $_SESSION['user_role'] . "<br>";
echo "- name: " . $_SESSION['first_name'] . " " . $_SESSION['last_name'] . "<br>";
echo "- email: " . $_SESSION['email'] . "<br><br>";

echo "Now you can go to the <a href='frontend/seller/messages.php'>messages page</a> and try sending a message.";
?>
