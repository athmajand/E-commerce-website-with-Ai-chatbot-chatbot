<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/CustomerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$error_message = '';
$success_message = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get customer ID from form
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    
    if ($customer_id > 0) {
        // Get customer data
        $customer = new CustomerRegistration($db);
        $customer->id = $customer_id;
        
        if ($customer->readOne()) {
            // Set session variables
            $_SESSION['jwt'] = 'manually_set_jwt_token';
            $_SESSION['user_id'] = $customer->id;
            $_SESSION['customer_id'] = $customer->id;
            $_SESSION['first_name'] = $customer->first_name;
            $_SESSION['last_name'] = $customer->last_name;
            $_SESSION['email'] = $customer->email;
            $_SESSION['role'] = 'customer';
            
            // Set success message
            $success_message = "Session variables set successfully for customer: " . $customer->first_name . " " . $customer->last_name;
        } else {
            $error_message = "Customer with ID $customer_id not found.";
        }
    } else {
        $error_message = "Please enter a valid customer ID.";
    }
}

// Get list of customers for dropdown
$customers = [];
$query = "SELECT id, first_name, last_name, email FROM customer_registrations ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $customers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Session Variables - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Set Session Variables</h1>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Current Session Variables</h5>
                <pre><?php print_r($_SESSION); ?></pre>
                <p>Session ID: <?php echo session_id(); ?></p>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Set Session Variables</h5>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Select Customer</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['id'] . ' - ' . $customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['email'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Set Session Variables</button>
                </form>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="test_session.php" class="btn btn-info">Check Session Variables</a>
            <a href="frontend/customer_profile.php" class="btn btn-success">Go to Profile Page</a>
            <a href="logout.php" class="btn btn-danger">Clear Session (Logout)</a>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
