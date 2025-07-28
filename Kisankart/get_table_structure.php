<?php
// Database connection details
$host = 'localhost';
$db_name = 'kisan_kart';
$username = 'root';
$password = '';

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the structure of the seller_registrations table
    $query = "DESCRIBE seller_registrations";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "<h2>Structure of seller_registrations table:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Get a sample row from the table
    $query = "SELECT * FROM seller_registrations LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<h2>Sample data from seller_registrations:</h2>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    } else {
        echo "<p>No data found in seller_registrations table.</p>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
