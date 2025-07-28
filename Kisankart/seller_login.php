<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'seller_login';

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("X-Content-Type-Options: nosniff");

// Initialize variables
$login_error = "";
$login_success = "";

// Redirect to seller dashboard if already logged in as seller
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller') {
    header('Location: /Kisankart/frontend/seller/dashboard.php');
    exit;
}

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database and models
    include_once __DIR__ . '/api/config/database.php';
    include_once __DIR__ . '/api/models/SellerRegistration.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Check if database connection was successful
    if (!$db) {
        $login_error = "Database connection failed. Please try again later or contact support.";
    } else {
        // Instantiate seller registration object
        $seller_registration = new SellerRegistration($db);

        // Check login method (email or phone)
        $login_method = isset($_POST['login_method']) ? $_POST['login_method'] : 'email';

        // Get email/phone and password from POST data (handling random field names)
        $email = '';
        $phone = '';
        $password = '';

        // Loop through POST data to find fields with email, phone, or password in their names
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'email_') === 0 && !empty($value)) {
                $email = $value;
            } else if (strpos($key, 'mobile_') === 0 && strpos($key, 'password') === false && !empty($value)) {
                $phone = $value;
            } else if ((strpos($key, 'password_') === 0 || strpos($key, 'mobile_password_') === 0) &&
                      strpos($key, 'hp') === false && !empty($value)) {
                $password = $value;
            }
        }

        // If we couldn't find the fields with random names, try the original field names
        if (empty($email) && !empty($_POST['email'])) {
            $email = $_POST['email'];
        }
        if (empty($phone) && !empty($_POST['mobile'])) {
            $phone = $_POST['mobile'];
        }
        if (empty($password) && !empty($_POST['password'])) {
            $password = $_POST['password'];
        }

        if ($login_method == 'email') {
            // Check if email and password are provided
            if (!empty($email) && !empty($password)) {
                // Login as a seller using the SellerRegistration model
                $seller_registration->email = $email;
                $seller_registration->password = $password;

                // Attempt to login with seller registration
                if ($seller_registration->loginWithEmail()) {
                    // Seller login successful
                    // Start session if not already started
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }

                    // Store user data in session
                    $_SESSION['user_id'] = $seller_registration->id; // Keep for backward compatibility
                    $_SESSION['seller_id'] = $seller_registration->id; // New seller_id session variable
                    $_SESSION['first_name'] = $seller_registration->first_name;
                    $_SESSION['last_name'] = $seller_registration->last_name;
                    $_SESSION['email'] = $seller_registration->email;
                    $_SESSION['user_role'] = 'seller';

                    // Set success message
                    $login_success = "Login successful. Redirecting...";

                    // Redirect to seller dashboard
                    header("Location: /Kisankart/frontend/seller/dashboard.php");
                    exit;
                } else {
                    // Login failed
                    $login_error = "Invalid email or password.";
                }
            }
            // Tell the user data is incomplete
            else {
                $login_error = "Unable to login. Email or password is missing.";
            }
        }
        else if ($login_method == 'phone') {
            // Check if phone and password are provided
            if (!empty($phone) && !empty($password)) {
                // Login as a seller using the SellerRegistration model
                $seller_registration->phone = $phone;
                $seller_registration->password = $password;

                // Attempt to login with seller registration
                if ($seller_registration->loginWithPhone()) {
                    // Seller login successful
                    // Start session if not already started
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }

                    // Store user data in session
                    $_SESSION['user_id'] = $seller_registration->id; // Keep for backward compatibility
                    $_SESSION['seller_id'] = $seller_registration->id; // New seller_id session variable
                    $_SESSION['first_name'] = $seller_registration->first_name;
                    $_SESSION['last_name'] = $seller_registration->last_name;
                    $_SESSION['email'] = $seller_registration->email;
                    $_SESSION['user_role'] = 'seller';

                    // Set success message
                    $login_success = "Login successful. Redirecting...";

                    // Redirect to seller dashboard
                    header("Location: /Kisankart/frontend/seller/dashboard.php");
                    exit;
                } else {
                    // Login failed
                    $login_error = "Invalid phone number or password.";
                }
            }
            // Tell the user data is incomplete
            else {
                $login_error = "Unable to login. Phone number or password is missing.";
            }
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
    <title>Seller Login - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
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

        .navbar {
            background-color: var(--white) !important;
            box-shadow: var(--shadow);
            padding: 10px 0;
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
            font-size: 24px;
        }

        .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
            padding: 8px 15px !important;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .login-section {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 60px 20px;
            min-height: 80vh;
            background-color: var(--background-color);
        }

        .login-container {
            margin: 0 auto;
            max-width: 1000px;
            background-color: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            position: relative;
            left: 0;
            right: 0;
        }

        .login-form-container {
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
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            box-sizing: border-box;
            padding: 40px;
            transition: all 0.6s ease-in-out;
            overflow-y: auto;
            max-height: 600px;
            background-color: var(--white);
            border-radius: 10px 0 0 10px;
            position: relative;
            z-index: 1;
        }

        .promo-container {
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
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            box-sizing: border-box;
            background-image: url('images/seller-bg.jpg');
            background-color: var(--primary-color); /* Fallback color */
            background-size: cover;
            background-position: center;
            position: relative;
            color: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            transition: all 0.6s ease-in-out;
            border-radius: 0 10px 10px 0;
        }

        .login-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .login-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 25px;
            text-align: center;
        }

        .nav-tabs {
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-light);
            padding: 10px 20px;
            margin: 0 10px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            border-bottom: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-light);
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            padding: 12px 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
            background-color: var(--white);
        }

        .input-group-text {
            background-color: var(--primary-light);
            border: 1px solid var(--border-color);
            color: var(--primary-dark);
            border-radius: 5px 0 0 5px;
        }

        .form-check-label {
            color: var(--text-light);
            font-size: 14px;
        }

        .forgot-password {
            color: var(--primary-color);
            font-size: 14px;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }

        .login-btn {
            padding: 12px;
            font-weight: 500;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.4);
        }

        .register-btn {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--white);
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 500;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
        }

        .register-btn:hover {
            background-color: #F57C00;
            border-color: #F57C00;
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 152, 0, 0.4);
        }

        .or-divider {
            display: flex;
            align-items: center;
            color: var(--text-light);
            margin: 25px 0;
            font-size: 14px;
            text-align: center;
        }

        .or-divider::before, .or-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }

        .or-divider::before {
            margin-right: 15px;
        }

        .or-divider::after {
            margin-left: 15px;
        }
    </style>
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

    <!-- Login Section -->
    <div class="login-section">
        <div class="container">
            <div class="row login-container">
            <div class="col-md-7 login-form-container">
                <h3 class="login-title">
                    <i class="fas fa-store"></i> Seller Login
                </h3>
                <p class="login-subtitle">Access your seller dashboard to manage products and orders</p>

                <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $login_error; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($login_success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $login_success; ?>
                </div>
                <?php endif; ?>

                <!-- Login Tabs -->
                <ul class="nav nav-tabs mb-4" id="loginTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-login" type="button" role="tab" aria-controls="email-login" aria-selected="true">
                            <i class="fas fa-envelope me-2"></i>Email Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mobile-tab" data-bs-toggle="tab" data-bs-target="#mobile-login" type="button" role="tab" aria-controls="mobile-login" aria-selected="false">
                            <i class="fas fa-mobile-alt me-2"></i>Mobile Login
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="loginTabsContent">
                    <!-- Email Login Tab -->
                    <div class="tab-pane fade show active" id="email-login" role="tabpanel" aria-labelledby="email-tab">
                        <form id="login-form" method="POST" action="seller_login.php" autocomplete="off">
                            <input type="hidden" name="login_method" value="email">
                            <!-- Honeypot field to confuse autofill -->
                            <div style="display:none;">
                                <input type="text" name="email_hp" value="">
                                <input type="password" name="password_hp" value="">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email_<?php echo rand(1000,9999); ?>" placeholder="Enter your email" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password_<?php echo rand(1000,9999); ?>" placeholder="Enter your password" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember-me" name="remember">
                                    <label class="form-check-label" for="remember-me">Remember me</label>
                                </div>
                                <a href="forgot_password.php?role=seller" class="forgot-password">Forgot Password?</a>
                            </div>
                            <button type="submit" class="btn btn-success w-100 login-btn">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                        </form>
                    </div>

                    <!-- Mobile Login Tab -->
                    <div class="tab-pane fade" id="mobile-login" role="tabpanel" aria-labelledby="mobile-tab">
                        <form id="mobile-login-form" method="POST" action="seller_login.php" autocomplete="off">
                            <input type="hidden" name="login_method" value="phone">
                            <!-- Honeypot field to confuse autofill -->
                            <div style="display:none;">
                                <input type="text" name="mobile_hp" value="">
                                <input type="password" name="mobile_password_hp" value="">
                            </div>
                            <div class="mb-3">
                                <label for="mobile" class="form-label">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                    <input type="tel" class="form-control" id="mobile" name="mobile_<?php echo rand(1000,9999); ?>" placeholder="Enter your mobile number" autocomplete="new-password" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="mobile-password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="mobile-password" name="mobile_password_<?php echo rand(1000,9999); ?>" placeholder="Enter your password" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="mobile-remember-me" name="remember">
                                    <label class="form-check-label" for="mobile-remember-me">Remember me</label>
                                </div>
                                <a href="forgot_password.php?role=seller" class="forgot-password">Forgot Password?</a>
                            </div>
                            <button type="submit" class="btn btn-success w-100 login-btn">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                        </form>
                    </div>
                </div>


            </div>
            <div class="col-md-5 promo-container">
                <br>
                <br>
                <br>
                <br>
                <br>
                <p>Don't have a seller account yet?</p>
                <a href="seller_registration.php" class="btn mt-3 btn-orange-register" style="background-color: #FF9800; border-color: #FF9800; color: #fff;">
                    <i class="fas fa-user-plus me-2"></i> Register as a Seller
                </a>
            </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Clear form fields on page load to prevent autofill
        window.addEventListener('load', function() {
            // Clear all input fields
            document.querySelectorAll('input').forEach(function(input) {
                if (input.type === 'text' || input.type === 'email' || input.type === 'password' || input.type === 'tel') {
                    input.value = '';
                }
            });

            // Add a slight delay and clear again (for some browsers)
            setTimeout(function() {
                document.querySelectorAll('input').forEach(function(input) {
                    if (input.type === 'text' || input.type === 'email' || input.type === 'password' || input.type === 'tel') {
                        input.value = '';
                    }
                });
            }, 100);
        });

        // Prevent autofill by changing input types temporarily
        document.addEventListener('DOMContentLoaded', function() {
            // Store original types
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"], input[type="tel"]');
            inputs.forEach(function(input) {
                input.dataset.originalType = input.type;
                input.type = 'text';

                // Change back to original type on focus
                input.addEventListener('focus', function() {
                    this.type = this.dataset.originalType;
                });
            });
        });
    </script>
    <script>
        // No client-side login handling - using server-side form submission instead
    </script>
</body>
</html>
