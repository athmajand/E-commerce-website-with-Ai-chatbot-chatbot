<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("X-Content-Type-Options: nosniff");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database and models
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/CustomerRegistration.php';

// Use PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

// Initialize variables
$error_message = "";
$success_message = "";

// Check if user is already logged in with full session
if (isset($_SESSION['user_id']) && !isset($_SESSION['temp_user'])) {
    // Redirect to dashboard
    header("Location: /Kisankart/frontend/customer_dashboard.php");
    exit();
}

// Check if temp_user session exists
if (!isset($_SESSION['temp_user'])) {
    // Redirect to login page
    header("Location: /Kisankart/login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];
    $user_id = $_SESSION['temp_user']['id'];

    // Get customer registration
    $customer_registration = new CustomerRegistration($db);
    $customer_registration->id = $user_id;

    if ($customer_registration->readOne()) {
        // Check if OTP is valid and not expired
        $current_time = date("Y-m-d H:i:s");

        if ($_SESSION['temp_user']['otp'] == $entered_otp && $_SESSION['temp_user']['otp_expiry'] > $current_time) {
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

            // Handle redirect URL with absolute path to prevent infinite loops
            if (strpos($redirect, 'http') === 0) {
                // Already a full URL, use as is
                header("Location: " . $redirect);
            } else if (strpos($redirect, '/') === 0) {
                // Already an absolute path, use as is
                header("Location: " . $redirect);
            } else if (strpos($redirect, 'frontend/') === 0) {
                // Has frontend prefix, make it absolute
                header("Location: /Kisankart/" . $redirect);
            } else {
                // Add frontend prefix and make absolute
                header("Location: /Kisankart/frontend/" . $redirect);
            }
            exit();
        } else {
            if ($_SESSION['temp_user']['otp_expiry'] <= $current_time) {
                $error_message = "OTP has expired. Please request a new OTP.";
            } else {
                $error_message = "Invalid OTP. Please try again.";
            }
        }
    } else {
        $error_message = "User not found. Please try logging in again.";
    }
}

// Handle resend OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    $user_id = $_SESSION['temp_user']['id'];

    // Get customer registration
    $customer_registration = new CustomerRegistration($db);
    $customer_registration->id = $user_id;

    if ($customer_registration->readOne()) {
        $email = $customer_registration->email;
        $otp = rand(100000, 999999);
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+3 minute"));
        $subject = "Your New OTP for Kisan Kart Login";
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
                    <p>You requested a new OTP for your Kisan Kart account. Please use the following code to complete your login:</p>
                    <p class='otp'>$otp</p>
                    <p>This OTP will expire in 3 minutes.</p>
                    <p>If you did not request this OTP, please ignore this email.</p>
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

        // Send email with PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'athmajand2003@gmail.com'; // host email
            $mail->Password = 'idpxklwuhirqtkfq'; // app password of your host email

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
            $mail->AltBody = "Hello,\n\nYou requested a new OTP for your Kisan Kart account. Please use the following code to complete your login:\n\n$otp\n\nThis OTP will expire in 3 minutes.\n\nIf you did not request this OTP, please ignore this email.\n\nBest regards,\nThe Kisan Kart Team";

            if (!$mail->send()) {
                error_log("Failed to send OTP email: " . $mail->ErrorInfo);
                throw new Exception($mail->ErrorInfo);
            }

            error_log("OTP email sent successfully to: $email");

            // Update session
            $_SESSION['temp_user']['otp'] = $otp;
            $_SESSION['temp_user']['otp_expiry'] = $otp_expiry;

            $success_message = "A new OTP has been sent to your email.";
        } catch (Exception $e) {
            $error_message = "Failed to send OTP email. Please try again. Error: " . $mail->ErrorInfo;
            error_log("OTP Email Error: " . $mail->ErrorInfo);
        }
    } else {
        $error_message = "User not found. Please try logging in again.";
    }
}

// Generate JWT token (copied from login.php)
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
    <title>OTP Verification - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="panel-login.css">
    <link rel="stylesheet" href="css/modern-buttons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .otp-input {
            letter-spacing: 1.5em;
            text-align: center;
            font-size: 1.5em;
        }
        .countdown {
            font-size: 1.2em;
            color: #dc3545;
            font-weight: bold;
            margin-top: 10px;
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

    <!-- OTP Verification Section -->
    <section class="login-page py-5">
        <div class="container">
            <div class="left-panel">
                <h2>Two-Factor Authentication</h2>
                <p>For your security, we've sent a one-time password (OTP) to your registered email address. Please enter it below to complete the login process.</p>
                <div class="text-center mt-4">
                    <i class="fas fa-shield-alt fa-5x text-success"></i>
                </div>
            </div>
            <div class="right-panel">
                <div class="form-container" id="otp-form-container">
                    <h2><i class="fas fa-key"></i> Enter OTP</h2>
                    <p class="text-center">We've sent a verification code to your email</p>

                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>

                    <form id="otp-form" method="POST" action="otp_verification.php">
                        <div class="form-group">
                            <label for="otp">Verification Code</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="text" class="form-control otp-input" id="otp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" pattern="[0-9]{6}" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="countdown" id="countdown">OTP expires in: 03:00</div>
                        </div>
                        <button type="submit" name="verify_otp" class="btn btn-primary btn-ripple w-100 mt-3">
                            <i class="fas fa-check-circle"></i> Verify OTP
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <form method="POST" action="otp_verification.php">
                            <button type="submit" name="resend_otp" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Resend OTP
                            </button>
                        </form>
                    </div>

                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
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
    <script src="js/modern-buttons.js"></script>

    <!-- Countdown Timer Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set the countdown time (3 minutes)
            let timeLeft = 180; // in seconds
            const countdownElement = document.getElementById('countdown');

            // Update the countdown every second
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
        });
    </script>
</body>
</html>
