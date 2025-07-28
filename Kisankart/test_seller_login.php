<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';
include_once __DIR__ . '/api/models/SellerLogin.php';

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

// Get all seller registrations
$sellers_query = "SELECT id, first_name, last_name, email, phone, status FROM seller_registrations LIMIT 10";
$sellers_result = executeQuery($db, $sellers_query, "Get all seller registrations");

// Test login functionality
$login_result = null;
$login_error = null;
$test_email = null;
$test_password = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_login'])) {
        $test_email = $_POST['email'];
        $test_password = $_POST['password'];

        try {
            // Create SellerLogin instance
            $seller_login = new SellerLogin($db);
            $seller_login->email = $test_email;
            $seller_login->password = $test_password;

            // Attempt to login
            if ($seller_login->login()) {
                $login_result = [
                    'success' => true,
                    'id' => $seller_login->id,
                    'username' => $seller_login->username,
                    'firstName' => $seller_login->firstName,
                    'lastName' => $seller_login->lastName,
                    'email' => $seller_login->email,
                    'phone' => $seller_login->phone,
                    'role' => $seller_login->role
                ];

                // Set session variables
                $_SESSION['user_id'] = $seller_login->id;
                $_SESSION['username'] = $seller_login->username;
                $_SESSION['firstName'] = $seller_login->firstName;
                $_SESSION['lastName'] = $seller_login->lastName;
                $_SESSION['email'] = $seller_login->email;
                $_SESSION['phone'] = $seller_login->phone;
                $_SESSION['user_role'] = $seller_login->role;
            } else {
                $login_result = [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
        } catch (Exception $e) {
            $login_error = $e->getMessage();
        }
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Seller Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --accent-color: #FF9800;
            --text-color: #333333;
            --text-light: #757575;
            --background-color: #f5fff5;
            --white: #ffffff;
            --error-color: #D32F2F;
            --success-color: #388E3C;
            --border-color: #E0E0E0;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            box-sizing: border-box;
            background-color: var(--background-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }

        .card-body {
            padding: 20px;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid var(--border-color);
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid var(--border-color);
        }

        .success-text {
            color: var(--success-color);
        }

        .error-text {
            color: var(--error-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Test Seller Login</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Login Test</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="test_login" class="btn btn-success">Test Login</button>
                        </form>

                        <?php if ($login_result): ?>
                            <div class="mt-4">
                                <h5>Login Result:</h5>
                                <?php if ($login_result['success']): ?>
                                    <div class="alert alert-success">
                                        <p><strong>Login Successful!</strong></p>
                                        <p>ID: <?php echo $login_result['id']; ?></p>
                                        <p>Name: <?php echo $login_result['firstName'] . ' ' . $login_result['lastName']; ?></p>
                                        <p>Email: <?php echo $login_result['email']; ?></p>
                                        <p>Phone: <?php echo $login_result['phone']; ?></p>
                                        <p>Role: <?php echo $login_result['role']; ?></p>
                                    </div>
                                    <div class="mt-3">
                                        <a href="frontend/seller/dashboard.html" class="btn btn-primary">Go to Seller Dashboard</a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        <p><strong>Login Failed!</strong></p>
                                        <p><?php echo $login_result['message']; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($login_error): ?>
                            <div class="mt-4 alert alert-danger">
                                <p><strong>Error:</strong> <?php echo $login_error; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Available Sellers</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($sellers_result['success'] && !empty($sellers_result['result'])): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sellers_result['result'] as $seller): ?>
                                            <tr>
                                                <td><?php echo $seller['id']; ?></td>
                                                <td><?php echo $seller['first_name'] . ' ' . $seller['last_name']; ?></td>
                                                <td><?php echo $seller['email']; ?></td>
                                                <td><?php echo $seller['phone']; ?></td>
                                                <td><?php echo $seller['status']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No sellers found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="seller_login.php" class="btn btn-primary">Go to Seller Login Page</a>
            <a href="seller_registration.php" class="btn btn-success">Go to Seller Registration Page</a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
