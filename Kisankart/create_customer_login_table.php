<?php
// Script to create the customer_logins table

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Read SQL file
$sql = file_get_contents(__DIR__ . '/api/config/customer_login_table.sql');

// Execute SQL
try {
    $result = $db->exec($sql);
    echo "Customer login table created successfully!\n";
    
    // Check if there are any existing customer profiles to migrate
    $query = "SELECT cp.id as profile_id, u.email, u.phone, u.password, u.id as user_id 
              FROM customer_profiles cp 
              JOIN users u ON cp.user_id = u.id 
              WHERE u.role = 'customer'";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $count = 0;
    
    // Migrate existing customer data
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $insert = "INSERT INTO customer_logins (email, phone, password, customer_profile_id, is_active) 
                   VALUES (:email, :phone, :password, :profile_id, 1)";
        
        $insert_stmt = $db->prepare($insert);
        $insert_stmt->bindParam(':email', $row['email']);
        $insert_stmt->bindParam(':phone', $row['phone']);
        $insert_stmt->bindParam(':password', $row['password']);
        $insert_stmt->bindParam(':profile_id', $row['profile_id']);
        
        if ($insert_stmt->execute()) {
            $count++;
        } else {
            echo "Error migrating data for user ID: " . $row['user_id'] . "\n";
        }
    }
    
    echo "Migrated $count customer login records from existing data.\n";
    
} catch (PDOException $e) {
    echo "Error creating customer login table: " . $e->getMessage() . "\n";
}
?>
