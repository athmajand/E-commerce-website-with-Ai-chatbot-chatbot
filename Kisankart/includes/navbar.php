<?php
/**
 * Common Navigation Bar for Kisankart
 *
 * This file contains the standardized navigation bar to be used across all pages.
 *
 * Usage:
 * 1. Start the session in your main PHP file BEFORE including this file
 * 2. Set $active_page variable before including to highlight the active menu item
 *    Possible values: 'home', 'products', 'customer_login', 'seller_login', 'register', 'about'
 */

// Default values
if (!isset($active_page)) {
    $active_page = '';
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = $isLoggedIn && isset($_SESSION['first_name']) && isset($_SESSION['last_name'])
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    : '';
$userRole = $isLoggedIn && isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Define base URL to prevent URL construction issues
$base_url = '/Kisankart';
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_url; ?>/frontend/index.php">
            <img src="<?php echo $base_url; ?>/images/farmer-logo.png" alt="Kisankart Logo" style="height: 32px; width: 32px; margin-right: 8px;"> Kisankart
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active_page == 'home') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/frontend/index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active_page == 'products') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/frontend/products.php">
                        <i class="fas fa-shopping-basket"></i> Products
                    </a>
                </li>
                <?php if (!$isLoggedIn): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active_page == 'customer_login') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/login.php">
                        <i class="fas fa-user"></i> Customer Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active_page == 'seller_login') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/seller_login.php">
                        <i class="fas fa-store"></i> Seller Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active_page == 'register') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/customer_registration.php">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active_page == 'about') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/frontend/index.php#about">
                        <i class="fas fa-info-circle"></i> About Us
                    </a>
                </li>
            </ul>

            <?php if ($isLoggedIn): ?>
            <!-- User Menu for Logged In Users -->
            <div class="d-flex user-menu">
                <?php if ($userRole == 'customer'): ?>
                <a href="<?php echo $base_url; ?>/frontend/cart.php" class="btn btn-outline-success me-2">
                    <i class="bi bi-cart"></i> Cart <span class="cart-count badge bg-success text-white">0</span>
                </a>
                <?php endif; ?>
                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php if ($userRole == 'customer'): ?>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>/frontend/customer_dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>/frontend/customer_profile_settings.php">Profile Settings</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>/frontend/customer_orders.php">Orders</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>/frontend/customer_wishlist.php">Wishlist</a></li>
                        <?php elseif ($userRole == 'seller'): ?>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>/frontend/seller/dashboard.php">Seller Dashboard</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item logout-btn" href="<?php echo $base_url; ?>/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
