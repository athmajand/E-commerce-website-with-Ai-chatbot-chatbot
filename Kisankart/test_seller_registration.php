<?php
// This script simulates a form submission to test the seller registration functionality

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include necessary files
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection was successful
if (!$db) {
    die("Database connection failed. Please check your database configuration.");
}

echo "Database connection successful.<br>";

// Create test data
$test_data = [
    'first_name' => 'Test',
    'last_name' => 'Seller',
    'email' => 'test.seller@example.com',
    'phone' => '9876543210',
    'password' => 'password123',
    'business_name' => 'Test Seller Business',
    'business_description' => 'This is a test seller business',
    'business_logo' => '',
    'business_address' => '123 Test Street, Test City',
    'business_country' => 'India',
    'business_state' => 'Karnataka',
    'business_city' => 'Bangalore',
    'business_postal_code' => '560001',
    'gst_number' => 'GST123456789',
    'pan_number' => 'PAN123456789',
    'bank_account_details' => 'Test Bank Account'
];

// Try direct database insertion
try {
    // Generate a unique email and phone
    $unique_id = uniqid();
    $email = "test.seller.{$unique_id}@example.com";
    $phone = "98765" . rand(10000, 99999);

    // Hash the password
    $password_hash = password_hash($test_data['password'], PASSWORD_BCRYPT);

    // Prepare the query
    $query = "INSERT INTO seller_registrations
              (first_name, last_name, email, phone, password, business_name, business_description, business_address,
               business_country, business_state, business_city, business_postal_code, gst_number, pan_number,
               bank_account_details, status)
              VALUES
              (:first_name, :last_name, :email, :phone, :password, :business_name, :business_description, :business_address,
               :business_country, :business_state, :business_city, :business_postal_code, :gst_number, :pan_number,
               :bank_account_details, :status)";

    $stmt = $db->prepare($query);

    // Bind parameters
    $stmt->bindParam(':first_name', $test_data['first_name']);
    $stmt->bindParam(':last_name', $test_data['last_name']);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password', $password_hash);
    $stmt->bindParam(':business_name', $test_data['business_name']);
    $stmt->bindParam(':business_description', $test_data['business_description']);
    $stmt->bindParam(':business_address', $test_data['business_address']);
    $stmt->bindParam(':business_country', $test_data['business_country']);
    $stmt->bindParam(':business_state', $test_data['business_state']);
    $stmt->bindParam(':business_city', $test_data['business_city']);
    $stmt->bindParam(':business_postal_code', $test_data['business_postal_code']);
    $stmt->bindParam(':gst_number', $test_data['gst_number']);
    $stmt->bindParam(':pan_number', $test_data['pan_number']);
    $stmt->bindParam(':bank_account_details', $test_data['bank_account_details']);
    $status = 'pending';
    $stmt->bindParam(':status', $status);

    // Execute the query
    if ($stmt->execute()) {
        $seller_id = $db->lastInsertId();
        echo "Direct insertion successful!<br>";
        echo "Seller ID: " . $seller_id . "<br>";

        // Update test data with the new email and phone
        $test_data['email'] = $email;
        $test_data['phone'] = $phone;
    } else {
        echo "Direct insertion failed.<br>";
        echo "Error: " . print_r($stmt->errorInfo(), true) . "<br>";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Test seller login
echo "<h2>Testing Seller Login</h2>";

// Try direct login using PDO
try {
    // Query to check if email exists
    $query = "SELECT * FROM seller_registrations WHERE email = :email LIMIT 0,1";

    // Prepare query
    $stmt = $db->prepare($query);

    // Bind email
    $stmt->bindParam(':email', $test_data['email']);

    // Execute query
    $stmt->execute();

    // Get row count
    $num = $stmt->rowCount();

    // If email exists
    if ($num > 0) {
        // Get record details
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if (password_verify($test_data['password'], $row['password'])) {
            echo "Direct login successful!<br>";
            echo "Seller ID: " . $row['id'] . "<br>";
            echo "Seller Name: " . $row['first_name'] . " " . $row['last_name'] . "<br>";
            echo "Seller Email: " . $row['email'] . "<br>";
            echo "Seller Phone: " . $row['phone'] . "<br>";
            echo "Seller Role: seller<br>";

            // Update last login time
            $update_query = "UPDATE seller_registrations SET last_login = NOW() WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $row['id']);
            $update_stmt->execute();
        } else {
            echo "Direct login failed. Invalid password.<br>";
        }
    } else {
        echo "Direct login failed. Email not found.<br>";
    }
} catch (PDOException $e) {
    echo "Login error: " . $e->getMessage() . "<br>";
}
?>
