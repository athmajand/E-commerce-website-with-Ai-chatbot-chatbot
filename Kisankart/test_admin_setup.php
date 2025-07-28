<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

echo "<h2>Testing Admin Setup</h2>";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    echo "<p style='color: green;'>✓ Database connection successful</p>";

    // Check if admin_users table exists
    $result = $conn->query("SHOW TABLES LIKE 'admin_users'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ admin_users table exists</p>";
        
        // Check for admin users
        $admin_result = $conn->query("SELECT * FROM admin_users");
        echo "<p>Admin users count: " . $admin_result->num_rows . "</p>";
        
        if ($admin_result->num_rows > 0) {
            echo "<h3>Admin Users:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Status</th><th>Created At</th></tr>";
            
            while ($row = $admin_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3>Login Instructions:</h3>";
            echo "<p><strong>URL:</strong> <a href='admin_login.php'>http://localhost/Kisankart/admin_login.php</a></p>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
            
        } else {
            echo "<p style='color: red;'>✗ No admin users found. Creating default admin...</p>";
            
            // Create default admin user
            $default_password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_admin = "INSERT INTO admin_users (username, password, name, email) 
                             VALUES ('admin', '$default_password', 'Administrator', 'admin@kisankart.com')";
            
            if ($conn->query($insert_admin) === TRUE) {
                echo "<p style='color: green;'>✓ Default admin user created successfully!</p>";
                echo "<p><strong>Username:</strong> admin</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
            } else {
                echo "<p style='color: red;'>✗ Error creating default admin user: " . $conn->error . "</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>✗ admin_users table does not exist. Creating table...</p>";
        
        // Create admin_users table
        $sql = "CREATE TABLE admin_users (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            status ENUM('active', 'inactive') DEFAULT 'active'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green;'>✓ admin_users table created successfully!</p>";
            
            // Create default admin user
            $default_password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_admin = "INSERT INTO admin_users (username, password, name, email) 
                             VALUES ('admin', '$default_password', 'Administrator', 'admin@kisankart.com')";
            
            if ($conn->query($insert_admin) === TRUE) {
                echo "<p style='color: green;'>✓ Default admin user created successfully!</p>";
                echo "<p><strong>Username:</strong> admin</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
            } else {
                echo "<p style='color: red;'>✗ Error creating default admin user: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
        }
    }

    $conn->close();

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Troubleshooting Tips:</h3>";
echo "<ul>";
echo "<li>Make sure you're accessing the correct URL: <strong>http://localhost/Kisankart/admin_login.php</strong></li>";
echo "<li>NOT: http://localhost:8080/Kisankart/admin_login.php/admin_dashboard.php</li>";
echo "<li>Clear your browser cookies and cache</li>";
echo "<li>Make sure XAMPP/WAMP is running</li>";
echo "<li>Check that the database 'kisan_kart' exists</li>";
echo "</ul>";

echo "<h3>Quick Links:</h3>";
echo "<p><a href='admin_login.php'>Admin Login</a></p>";
echo "<p><a href='index.php'>Homepage</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}

h2, h3 {
    color: #1e8449;
}

table {
    background-color: white;
    padding: 10px;
}

th {
    background-color: #1e8449;
    color: white;
    padding: 8px;
}

td {
    padding: 8px;
}

a {
    color: #1e8449;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
