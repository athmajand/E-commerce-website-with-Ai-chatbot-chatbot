<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug information
error_log("Accessed standalone_profile.php");
error_log("Session data: " . print_r($_SESSION, true));

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$customer_data = null;
$error_message = '';
$success_message = '';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Get customer data
    $customer_id = $_SESSION['user_id'];
    
    // Get customer data from database
    $query = "SELECT * FROM customer_registrations WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$customer_id]);
    $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer_data) {
        $error_message = "Failed to load customer profile data.";
    }
} else {
    // Not logged in, but we'll still show the page for testing
    $error_message = "You are not logged in. This is a test page.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    
    // Update customer data in database
    $query = "UPDATE customer_registrations SET first_name = ?, last_name = ?, phone = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$first_name, $last_name, $phone, $customer_id])) {
        // Update session data
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        
        // Refresh customer data
        $stmt = $db->prepare("SELECT * FROM customer_registrations WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success_message = "Profile updated successfully.";
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standalone Profile - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .profile-header {
            background-color: #4CAF50;
            color: white;
            padding: 2rem 0;
        }
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="profile-header">
        <div class="container text-center">
            <img src="https://via.placeholder.com/150" alt="Profile Image" class="profile-image mb-3">
            <h1>My Profile</h1>
            <p>Manage your personal information</p>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <!-- Navigation Links -->
                <div class="mb-4">
                    <a href="frontend/customer_dashboard.php" class="btn btn-outline-success me-2">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="frontend/customer_orders.php" class="btn btn-outline-success me-2">
                        <i class="bi bi-box"></i> Orders
                    </a>
                    <a href="frontend/customer_wishlist.php" class="btn btn-outline-success me-2">
                        <i class="bi bi-heart"></i> Wishlist
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
                
                <!-- Debug Information -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>Session Data:</h6>
                        <pre><?php print_r($_SESSION); ?></pre>
                        
                        <h6 class="mt-3">Customer Data:</h6>
                        <pre><?php print_r($customer_data); ?></pre>
                    </div>
                </div>
                
                <!-- Profile Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
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
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" 
                                           value="<?php echo isset($customer_data['first_name']) ? htmlspecialchars($customer_data['first_name']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" 
                                           value="<?php echo isset($customer_data['last_name']) ? htmlspecialchars($customer_data['last_name']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?php echo isset($customer_data['email']) ? htmlspecialchars($customer_data['email']) : ''; ?>" readonly>
                                <div class="form-text">Email address cannot be changed.</div>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($customer_data['phone']) ? htmlspecialchars($customer_data['phone']) : ''; ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-success">Save Changes</button>
                        </form>
                    </div>
                </div>
                
                <!-- Navigation Test Links -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Navigation Test Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="test_profile.php" class="list-group-item list-group-item-action">Go to Test Profile Page</a>
                            <a href="frontend/customer_dashboard.php" class="list-group-item list-group-item-action">Go to Dashboard</a>
                            <a href="frontend/customer_profile.php" class="list-group-item list-group-item-action">Go to Old Profile Page</a>
                            <a href="frontend/new_profile.php" class="list-group-item list-group-item-action">Go to New Profile Page</a>
                            <a href="standalone_profile.php" class="list-group-item list-group-item-action">Refresh This Page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container text-center">
            <p class="mb-0">© 2025 Kisan Kart. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
