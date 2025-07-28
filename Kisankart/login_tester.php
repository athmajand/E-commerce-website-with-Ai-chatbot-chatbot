<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Login Tester</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #1e8449; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        form { margin-top: 20px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 8px; margin-top: 5px; }
        button { background: #1e8449; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin-top: 15px; }
        button:hover { background: #166938; }
    </style>
</head>
<body>
    <h1>Login Tester</h1>';

// Include database and models
include_once 'api/config/database.php';
include_once 'api/models/User.php';
include_once 'api/models/CustomerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '<div class="section">
        <p class="error">Database connection failed. Please check your MySQL connection.</p>
    </div>';
} else {
    echo '<div class="section">
        <p class="success">Database connection successful.</p>
    </div>';
    
    // Check if customer_registrations table exists
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'customer_registrations'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<div class="section">
                <p class="error">The customer_registrations table does not exist.</p>
                <p><a href="create_database.php"><button>Create Database Tables</button></a></p>
            </div>';
        } else {
            echo '<div class="section">
                <p class="success">The customer_registrations table exists.</p>';
            
            // Check table structure
            $stmt = $db->query("DESCRIBE customer_registrations");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo '<p>Table columns: ' . implode(', ', $columns) . '</p>';
            
            // Check if there are any records
            $stmt = $db->query("SELECT COUNT(*) FROM customer_registrations");
            $count = $stmt->fetchColumn();
            
            echo '<p>Number of records: ' . $count . '</p>';
            
            if ($count > 0) {
                // Show first record for debugging
                $stmt = $db->query("SELECT * FROM customer_registrations LIMIT 1");
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo '<p>Sample record:</p>';
                echo '<div class="code">';
                foreach ($record as $key => $value) {
                    if ($key === 'password') {
                        echo "$key: [HIDDEN]\n";
                    } else {
                        echo "$key: $value\n";
                    }
                }
                echo '</div>';
                
                // Check if any records are verified and approved
                $stmt = $db->query("SELECT COUNT(*) FROM customer_registrations WHERE is_verified = 1 AND status = 'approved'");
                $verifiedCount = $stmt->fetchColumn();
                
                if ($verifiedCount > 0) {
                    echo '<p class="success">Found ' . $verifiedCount . ' verified and approved records.</p>';
                } else {
                    echo '<p class="warning">No verified and approved records found. Let\'s update the first record.</p>';
                    
                    // Update the first record to be verified and approved
                    $stmt = $db->prepare("UPDATE customer_registrations SET is_verified = 1, status = 'approved' WHERE id = ?");
                    $stmt->execute([$record['id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo '<p class="success">Updated record ID ' . $record['id'] . ' to be verified and approved.</p>';
                    } else {
                        echo '<p class="error">Failed to update record.</p>';
                    }
                }
            }
            
            echo '</div>';
            
            // Test login form
            echo '<div class="section">
                <h2>Test Login</h2>
                <form method="post" action="login_tester.php">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    
                    <button type="submit" name="test_login">Test Login</button>
                </form>
            </div>';
            
            // Process test login
            if (isset($_POST['test_login'])) {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                echo '<div class="section">
                    <h2>Login Test Results</h2>';
                
                if (empty($email) || empty($password)) {
                    echo '<p class="error">Email and password are required.</p>';
                } else {
                    echo '<p>Testing login with email: ' . $email . '</p>';
                    
                    // Test with User model
                    $user = new User($db);
                    $user->username = $email; // Using username property to store email
                    $user->password = $password;
                    
                    echo '<h3>Testing with User model:</h3>';
                    
                    // Enable detailed logging
                    echo '<div class="code">';
                    
                    // Check if table exists
                    $tableExists = $user->tableExists();
                    echo "Table exists check: " . ($tableExists ? "Yes" : "No") . "\n";
                    
                    // Get the record directly for debugging
                    $stmt = $db->prepare("SELECT * FROM customer_registrations WHERE email = ?");
                    $stmt->execute([$email]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($record) {
                        echo "Found record with email: $email\n";
                        echo "Record ID: {$record['id']}\n";
                        echo "Name: {$record['first_name']} {$record['last_name']}\n";
                        echo "Status: {$record['status']}\n";
                        echo "Verified: " . ($record['is_verified'] ? "Yes" : "No") . "\n";
                        
                        // Test password verification
                        $stored_hash = $record['password'];
                        $password_verified = password_verify($password, $stored_hash);
                        
                        echo "Password verification: " . ($password_verified ? "Success" : "Failed") . "\n";
                        
                        if (!$password_verified) {
                            echo "Stored hash: $stored_hash\n";
                            echo "Testing with different hash algorithms...\n";
                            
                            // Try with different algorithms
                            echo "MD5: " . (md5($password) === $stored_hash ? "Match" : "No match") . "\n";
                            echo "SHA1: " . (sha1($password) === $stored_hash ? "Match" : "No match") . "\n";
                            
                            // Create a new hash for reference
                            $new_hash = password_hash($password, PASSWORD_BCRYPT);
                            echo "New bcrypt hash for '$password': $new_hash\n";
                            
                            // Update password if needed
                            echo "\nWould you like to update the password? ";
                            echo '<form method="post" action="login_tester.php" style="display:inline;">
                                <input type="hidden" name="update_id" value="' . $record['id'] . '">
                                <input type="hidden" name="update_email" value="' . $email . '">
                                <input type="hidden" name="update_password" value="' . $password . '">
                                <button type="submit" name="update_password">Update Password</button>
                            </form>';
                        }
                    } else {
                        echo "No record found with email: $email\n";
                    }
                    
                    echo '</div>';
                    
                    // Try the actual login
                    $login_result = $user->login();
                    
                    if ($login_result) {
                        echo '<p class="success">Login successful with User model!</p>';
                        echo '<p>User details:</p>';
                        echo '<div class="code">';
                        echo "ID: {$user->id}\n";
                        echo "Username: {$user->username}\n";
                        echo "First Name: {$user->firstName}\n";
                        echo "Last Name: {$user->lastName}\n";
                        echo "Email: {$user->email}\n";
                        echo "Phone: {$user->phone}\n";
                        echo "Role: {$user->role}\n";
                        echo '</div>';
                    } else {
                        echo '<p class="error">Login failed with User model.</p>';
                    }
                    
                    // Test with CustomerRegistration model
                    $customer_registration = new CustomerRegistration($db);
                    $customer_registration->email = $email;
                    $customer_registration->password = $password;
                    
                    echo '<h3>Testing with CustomerRegistration model:</h3>';
                    
                    $login_result = $customer_registration->loginWithEmail();
                    
                    if ($login_result) {
                        echo '<p class="success">Login successful with CustomerRegistration model!</p>';
                        echo '<p>Customer details:</p>';
                        echo '<div class="code">';
                        echo "ID: {$customer_registration->id}\n";
                        echo "Name: {$customer_registration->first_name} {$customer_registration->last_name}\n";
                        echo "Email: {$customer_registration->email}\n";
                        echo "Phone: {$customer_registration->phone}\n";
                        echo '</div>';
                    } else {
                        echo '<p class="error">Login failed with CustomerRegistration model.</p>';
                    }
                }
                
                echo '</div>';
            }
            
            // Process password update
            if (isset($_POST['update_password'])) {
                $id = $_POST['update_id'] ?? '';
                $email = $_POST['update_email'] ?? '';
                $password = $_POST['update_password'] ?? '';
                
                if (!empty($id) && !empty($password)) {
                    // Hash the password
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Update the password
                    $stmt = $db->prepare("UPDATE customer_registrations SET password = ? WHERE id = ?");
                    $result = $stmt->execute([$password_hash, $id]);
                    
                    echo '<div class="section">';
                    if ($result) {
                        echo '<p class="success">Password updated successfully for ID ' . $id . ' (' . $email . ').</p>';
                    } else {
                        echo '<p class="error">Failed to update password.</p>';
                    }
                    echo '</div>';
                }
            }
        }
    } catch (PDOException $e) {
        echo '<div class="section">
            <p class="error">Database error: ' . $e->getMessage() . '</p>
        </div>';
    }
}

echo '</body>
</html>';
?>
