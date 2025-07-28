<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database and models
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection was successful
if (!$db) {
    die("Database connection failed. Please check your database configuration.");
}

// Create a test seller
$seller = new SellerRegistration($db);
$seller->first_name = 'Test';
$seller->last_name = 'Seller';
$seller->email = 'test@seller.com';
$seller->phone = '1234567890';
$seller->password = 'password123';
$seller->business_name = 'Test Business';
$seller->business_address = 'Test Address';
$seller->status = 'approved';
$seller->is_verified = 1;

// Check if email already exists
if ($seller->emailExists()) {
    echo "Email already exists. Using existing account.<br>";
    
    // Try to login with the test account
    if ($seller->loginWithEmail()) {
        echo "Login successful with email: " . $seller->email . "<br>";
        echo "Seller ID: " . $seller->id . "<br>";
        echo "Name: " . $seller->first_name . " " . $seller->last_name . "<br>";
    } else {
        echo "Login failed with email: " . $seller->email . "<br>";
        echo "Error: " . $seller->error . "<br>";
    }
} else {
    // Create the seller
    if ($seller->create()) {
        echo "Test seller created successfully.<br>";
        echo "Seller ID: " . $seller->id . "<br>";
        echo "Email: " . $seller->email . "<br>";
        echo "Phone: " . $seller->phone . "<br>";
    } else {
        echo "Failed to create test seller.<br>";
        echo "Error: " . $seller->error . "<br>";
    }
}

// Check if we can login with the test account
$login_seller = new SellerRegistration($db);
$login_seller->email = 'test@seller.com';
$login_seller->password = 'password123';

if ($login_seller->loginWithEmail()) {
    echo "Login successful with email: " . $login_seller->email . "<br>";
    echo "Seller ID: " . $login_seller->id . "<br>";
    echo "Name: " . $login_seller->first_name . " " . $login_seller->last_name . "<br>";
} else {
    echo "Login failed with email: " . $login_seller->email . "<br>";
    echo "Error: " . $login_seller->error . "<br>";
}

// Check if the seller_registrations table exists
$stmt = $db->query("SHOW TABLES LIKE 'seller_registrations'");
$tableExists = $stmt->rowCount() > 0;

echo "seller_registrations table exists: " . ($tableExists ? 'Yes' : 'No') . "<br>";

if ($tableExists) {
    // Check the structure of the seller_registrations table
    $stmt = $db->query("DESCRIBE seller_registrations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "seller_registrations table structure:<br>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check if there are any records in the seller_registrations table
    $stmt = $db->query("SELECT COUNT(*) as count FROM seller_registrations");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Number of records in seller_registrations table: " . $count['count'] . "<br>";
    
    if ($count['count'] > 0) {
        // Get all records from the seller_registrations table
        $stmt = $db->query("SELECT id, first_name, last_name, email, phone, status, is_verified FROM seller_registrations");
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sellers in the database:<br>";
        echo "<pre>";
        print_r($sellers);
        echo "</pre>";
    }
}
?>
