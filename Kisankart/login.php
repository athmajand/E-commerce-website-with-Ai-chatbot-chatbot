<?php
// Start the session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'customer_login';

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("X-Content-Type-Options: nosniff");

// Include PHPMailer
require_once './PHPMailer/src/Exception.php';
require_once './PHPMailer/src/PHPMailer.php';
require_once './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize variables
$login_error = "";
$login_success = "";
$otp_error = "";
$otp_success = "";
$show_otp_form = false;

// Function to generate and send OTP
function generateAndSendOTP($customer_id, $email) {
    // Validate inputs
    if (empty($customer_id) || empty($email)) {
        error_log("Invalid parameters for OTP generation: customer_id=$customer_id, email=$email");
        return false;
    }

    // Generate a random 6-digit OTP
    $otp = rand(100000, 999999);
    $otp_expiry = date("Y-m-d H:i:s", strtotime("+3 minute"));

    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Store temporary user data in session
    $_SESSION['temp_user'] = [
        'id' => $customer_id,
        'otp' => $otp,
        'otp_expiry' => $otp_expiry
    ];

    // Send OTP via email
    try {
        $subject = "Your OTP for Kisan Kart Login";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; }
                .otp { font-size: 24px; font-weight: bold; text-align: center; margin: 20px 0; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Kisan Kart - OTP Verification</h2>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>Thank you for logging in to your Kisan Kart account. Please use the following code to verify your identity:</p>
                    <p class='otp'>$otp</p>
                    <p>This OTP will expire in 3 minutes.</p>
                    <p>If you did not attempt to login, please ignore this email and consider changing your password.</p>
                    <p>Best regards,<br>The Kisan Kart Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>&copy; " . date('Y') . " Kisan Kart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Set the sender email address
        $mail->Username = 'athmajand2003@gmail.com';

        // TODO: Replace with your app password (NOT your regular Gmail password)
        // To generate an app password:
        // 1. Go to your Google Account settings
        // 2. Select Security
        // 3. Under "Signing in to Google," select "App passwords" (you may need to enable 2-Step Verification first)
        // 4. Select "Mail" and "Other (Custom name)" and enter "Kisan Kart"
        // 5. Copy the generated password and paste it here
        $mail->Password = 'idpxklwuhirqtkfq';

        // Enable debug output for troubleshooting
        $mail->SMTPDebug = 0; // Set to 2 for detailed debug output

        $mail->Port = 465;
        $mail->SMTPSecure = 'ssl';
        $mail->isHTML(true);
        $mail->setFrom('athmajand2003@gmail.com', 'Kisan Kart Authentication'); // Sender's Email & Name
        $mail->addAddress($email); // Receiver's Email
        $mail->Subject = $subject;
        $mail->Body = $message;

        // Add plain text version for email clients that don't support HTML
        $mail->AltBody = "Hello,\n\nThank you for logging in to your Kisan Kart account. Please use the following code to verify your identity:\n\n$otp\n\nThis OTP will expire in 3 minutes.\n\nIf you did not attempt to login, please ignore this email and consider changing your password.\n\nBest regards,\nThe Kisan Kart Team";

        if (!$mail->send()) {
            error_log("Failed to send OTP email: " . $mail->ErrorInfo);
            return false;
        }

        error_log("OTP email sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        error_log("Exception while sending OTP email: " . $e->getMessage());
        return false;
    }
}

// Function to verify OTP
function verifyOTP($entered_otp, $customer_id) {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check if temp_user session exists
    if (!isset($_SESSION['temp_user'])) {
        return ['success' => false, 'message' => 'Session expired. Please try logging in again.'];
    }

    // Check if OTP is valid and not expired
    $current_time = date("Y-m-d H:i:s");

    if ($_SESSION['temp_user']['otp'] == $entered_otp) {
        if ($_SESSION['temp_user']['otp_expiry'] > $current_time) {
            return ['success' => true, 'message' => 'OTP verified successfully.'];
        } else {
            return ['success' => false, 'message' => 'OTP has expired. Please request a new OTP.'];
        }
    } else {
        return ['success' => false, 'message' => 'Invalid OTP. Please try again.'];
    }
}

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database and models
    include_once __DIR__ . '/api/config/database.php';
    include_once __DIR__ . '/api/models/CustomerRegistration.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Check if database connection was successful
    if (!$db) {
        $login_error = "Database connection failed. Please try again later or contact support.";
    } else {
        // Instantiate customer registration object
        $customer_registration = new CustomerRegistration($db);

        // Handle OTP verification
        if (isset($_POST['verify_otp'])) {
            $entered_otp = $_POST['otp'];

            // Debug logging
            error_log("OTP verification attempt: " . $entered_otp);

            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Debug session data
            error_log("Session data during OTP verification: " . print_r($_SESSION, true));

            // Check if temp_user session exists
            if (isset($_SESSION['temp_user'])) {
                $user_id = $_SESSION['temp_user']['id'];

                // Get customer registration
                $customer_registration->id = $user_id;

                if ($customer_registration->readOne()) {
                    // Verify OTP
                    $verification_result = verifyOTP($entered_otp, $user_id);

                    if ($verification_result['success']) {
                        // OTP is valid, set user session
                        $jwt = generateJWT($customer_registration->id, $customer_registration->email, 'customer');

                        // Store user data in session
                        $_SESSION['jwt'] = $jwt;
                        $_SESSION['user_id'] = $customer_registration->id;
                        $_SESSION['customer_id'] = $customer_registration->id; // Add customer_id for compatibility
                        $_SESSION['first_name'] = $customer_registration->first_name;
                        $_SESSION['last_name'] = $customer_registration->last_name;
                        $_SESSION['email'] = $customer_registration->email;
                        $_SESSION['role'] = 'customer';

                        // Debug session information
                        error_log("Session data after OTP verification: " . print_r($_SESSION, true));

                        // Also set cookies for backward compatibility
                        setcookie("jwt", $jwt, time() + 3600, "/");
                        setcookie("user_id", $customer_registration->id, time() + 3600, "/");
                        setcookie("username", $customer_registration->first_name . ' ' . $customer_registration->last_name, time() + 3600, "/");
                        setcookie("email", $customer_registration->email, time() + 3600, "/");
                        setcookie("role", 'customer', time() + 3600, "/");

                        // Clear the temporary session
                        unset($_SESSION['temp_user']);

                        // Redirect to dashboard
                        $redirect = isset($_SESSION['redirect_after_otp']) ? $_SESSION['redirect_after_otp'] : 'customer_dashboard.php';
                        unset($_SESSION['redirect_after_otp']);

                        // Debug the redirect URL
                        error_log("Redirecting after OTP verification to: " . $redirect);

                        // Handle redirect URL with absolute path to prevent infinite loops
                        if (strpos($redirect, 'http') === 0) {
                            // Already a full URL, use as is
                            error_log("Redirecting to full URL: " . $redirect);
                            header("Location: " . $redirect);
                        } else if (strpos($redirect, '/') === 0) {
                            // Already an absolute path, use as is
                            error_log("Redirecting to absolute path: " . $redirect);
                            header("Location: " . $redirect);
                        } else if (strpos($redirect, 'frontend/') === 0) {
                            // Has frontend prefix, make it absolute
                            error_log("Redirecting to: /Kisankart/" . $redirect);
                            header("Location: /Kisankart/" . $redirect);
                        } else {
                            // Add frontend prefix and make absolute
                            error_log("Redirecting to: /Kisankart/frontend/" . $redirect);
                            header("Location: /Kisankart/frontend/" . $redirect);
                        }
                        exit();
                    } else {
                        $otp_error = $verification_result['message'];
                        $show_otp_form = true;
                    }
                } else {
                    $otp_error = "User not found. Please try logging in again.";
                    // Clear the temporary session
                    unset($_SESSION['temp_user']);
                }
            } else {
                $otp_error = "Session expired. Please try logging in again.";
            }
        }

        // Handle resend OTP
        else if (isset($_POST['resend_otp'])) {
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Check if temp_user session exists
            if (isset($_SESSION['temp_user'])) {
                $user_id = $_SESSION['temp_user']['id'];

                // Get customer registration
                $customer_registration->id = $user_id;

                if ($customer_registration->readOne()) {
                    // Generate and send new OTP
                    $email = $customer_registration->email;
                    $result = generateAndSendOTP($user_id, $email);

                    if ($result) {
                        $otp_success = "A new OTP has been sent to your email.";
                    } else {
                        $otp_error = "Failed to send OTP email. Please try again.";
                    }
                    $show_otp_form = true;
                } else {
                    $otp_error = "User not found. Please try logging in again.";
                    // Clear the temporary session
                    unset($_SESSION['temp_user']);
                }
            } else {
                $otp_error = "Session expired. Please try logging in again.";
            }
        }

    // Only process login if we're not handling OTP verification or resend
    if (!isset($_POST['verify_otp']) && !isset($_POST['resend_otp'])) {
        // Check login method (email/username or phone)
        $login_method = isset($_POST['login_method']) ? $_POST['login_method'] : 'email';

        // Get username/email and password from POST data (handling random field names)
        $username = '';
        $email = '';
        $password = '';
        $phone = '';

    // Loop through POST data to find fields with username, email, password, or phone in their names
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'username_') === 0 && !empty($value)) {
            $username = $value;
        } else if (strpos($key, 'email_') === 0 && !empty($value)) {
            $email = $value;
        } else if ((strpos($key, 'password_') === 0) && strpos($key, 'hp') === false && !empty($value)) {
            $password = $value;
        } else if (strpos($key, 'phone_') === 0 && !empty($value)) {
            $phone = $value;
        }
    }

    // If we couldn't find the fields with random names, try the original field names
    if (empty($username) && !empty($_POST['username'])) {
        $username = $_POST['username'];
    }
    if (empty($email) && !empty($_POST['email'])) {
        $email = $_POST['email'];
    }
    if (empty($password) && !empty($_POST['password'])) {
        $password = $_POST['password'];
    }
    if (empty($phone) && !empty($_POST['phone'])) {
        $phone = $_POST['phone'];
    }

    if ($login_method == 'email') {
        // Check if login credential (username or email) and password are provided
        if ((!empty($username) || !empty($email)) && !empty($password)) {
            // Login as a customer using the CustomerRegistration model
            $login_email = !empty($email) ? $email : $username;
            $customer_registration->email = $login_email;
            $customer_registration->password = $password;

            // Attempt to login with customer registration
            if ($customer_registration->loginWithEmail()) {
                // Customer login successful
                // Store redirect URL for after OTP verification
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'customer_dashboard.php';

                // Start session if not already started
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                $_SESSION['redirect_after_otp'] = $redirect;
                error_log("Setting redirect_after_otp to: " . $redirect);

                // Generate and send OTP
                $result = generateAndSendOTP($customer_registration->id, $customer_registration->email);

                if ($result) {
                    // Set success message
                    $login_success = "Login successful. Please verify OTP sent to your email.";

                    // Show OTP form
                    $show_otp_form = true;
                } else {
                    // If email fails, show error message but still require OTP
                    $login_error = "Login successful but failed to send OTP email. Please try again.";

                    // Log the error
                    error_log("Failed to send OTP email to: " . $customer_registration->email);

                    // Clear any existing session
                    if (isset($_SESSION['temp_user'])) {
                        unset($_SESSION['temp_user']);
                    }
                }
            } else {
                // Login failed
                $login_error = "Invalid username/email or password.";
            }
        }
        // Tell the user data is incomplete
        else {
            $login_error = "Unable to login. Username/email or password is missing.";
        }
    }
    else if ($login_method == 'phone') {
        // Check if phone and password are provided
        if (!empty($phone) && !empty($password)) {
            // Login as a customer using the CustomerRegistration model
            $customer_registration->phone = $phone;
            $customer_registration->password = $password;

            // Attempt to login with customer registration
            if ($customer_registration->loginWithPhone()) {
                // Customer login successful
                // Store redirect URL for after OTP verification
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'customer_dashboard.php';

                // Start session if not already started
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                $_SESSION['redirect_after_otp'] = $redirect;
                error_log("Setting redirect_after_otp to: " . $redirect);

                // Generate and send OTP
                $result = generateAndSendOTP($customer_registration->id, $customer_registration->email);

                if ($result) {
                    // Set success message
                    $login_success = "Login successful. Please verify OTP sent to your email.";

                    // Show OTP form
                    $show_otp_form = true;
                } else {
                    // If email fails, show error message but still require OTP
                    $login_error = "Login successful but failed to send OTP email. Please try again.";

                    // Log the error
                    error_log("Failed to send OTP email to: " . $customer_registration->email);

                    // Clear any existing session
                    if (isset($_SESSION['temp_user'])) {
                        unset($_SESSION['temp_user']);
                    }
                }
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
}

// Generate JWT token
function generateJWT($user_id, $username, $role) {
    $secret_key = "kisan_kart_jwt_secret";
    $issuer_claim = "kisan_kart_api"; // this can be the servername
    $audience_claim = "kisan_kart_client";
    $issuedat_claim = time(); // issued at
    $notbefore_claim = $issuedat_claim; // not before
    $expire_claim = $issuedat_claim + 3600; // expire time (1 hour)

    $token = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "nbf" => $notbefore_claim,
        "exp" => $expire_claim,
        "data" => array(
            "id" => $user_id,
            "username" => $username,
            "role" => $role
        )
    );

    // Create JWT parts
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode($token));

    // Create signature
    $signature_data = $header . '.' . $payload;
    $signature = base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));

    // Combine all parts to form the JWT
    $jwt = $header . '.' . $payload . '.' . $signature;

    return $jwt;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <title>Login - Kisan Kart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="images/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="panel-login.css">
    <link rel="stylesheet" href="css/modern-buttons.css">
    <link rel="stylesheet" href="login-background.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .otp-input {
            letter-spacing: 0.5em;
            text-align: center;
            font-size: 1.5em;
        }
        .countdown {
            font-size: 1.2em;
            color: #dc3545;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
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
    <section class="login-page py-5">
        <div class="container">
            <div class="left-panel">
                <h2>Welcome to Kisan Kart</h2>
                <p>Connecting farmers and customers for a better agricultural ecosystem. Login to access your account and explore our products.</p>
                <a href="customer_registration.php"><button id="register-btn">Create an Account</button></a>
            </div>
            <div class="right-panel">
                <?php if ($show_otp_form): ?>
                <!-- OTP Verification Form -->
                <div class="form-container" id="otp-form-container">
                    <h2><i class="fas fa-key"></i> Enter OTP</h2>
                    <p class="text-center">We've sent a verification code to your email</p>

                    <?php if (!empty($otp_error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $otp_error; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($otp_success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $otp_success; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($login_success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $login_success; ?>
                    </div>
                    <?php endif; ?>

                    <form id="otp-form" method="POST" action="login.php">
                        <div class="form-group">
                            <label for="otp">Verification Code</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" pattern="[0-9]{6}" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="countdown" id="countdown">OTP expires in: 03:00</div>
                        </div>
                        <input type="hidden" name="verify_otp" value="1">
                        <button type="submit" class="btn btn-primary btn-ripple w-100 mt-3">
                            <i class="fas fa-check-circle"></i> Verify OTP
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <form method="POST" action="login.php">
                            <button type="submit" name="resend_otp" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Resend OTP
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <!-- Login Form -->
                <div class="form-container" id="login-form-container">
                    <h2><i class="fas fa-user-circle"></i> Login to Your Account</h2>

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
                <?php endif; ?>

                    <?php if (!$show_otp_form): ?>
                    <!-- Login Tabs -->
                    <ul class="nav nav-tabs mb-4" id="loginTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-login" type="button" role="tab" aria-controls="email-login" aria-selected="true">
                                <i class="fas fa-envelope me-2"></i>Email/Username
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="phone-tab" data-bs-toggle="tab" data-bs-target="#phone-login" type="button" role="tab" aria-controls="phone-login" aria-selected="false">
                                <i class="fas fa-phone me-2"></i>Phone Number
                            </button>
                        </li>
                    </ul>
                    <?php endif; ?>

                    <?php if (!$show_otp_form): ?>
                    <!-- Tab Content -->
                    <div class="tab-content" id="loginTabsContent">
                        <!-- Email/Username Login Tab -->
                        <div class="tab-pane fade show active" id="email-login" role="tabpanel" aria-labelledby="email-tab">
                            <form id="email-login-form" method="POST" action="login.php" autocomplete="off">
                                <input type="hidden" name="login_method" value="email">
                                <!-- Honeypot field to confuse autofill -->
                                <div style="display:none;">
                                    <input type="text" name="username_hp" value="">
                                    <input type="password" name="password_hp" value="">
                                </div>
                                <div class="form-group">
                                    <label for="username">Username or Email</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" class="form-control" id="username" name="username_<?php echo rand(1000,9999); ?>" placeholder="Enter your username or email" required autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" class="form-control" id="password" name="password_<?php echo rand(1000,9999); ?>" placeholder="Enter your password" required autocomplete="new-password">
                                        <i class="fas fa-eye toggle-password"></i>
                                    </div>
                                </div>
                                <div class="form-footer">
                                    <label class="remember-me">
                                        <input type="checkbox" name="remember"> Remember me
                                    </label>
                                    <a href="#" class="forgot-link" id="forgot-link">Forgot Password?</a>
                                </div>
                                <button type="submit" class="btn btn-primary btn-ripple">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>
                        </div>

                        <!-- Phone Login Tab -->
                        <div class="tab-pane fade" id="phone-login" role="tabpanel" aria-labelledby="phone-tab">
                            <form id="phone-login-form" method="POST" action="login.php" autocomplete="off">
                                <input type="hidden" name="login_method" value="phone">
                                <!-- Honeypot field to confuse autofill -->
                                <div style="display:none;">
                                    <input type="tel" name="phone_hp" value="">
                                    <input type="password" name="phone_password_hp" value="">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-phone"></i>
                                        <input type="tel" class="form-control" id="phone" name="phone_<?php echo rand(1000,9999); ?>" placeholder="Enter your phone number" pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" required autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="phone-password">Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" class="form-control" id="phone-password" name="password_phone_<?php echo rand(1000,9999); ?>" placeholder="Enter your password" required autocomplete="new-password">
                                        <i class="fas fa-eye toggle-password"></i>
                                    </div>
                                </div>
                                <div class="form-footer">
                                    <label class="remember-me">
                                        <input type="checkbox" name="remember"> Remember me
                                    </label>
                                    <a href="#" class="forgot-link" id="forgot-phone-link">Forgot Password?</a>
                                </div>
                                <button type="submit" class="btn btn-primary btn-ripple">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="form-switch">
                        Don't have an account? <a href="customer_registration.php" id="register-link">Register Now</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/panel-login.js"></script>
    <script src="js/modern-buttons.js"></script>

    <!-- Custom JS for password toggle, tabs, and OTP countdown -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // OTP Countdown Timer
            if (document.getElementById('countdown')) {
                let timeLeft = 180; // 3 minutes in seconds
                const countdownElement = document.getElementById('countdown');

                const countdownTimer = setInterval(function() {
                    // Calculate minutes and seconds
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;

                    // Display the time left
                    countdownElement.innerHTML = `OTP expires in: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                    // Decrease the time left
                    timeLeft--;

                    // If the countdown is over, display a message
                    if (timeLeft < 0) {
                        clearInterval(countdownTimer);
                        countdownElement.innerHTML = 'OTP has expired. Please request a new one.';
                        countdownElement.style.color = '#dc3545';
                    }
                }, 1000);
            }

            // Clear form fields on page load to prevent autofill
            function clearFormFields() {
                // Clear all input fields
                document.querySelectorAll('input').forEach(function(input) {
                    if (input.type === 'text' || input.type === 'email' || input.type === 'password' || input.type === 'tel') {
                        input.value = '';
                    }
                });
            }

            // Clear fields immediately
            clearFormFields();

            // Add a slight delay and clear again (for some browsers)
            setTimeout(clearFormFields, 100);
            setTimeout(clearFormFields, 500);

            // Prevent autofill by changing input types temporarily
            const inputs = document.querySelectorAll('input[type="text"], input[type="password"], input[type="tel"]');
            inputs.forEach(function(input) {
                input.dataset.originalType = input.type;
                input.type = 'text';

                // Change back to original type on focus
                input.addEventListener('focus', function() {
                    this.type = this.dataset.originalType;
                });
            });

            // Toggle password visibility for all password fields
            const togglePasswordElements = document.querySelectorAll('.toggle-password');

            togglePasswordElements.forEach(function(element) {
                element.addEventListener('click', function() {
                    const passwordInput = this.previousElementSibling;
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            });

            // Set active tab based on previous selection or error
            const urlParams = new URLSearchParams(window.location.search);
            const loginMethod = urlParams.get('method');

            if (loginMethod === 'phone') {
                const phoneTab = document.getElementById('phone-tab');
                if (phoneTab) {
                    phoneTab.click();
                }
            }

            // Background image slideshow
            const leftPanel = document.querySelector('.left-panel');
            const images = [
                'images/categories/grains.jpg',
                'images/268926-1080x1920-phone-1080p-farm-wallpaper.jpg'
            ];
            let currentImageIndex = 0;

            // Preload images to prevent flickering
            images.forEach(src => {
                const img = new Image();
                img.src = src;
            });

            setInterval(() => {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                const newImageUrl = images[currentImageIndex];
                leftPanel.style.background = `linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('${newImageUrl}')`;
                leftPanel.style.backgroundSize = 'cover';
                leftPanel.style.backgroundPosition = 'center';
            }, 3000); // Change image every 3 seconds
        });
    </script>
</body>
</html>
