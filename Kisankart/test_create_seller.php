<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';

// Get database connection with buffered queries
$database = new Database();
$db = $database->getConnection();

// Double-check that buffered queries are enabled
if ($db) {
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
}

// Create a test seller registration
$seller = new SellerRegistration($db);

// Generate unique email and phone
$unique_id = uniqid();
$seller->first_name = "Test";
$seller->last_name = "Seller";
$seller->date_of_birth = "1990-01-01";
$seller->email = "test.seller.{$unique_id}@example.com";
$seller->phone = "98765" . rand(10000, 99999);
$seller->password = "password123";
$seller->business_name = "Test Seller Business";
$seller->business_description = "This is a test seller business";
$seller->business_address = "123 Test Street, Test City";
$seller->business_country = "India";
$seller->business_state = "Karnataka";
$seller->business_city = "Bangalore";
$seller->business_postal_code = "560001";
$seller->gst_number = "GST123456789";
$seller->pan_number = "PAN123456789";
$seller->id_type = "Passport";
$seller->id_document_path = "";
$seller->tax_classification = "Individual";
$seller->tax_document_path = "";
$seller->bank_account_details = json_encode([
    "account_number" => "123456789",
    "account_holder_name" => "Test Seller",
    "ifsc_code" => "IFSC12345"
]);
$seller->bank_account_number = "123456789";
$seller->account_holder_name = "Test Seller";
$seller->ifsc_code = "IFSC12345";
$seller->bank_document_path = "";
$seller->store_display_name = "Test Seller Store";
$seller->product_categories = json_encode(["Vegetables", "Fruits"]);
$seller->marketplace = "IN";
$seller->store_logo_path = "";
$seller->status = "pending";

// Try to create the seller registration
$result = $seller->create();

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Create Seller</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #4CAF50;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .back-btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Create Seller</h1>
        
        <div class="section">
            <h2>Create Seller Registration Result</h2>
            <?php if ($result): ?>
                <div class="success">
                    <h3>Success!</h3>
                    <p>Seller registration created successfully with ID: <?php echo $seller->id; ?></p>
                    <p>Email: <?php echo $seller->email; ?></p>
                    <p>Phone: <?php echo $seller->phone; ?></p>
                </div>
            <?php else: ?>
                <div class="error">
                    <h3>Error!</h3>
                    <p>Failed to create seller registration.</p>
                    <p>Check the error logs for more details.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Seller Registration Details</h2>
            <pre><?php print_r($seller); ?></pre>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
