<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';

// Get database connection with buffered queries
$database = new Database();
$db = $database->getConnection();

// Double-check that buffered queries are enabled
if ($db) {
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
}

// Initialize variables
$verification_status = null;
$verification_message = null;

// Check if token and email are provided
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    try {
        // Verify the token
        $query = "SELECT id, first_name, last_name, verification_token, is_verified FROM seller_registrations 
                  WHERE email = ? AND verification_token = ? LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->bindParam(2, $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if already verified
            if ($row['is_verified']) {
                $verification_status = 'already_verified';
                $verification_message = 'Your account has already been verified. You can now login.';
            } else {
                // Update verification status
                $update_query = "UPDATE seller_registrations SET is_verified = 1 WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $row['id']);
                
                if ($update_stmt->execute()) {
                    $verification_status = 'success';
                    $verification_message = 'Your account has been successfully verified. You can now login.';
                } else {
                    $verification_status = 'error';
                    $verification_message = 'Failed to verify your account. Please try again or contact support.';
                }
            }
        } else {
            $verification_status = 'invalid';
            $verification_message = 'Invalid verification token or email. Please check your email and try again.';
        }
    } catch (PDOException $e) {
        $verification_status = 'error';
        $verification_message = 'Database error: ' . $e->getMessage();
    }
} else {
    $verification_status = 'missing';
    $verification_message = 'Verification token or email is missing. Please check your email and try again.';
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Seller Account - Kisan Kart</title>
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
            max-width: 600px;
            width: 100%;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 30px;
            text-align: center;
        }

        .verification-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .verification-icon.success {
            color: var(--success-color);
        }

        .verification-icon.error {
            color: var(--error-color);
        }

        .verification-icon.warning {
            color: var(--accent-color);
        }

        .verification-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .verification-message {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 25px;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: 500;
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
            <?php if ($verification_status === 'success'): ?>
                <div class="verification-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="verification-title">Email Verified!</h2>
                <p class="verification-message"><?php echo $verification_message; ?></p>
                <a href="seller_login.php" class="btn btn-success">Login to Your Account</a>
            <?php elseif ($verification_status === 'already_verified'): ?>
                <div class="verification-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="verification-title">Already Verified</h2>
                <p class="verification-message"><?php echo $verification_message; ?></p>
                <a href="seller_login.php" class="btn btn-success">Login to Your Account</a>
            <?php elseif ($verification_status === 'invalid'): ?>
                <div class="verification-icon error">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h2 class="verification-title">Invalid Verification</h2>
                <p class="verification-message"><?php echo $verification_message; ?></p>
                <a href="seller_registration.php" class="btn btn-success">Register Again</a>
            <?php elseif ($verification_status === 'missing'): ?>
                <div class="verification-icon warning">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h2 class="verification-title">Missing Information</h2>
                <p class="verification-message"><?php echo $verification_message; ?></p>
                <a href="frontend/index.html" class="btn btn-success">Go to Homepage</a>
            <?php else: ?>
                <div class="verification-icon error">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="verification-title">Verification Error</h2>
                <p class="verification-message"><?php echo $verification_message; ?></p>
                <a href="seller_login.php" class="btn btn-success">Try Logging In</a>
            <?php endif; ?>
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
