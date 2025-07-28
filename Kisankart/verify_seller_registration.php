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

// Function to execute a query and handle errors
function executeQuery($db, $query, $description, $params = []) {
    try {
        $stmt = $db->prepare($query);
        
        // Bind parameters if provided
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        // For SELECT queries, fetch results
        if (stripos(trim($query), 'SELECT') === 0 || 
            stripos(trim($query), 'SHOW') === 0 || 
            stripos(trim($query), 'DESCRIBE') === 0) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'success' => true,
                'description' => $description,
                'result' => $result,
                'row_count' => $stmt->rowCount()
            ];
        } else {
            // For non-SELECT queries, return affected rows
            return [
                'success' => true,
                'description' => $description,
                'affected_rows' => $stmt->rowCount()
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'description' => $description,
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        ];
    }
}

// Check table structure
$table_structure_query = "DESCRIBE seller_registrations";
$table_structure_result = executeQuery($db, $table_structure_query, "Get seller_registrations table structure");

// Test creating a seller registration
$test_create_result = [];
try {
    // Generate unique email and phone
    $unique_id = uniqid();
    $test_email = "test.seller.{$unique_id}@example.com";
    $test_phone = "98765" . rand(10000, 99999);
    
    // Create a seller registration object
    $seller = new SellerRegistration($db);
    
    // Set seller registration properties
    $seller->first_name = "Test";
    $seller->last_name = "Seller";
    $seller->date_of_birth = "1990-01-01";
    $seller->email = $test_email;
    $seller->phone = $test_phone;
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
    $seller->id_document_path = "/uploads/seller/id_documents/test.jpg";
    $seller->tax_classification = "Individual";
    $seller->tax_document_path = "/uploads/seller/tax_documents/test.jpg";
    $seller->bank_account_details = json_encode([
        "account_number" => "123456789",
        "account_holder_name" => "Test Seller",
        "ifsc_code" => "IFSC12345"
    ]);
    $seller->bank_account_number = "123456789";
    $seller->account_holder_name = "Test Seller";
    $seller->ifsc_code = "IFSC12345";
    $seller->bank_document_path = "/uploads/seller/bank_documents/test.jpg";
    $seller->store_display_name = "Test Seller Store";
    $seller->product_categories = json_encode(["Vegetables", "Fruits"]);
    $seller->marketplace = "IN";
    $seller->store_logo_path = "/uploads/seller/store_logos/test.jpg";
    $seller->status = "pending";
    
    // Create the seller registration
    $create_success = $seller->create();
    
    $test_create_result = [
        'success' => $create_success,
        'description' => "Test SellerRegistration create method",
        'seller_id' => $seller->id ?? null,
        'email' => $test_email,
        'phone' => $test_phone
    ];
    
    if (!$create_success) {
        // Check for database error
        $db_error = $db->errorInfo();
        if (!empty($db_error[2])) {
            $test_create_result['db_error'] = $db_error[2];
        }
    }
} catch (Exception $e) {
    $test_create_result = [
        'success' => false,
        'description' => "Test SellerRegistration create method",
        'error' => $e->getMessage()
    ];
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Seller Registration</title>
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
            max-width: 1200px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
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
        <h1>Verify Seller Registration</h1>
        
        <div class="section">
            <h2>Table Structure</h2>
            <?php if ($table_structure_result['success']): ?>
                <table>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                    <?php foreach ($table_structure_result['result'] as $column): ?>
                        <tr>
                            <td><?php echo $column['Field']; ?></td>
                            <td><?php echo $column['Type']; ?></td>
                            <td><?php echo $column['Null']; ?></td>
                            <td><?php echo $column['Key']; ?></td>
                            <td><?php echo $column['Default']; ?></td>
                            <td><?php echo $column['Extra']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="error">
                    <p>Error getting table structure: <?php echo htmlspecialchars($table_structure_result['error']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Test Create Seller Registration</h2>
            <div class="<?php echo $test_create_result['success'] ? 'success' : 'error'; ?>">
                <h3><?php echo htmlspecialchars($test_create_result['description']); ?>: <?php echo $test_create_result['success'] ? 'Success' : 'Error'; ?></h3>
                
                <?php if ($test_create_result['success']): ?>
                    <p>Seller ID: <?php echo $test_create_result['seller_id']; ?></p>
                    <p>Email: <?php echo $test_create_result['email']; ?></p>
                    <p>Phone: <?php echo $test_create_result['phone']; ?></p>
                <?php else: ?>
                    <?php if (isset($test_create_result['error'])): ?>
                        <p>Error: <?php echo htmlspecialchars($test_create_result['error']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($test_create_result['db_error'])): ?>
                        <p>Database Error: <?php echo htmlspecialchars($test_create_result['db_error']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Next Steps</h2>
            <?php if ($test_create_result['success']): ?>
                <p>The seller registration functionality is working correctly. You can now register sellers through the registration form.</p>
            <?php else: ?>
                <p>There are still issues with the seller registration functionality. Please check the error messages above and fix the issues.</p>
            <?php endif; ?>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
