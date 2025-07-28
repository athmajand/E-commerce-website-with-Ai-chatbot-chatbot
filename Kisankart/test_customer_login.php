<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Include database and models
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/CustomerLogin.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$test_results = [];
$error = null;

// Test data
$test_email = "hemanth@gmail.com"; // Replace with a valid email from your database
$test_phone = ""; // Replace with a valid phone from your database
$test_password = "password123"; // Replace with the correct password

try {
    // Instantiate CustomerLogin object
    $customer_login = new CustomerLogin($db);

    // Test 1: Check if email exists
    $customer_login->email = $test_email;
    $email_exists = $customer_login->emailExists();
    $test_results[] = [
        'name' => 'Email Exists Check',
        'result' => $email_exists ? 'PASS' : 'FAIL',
        'details' => "Checked if email '$test_email' exists in customer_logins table"
    ];

    // Test 2: Login with email
    $customer_login->email = $test_email;
    $customer_login->password = $test_password;
    $login_success = $customer_login->loginWithEmail();
    $test_results[] = [
        'name' => 'Login with Email',
        'result' => $login_success ? 'PASS' : 'FAIL',
        'details' => "Attempted login with email '$test_email'"
    ];

    if ($login_success) {
        $user_details = [
            'id' => $customer_login->id,
            'email' => $customer_login->email,
            'phone' => $customer_login->phone,
            'customer_profile_id' => $customer_login->customer_profile_id
        ];

        // Check if user_id property exists
        if (isset($customer_login->user_id)) {
            $user_details['user_id'] = $customer_login->user_id;
        }

        // Check if name properties exist
        if (isset($customer_login->firstName) && isset($customer_login->lastName)) {
            $user_details['name'] = $customer_login->firstName . ' ' . $customer_login->lastName;
        }

        // Check if role property exists
        if (isset($customer_login->role)) {
            $user_details['role'] = $customer_login->role;
        }
    }

    // Test 3: Login with phone (if phone is provided)
    if (!empty($test_phone)) {
        $customer_login = new CustomerLogin($db); // Reset object
        $customer_login->phone = $test_phone;
        $customer_login->password = $test_password;
        $phone_login_success = $customer_login->loginWithPhone();
        $test_results[] = [
            'name' => 'Login with Phone',
            'result' => $phone_login_success ? 'PASS' : 'FAIL',
            'details' => "Attempted login with phone '$test_phone'"
        ];
    }

} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Customer Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 800px;
        }
        h1 {
            color: #1e8449;
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #1e8449;
            color: white;
            font-weight: 600;
        }
        .pass {
            color: #198754;
            font-weight: bold;
        }
        .fail {
            color: #dc3545;
            font-weight: bold;
        }
        .btn-success {
            background-color: #1e8449;
            border-color: #1e8449;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Customer Login</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Test Results
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Test</th>
                                <th>Result</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($test_results as $test): ?>
                                <tr>
                                    <td><?php echo $test['name']; ?></td>
                                    <td class="<?php echo strtolower($test['result']); ?>"><?php echo $test['result']; ?></td>
                                    <td><?php echo $test['details']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (isset($user_details) && $login_success): ?>
            <div class="card">
                <div class="card-header">
                    User Details
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <?php foreach ($user_details as $key => $value): ?>
                                    <tr>
                                        <th><?php echo ucfirst(str_replace('_', ' ', $key)); ?></th>
                                        <td><?php echo $value; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="login.php" class="btn btn-success">Go to Login Page</a>
            <a href="check_customer_logins_table.php" class="btn btn-primary">Check Table Status</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
