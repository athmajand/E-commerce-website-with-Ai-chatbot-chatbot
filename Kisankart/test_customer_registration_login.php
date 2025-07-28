<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration and models
include_once 'api/config/database.php';
include_once 'api/models/CustomerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$test_results = [];
$login_success = false;
$user_details = null;

// Get the most recent registration for testing
try {
    $query = "SELECT * FROM customer_registrations ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $test_user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Test email and phone for login
        $test_email = $test_user['email'];
        $test_phone = $test_user['phone'];

        // For testing purposes, we'll use a known password
        // In a real scenario, you would need to know the actual password
        $test_password = "password123"; // This is the password we used when creating the test user

        // Instantiate CustomerRegistration object
        $customer_registration = new CustomerRegistration($db);

        // Test 1: Check if email exists
        $customer_registration->email = $test_email;
        $email_exists = $customer_registration->emailExists();
        $test_results[] = [
            'name' => 'Email Exists Check',
            'result' => $email_exists ? 'PASS' : 'FAIL',
            'details' => "Checked if email '$test_email' exists in customer_registrations table"
        ];

        // Test 2: Check if phone exists
        $customer_registration->phone = $test_phone;
        $phone_exists = $customer_registration->phoneExists();
        $test_results[] = [
            'name' => 'Phone Exists Check',
            'result' => $phone_exists ? 'PASS' : 'FAIL',
            'details' => "Checked if phone '$test_phone' exists in customer_registrations table"
        ];

        // Test 3: Login with email
        $customer_registration->email = $test_email;
        $customer_registration->password = $test_password;
        $login_with_email = $customer_registration->loginWithEmail();
        $test_results[] = [
            'name' => 'Login with Email',
            'result' => $login_with_email ? 'PASS' : 'FAIL',
            'details' => "Attempted login with email '$test_email'"
        ];

        // Test 4: Login with phone
        $customer_registration->phone = $test_phone;
        $customer_registration->password = $test_password;
        $login_with_phone = $customer_registration->loginWithPhone();
        $test_results[] = [
            'name' => 'Login with Phone',
            'result' => $login_with_phone ? 'PASS' : 'FAIL',
            'details' => "Attempted login with phone '$test_phone'"
        ];

        // If login was successful, store user details
        if ($login_with_email || $login_with_phone) {
            $login_success = true;
            $user_details = [
                'id' => $customer_registration->id,
                'first_name' => $customer_registration->first_name,
                'last_name' => $customer_registration->last_name,
                'email' => $customer_registration->email,
                'phone' => $customer_registration->phone,
                'address' => $customer_registration->address,
                'city' => $customer_registration->city,
                'state' => $customer_registration->state,
                'postal_code' => $customer_registration->postal_code,
                'status' => $customer_registration->status,
                'is_verified' => $customer_registration->is_verified,
                'last_login' => $customer_registration->last_login
            ];
        }
    } else {
        $test_results[] = [
            'name' => 'Find Test User',
            'result' => 'FAIL',
            'details' => "No registrations found in the database"
        ];
    }
} catch (Exception $e) {
    $test_results[] = [
        'name' => 'Database Query',
        'result' => 'FAIL',
        'details' => "Error: " . $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Test Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Login Test Results</h1>

        <div class="card mb-4">
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
                                    <td>
                                        <?php if ($test['result'] === 'PASS'): ?>
                                            <span class="badge bg-success">PASS</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">FAIL</span>
                                        <?php endif; ?>
                                    </td>
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
            <a href="customer_registration.php" class="btn btn-primary">Go to Registration Page</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
