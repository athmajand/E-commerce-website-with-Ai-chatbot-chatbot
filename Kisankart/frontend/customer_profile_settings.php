<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'home';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    // Redirect to login page if not logged in
    header("Location: ../login.php?redirect=frontend/customer_profile_settings.php");
    exit;
}

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';
include_once __DIR__ . '/../api/models/CustomerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$customer_data = null;
$error_message = '';
$success_message = '';

// Get customer data
$customer_id = $_SESSION['user_id'];
$customer = new CustomerRegistration($db);
$customer->id = $customer_id;

// Fetch customer data
if (!$customer->readOne()) {
    $error_message = "Failed to load customer profile data.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $postal_code = $_POST['postal_code'];

    // Validate form data
    if (empty($first_name) || empty($last_name)) {
        $error_message = "First name and last name are required.";
    } else {
        // Update customer data using the model
        $customer->first_name = $first_name;
        $customer->last_name = $last_name;
        $customer->phone = $phone;
        $customer->address = $address;
        $customer->city = $city;
        $customer->state = $state;
        $customer->postal_code = $postal_code;

        if ($customer->update()) {
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;

            // Refresh customer data
            $customer->readOne();
            $success_message = "Profile updated successfully.";
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } else if ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } else {
        // Change password
        if ($customer->changePassword($current_password, $new_password)) {
            $success_message = "Password changed successfully.";
        } else {
            $error_message = "Failed to change password. Please check your current password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php
    // Include the navigation bar
    include_once('../includes/navbar.php');
    ?>

    <!-- Profile Section -->
    <section class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row">
                <?php include 'includes/sidebar.php'; ?>

                <!-- Main Content -->
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Profile Settings</h4>

                            <!-- Alert for success/error messages -->
                            <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <!-- Profile Form -->
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="profile-form" class="needs-validation" novalidate>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer->first_name); ?>" required>
                                        <div class="invalid-feedback">
                                            Please enter your first name.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer->last_name); ?>" required>
                                        <div class="invalid-feedback">
                                            Please enter your last name.
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($customer->email); ?>" readonly>
                                    <div class="form-text">Email address cannot be changed.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer->phone); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($customer->address); ?>">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($customer->city); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <label for="state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($customer->state); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="postal_code" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($customer->postal_code); ?>">
                                    </div>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-success">Save Changes</button>
                            </form>
                        </div>
                    </div>

                    <!-- Password Change Section -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Change Password</h4>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="password-form" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container px-5">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Kisan Kart</h5>
                    <p>Connecting farmers and customers for a better agricultural ecosystem.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.html" class="text-white">Home</a></li>
                        <li><a href="products.html" class="text-white">Products</a></li>
                        <li><a href="index.html#about" class="text-white">About Us</a></li>
                        <li><a href="../login.php" class="text-white">Login</a></li>
                        <li><a href="../customer_registration.php" class="text-white">Register</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5>Contact Us</h5>
                    <p>Email: info@kisankart.com<br>
                    Phone: +91 1234567890</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="small mb-0">© 2025 Kisan Kart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>
