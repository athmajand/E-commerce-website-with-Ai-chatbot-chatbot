<?php
// Initialize variables
$success = false;
$error = null;
$message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_table'])) {
    // Include database configuration
    include_once __DIR__ . '/api/config/database.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_customer_registrations_table.sql');

    // Execute SQL
    try {
        // Execute the SQL directly
        $result = $db->exec($sql);

        // Check if table exists
        $check_query = "SHOW TABLES LIKE 'customer_registrations'";
        $stmt = $db->prepare($check_query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $success = true;
            $message = "Customer registrations table created or already exists!";
        } else {
            $success = false;
            $message = "Error: Table was not created successfully.";
        }
    } catch (PDOException $e) {
        $success = false;
        $message = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Customer Registrations Table - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4CAF50;
            --accent-color: #FF9800;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-header h1 {
            color: var(--primary-color);
            font-weight: 600;
        }
        .setup-content {
            margin-bottom: 30px;
        }
        .setup-footer {
            text-align: center;
            margin-top: 30px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #3d8b40;
            border-color: #3d8b40;
        }
        .alert-success {
            background-color: #e8f5e9;
            border-color: #c8e6c9;
            color: #2e7d32;
        }
        .alert-danger {
            background-color: #ffebee;
            border-color: #ffcdd2;
            color: #c62828;
        }
        .code-block {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="frontend/index.html">Kisan Kart</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.html#products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.html#about">About Us</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container setup-container">
        <div class="setup-header">
            <h1><i class="fas fa-database"></i> Setup Customer Registrations Table</h1>
            <p class="text-muted">This utility will create the customer_registrations table in your database</p>
        </div>

        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_table'])): ?>
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="setup-content">
            <h4>Table Structure</h4>
            <div class="code-block">
                <pre>CREATE TABLE IF NOT EXISTS customer_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verification_token VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</pre>
            </div>

            <h4>Create Table</h4>
            <p>Click the button below to create the customer_registrations table in your database:</p>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <button type="submit" name="create_table" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create Table
                </button>
            </form>
        </div>

        <div class="setup-footer">
            <a href="login.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
            <a href="customer_registration.php" class="btn btn-outline-success">
                <i class="fas fa-user-plus"></i> Go to Registration
            </a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
