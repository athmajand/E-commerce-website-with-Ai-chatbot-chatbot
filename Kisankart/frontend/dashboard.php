<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php?redirect=dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.html">Kisan Kart</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.html">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#contact">Contact</a>
                    </li>
                </ul>
                <div class="d-flex auth-buttons">
                    <a href="../login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="register.html" class="btn btn-light">Register</a>
                </div>
                <div class="d-flex user-menu" style="display: none !important;">
                    <a href="cart.html" class="btn btn-outline-light me-2">
                        <i class="bi bi-cart"></i> Cart <span class="cart-count badge bg-light text-dark">0</span>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <span class="user-name">
                                <?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item active" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="customer_orders.php">Orders</a></li>
                            <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item logout-btn" href="#">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Section -->
    <section class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row">
                <?php include 'includes/sidebar.php'; ?>

                <!-- Main Content -->
                <div class="col-lg-9">
                    <h4 class="mb-4">Dashboard</h4>

                    <!-- Dashboard Cards -->
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded">
                                            <i class="bi bi-box fs-4 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Total Orders</h6>
                                            <h3 class="mb-0" id="total-orders">0</h3>
                                        </div>
                                    </div>
                                    <a href="customer_orders.php" class="btn btn-sm btn-outline-success w-100">View All Orders</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded">
                                            <i class="bi bi-heart fs-4 text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Wishlist Items</h6>
                                            <h3 class="mb-0" id="wishlist-count">0</h3>
                                        </div>
                                    </div>
                                    <a href="wishlist.php" class="btn btn-sm btn-outline-primary w-100">View Wishlist</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded">
                                            <i class="bi bi-geo-alt fs-4 text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Saved Addresses</h6>
                                            <h3 class="mb-0" id="address-count">0</h3>
                                        </div>
                                    </div>
                                    <a href="profile.php" class="btn btn-sm btn-outline-warning w-100">Manage Addresses</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Recent Orders</h5>
                                <a href="customer_orders.php" class="btn btn-sm btn-outline-success">View All</a>
                            </div>
                            <div id="recent-orders-container">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommended Products -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Recommended Products</h5>
                                <a href="products.html" class="btn btn-sm btn-outline-success">View All</a>
                            </div>
                            <div id="recommended-products-container">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container px-5">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Kisan Kart</h5>
                    <p>Connecting farmers and customers for a better agricultural ecosystem.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.html" class="text-white">Home</a></li>
                        <li><a href="products.html" class="text-white">Products</a></li>
                        <li><a href="index.html#about" class="text-white">About Us</a></li>
                        <li><a href="login.html" class="text-white">Login</a></li>
                        <li><a href="register.html" class="text-white">Register</a></li>
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
    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
