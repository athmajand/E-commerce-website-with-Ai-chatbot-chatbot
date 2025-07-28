<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

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

if (!$table_exists) {
    // Create the table if it doesn't exist
    $create_table_query = "CREATE TABLE seller_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
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
        bank_account_details TEXT,
        verification_token VARCHAR(255),
        is_verified BOOLEAN DEFAULT FALSE,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        last_login TIMESTAMP NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY email (email),
        UNIQUE KEY phone (phone)
    )";
    $results[] = executeQuery($db, $create_table_query, "Create seller_registrations table");
} else {
    // Get the current table structure
    $table_structure_query = "DESCRIBE seller_registrations";
    $table_structure_result = executeQuery($db, $table_structure_query, "Get seller_registrations table structure");
    
    // Check for existing columns
    $existing_columns = [];
    foreach ($table_structure_result['result'] as $column) {
        $existing_columns[] = $column['Field'];
    }
    
    // Define the columns that should be added
    $columns_to_add = [
        'date_of_birth' => 'ADD COLUMN date_of_birth DATE NULL AFTER last_name',
        'id_type' => 'ADD COLUMN id_type VARCHAR(50) NULL AFTER pan_number',
        'id_document_path' => 'ADD COLUMN id_document_path VARCHAR(255) NULL AFTER id_type',
        'tax_classification' => 'ADD COLUMN tax_classification VARCHAR(50) NULL AFTER id_document_path',
        'tax_document_path' => 'ADD COLUMN tax_document_path VARCHAR(255) NULL AFTER tax_classification',
        'bank_account_number' => 'ADD COLUMN bank_account_number VARCHAR(50) NULL AFTER bank_account_details',
        'account_holder_name' => 'ADD COLUMN account_holder_name VARCHAR(100) NULL AFTER bank_account_number',
        'ifsc_code' => 'ADD COLUMN ifsc_code VARCHAR(20) NULL AFTER account_holder_name',
        'bank_document_path' => 'ADD COLUMN bank_document_path VARCHAR(255) NULL AFTER ifsc_code',
        'store_display_name' => 'ADD COLUMN store_display_name VARCHAR(100) NULL AFTER bank_document_path',
        'product_categories' => 'ADD COLUMN product_categories TEXT NULL AFTER store_display_name',
        'marketplace' => 'ADD COLUMN marketplace VARCHAR(10) NULL AFTER product_categories',
        'store_logo_path' => 'ADD COLUMN store_logo_path VARCHAR(255) NULL AFTER marketplace'
    ];
    
    // Add missing columns
    foreach ($columns_to_add as $column => $alter_statement) {
        if (!in_array($column, $existing_columns)) {
            $alter_query = "ALTER TABLE seller_registrations $alter_statement";
            $results[] = executeQuery($db, $alter_query, "Add $column column");
        } else {
            $results[] = [
                'success' => true,
                'description' => "Column $column already exists",
                'affected_rows' => 0
            ];
        }
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
    
    // Add unique constraints if they don't exist
    if (!$has_email_index) {
        $add_email_index_query = "ALTER TABLE seller_registrations ADD UNIQUE INDEX email (email)";
        $results[] = executeQuery($db, $add_email_index_query, "Add unique constraint to email column");
    }
    
    if (!$has_phone_index) {
        $add_phone_index_query = "ALTER TABLE seller_registrations ADD UNIQUE INDEX phone (phone)";
        $results[] = executeQuery($db, $add_phone_index_query, "Add unique constraint to phone column");
    }
}

// Create upload directories
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

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Missing Columns to Seller Registrations Table</title>
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
        <h1>Add Missing Columns to Seller Registrations Table</h1>
        
        <div class="section">
            <h2>Database Operations</h2>
            <?php foreach ($results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <h3><?php echo htmlspecialchars($result['description']); ?>: <?php echo $result['success'] ? 'Success' : 'Error'; ?></h3>
                    
                    <?php if (isset($result['affected_rows'])): ?>
                        <p>Affected rows: <?php echo $result['affected_rows']; ?></p>
                    <?php endif; ?>
                    
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
            <h2>Next Steps</h2>
            <p>The missing columns have been added to the seller_registrations table. You can now try to register a seller again.</p>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
