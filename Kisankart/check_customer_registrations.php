<?php
// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Check if customer_registrations table exists
    $query = "SHOW TABLES LIKE 'customer_registrations'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<h2>customer_registrations table exists</h2>";
        
        // Show table structure
        $query = "DESCRIBE customer_registrations";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        echo "<h3>Table structure:</h3>";
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
    } else {
        echo "<h2>customer_registrations table does NOT exist</h2>";
        
        // Create SQL for the table
        echo "<h3>SQL to create the table:</h3>";
        echo "<pre>
CREATE TABLE `customer_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `registration_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        </pre>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
