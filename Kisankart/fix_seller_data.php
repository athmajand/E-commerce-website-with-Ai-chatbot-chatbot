<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database configuration and models
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection was successful
if (!$db) {
    die("Database connection failed. Please check your database configuration.");
}

echo "<h1>Seller Data Fix Utility</h1>";
echo "<p>This script will check and fix seller data in the database.</p>";

// Check if seller_registrations table exists
try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'seller_registrations'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color:red;'>Error: The seller_registrations table does not exist!</p>";
        echo "<p>Creating the seller_registrations table...</p>";
        
        // Create the seller_registrations table
        $sql = "CREATE TABLE `seller_registrations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `first_name` varchar(100) NOT NULL,
          `last_name` varchar(100) NOT NULL,
          `date_of_birth` date DEFAULT NULL,
          `email` varchar(255) NOT NULL,
          `phone` varchar(20) NOT NULL,
          `password` varchar(255) NOT NULL,
          `business_name` varchar(100) DEFAULT NULL,
          `business_description` text,
          `business_logo` varchar(255) DEFAULT NULL,
          `business_address` text,
          `business_country` varchar(50) DEFAULT NULL,
          `business_state` varchar(100) DEFAULT NULL,
          `business_city` varchar(100) DEFAULT NULL,
          `business_postal_code` varchar(20) DEFAULT NULL,
          `gst_number` varchar(50) DEFAULT NULL,
          `pan_number` varchar(50) DEFAULT NULL,
          `id_type` varchar(50) DEFAULT NULL,
          `id_document_path` varchar(255) DEFAULT NULL,
          `tax_classification` varchar(50) DEFAULT NULL,
          `tax_document_path` varchar(255) DEFAULT NULL,
          `bank_account_details` text,
          `bank_account_number` varchar(50) DEFAULT NULL,
          `account_holder_name` varchar(100) DEFAULT NULL,
          `ifsc_code` varchar(20) DEFAULT NULL,
          `bank_document_path` varchar(255) DEFAULT NULL,
          `store_display_name` varchar(100) DEFAULT NULL,
          `product_categories` text,
          `marketplace` varchar(10) DEFAULT NULL,
          `store_logo_path` varchar(255) DEFAULT NULL,
          `verification_token` varchar(255) DEFAULT NULL,
          `is_verified` tinyint(1) DEFAULT '0',
          `status` enum('pending','approved','rejected') DEFAULT 'pending',
          `last_login` datetime DEFAULT NULL,
          `notes` text,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `email` (`email`),
          UNIQUE KEY `phone` (`phone`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($sql);
        echo "<p style='color:green;'>Successfully created the seller_registrations table.</p>";
    } else {
        echo "<p style='color:green;'>The seller_registrations table exists.</p>";
    }
    
    // Check if seller with ID 3 exists
    $stmt = $db->prepare("SELECT * FROM seller_registrations WHERE id = ?");
    $sellerId = 3;
    $stmt->bindParam(1, $sellerId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red;'>Seller with ID 3 does not exist in the database.</p>";
        
        // Get session data
        $firstName = $_SESSION['first_name'] ?? 'Hemanth';
        $lastName = $_SESSION['last_name'] ?? 'Kumar';
        $email = $_SESSION['email'] ?? 'hemanth@gmail.com';
        
        echo "<p>Creating seller record for $firstName $lastName ($email)...</p>";
        
        // Generate a password hash
        $password = password_hash('password123', PASSWORD_BCRYPT);
        
        // Insert the seller record
        $insertSql = "INSERT INTO seller_registrations 
                     (id, first_name, last_name, email, phone, password, business_name, business_address, status, is_verified) 
                     VALUES 
                     (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insertStmt = $db->prepare($insertSql);
        $id = 3;
        $phone = '9876543210';
        $businessName = 'Hemanth Farms';
        $businessAddress = 'Sample Address, City, State';
        $status = 'approved';
        $isVerified = 1;
        
        $insertStmt->bindParam(1, $id);
        $insertStmt->bindParam(2, $firstName);
        $insertStmt->bindParam(3, $lastName);
        $insertStmt->bindParam(4, $email);
        $insertStmt->bindParam(5, $phone);
        $insertStmt->bindParam(6, $password);
        $insertStmt->bindParam(7, $businessName);
        $insertStmt->bindParam(8, $businessAddress);
        $insertStmt->bindParam(9, $status);
        $insertStmt->bindParam(10, $isVerified);
        
        if ($insertStmt->execute()) {
            echo "<p style='color:green;'>Successfully created seller record with ID 3.</p>";
        } else {
            echo "<p style='color:red;'>Failed to create seller record: " . implode(", ", $insertStmt->errorInfo()) . "</p>";
        }
    } else {
        echo "<p style='color:green;'>Seller with ID 3 exists in the database.</p>";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Seller details: " . $row['first_name'] . " " . $row['last_name'] . " (" . $row['email'] . ")</p>";
    }
    
    // Check if there are any other sellers in the database
    $stmt = $db->query("SELECT id, first_name, last_name, email FROM seller_registrations");
    echo "<h2>All Sellers in Database:</h2>";
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>ID: " . $row['id'] . " - " . $row['first_name'] . " " . $row['last_name'] . " (" . $row['email'] . ")</li>";
    }
    echo "</ul>";
    
    echo "<p>Done! You can now <a href='frontend/seller/dashboard.php'>go to the seller dashboard</a>.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
