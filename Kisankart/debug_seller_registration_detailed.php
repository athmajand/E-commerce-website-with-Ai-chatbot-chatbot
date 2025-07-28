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

// Array to store results
$results = [];

// 1. Check if the table exists
$table_check_query = "SHOW TABLES LIKE 'seller_registrations'";
$table_check_result = executeQuery($db, $table_check_query, "Check if seller_registrations table exists");
$table_exists = !empty($table_check_result['result']);

// 2. If table exists, check its structure
if ($table_exists) {
    $table_structure_query = "DESCRIBE seller_registrations";
    $table_structure_result = executeQuery($db, $table_structure_query, "Get seller_registrations table structure");
    
    // Check for required columns
    $required_columns = [
        'id', 'first_name', 'last_name', 'email', 'phone', 'password',
        'business_name', 'business_address', 'status'
    ];
    
    $existing_columns = [];
    foreach ($table_structure_result['result'] as $column) {
        $existing_columns[] = $column['Field'];
    }
    
    $missing_columns = array_diff($required_columns, $existing_columns);
    
    if (!empty($missing_columns)) {
        $results[] = [
            'success' => false,
            'description' => "Missing required columns",
            'missing_columns' => $missing_columns
        ];
    } else {
        $results[] = [
            'success' => true,
            'description' => "All required columns exist"
        ];
    }
    
    // Check for unique constraints on email and phone
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
    
    if (!$has_email_index) {
        $results[] = [
            'success' => false,
            'description' => "Missing unique constraint on email column"
        ];
    }
    
    if (!$has_phone_index) {
        $results[] = [
            'success' => false,
            'description' => "Missing unique constraint on phone column"
        ];
    }
} else {
    $results[] = [
        'success' => false,
        'description' => "seller_registrations table does not exist"
    ];
}

// 3. Test the SellerRegistration model
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

// 4. Test creating a seller registration with minimal data
$test_create_result = [];
try {
    // Generate unique email and phone
    $unique_id = uniqid();
    $test_email = "test.seller.{$unique_id}@example.com";
    $test_phone = "98765" . rand(10000, 99999);
    
    // Create a direct SQL insert query
    $insert_query = "INSERT INTO seller_registrations 
                    (first_name, last_name, email, phone, password, business_name, business_address, status) 
                    VALUES 
                    (:first_name, :last_name, :email, :phone, :password, :business_name, :business_address, :status)";
    
    $params = [
        ':first_name' => 'Test',
        ':last_name' => 'Seller',
        ':email' => $test_email,
        ':phone' => $test_phone,
        ':password' => password_hash('password123', PASSWORD_BCRYPT),
        ':business_name' => 'Test Business',
        ':business_address' => 'Test Address',
        ':status' => 'pending'
    ];
    
    $test_create_result = executeQuery($db, $insert_query, "Test direct SQL insert", $params);
    
    if ($test_create_result['success']) {
        // Try to retrieve the inserted record
        $select_query = "SELECT * FROM seller_registrations WHERE email = :email";
        $select_params = [':email' => $test_email];
        $select_result = executeQuery($db, $select_query, "Verify inserted record", $select_params);
        
        $test_create_result['verification'] = $select_result;
    }
} catch (Exception $e) {
    $test_create_result = [
        'success' => false,
        'description' => "Test direct SQL insert",
        'error' => $e->getMessage()
    ];
}

// 5. Test the SellerRegistration create method
$model_create_result = [];
try {
    // Generate unique email and phone
    $unique_id = uniqid();
    $test_email = "test.model.{$unique_id}@example.com";
    $test_phone = "98765" . rand(10000, 99999);
    
    $seller = new SellerRegistration($db);
    $seller->first_name = "Test";
    $seller->last_name = "Model";
    $seller->email = $test_email;
    $seller->phone = $test_phone;
    $seller->password = "password123";
    $seller->business_name = "Test Model Business";
    $seller->business_address = "Test Model Address";
    $seller->status = "pending";
    
    $create_success = $seller->create();
    
    $model_create_result = [
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
            $model_create_result['db_error'] = $db_error[2];
        }
    }
} catch (Exception $e) {
    $model_create_result = [
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
    <title>Detailed Seller Registration Debug</title>
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
        <h1>Detailed Seller Registration Debug</h1>
        
        <div class="section">
            <h2>Table Structure Check</h2>
            <?php foreach ($results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <h3><?php echo htmlspecialchars($result['description']); ?>: <?php echo $result['success'] ? 'Success' : 'Error'; ?></h3>
                    
                    <?php if (isset($result['missing_columns'])): ?>
                        <p>Missing columns: <?php echo implode(', ', $result['missing_columns']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h2>SellerRegistration Model Test</h2>
            <div class="<?php echo $model_test_result['success'] ? 'success' : 'error'; ?>">
                <h3><?php echo htmlspecialchars($model_test_result['description']); ?>: <?php echo $model_test_result['success'] ? 'Success' : 'Error'; ?></h3>
                
                <?php if (isset($model_test_result['message'])): ?>
                    <p>Message: <?php echo htmlspecialchars($model_test_result['message']); ?></p>
                <?php endif; ?>
                
                <?php if (isset($model_test_result['error'])): ?>
                    <p>Error: <?php echo htmlspecialchars($model_test_result['error']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Direct SQL Insert Test</h2>
            <div class="<?php echo $test_create_result['success'] ? 'success' : 'error'; ?>">
                <h3><?php echo htmlspecialchars($test_create_result['description']); ?>: <?php echo $test_create_result['success'] ? 'Success' : 'Error'; ?></h3>
                
                <?php if (isset($test_create_result['affected_rows'])): ?>
                    <p>Affected rows: <?php echo $test_create_result['affected_rows']; ?></p>
                <?php endif; ?>
                
                <?php if (isset($test_create_result['error'])): ?>
                    <p>Error: <?php echo htmlspecialchars($test_create_result['error']); ?></p>
                <?php endif; ?>
                
                <?php if (isset($test_create_result['verification']) && $test_create_result['verification']['success']): ?>
                    <h4>Verification:</h4>
                    <p>Record found: <?php echo $test_create_result['verification']['row_count'] > 0 ? 'Yes' : 'No'; ?></p>
                    
                    <?php if ($test_create_result['verification']['row_count'] > 0): ?>
                        <pre><?php print_r($test_create_result['verification']['result'][0]); ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>SellerRegistration Create Method Test</h2>
            <div class="<?php echo $model_create_result['success'] ? 'success' : 'error'; ?>">
                <h3><?php echo htmlspecialchars($model_create_result['description']); ?>: <?php echo $model_create_result['success'] ? 'Success' : 'Error'; ?></h3>
                
                <?php if ($model_create_result['success']): ?>
                    <p>Seller ID: <?php echo $model_create_result['seller_id']; ?></p>
                    <p>Email: <?php echo $model_create_result['email']; ?></p>
                    <p>Phone: <?php echo $model_create_result['phone']; ?></p>
                <?php else: ?>
                    <?php if (isset($model_create_result['error'])): ?>
                        <p>Error: <?php echo htmlspecialchars($model_create_result['error']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($model_create_result['db_error'])): ?>
                        <p>Database Error: <?php echo htmlspecialchars($model_create_result['db_error']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
