<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Kisan Kart</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-green: #28a745;
            --secondary-green: #20c997;
            --dark-green: #1e7e34;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: var(--primary-green) !important;
        }
        
        .btn-success {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        
        .btn-success:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
        }
        
        .text-success {
            color: var(--primary-green) !important;
        }
        
        .product-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        
        .image-container {
            background-color: #f8f9fa;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        
        .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .card-footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop me-2"></i>Kisan Kart
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products_page.php">
                            <i class="bi bi-grid me-1"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-tags me-1"></i>Categories
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="bi bi-cart me-1"></i>Cart
                                <span class="badge bg-success cart-count">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="wishlist.php">
                                <i class="bi bi-heart me-1"></i>Wishlist
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person me-1"></i>Account
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Customer Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="seller_login.php">
                                <i class="bi bi-shop me-1"></i>Seller Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="bi bi-person-plus me-1"></i>Register
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about.php">
                                <i class="bi bi-info-circle me-1"></i>About Us
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
