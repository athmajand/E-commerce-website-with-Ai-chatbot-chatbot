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
$reset_status = null;
$reset_message = null;
$token_valid = false;
$email = '';
$role = '';

// Check if token, email, and role are provided
if (isset($_GET['token']) && isset($_GET['email']) && isset($_GET['role'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    $role = $_GET['role'];
    
    try {
        // Determine the table based on role
        $table = ($role === 'seller') ? 'seller_registrations' : 'customer_registrations';
        
        // Verify the token
        $query = "SELECT id, reset_token, reset_token_expires FROM $table 
                  WHERE email = ? AND reset_token = ? LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->bindParam(2, $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if token has expired
            $expires = strtotime($row['reset_token_expires']);
            $now = time();
            
            if ($expires && $now > $expires) {
                $reset_status = 'expired';
                $reset_message = 'Your password reset link has expired. Please request a new one.';
            } else {
                $token_valid = true;
                $user_id = $row['id'];
            }
        } else {
            $reset_status = 'invalid';
            $reset_message = 'Invalid reset token or email. Please check your email and try again.';
        }
    } catch (PDOException $e) {
        $reset_status = 'error';
        $reset_message = 'Database error: ' . $e->getMessage();
    }
} else {
    $reset_status = 'missing';
    $reset_message = 'Reset token, email, or role is missing. Please check your email and try again.';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (strlen($password) < 8) {
        $reset_status = 'error';
        $reset_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $reset_status = 'error';
        $reset_message = 'Passwords do not match.';
    } else {
        try {
            // Determine the table based on role
            $table = ($role === 'seller') ? 'seller_registrations' : 'customer_registrations';
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Update the password and clear the reset token
            $update_query = "UPDATE $table SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $hashed_password);
            $update_stmt->bindParam(2, $user_id);
            
            if ($update_stmt->execute()) {
                $reset_status = 'success';
                $reset_message = 'Your password has been successfully reset. You can now login with your new password.';
            } else {
                $reset_status = 'error';
                $reset_message = 'Failed to reset your password. Please try again or contact support.';
            }
        } catch (PDOException $e) {
            $reset_status = 'error';
            $reset_message = 'Database error: ' . $e->getMessage();
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
    <title>Reset Password - Kisan Kart</title>
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

        .reset-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .reset-container {
            max-width: 500px;
            width: 100%;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 30px;
        }

        .reset-icon {
            font-size: 60px;
            margin-bottom: 20px;
            text-align: center;
        }

        .reset-icon.success {
            color: var(--success-color);
        }

        .reset-icon.error {
            color: var(--error-color);
        }

        .reset-icon.warning {
            color: var(--accent-color);
        }

        .reset-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        .reset-message {
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

    <!-- Reset Password Section -->
    <div class="reset-section">
        <div class="reset-container">
            <?php if ($reset_status === 'success'): ?>
                <div class="reset-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="reset-title">Password Reset Successful!</h2>
                <p class="reset-message"><?php echo $reset_message; ?></p>
                <a href="<?php echo $role === 'seller' ? 'seller_login.php' : 'login.php'; ?>" class="btn btn-success">Login to Your Account</a>
            <?php elseif ($token_valid): ?>
                <h2 class="reset-title">Reset Your Password</h2>
                <p class="reset-message">Please enter your new password below.</p>
                
                <?php if ($reset_status === 'error'): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo $reset_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-success">Reset Password</button>
                </form>
            <?php else: ?>
                <div class="reset-icon <?php echo $reset_status === 'expired' ? 'warning' : 'error'; ?>">
                    <i class="fas fa-<?php echo $reset_status === 'expired' ? 'exclamation-circle' : 'times-circle'; ?>"></i>
                </div>
                <h2 class="reset-title"><?php echo $reset_status === 'expired' ? 'Link Expired' : 'Invalid Link'; ?></h2>
                <p class="reset-message"><?php echo $reset_message; ?></p>
                <a href="<?php echo $role === 'seller' ? 'seller_login.php' : 'login.php'; ?>" class="btn btn-success">Back to Login</a>
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
