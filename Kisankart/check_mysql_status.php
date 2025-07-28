<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if MySQL is running
$connection = @fsockopen('localhost', 3306);

if (is_resource($connection)) {
    echo "MySQL is running on localhost:3306";
    fclose($connection);
} else {
    echo "MySQL is NOT running on localhost:3306";
}

echo "<br><br>";
echo "PHP Version: " . phpversion();
echo "<br><br>";
echo "Server Info: " . $_SERVER['SERVER_SOFTWARE'];
?>
