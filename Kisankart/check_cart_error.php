<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    // Connect to the database
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the last error from the database
    $stmt = $db->query("SHOW ENGINE INNODB STATUS");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Last Database Error</h2>";
    echo "<pre>" . htmlspecialchars($status['Status']) . "</pre>";
    
    // Check for any recent attempts to add to cart
    $stmt = $db->query("SELECT * FROM information_schema.processlist WHERE info LIKE '%INSERT INTO cart%' OR info LIKE '%UPDATE cart%'");
    $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Recent Cart Operations</h2>";
    if (count($processes) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User</th><th>Host</th><th>DB</th><th>Command</th><th>Time</th><th>State</th><th>Info</th></tr>";
        foreach ($processes as $process) {
            echo "<tr>";
            foreach ($process as $key => $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No recent cart operations found.</p>";
    }
    
    // Check for any recent errors in the error log
    if (file_exists(error_log)) {
        $log = file_get_contents(error_log);
        echo "<h2>Error Log</h2>";
        echo "<pre>" . htmlspecialchars($log) . "</pre>";
    }
    
    // Check for any test_buy_now.php file
    if (file_exists("test_buy_now.php")) {
        echo "<h2>test_buy_now.php Content</h2>";
        echo "<pre>" . htmlspecialchars(file_get_contents("test_buy_now.php")) . "</pre>";
    }
    
} catch(PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
