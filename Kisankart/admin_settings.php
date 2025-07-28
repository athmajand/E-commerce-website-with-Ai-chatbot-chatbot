<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

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

// Initialize variables
$admin_id = $_SESSION['admin_id'];
$admin_data = [];
$message = '';
$message_type = '';

// Get admin data
$admin_query = "SELECT * FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
} else {
    $message = "Admin user not found.";
    $message_type = "danger";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        
        // Validate input
        $errors = [];
        
        if (empty($name)) {
            $errors[] = "Name is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        
        // Check if email is already in use by another admin
        if (!empty($email) && $email !== $admin_data['email']) {
            $check_email = "SELECT id FROM admin_users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_email);
            $check_stmt->bind_param("si", $email, $admin_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "Email is already in use by another admin";
            }
            
            $check_stmt->close();
        }
        
        // Check if username is already in use by another admin
        if (!empty($username) && $username !== $admin_data['username']) {
            $check_username = "SELECT id FROM admin_users WHERE username = ? AND id != ?";
            $check_stmt = $conn->prepare($check_username);
            $check_stmt->bind_param("si", $username, $admin_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "Username is already in use by another admin";
            }
            
            $check_stmt->close();
        }
        
        if (empty($errors)) {
            // Update admin profile
            $update_query = "UPDATE admin_users SET name = ?, email = ?, username = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $name, $email, $username, $admin_id);
            
            if ($update_stmt->execute()) {
                $message = "Profile updated successfully!";
                $message_type = "success";
                
                // Update session variables
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_username'] = $username;
                
                // Refresh admin data
                $result = $conn->query("SELECT * FROM admin_users WHERE id = $admin_id");
                $admin_data = $result->fetch_assoc();
            } else {
                $message = "Error updating profile: " . $conn->error;
                $message_type = "danger";
            }
            
            $update_stmt->close();
        } else {
            $message = implode("<br>", $errors);
            $message_type = "danger";
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            // Verify current password
            if (password_verify($current_password, $admin_data['password'])) {
                // Hash new password
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE admin_users SET password = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $password_hash, $admin_id);
                
                if ($update_stmt->execute()) {
                    $message = "Password changed successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error changing password: " . $conn->error;
                    $message_type = "danger";
                }
                
                $update_stmt->close();
            } else {
                $message = "Current password is incorrect";
                $message_type = "danger";
            }
        } else {
            $message = implode("<br>", $errors);
            $message_type = "danger";
        }
    } elseif (isset($_POST['update_status'])) {
        // Update account status
        $status = $_POST['status'];
        
        if ($status === 'active' || $status === 'inactive') {
            $update_query = "UPDATE admin_users SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $status, $admin_id);
            
            if ($update_stmt->execute()) {
                $message = "Account status updated successfully!";
                $message_type = "success";
                
                // Refresh admin data
                $result = $conn->query("SELECT * FROM admin_users WHERE id = $admin_id");
                $admin_data = $result->fetch_assoc();
            } else {
                $message = "Error updating account status: " . $conn->error;
                $message_type = "danger";
            }
            
            $update_stmt->close();
        } else {
            $message = "Invalid status value";
            $message_type = "danger";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Kisan Kart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e8449;
            --accent-color: #FF9800;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
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
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: white;
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(30, 132, 73, 0.1);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        .sidebar-menu a.active {
            font-weight: 500;
        }

        .sidebar-menu i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #166036;
            border-color: #166036;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                width: 250px;
            }

            .main-content.active {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Kisan Kart Admin</h3>
        </div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="admin_customers.php"><i class="bi bi-people"></i> Customers</a>
            <a href="admin_sellers.php"><i class="bi bi-shop"></i> Sellers</a>
            <a href="admin_products.php"><i class="bi bi-box"></i> Products</a>
            <a href="admin_orders.php"><i class="bi bi-cart"></i> Orders</a>
            <a href="admin_categories.php"><i class="bi bi-tags"></i> Categories</a>
            <a href="admin_settings.php" class="active"><i class="bi bi-gear"></i> Settings</a>
            <a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <nav class="navbar navbar-expand-lg navbar-light mb-4">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary me-3" id="sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand">Settings</span>
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['admin_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="admin_settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin_logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Settings -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-person me-2"></i> Profile Settings
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($admin_data['username'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($admin_data['name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="created_at" class="form-label">Account Created</label>
                                <input type="text" class="form-control" id="created_at" value="<?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($admin_data['created_at'] ?? 'now'))); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="last_login" class="form-label">Last Login</label>
                                <input type="text" class="form-control" id="last_login" value="<?php echo !empty($admin_data['last_login']) ? htmlspecialchars(date('F j, Y, g:i a', strtotime($admin_data['last_login']))) : 'Never'; ?>" readonly>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Password Settings -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-lock me-2"></i> Change Password
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>

                <!-- Account Status -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-shield-check me-2"></i> Account Status
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo (isset($admin_data['status']) && $admin_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (isset($admin_data['status']) && $admin_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="form-text">Setting your account to inactive will prevent you from logging in.</div>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });
    </script>
</body>
</html>
