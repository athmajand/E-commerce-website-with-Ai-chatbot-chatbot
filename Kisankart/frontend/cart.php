<?php
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
$userRole = '';

if ($isLoggedIn) {
    // Safely get user name from session variables (using correct names from login.php)
    $firstName = $_SESSION['first_name'] ?? '';
    $lastName = $_SESSION['last_name'] ?? '';
    $userName = trim($firstName . ' ' . $lastName);
    
    // If no name is available, use a fallback
    if (empty($userName)) {
        $userName = $_SESSION['email'] ?? 'User';
    }
    
    $userRole = $_SESSION['role'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Kisankart</title>
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
            <a class="navbar-brand" href="index.php">Kisankart</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                </ul>
                
                <?php if ($isLoggedIn): ?>
                    <div class="d-flex user-menu">
                        <a href="cart.php" class="btn btn-outline-light me-2 active">
                            <i class="bi bi-cart"></i> Cart <span class="cart-count badge bg-light text-dark">0</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="customer_dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="customer_profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="customer_orders.php">Orders</a></li>
                                <li><a class="dropdown-item" href="customer_wishlist.php">Wishlist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item logout-btn" href="../logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex auth-buttons">
                        <a href="../login.php" class="btn btn-outline-light me-2">Login</a>
                        <a href="../customer_registration.php" class="btn btn-light">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Cart Section -->
    <section class="py-5">
        <div class="container px-4 px-lg-5">
            <h1 class="mb-4">Shopping Cart</h1>

            <?php if (!$isLoggedIn): ?>
                <!-- Login Required Message -->
                <div class="alert alert-info" role="alert">
                    <h4 class="alert-heading">Login Required</h4>
                    <p>Please login to view your shopping cart.</p>
                    <hr>
                    <p class="mb-0">
                        <a href="../login.php" class="btn btn-success">Login Now</a>
                        <a href="../customer_registration.php" class="btn btn-outline-success ms-2">Register</a>
                    </p>
                </div>
            <?php else: ?>
                <!-- Cart Container -->
                <div id="cart-container">
                    <!-- Loading spinner -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="index.php#about" class="text-white">About Us</a></li>
                        <li><a href="../login.php" class="text-white">Login</a></li>
                        <li><a href="../customer_registration.php" class="text-white">Register</a></li>
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
    <?php if ($isLoggedIn): ?>
        <script src="js/cart.js"></script>
        <script>
            // Initialize cart when page loads
            document.addEventListener('DOMContentLoaded', function() {
                // Update navigation
                updateNavigation();
                
                // Fetch cart data
                fetchCart();
            });
        </script>
    <?php endif; ?>
</body>
</html> 