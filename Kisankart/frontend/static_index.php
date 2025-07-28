<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisankart - Connecting Farmers and Customers</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="../images/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/product-cards.css">

    <style>
        /* Additional styles to prevent layout shifts */
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .product-card {
            display: flex;
            flex-direction: column;
        }

        .product-card .card-body {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-card .card-footer {
            margin-top: auto;
            min-height: 58px;
        }

        .price-container {
            min-height: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card-text {
            min-height: 3em;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="../images/farmer-logo.png" alt="Kisankart Logo" style="height: 32px; width: 32px; margin-right: 8px;"> Kisankart
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">
                            <i class="fas fa-shopping-basket"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../login.php">
                            <i class="fas fa-user"></i> Customer Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../seller_login.php">
                            <i class="fas fa-store"></i> Seller Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../customer_registration.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">
                            <i class="fas fa-info-circle"></i> About Us
                        </a>
                    </li>
                </ul>
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
            <div class="row g-4" id="featured-products">
                <!-- Static product cards -->
                <div class="col-md-6 col-lg-4">
                    <div class="card product-card h-100">
                        <a href="product-details.html?id=1" class="text-decoration-none">
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-18%</span>
                            <img src="https://via.placeholder.com/300x200?text=Organic+Tomatoes" class="card-img-top product-image" alt="Organic Tomatoes">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Organic Tomatoes</h5>
                                <p class="card-text text-muted small">Fresh organic tomatoes from local farms</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="price-container">
                                        <span class="text-decoration-line-through text-muted small">₹120.00</span><br>
                                        <span class="fs-5 fw-bold text-success">₹99.00</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <a href="../login.php" class="btn btn-success flex-grow-1 me-2">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </a>
                            <a href="../login.php" class="btn btn-outline-success">
                                <i class="bi bi-heart"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card product-card h-100">
                        <a href="product-details.html?id=2" class="text-decoration-none">
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-9%</span>
                            <img src="https://via.placeholder.com/300x200?text=Premium+Rice" class="card-img-top product-image" alt="Premium Rice">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Premium Rice</h5>
                                <p class="card-text text-muted small">High-quality basmati rice</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="price-container">
                                        <span class="text-decoration-line-through text-muted small">₹350.00</span><br>
                                        <span class="fs-5 fw-bold text-success">₹320.00</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <a href="../login.php" class="btn btn-success flex-grow-1 me-2">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </a>
                            <a href="../login.php" class="btn btn-outline-success">
                                <i class="bi bi-heart"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card product-card h-100">
                        <a href="product-details.html?id=3" class="text-decoration-none">
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-17%</span>
                            <img src="https://via.placeholder.com/300x200?text=Fresh+Apples" class="card-img-top product-image" alt="Fresh Apples">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Fresh Apples</h5>
                                <p class="card-text text-muted small">Crisp and juicy apples</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="price-container">
                                        <span class="text-decoration-line-through text-muted small">₹180.00</span><br>
                                        <span class="fs-5 fw-bold text-success">₹150.00</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <a href="../login.php" class="btn btn-success flex-grow-1 me-2">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </a>
                            <a href="../login.php" class="btn btn-outline-success">
                                <i class="bi bi-heart"></i>
                            </a>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
