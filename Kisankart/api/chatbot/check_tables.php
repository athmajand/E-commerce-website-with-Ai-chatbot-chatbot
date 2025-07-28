<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kisan_kart', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Messages table structure:\n";
    $stmt = $db->query('DESCRIBE messages');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nCustomer service requests table structure:\n";
    $stmt = $db->query('DESCRIBE customer_service_requests');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
