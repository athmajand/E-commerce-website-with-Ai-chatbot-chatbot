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

// 1. Create the table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS seller_registrations (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$results[] = executeQuery($db, $create_table_query, "Create seller_registrations table if it doesn't exist");

// 2. Create temporary table for duplicate emails
$temp_emails_query = "CREATE TEMPORARY TABLE IF NOT EXISTS temp_duplicate_emails AS
SELECT email, COUNT(*) as count
FROM seller_registrations
GROUP BY email
HAVING count > 1";
$results[] = executeQuery($db, $temp_emails_query, "Create temporary table for duplicate emails");

// 3. Update duplicate emails to make them unique
$update_emails_query = "UPDATE seller_registrations sr
JOIN temp_duplicate_emails tde ON sr.email = tde.email
SET sr.email = CONCAT(sr.email, '-', sr.id)
WHERE EXISTS (SELECT 1 FROM temp_duplicate_emails WHERE email = sr.email)";
$results[] = executeQuery($db, $update_emails_query, "Update duplicate emails to make them unique");

// 4. Create temporary table for duplicate phones
$temp_phones_query = "CREATE TEMPORARY TABLE IF NOT EXISTS temp_duplicate_phones AS
SELECT phone, COUNT(*) as count
FROM seller_registrations
GROUP BY phone
HAVING count > 1";
$results[] = executeQuery($db, $temp_phones_query, "Create temporary table for duplicate phones");

// 5. Update duplicate phones to make them unique
$update_phones_query = "UPDATE seller_registrations sr
JOIN temp_duplicate_phones tdp ON sr.phone = tdp.phone
SET sr.phone = CONCAT(sr.phone, '-', sr.id)
WHERE EXISTS (SELECT 1 FROM temp_duplicate_phones WHERE phone = sr.phone)";
$results[] = executeQuery($db, $update_phones_query, "Update duplicate phones to make them unique");

// 6. Drop temporary tables
$drop_temp_emails_query = "DROP TEMPORARY TABLE IF EXISTS temp_duplicate_emails";
$results[] = executeQuery($db, $drop_temp_emails_query, "Drop temporary emails table");

$drop_temp_phones_query = "DROP TEMPORARY TABLE IF EXISTS temp_duplicate_phones";
$results[] = executeQuery($db, $drop_temp_phones_query, "Drop temporary phones table");

// 7. Get table structure
$table_structure_query = "SHOW CREATE TABLE seller_registrations";
$table_structure_result = executeQuery($db, $table_structure_query, "Get table structure");

// 8. Get existing records
$records_query = "SELECT id, first_name, last_name, email, phone, business_name, status 
FROM seller_registrations 
LIMIT 10";
$records_result = executeQuery($db, $records_query, "Get existing records");

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Fix Seller Registrations</title>
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
        <h1>Direct Fix Seller Registrations</h1>
        
        <div class="section">
            <h2>About This Page</h2>
            <p>This page directly fixes the seller_registrations table in the database using PHP code instead of an SQL script.</p>
            <p>Each query is executed separately and results are properly fetched to avoid the "Cannot execute queries while other unbuffered queries are active" error.</p>
            <p>PDO buffered queries enabled: <?php echo $db && $db->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></p>
        </div>
        
        <div class="section">
            <h2>Execution Results</h2>
            <?php foreach ($results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <h3><?php echo htmlspecialchars($result['description']); ?>: <?php echo $result['success'] ? 'Success' : 'Error'; ?></h3>
                    
                    <?php if ($result['success']): ?>
                        <?php if (isset($result['affected_rows'])): ?>
                            <p>Affected rows: <?php echo $result['affected_rows']; ?></p>
                        <?php endif; ?>
                        
                        <?php if (isset($result['result']) && !empty($result['result']) && count($result['result']) <= 5): ?>
                            <div>
                                <h4>Query Results:</h4>
                                <table>
                                    <tr>
                                        <?php foreach (array_keys($result['result'][0]) as $key): ?>
                                            <th><?php echo htmlspecialchars($key); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php foreach ($result['result'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?php echo htmlspecialchars($value); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        <?php elseif (isset($result['result']) && !empty($result['result'])): ?>
                            <p>Query returned <?php echo count($result['result']); ?> rows (not displayed for brevity)</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Error message: <?php echo htmlspecialchars($result['error']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($table_structure_result['success'] && !empty($table_structure_result['result'])): ?>
            <div class="section">
                <h2>Table Structure</h2>
                <pre><?php echo htmlspecialchars($table_structure_result['result'][0]['Create Table']); ?></pre>
            </div>
        <?php endif; ?>
        
        <?php if ($records_result['success'] && !empty($records_result['result'])): ?>
            <div class="section">
                <h2>Existing Records</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Business Name</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($records_result['result'] as $record): ?>
                        <tr>
                            <td><?php echo $record['id']; ?></td>
                            <td><?php echo $record['first_name']; ?></td>
                            <td><?php echo $record['last_name']; ?></td>
                            <td><?php echo $record['email']; ?></td>
                            <td><?php echo $record['phone']; ?></td>
                            <td><?php echo $record['business_name']; ?></td>
                            <td><?php echo $record['status']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
