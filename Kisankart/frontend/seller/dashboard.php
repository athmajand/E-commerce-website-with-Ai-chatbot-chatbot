<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include session helper
require_once '../../api/helpers/session_helper.php';

// Synchronize seller IDs in session
synchronizeSellerSessionIds();

// Check if user is logged in as a seller
if (!getSellerIdFromSession() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    // Redirect to login page if not logged in as a seller
    header("Location: ../../seller_login.php?redirect=frontend/seller/dashboard.php");
    exit;
}

// Include database configuration
include_once __DIR__ . '/../../api/config/database.php';
include_once __DIR__ . '/../../api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$seller_data = null;
$error_message = '';
$success_message = '';

// Include session helper
require_once '../../api/helpers/session_helper.php';

// Synchronize seller IDs in session
synchronizeSellerSessionIds();

// Get seller data using the helper function
$seller_id = getSellerIdFromSession();

// Debug information
$debug_info = "Attempting to load seller with ID: " . $seller_id;
error_log($debug_info);

// Create seller object
$seller = new SellerRegistration($db);
$seller->id = $seller_id;

// Try to fetch seller data from database
if (!$seller->readOne()) {
    // If database fetch fails, create a fallback seller object with session data
    error_log("Failed to load seller data for ID: " . $seller_id . ". Error: " . ($seller->error ?? 'Unknown error'));
    error_log("Using session data instead. Session data: " . json_encode($_SESSION));

    // Set error message with more details
    $error_message = "Failed to load seller data from database. Using session data instead.";

    // Set fallback data from session
    $seller->first_name = $_SESSION['first_name'] ?? '';
    $seller->last_name = $_SESSION['last_name'] ?? '';
    $seller->email = $_SESSION['email'] ?? '';

    // Add more detailed error information for debugging
    $error_details = "Error: " . ($seller->error ?? 'Unknown error');
    error_log($error_details);
} else {
    // Successfully loaded seller data from database
    error_log("Successfully loaded seller data from database for ID: " . $seller_id);
    $success_message = "Welcome back, " . $seller->first_name . "!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Seller Dashboard - Kisan Kart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../../images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="../../images/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/seller.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom bg-white text-success">
                <img src="../../images/farmer-logo.png" alt="Kisan Kart Logo" style="height: 24px; width: 24px; margin-right: 8px;"> Seller Center
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3 active" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="products.php">
                    <i class="bi bi-box me-2"></i> Products
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="orders.php">
                    <i class="bi bi-cart me-2"></i> Orders
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="profile.php">
                    <i class="bi bi-person me-2"></i> Profile
                </a>

                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="../index.html">
                    <i class="bi bi-house me-2"></i> Back to Store
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 text-danger" href="../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
        </div>

        <!-- Page content wrapper -->
        <div id="page-content-wrapper">
            <!-- Top navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-sm btn-outline-success" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-label="Toggle Navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="notificationDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell" aria-hidden="true"></i>
                                    <span class="badge bg-danger rounded-pill notification-badge" style="display: none;">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" id="notification-dropdown">
                                    <div class="dropdown-header">Notifications</div>
                                    <div class="dropdown-divider"></div>
                                    <div id="notification-list">
                                        <a class="dropdown-item" href="#">No new notifications</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                                    <span class="seller-name"><?php echo htmlspecialchars(trim(($seller->first_name ?? '') . (empty($seller->last_name) ? '' : ' ' . $seller->last_name))); ?></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="profile.php">Profile</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../../logout.php">Logout</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page content -->
            <div class="container-fluid p-4">
                <h1 class="mt-2 mb-4">Seller Dashboard</h1>

                <!-- Display error/success messages -->
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php if (isset($error_details)): ?>
                <div class="alert alert-warning" role="alert">
                    <h5>Technical Details:</h5>
                    <p><?php echo htmlspecialchars($error_details); ?></p>
                    <hr>
                    <p class="mb-0">If this issue persists, please contact support with the above information.</p>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Dashboard Content -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Welcome, <?php echo htmlspecialchars($seller->first_name ?? ''); ?>!</h5>
                                <p class="card-text">This is your seller dashboard. You can manage your products, orders, and profile here.</p>
                                <a href="profile.php" class="btn btn-success">View Profile</a>

                                <!-- Debug information - always shown for now -->
                                <div class="mt-4 p-3 bg-light border rounded">
                                    <h6>Debug Information:</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Session Information</h6>
                                            <p>Session ID: <?php echo session_id(); ?></p>
                                            <p>User ID: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
                                            <p>Seller ID: <?php echo $_SESSION['seller_id'] ?? 'Not set'; ?></p>
                                            <p>User Role: <?php echo $_SESSION['user_role'] ?? 'Not set'; ?></p>
                                            <p>First Name from Session: <?php echo $_SESSION['first_name'] ?? 'Not set'; ?></p>
                                            <p>Last Name from Session: <?php echo $_SESSION['last_name'] ?? 'Not set'; ?></p>
                                            <p>Email from Session: <?php echo $_SESSION['email'] ?? 'Not set'; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Database Information</h6>
                                            <p>First Name from Object: <?php echo $seller->first_name ?? 'Not set'; ?></p>
                                            <p>Last Name from Object: <?php echo $seller->last_name ?? 'Not set'; ?></p>
                                            <p>Email from Object: <?php echo $seller->email ?? 'Not set'; ?></p>
                                            <p>Business Name: <?php echo $seller->business_name ?? 'Not set'; ?></p>
                                            <p>Data Source: <?php echo empty($error_message) ? 'Database' : 'Session'; ?></p>
                                            <p>ID Used for Database Query: <?php echo $seller_id; ?></p>
                                            <?php if (!empty($seller->error)): ?>
                                            <p class="text-danger">Error Message: <?php echo htmlspecialchars($seller->error); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/main.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    </script>
</body>
</html>
