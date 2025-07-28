<?php
// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Check table structure
    $query = "DESCRIBE seller_registrations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<h2>Seller Registrations Table Structure:</h2>";
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
    
    // Check existing records
    $query = "SELECT id, first_name, last_name, email, phone, business_name FROM seller_registrations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<h2>Existing Seller Registrations:</h2>";
    
    if ($stmt->rowCount() > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th><th>Business Name</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['first_name'] . "</td>";
            echo "<td>" . $row['last_name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "<td>" . $row['business_name'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No records found in the seller_registrations table.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
