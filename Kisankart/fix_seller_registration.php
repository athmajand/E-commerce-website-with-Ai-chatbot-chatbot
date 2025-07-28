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
function executeQuery($db, $query, $description) {
    try {
        // For SELECT queries, use query() and fetchAll()
        if (stripos(trim($query), 'SELECT') === 0 || 
            stripos(trim($query), 'SHOW') === 0 || 
            stripos(trim($query), 'DESCRIBE') === 0) {
            $stmt = $db->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'success' => true,
                'description' => $description,
                'result' => $result
            ];
        } else {
            // For non-SELECT queries, use exec()
            $result = $db->exec($query);
            return [
                'success' => true,
                'description' => $description,
                'affected_rows' => $result
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'description' => $description,
            'error' => $e->getMessage()
        ];
    }
}

// Array to store results
$results = [];

// 1. Check if the table exists
$table_check_query = "SHOW TABLES LIKE 'seller_registrations'";
$table_check_result = executeQuery($db, $table_check_query, "Check if seller_registrations table exists");
$table_exists = !empty($table_check_result['result']);

// 2. Create the table if it doesn't exist
if (!$table_exists) {
    $create_table_query = "CREATE TABLE seller_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        date_of_birth DATE NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(15) NOT NULL,
        password VARCHAR(255) NOT NULL,
        business_name VARCHAR(100) NOT NULL,
        business_description TEXT,
        business_logo VARCHAR(255),
        business_address TEXT NOT NULL,
        business_country VARCHAR(50),
        business_state VARCHAR(100),
        business_city VARCHAR(100),
        business_postal_code VARCHAR(20),
        gst_number VARCHAR(50),
        pan_number VARCHAR(50),
        id_type VARCHAR(50),
        id_document_path VARCHAR(255),
        tax_classification VARCHAR(50),
        tax_document_path VARCHAR(255),
        bank_account_details TEXT,
        bank_account_number VARCHAR(50),
        account_holder_name VARCHAR(100),
        ifsc_code VARCHAR(20),
        bank_document_path VARCHAR(255),
        store_display_name VARCHAR(100),
        product_categories TEXT,
        marketplace VARCHAR(10),
        store_logo_path VARCHAR(255),
        verification_token VARCHAR(255),
        is_verified BOOLEAN DEFAULT FALSE,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        last_login TIMESTAMP NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $results[] = executeQuery($db, $create_table_query, "Create seller_registrations table");
}

// 3. Check if email and phone have unique constraints
$index_check_query = "SHOW INDEX FROM seller_registrations WHERE Column_name IN ('email', 'phone')";
$index_check_result = executeQuery($db, $index_check_query, "Check if email and phone have unique constraints");

$has_email_index = false;
$has_phone_index = false;

if ($index_check_result['success'] && !empty($index_check_result['result'])) {
    foreach ($index_check_result['result'] as $index) {
        if ($index['Column_name'] == 'email') {
            $has_email_index = true;
        }
        if ($index['Column_name'] == 'phone') {
            $has_phone_index = true;
        }
    }
}

// 4. Add unique constraints if they don't exist
if (!$has_email_index) {
    $add_email_index_query = "ALTER TABLE seller_registrations ADD UNIQUE INDEX email (email)";
    $results[] = executeQuery($db, $add_email_index_query, "Add unique constraint to email column");
}

if (!$has_phone_index) {
    $add_phone_index_query = "ALTER TABLE seller_registrations ADD UNIQUE INDEX phone (phone)";
    $results[] = executeQuery($db, $add_phone_index_query, "Add unique constraint to phone column");
}

// 5. Check if upload directories exist and create them if they don't
$upload_dirs = [
    'uploads',
    'uploads/seller',
    'uploads/seller/id_documents',
    'uploads/seller/tax_documents',
    'uploads/seller/bank_documents',
    'uploads/seller/store_logos'
];

$upload_results = [];
foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (!file_exists($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            $upload_results[] = [
                'success' => true,
                'description' => "Created directory: $dir"
            ];
        } else {
            $upload_results[] = [
                'success' => false,
                'description' => "Failed to create directory: $dir",
                'error' => "Permission denied or other error"
            ];
        }
    } else {
        $upload_results[] = [
            'success' => true,
            'description' => "Directory already exists: $dir"
        ];
    }
}

// 6. Test the SellerRegistration model
$model_test_result = [];
try {
    $seller = new SellerRegistration($db);
    $structure_validation = $seller->validateTableStructure();
    $model_test_result = [
        'success' => $structure_validation['valid'],
        'description' => "Test SellerRegistration model",
        'message' => $structure_validation['message']
    ];
} catch (Exception $e) {
    $model_test_result = [
        'success' => false,
        'description' => "Test SellerRegistration model",
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
    <title>Fix Seller Registration</title>
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
        <h1>Fix Seller Registration</h1>
        
        <div class="section">
            <h2>Database Table Structure</h2>
            <?php foreach ($results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <h3><?php echo htmlspecialchars($result['description']); ?>: <?php echo $result['success'] ? 'Success' : 'Error'; ?></h3>
                    
                    <?php if (!$result['success']): ?>
                        <p>Error message: <?php echo htmlspecialchars($result['error']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h2>Upload Directories</h2>
            <?php foreach ($upload_results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <p><?php echo htmlspecialchars($result['description']); ?></p>
                    
                    <?php if (!$result['success']): ?>
                        <p>Error message: <?php echo htmlspecialchars($result['error']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h2>SellerRegistration Model Test</h2>
            <div class="<?php echo $model_test_result['success'] ? 'success' : 'error'; ?>">
                <h3><?php echo htmlspecialchars($model_test_result['description']); ?>: <?php echo $model_test_result['success'] ? 'Success' : 'Error'; ?></h3>
                
                <?php if ($model_test_result['success']): ?>
                    <p>Message: <?php echo htmlspecialchars($model_test_result['message']); ?></p>
                <?php else: ?>
                    <p>Error message: <?php echo htmlspecialchars($model_test_result['error'] ?? $model_test_result['message']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Next Steps</h2>
            <p>If all tests passed, try registering a seller again. If you still encounter issues, check the error logs for more details.</p>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
