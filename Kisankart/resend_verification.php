<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/utils/EmailSender.php';
include_once __DIR__ . '/api/utils/ErrorHandler.php';

// Get database connection with buffered queries
$database = new Database();
$db = $database->getConnection();

// Double-check that buffered queries are enabled
if ($db) {
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
}

// Initialize variables
$message = null;
$message_type = null;
$email = isset($_GET['email']) ? $_GET['email'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'seller';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
    $email = $_POST['email'];
    $type = $_POST['type'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    } else {
        try {
            // Determine the table based on type
            $table = ($type === 'seller') ? 'seller_registrations' : 'customer_registrations';
            
            // Check if email exists
            $query = "SELECT id, first_name, last_name, is_verified FROM $table WHERE email = ? LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Check if already verified
                if ($row['is_verified']) {
                    $message = 'Your account is already verified. You can now login.';
                    $message_type = 'info';
                } else {
                    // Generate new verification token
                    $token = bin2hex(random_bytes(32));
                    
                    // Update user with new verification token
                    $update_query = "UPDATE $table SET verification_token = ? WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(1, $token);
                    $update_stmt->bindParam(2, $row['id']);
                    
                    if ($update_stmt->execute()) {
                        // Send verification email
                        $emailSender = new EmailSender();
                        $name = $row['first_name'] . ' ' . $row['last_name'];
                        
                        if ($type === 'seller') {
                            $emailSent = $emailSender->sendSellerVerificationEmail($email, $name, $token);
                        } else {
                            $emailSent = $emailSender->sendCustomerVerificationEmail($email, $name, $token);
                        }
                        
                        if ($emailSent) {
                            $message = 'A new verification link has been sent to your email address. Please check your inbox.';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to send verification email. Please try again later.';
                            $message_type = 'danger';
                        }
                    } else {
                        $message = 'Failed to process your request. Please try again later.';
                        $message_type = 'danger';
                    }
                }
            } else {
                // Don't reveal that the email doesn't exist for security reasons
                $message = 'If your email is registered with us, you will receive a verification link shortly.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Database error. Please try again later.';
            $message_type = 'danger';
        } catch (Exception $e) {
            $message = 'An error occurred. Please try again later.';
            $message_type = 'danger';
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
    <title>Resend Verification Email - Kisan Kart</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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

        .verification-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .verification-container {
            max-width: 500px;
            width: 100%;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 30px;
        }

        .verification-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
            color: var(--primary-color);
        }

        .verification-message {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 25px;
            text-align: center;
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

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px;
            font-weight: 500;
            width: 100%;
        }

        .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .footer {
            background-color: var(--white);
            padding: 20px 0;
            text-align: center;
            color: var(--text-light);
            font-size: 14px;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="frontend/index.html">
                <i class="fas fa-leaf text-success"></i> Kisan Kart
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.html">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-user"></i> Customer Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="seller_login.php">
                            <i class="fas fa-store"></i> Seller Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Verification Section -->
    <div class="verification-section">
        <div class="verification-container">
            <h2 class="verification-title">Resend Verification Email</h2>
            <p class="verification-message">Enter your email address and we'll send you a new verification link.</p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">Account Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="seller" <?php echo $type === 'seller' ? 'selected' : ''; ?>>Seller</option>
                        <option value="customer" <?php echo $type === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>
                <button type="submit" name="resend_verification" class="btn btn-success">Send Verification Link</button>
            </form>
            
            <div class="text-center mt-4">
                <p>Remember your password? <a href="<?php echo $type === 'seller' ? 'seller_login.php' : 'login.php'; ?>">Login here</a></p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Kisan Kart. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
