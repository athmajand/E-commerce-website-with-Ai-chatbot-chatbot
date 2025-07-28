<?php
// Database connection file
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
