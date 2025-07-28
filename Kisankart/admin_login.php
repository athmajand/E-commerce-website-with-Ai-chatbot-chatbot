<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = false;

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Prepare a statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, name FROM admin_users WHERE username = ? AND status = 'active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Password is correct, store data in session variables
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_name'] = $row['name'];
                $_SESSION['admin_logged_in'] = true;
                
                // Update last login time
                $update_stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $row['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                $success = true;
                
                // Use JavaScript redirect to ensure session is saved
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'admin_dashboard.php';
                    }, 1000);
                </script>";
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
        
        $stmt->close();
    }
}

// Check if already logged in (but not during POST to avoid redirect loop)
if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Kisan Kart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e8449;
            --accent-color: #FF9800;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
            --error-color: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px;
            width: 100%;
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #166036;
        }
        
        .error-message {
            color: var(--error-color);
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 4px;
        }
        
        .success-message {
            color: #27ae60;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background-color: rgba(39, 174, 96, 0.1);
            border-radius: 4px;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            max-width: 120px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="assets/images/logo.png" alt="Kisan Kart Logo">
        </div>
        
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Enter your credentials to access the admin panel</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                Login successful! Redirecting to dashboard...
                <div style="text-align: center; margin-top: 10px; color: #1e8449;">Please wait...</div>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        <?php endif; ?>
        
        <a href="index.php" class="back-link">Back to Homepage</a>
    </div>
</body>
</html>
