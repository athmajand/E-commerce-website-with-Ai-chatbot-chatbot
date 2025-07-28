<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create admin table
$sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Drop existing table if it exists
$conn->query("DROP TABLE IF EXISTS admin_users");
echo "Dropped existing admin_users table if it existed\n";

// Execute query to create new table
if ($conn->query($sql) === TRUE) {
    echo "Admin users table created successfully!\n";
    
    // Check if default admin exists
    $check_admin = "SELECT * FROM admin_users WHERE username = 'admin'";
    $result = $conn->query($check_admin);
    
    if ($result->num_rows == 0) {
        // Create default admin user (password: admin123)
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin = "INSERT INTO admin_users (username, password, name, email) 
                         VALUES ('admin', '$default_password', 'Administrator', 'admin@kisankart.com')";
        
        if ($conn->query($insert_admin) === TRUE) {
            echo "Default admin user created successfully!\n";
            echo "Username: admin\n";
            echo "Password: admin123\n";
            echo "Please change this password after first login for security reasons.\n";
        } else {
            echo "Error creating default admin user: " . $conn->error;
        }
    } else {
        echo "Default admin user already exists.";
    }
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>

<style>
    body {
        font-family: 'Poppins', Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 20px;
        color: #333;
    }
    
    div {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    h1 {
        color: #1e8449;
        text-align: center;
    }
    
    p {
        line-height: 1.6;
    }
    
    .success {
        color: #1e8449;
        font-weight: bold;
    }
    
    .error {
        color: #e74c3c;
        font-weight: bold;
    }
    
    .warning {
        color: #f39c12;
        font-weight: bold;
    }
    
    a {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 15px;
        background-color: #1e8449;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }
    
    a:hover {
        background-color: #166036;
    }
</style>

<div>
    <h1>Kisan Kart Admin Setup</h1>
    <p>This script creates the admin_users table in the Kisan Kart database and sets up a default admin account.</p>
    <p>After running this script, you can access the admin panel with the default credentials.</p>
    <p>For security reasons, please change the default password after your first login.</p>
    <a href="../Kisankart/">Return to Homepage</a>
</div>
