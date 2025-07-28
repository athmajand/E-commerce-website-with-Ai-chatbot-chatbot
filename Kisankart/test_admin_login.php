<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Admin Login Test</h2>";

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p style='color: green;'>✓ Database connection successful</p>";

    // Test 1: Check admin_users table
    $result = $conn->query("SHOW TABLES LIKE 'admin_users'");
    if ($result->num_rows == 0) {
        throw new Exception("admin_users table does not exist");
    }
    echo "<p style='color: green;'>✓ admin_users table exists</p>";

    // Test 2: Check admin user existence
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Create default admin user
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO admin_users (username, password, name, email, status) 
                      VALUES ('admin', ?, 'Administrator', 'admin@kisankart.com', 'active')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("s", $password_hash);
        $insert_stmt->execute();
        echo "<p style='color: green;'>✓ Default admin user created</p>";
    } else {
        echo "<p style='color: green;'>✓ Admin user exists</p>";
    }

    // Test 3: Verify login functionality
    echo "<h3>Testing Login Functionality</h3>";
    echo "<p>Default credentials:</p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";

    // Clear any existing session
    $_SESSION = array();
    session_destroy();
    session_start();

    echo "<h3>Access Instructions:</h3>";
    echo "<ol>";
    echo "<li>Access the admin login at: <a href='admin_login_fixed.php'>admin_login_fixed.php</a></li>";
    echo "<li>Use the credentials above to log in</li>";
    echo "<li>You should be redirected to: <a href='admin_dashboard_fixed.php'>admin_dashboard_fixed.php</a></li>";
    echo "</ol>";

    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure you're accessing through proper URL structure (not admin_login.php/admin_dashboard.php)</li>";
    echo "<li>Clear your browser cookies if you experience any issues</li>";
    echo "<li>Check that all files are in the correct directory</li>";
    echo "</ul>";

    // Display current file structure
    echo "<h3>Current File Structure:</h3>";
    echo "<ul>";
    echo "<li>admin_login_fixed.php - Fixed login page</li>";
    echo "<li>admin_dashboard_fixed.php - Fixed dashboard page</li>";
    echo "<li>admin_logout.php - Logout script</li>";
    echo "</ul>";

    $conn->close();

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}

h2 {
    color: #1e8449;
}

h3 {
    color: #2c3e50;
    margin-top: 20px;
}

p {
    margin: 10px 0;
}

ul, ol {
    margin: 10px 0;
    padding-left: 20px;
}

a {
    color: #1e8449;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

.error {
    color: #e74c3c;
    background: #fadbd8;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}

.success {
    color: #27ae60;
    background: #d4efdf;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}
</style>
