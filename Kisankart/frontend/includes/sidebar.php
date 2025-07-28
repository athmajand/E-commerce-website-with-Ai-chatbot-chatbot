<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug session information
error_log("Session data in sidebar.php: " . print_r($_SESSION, true));

// Get the current page to highlight the active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="col-lg-3 mb-4 mb-lg-0">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="text-center mb-3">
                <div class="profile-image-container mb-3">
                    <img src="https://via.placeholder.com/150" class="rounded-circle profile-image" alt="Profile Image" id="profile-image">
                </div>
                <h5 class="mb-0" id="sidebar-user-name">
                    <?php
                    // Display the user's full name from session
                    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                        echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                    } else {
                        echo 'User Name';
                    }
                    ?>
                </h5>
                <p class="text-muted small" id="sidebar-user-email">
                    <?php
                    // Display the user's email from session
                    echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'user@example.com';
                    ?>
                </p>
            </div>
            <hr>
            <div class="list-group list-group-flush">
                <a href="customer_dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'customer_dashboard.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a href="customer_profile_settings.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'customer_profile_settings.php') ? 'active' : ''; ?>">
                    <i class="bi bi-gear me-2"></i> Profile Settings
                </a>
                <a href="customer_orders.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'customer_orders.php' || $current_page == 'orders.php') ? 'active' : ''; ?>">
                    <i class="bi bi-box me-2"></i> My Orders
                </a>
                <a href="customer_wishlist.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'customer_wishlist.php' || $current_page == 'wishlist.php') ? 'active' : ''; ?>">
                    <i class="bi bi-heart me-2"></i> My Wishlist
                </a>

                <a href="../logout.php" class="list-group-item list-group-item-action logout-btn text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>
