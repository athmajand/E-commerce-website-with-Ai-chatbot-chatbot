<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if MySQL is running
$connection = @fsockopen('localhost', 3306);

if (is_resource($connection)) {
    echo "MySQL is already running on localhost:3306<br>";
    fclose($connection);
} else {
    echo "MySQL is NOT running on localhost:3306<br>";
    echo "Attempting to start MySQL service...<br>";
    
    // Path to XAMPP MySQL executable
    $xamppPath = 'C:\\xampp\\mysql\\bin\\';
    
    // Try to start MySQL using the XAMPP control panel
    $output = [];
    $return_var = 0;
    
    // Try to start MySQL using the command line
    exec('net start MySQL', $output, $return_var);
    
    if ($return_var === 0) {
        echo "MySQL service started successfully.<br>";
    } else {
        echo "Failed to start MySQL service using 'net start MySQL'.<br>";
        echo "Output: " . implode("<br>", $output) . "<br>";
        
        // Try using the XAMPP executable
        exec($xamppPath . 'mysql.exe', $output, $return_var);
        
        if ($return_var === 0) {
            echo "MySQL service started successfully using XAMPP executable.<br>";
        } else {
            echo "Failed to start MySQL service using XAMPP executable.<br>";
            echo "Output: " . implode("<br>", $output) . "<br>";
            
            echo "<br><strong>Please start MySQL manually using XAMPP Control Panel.</strong><br>";
        }
    }
}

echo "<br>PHP Version: " . phpversion() . "<br>";
echo "Server Info: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Check if we can connect to MySQL now
echo "<br>Checking MySQL connection after startup attempt:<br>";
try {
    $conn = new PDO("mysql:host=localhost", "root", "");
    echo "Successfully connected to MySQL server!<br>";
    
    // Check if kisan_kart database exists
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'kisan_kart'");
    $databaseExists = $stmt->rowCount() > 0;
    
    if ($databaseExists) {
        echo "Database 'kisan_kart' exists.<br>";
    } else {
        echo "Database 'kisan_kart' does not exist. Creating it now...<br>";
        $conn->exec("CREATE DATABASE IF NOT EXISTS `kisan_kart`");
        echo "Database 'kisan_kart' created successfully.<br>";
    }
    
    // Connect to the kisan_kart database
    $conn = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
    echo "Successfully connected to 'kisan_kart' database!<br>";
    
} catch (PDOException $e) {
    echo "MySQL Connection Error: " . $e->getMessage() . "<br>";
}

// Add a link to go back to the login page
echo "<br><a href='login.php' class='btn btn-primary'>Go to Login Page</a>";
?>
