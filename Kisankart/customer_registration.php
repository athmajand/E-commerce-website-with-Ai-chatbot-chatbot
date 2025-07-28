<?php
// Start the session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'register';

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("X-Content-Type-Options: nosniff");

// Initialize variables
$registration_error = "";
$registration_success = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit1'])) {
    // Include database and models
    include_once __DIR__ . '/api/config/database.php';
    // Removed User.php include as it's no longer needed
    include_once __DIR__ . '/api/models/CustomerRegistration.php'; // Model for customer registrations

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get form data
    $firstName = $_POST['firstname'] ?? '';
    $lastName = $_POST['lastname'] ?? '';
    $email = $_POST['mail'] ?? '';
    $phone = $_POST['phonem'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $terms_accepted = isset($_POST['terms']) ? true : false;

    // Instantiate customer registration object
    $customer_registration = new CustomerRegistration($db);
    $customer_registration->email = $email;
    $customer_registration->phone = $phone;

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Please enter a valid email address.";
    }
    // Validate password length
    else if (strlen($password) < 6) {
        $registration_error = "Password must be at least 6 characters long.";
    }
    // Validate password confirmation
    else if ($password !== $confirm_password) {
        $registration_error = "Passwords do not match. Please try again.";
    }
    // Validate phone number
    else if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $registration_error = "Please enter a valid 10-digit phone number.";
    }
    // Validate terms acceptance
    else if (!$terms_accepted) {
        $registration_error = "You must accept the Terms and Conditions to register.";
    }
    // Check if email exists in customer_registrations table
    else if ($customer_registration->emailExists()) {
        $registration_error = "Email already exists in our registration system. Please use a different email or login.";
    }
    // Check if phone exists in customer_registrations table
    else if ($customer_registration->phoneExists()) {
        $registration_error = "Phone number already exists in our registration system. Please use a different phone number or login.";
    }
    else {
        // Begin transaction
        $db->beginTransaction();

        try {
            // Skip directly to creating customer registration record
            error_log("Skipping customer profile and login creation - directly creating registration record");

            // Create customer registration record
            $customer_registration->first_name = $firstName;
            $customer_registration->last_name = $lastName;
            $customer_registration->email = $email;
            $customer_registration->phone = $phone;
            $customer_registration->password = $password; // Include password
            $customer_registration->address = $address;
            $customer_registration->city = $city;
            $customer_registration->state = $state;
            $customer_registration->postal_code = $pincode;
            $customer_registration->status = 'approved'; // Auto-approve for now
            $customer_registration->is_verified = 1; // Auto-verify for now

            if (!$customer_registration->create()) {
                throw new Exception("Failed to create customer registration record");
            }

            // Commit transaction
            $db->commit();

            // Log successful registration
            error_log("Customer registration successful - Name: $firstName $lastName, Email: $email");
            error_log("Customer registration record created directly - ID: {$customer_registration->id}, Address: $address, City: $city, State: $state, Postal Code: $pincode");

            $registration_success = "Registration successful! You can now login to your account.";

            // Auto-redirect to login page after 3 seconds
            header("refresh:3;url=login.php");

        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            error_log("Customer registration failed: " . $e->getMessage());
            $registration_error = "Unable to complete registration. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <title>Customer Registration - Kisan Kart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="images/favicon.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/customer-registration.css">
</head>
<body>
    <!-- Hidden form to trick browser autofill -->
    <div style="display:none;">
        <form id="dummy-form">
            <input type="text" name="username">
            <input type="password" name="password">
        </form>
    </div>

    <?php
    // Include the navigation bar
    include_once('includes/navbar.php');
    ?>

    <!-- Main Content -->
    <div class="login-page">
        <h2 class="login-page-title">Create Your KisanKart Account</h2>
        <p class="login-page-subtitle">Register to start shopping fresh produce directly from farmers</p>

        <div class="container">
            <!-- Left Panel (Register) -->
            <div class="left-panel active">
                <h2>New Customer?</h2>
                <p>Create an account to enjoy a personalized shopping experience, track orders, and get access to exclusive deals.</p>
                <div class="left-panel-buttons">
                    <button type="button" id="register-btn">Create Account</button>
                    <a href="login.php" class="login-btn-alt">Already have account? Login</a>
                </div>
            </div>

            <!-- Right Panel (Login) -->
            <div class="right-panel">
                <!-- Login Form Container -->
                <div class="form-container hidden" id="login-form-container">
                    <h2><i class="fas fa-user-circle"></i> Login to Your Account</h2>

                    <form id="login-form" method="POST" action="login.php" autocomplete="off">
                        <input type="hidden" name="login_method" value="email">
                        <!-- Honeypot field to confuse autofill -->
                        <div style="display:none;">
                            <input type="text" name="username_hp" value="">
                            <input type="password" name="password_hp" value="">
                        </div>

                        <div class="login-tabs">
                            <div class="login-tab active" id="emailTab">
                                <i class="fas fa-envelope"></i> Email Login
                            </div>
                            <div class="login-tab" id="mobileTab">
                                <i class="fas fa-mobile-alt"></i> Phone Login
                            </div>
                        </div>

                        <!-- Email Login Form -->
                        <div class="login-form-content active" id="emailLoginContent">
                            <div class="form-group">
                                <label for="login-email">Email Address</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input id="login-email" name="username_<?php echo rand(1000,9999); ?>" type="email" class="form-control" required autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <!-- Phone Login Form -->
                        <div class="login-form-content" id="phoneLoginContent">
                            <div class="form-group">
                                <label for="login-phone">Phone Number</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                    <input id="login-phone" name="phone_<?php echo rand(1000,9999); ?>" type="tel" class="form-control" pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input id="login-password" name="password_<?php echo rand(1000,9999); ?>" type="password" class="form-control" required autocomplete="new-password">
                                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                            </div>
                        </div>

                        <div class="form-footer">
                            <label class="remember-me">
                                <input type="checkbox" name="remember">
                                <span>Remember Me</span>
                            </label>
                            <a href="#" class="forgot-link" id="forgot-link">Forgot Password?</a>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary btn-ripple btn-block">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>

                        <div class="demo-access">
                            <span>or</span>
                            <a href="customer_home.php" class="btn btn-demo btn-ripple btn-block">
                                <i class="fas fa-eye"></i> Browse as Demo
                            </a>
                        </div>
                    </form>

                    <div class="form-switch">
                        <p>Don't have an account? <a href="#" id="register-link">Register Now</a></p>
                    </div>
                </div>

                <!-- Register Form Container -->
                <div class="form-container" id="register-form-container">
                    <h2><i class="fas fa-user-plus"></i> Create Account</h2>

                    <?php if (!empty($registration_error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $registration_error; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($registration_success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $registration_success; ?>
                    </div>
                    <?php endif; ?>

                    <form id="register-form" method="POST" action="customer_registration.php" autocomplete="off">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first-name">First Name</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input id="first-name" name="firstname" type="text" class="form-control" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last-name">Last Name</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input id="last-name" name="lastname" type="text" class="form-control" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="register-email">Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input id="register-email" name="mail" type="email" class="form-control" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="register-phone">Phone Number</label>
                            <div class="input-with-icon">
                                <i class="fas fa-mobile-alt"></i>
                                <input id="register-phone" name="phonem" type="tel" class="form-control" pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="register-password">Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input id="register-password" name="password" type="password" class="form-control" required autocomplete="new-password" minlength="6">
                                        <i class="fas fa-eye toggle-password"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm-password">Confirm Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input id="confirm-password" name="confirm_password" type="password" class="form-control" required autocomplete="new-password" minlength="6">
                                        <i class="fas fa-eye toggle-password"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-home"></i>
                                <input id="address" name="address" type="text" class="form-control" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-city"></i>
                                        <input id="city" name="city" type="text" class="form-control" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <input id="state" name="state" type="text" class="form-control" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pincode">Pincode</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-map-pin"></i>
                                        <input id="pincode" name="pincode" type="text" class="form-control" pattern="[0-9]{6}" title="Please enter a valid 6-digit pincode" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group terms-checkbox">
                            <label class="checkbox-container">
                                <input type="checkbox" name="terms" required>
                                <span class="checkmark"></span>
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                            </label>
                        </div>

                        <input type="hidden" name="user_type_id" value="2">
                        <input type="hidden" name="place" value="Default Location">

                        <button type="submit" name="submit1" class="btn btn-gradient-primary btn-ripple btn-block">
                            <i class="fas fa-user-plus"></i> Register as Customer
                        </button>
                    </form>

                    <div class="form-switch">
                        <p>Already have account? <a href="login.php" id="login-link">Login</a></p>
                    </div>
                </div>

                <!-- Forgot Password Form Container -->
                <div class="form-container hidden" id="forgot-form-container">
                    <h2><i class="fas fa-key"></i> Reset Password</h2>

                    <p>Enter your email address below and we'll send you a link to reset your password.</p>

                    <form action="forgot2.php" method="POST">
                        <div class="form-group">
                            <label for="forgot-email">Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input id="forgot-email" type="email" name="Email" class="form-control" required autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" name="forgot" class="btn btn-accent btn-ripple btn-block">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
                        </button>
                    </form>

                    <div class="form-switch">
                        <p>Remember your password? <a href="#" id="back-to-login">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Kisan Kart</h5>
                    <p>Connecting farmers and customers for a better agricultural ecosystem.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="frontend/index.html" class="text-white">Home</a></li>
                        <li><a href="frontend/index.html#products" class="text-white">Products</a></li>
                        <li><a href="frontend/index.html#about" class="text-white">About Us</a></li>
                        <li><a href="login.php" class="text-white">Login</a></li>
                        <li><a href="customer_registration.php" class="text-white">Register</a></li>
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

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Introduction</h6>
                    <p>Welcome to Kisan Kart. These terms and conditions govern your use of our website and services.</p>

                    <h6>2. Acceptance of Terms</h6>
                    <p>By registering for an account, you agree to be bound by these Terms and Conditions.</p>

                    <h6>3. User Accounts</h6>
                    <p>You are responsible for maintaining the confidentiality of your account information and password.</p>

                    <h6>4. Ordering and Payment</h6>
                    <p>All orders placed through our platform are subject to acceptance and availability.</p>

                    <h6>5. Delivery</h6>
                    <p>Delivery times are estimates and may vary based on location and other factors.</p>

                    <h6>6. Returns and Refunds</h6>
                    <p>Our return and refund policy applies to all purchases made through our platform.</p>

                    <h6>7. Prohibited Activities</h6>
                    <p>You agree not to engage in any activity that may interfere with the proper functioning of our services.</p>

                    <h6>8. Termination</h6>
                    <p>We reserve the right to terminate or suspend your account for violations of these terms.</p>

                    <h6>9. Changes to Terms</h6>
                    <p>We may update these terms from time to time. Continued use of our services constitutes acceptance of any changes.</p>

                    <h6>10. Contact Information</h6>
                    <p>For questions about these terms, please contact us at info@kisankart.com.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Information We Collect</h6>
                    <p>We collect personal information such as name, email, phone number, and address when you register.</p>

                    <h6>2. How We Use Your Information</h6>
                    <p>We use your information to process orders, provide customer service, and improve our services.</p>

                    <h6>3. Information Sharing</h6>
                    <p>We do not sell or rent your personal information to third parties.</p>

                    <h6>4. Data Security</h6>
                    <p>We implement appropriate security measures to protect your personal information.</p>

                    <h6>5. Cookies</h6>
                    <p>We use cookies to enhance your browsing experience and analyze website traffic.</p>

                    <h6>6. Your Rights</h6>
                    <p>You have the right to access, correct, or delete your personal information.</p>

                    <h6>7. Changes to Privacy Policy</h6>
                    <p>We may update this privacy policy from time to time.</p>

                    <h6>8. Contact Information</h6>
                    <p>For questions about our privacy practices, please contact us at privacy@kisankart.com.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="js/customer-registration.js"></script>
</body>
</html>
