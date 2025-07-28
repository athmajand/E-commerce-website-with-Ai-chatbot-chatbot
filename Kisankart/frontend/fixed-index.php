<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisan Kart - Connecting Farmers and Customers</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/product-cards.css">
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="../login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="../customer_registration.php" class="btn btn-light me-2">Register</a>
                    <a href="../seller_login.php" class="login-nav-link seller-login-btn" title="Login as a seller"><i class="fas fa-store"></i> Seller Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="bg-light py-5">
        <div class="container px-5">
            <div class="row gx-5 align-items-center">
                <div class="col-lg-6">
                    <div class="mb-5 mb-lg-0 text-center text-lg-start">
                        <h1 class="display-4 fw-bold">Farm Fresh Products</h1>
                        <p class="lead fw-normal text-muted mb-4">Connecting farmers directly with customers for fresher produce and better prices.</p>
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center justify-content-lg-start">
                            <a class="btn btn-success btn-lg px-4 me-sm-3" href="../customer_registration.php">Get Started</a>
                            <a class="btn btn-outline-success btn-lg px-4" href="#products">Browse Products</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/hero-image.jpg" class="img-fluid rounded" alt="Farm fresh vegetables" onerror="this.src='https://via.placeholder.com/600x400?text=Farm+Fresh+Products'">
                </div>
            </div>
        </div>
    </header>

    <!-- Featured Products Section -->
    <section class="py-5" id="products">
        <div class="container px-5">
            <h2 class="text-center mb-5">Featured Products</h2>
            <div class="row" id="featured-products" data-php-loaded="true">
                <!-- Static product cards to prevent continuous reloading -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="product-details.html?id=3" class="text-decoration-none">
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-13%</span>
                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top product-image" alt="Fresh Tomatoes" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Fresh Tomatoes</h5>
                                <p class="card-text text-muted small">Organically grown fresh tomatoes from local farms....</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><span class="text-decoration-line-through text-muted small">₹40.00</span><br>
                                    <span class="fs-5 fw-bold text-success">₹35.00</span></div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="3">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="3">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="product-details.html?id=4" class="text-decoration-none">
                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top product-image" alt="Organic Potatoes" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Organic Potatoes</h5>
                                <p class="card-text text-muted small">Premium quality potatoes grown without pesticides....</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><span class="fs-5 fw-bold text-success">₹30.00</span></div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="4">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="4">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="product-details.html?id=5" class="text-decoration-none">
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-20%</span>
                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top product-image" alt="Fresh Onions" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Fresh Onions</h5>
                                <p class="card-text text-muted small">High-quality onions from local farms....</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><span class="text-decoration-line-through text-muted small">₹25.00</span><br>
                                    <span class="fs-5 fw-bold text-success">₹20.00</span></div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="5">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="5">
                                <i class="bi bi-heart"></i>
                            </button>
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
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="#products" class="text-white">Products</a></li>
                        <li><a href="#about" class="text-white">About Us</a></li>
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

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>
    <script src="js/products-fixed.js"></script>
</body>
</html>
