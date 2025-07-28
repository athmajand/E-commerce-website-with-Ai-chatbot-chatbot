<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Include database configuration
include_once 'api/config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Check if connection was successful
if (!$db) {
    die("<div class='alert alert-danger'>Database connection failed</div>");
}

// SQL to create the customer_logins table
$sql = "
CREATE TABLE IF NOT EXISTS customer_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) UNIQUE,
    password VARCHAR(255) NOT NULL,
    customer_profile_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_profile_id) REFERENCES customer_profiles(id) ON DELETE CASCADE
);

-- Add index for faster login queries
CREATE INDEX idx_customer_logins_email ON customer_logins(email);
CREATE INDEX idx_customer_logins_phone ON customer_logins(phone);
";

try {
    // Execute SQL
    $db->exec($sql);
    echo "<div class='alert alert-success'>Customer logins table created successfully!</div>";
    
    // Check if the table was created
    $check_query = "SHOW TABLES LIKE 'customer_logins'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    $table_exists = ($stmt->rowCount() > 0);
    
    if ($table_exists) {
        echo "<div class='alert alert-info'>Table customer_logins exists in the database.</div>";
    } else {
        echo "<div class='alert alert-warning'>Table customer_logins was not created.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error creating table: " . $e->getMessage() . "</div>";
}
?>

<html>
<head>
    <title>Create Customer Logins Table</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Create Customer Logins Table</h1>
        <a href="customer_registration.php" class="btn btn-primary mt-3">Go to Customer Registration</a>
    </div>
</body>
</html>
