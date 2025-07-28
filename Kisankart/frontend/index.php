<?php
// Start the session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'home';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
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
    <link rel="stylesheet" href="css/dynamic-image-styles.css">
    <style>
        /* Category Cards Styles */
        .category-section {
            margin-bottom: 3rem;
        }

        .category-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 0 auto;
            max-width: 900px;
        }

        .category-card {
            position: relative;
            height: 180px;
            border-radius: 10px;
            overflow: hidden;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-size: cover;
            background-position: center;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .category-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            transition: background 0.3s ease;
        }

        .grains .category-overlay {
            background: rgba(0, 0, 0, 0.45);
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.6));
        }

        .category-card:hover .category-overlay {
            background: rgba(0, 0, 0, 0.2);
        }

        .category-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .category-content i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .category-content h3 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Category background images */
        .vegetables {
            background-image: url('https://images.unsplash.com/photo-1566385101042-1a0aa0c1268c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');
        }

        .fruits {
            background-image: url('https://images.unsplash.com/photo-1619566636858-adf3ef46400b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');
        }

        .dairy {
            background-image: url('https://images.unsplash.com/photo-1628088062854-d1870b4553da?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');
        }

        .grains {
            background-image: url('https://cdn.pixabay.com/photo/2017/07/17/18/13/wheat-2513272_1280.jpg');
            background-position: center;
            background-size: cover;
        }

        .spices {
            background-image: url('https://images.unsplash.com/photo-1532336414038-cf19250c5757?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');
        }

        .organic {
            background-image: url('https://images.unsplash.com/photo-1550989460-0adf9ea622e2?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');
        }

        /* Style for the "Organic" category to make it smaller */
        .category-card.organic {
            grid-column: 2;
            height: 140px;
        }

        .category-card.organic .category-content i {
            font-size: 2rem;
        }

        .other {
            background-image: url('https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');
            height: 140px;
        }

        .other .category-content i {
            font-size: 2.8rem;
        }

        .other .category-content h3 {
            font-size: 1.8rem;
        }

        /* Admin login button style */
        .admin-login-btn {
            color: #fff;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s;
        }

        .admin-login-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        /* Banner Slider Styles */
        .banner-carousel {
            margin-bottom: 0;
        }

        .banner-carousel .carousel-item {
            height: 500px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Removed the black overlay to show images clearly */
        /*
        .banner-carousel .carousel-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }
        */

        .banner-carousel .carousel-caption {
            z-index: 2;
            bottom: 20%;
            text-align: center;
        }

        .banner-carousel .carousel-caption h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .banner-carousel .carousel-caption p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .banner-carousel .carousel-caption .btn {
            font-size: 1.1rem;
            padding: 12px 30px;
            margin: 0 10px;
            border-radius: 50px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .banner-carousel .carousel-caption .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .banner-carousel .carousel-control-prev,
        .banner-carousel .carousel-control-next {
            width: 5%;
            opacity: 0.8;
        }

        .banner-carousel .carousel-control-prev:hover,
        .banner-carousel .carousel-control-next:hover {
            opacity: 1;
        }

        .banner-carousel .carousel-indicators {
            bottom: 30px;
        }

        .banner-carousel .carousel-indicators button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 5px;
            background-color: rgba(255, 255, 255, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .banner-carousel .carousel-indicators button.active {
            background-color: #fff;
            border-color: #fff;
        }

        @media (max-width: 768px) {
            .banner-carousel .carousel-item {
                height: 400px;
            }

            .banner-carousel .carousel-caption h1 {
                font-size: 2.5rem;
            }

            .banner-carousel .carousel-caption p {
                font-size: 1rem;
            }

            .banner-carousel .carousel-caption .btn {
                font-size: 1rem;
                padding: 10px 20px;
                margin: 5px;
            }

            .category-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .category-card {
                height: 150px;
            }

            .category-card.organic {
                grid-column: auto;
            }

            .category-card.other {
                grid-column: auto;
            }

            .category-content i {
                font-size: 2rem;
            }

            .category-content h3 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .banner-carousel .carousel-item {
                height: 350px;
            }

            .banner-carousel .carousel-caption h1 {
                font-size: 2rem;
            }

            .banner-carousel .carousel-caption p {
                font-size: 0.9rem;
            }

            .banner-carousel .carousel-caption .btn {
                font-size: 0.9rem;
                padding: 8px 16px;
                margin: 3px;
            }

            .category-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php
// Include the navigation bar
include_once(__DIR__ . '/../includes/navbar.php');
?>

    <!-- Banner Slider Section -->
    <div id="bannerCarousel" class="carousel slide banner-carousel" data-bs-ride="carousel" data-bs-interval="5000">
        <!-- Carousel Indicators -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="4" aria-label="Slide 5"></button>
            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="5" aria-label="Slide 6"></button>
        </div>

        <!-- Carousel Items -->
        <div class="carousel-inner">
            <!-- Slide 1 - Quality Assured (Now First) -->
            <div class="carousel-item active" style="background-image: url('../images/2452637.jpg');">
                <div class="carousel-caption">
                    <h1 class="display-4 fw-bold">Quality Assured</h1>
                    <p class="lead fw-normal">Every product is carefully selected and quality-checked for your satisfaction.</p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-success btn-lg px-4 me-sm-3" href="products.php">Shop Quality</a>
                        <a class="btn btn-outline-light btn-lg px-4" href="../customer_registration.php">Register Today</a>
                    </div>
                </div>
            </div>

            <!-- Slide 2 - Farm Fresh Products -->
            <div class="carousel-item" style="background-image: url('../images/5858786.jpg');">
                <div class="carousel-caption">
                    <h1 class="display-4 fw-bold">Farm Fresh Products</h1>
                    <p class="lead fw-normal">Connecting farmers directly with customers for fresher produce and better prices.</p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-success btn-lg px-4 me-sm-3" href="../customer_registration.php">Get Started</a>
                        <a class="btn btn-outline-light btn-lg px-4" href="products.php">Browse Products</a>
                    </div>
                </div>
            </div>

            <!-- Slide 3 - Fresh From Farm -->
            <div class="carousel-item" style="background-image: url('../images/5795221.jpg');">
                <div class="carousel-caption">
                    <h1 class="display-4 fw-bold">Fresh From Farm</h1>
                    <p class="lead fw-normal">Get the freshest vegetables and fruits delivered straight from local farmers.</p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-success btn-lg px-4 me-sm-3" href="products.php">Shop Now</a>
                        <a class="btn btn-outline-light btn-lg px-4" href="../seller_registration.php">Become a Seller</a>
                    </div>
                </div>
            </div>

            <!-- Slide 4 - Organic & Natural -->
            <div class="carousel-item" style="background-image: url('../images/3588049.jpg');">
                <div class="carousel-caption">
                    <h1 class="display-4 fw-bold">Organic & Natural</h1>
                    <p class="lead fw-normal">Discover organic products grown with care and without harmful chemicals.</p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-success btn-lg px-4 me-sm-3" href="products.php?category=6">Organic Products</a>
                        <a class="btn btn-outline-light btn-lg px-4" href="../customer_registration.php">Join Now</a>
                    </div>
                </div>
            </div>

            <!-- Slide 5 - Support Local Farmers -->
            <div class="carousel-item" style="background-image: url('../images/5795206.jpg');">
                <div class="carousel-caption">
                    <h1 class="display-4 fw-bold">Support Local Farmers</h1>
                    <p class="lead fw-normal">Help local farmers grow their business while getting quality products.</p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-success btn-lg px-4 me-sm-3" href="products.php">Explore Products</a>
                        <a class="btn btn-outline-light btn-lg px-4" href="../seller_registration.php">Sell Your Products</a>
                    </div>
                </div>
            </div>

            <!-- Slide 6 - Fast Delivery -->
            <div class="carousel-item" style="background-image: url('../images/735741.jpg');">
                <div class="carousel-caption">
                    <h1 class="display-4 fw-bold">Fast Delivery</h1>
                    <p class="lead fw-normal">Quick and reliable delivery to your doorstep with the best customer service.</p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a class="btn btn-success btn-lg px-4 me-sm-3" href="products.php">Order Now</a>
                        <a class="btn btn-outline-light btn-lg px-4" href="../login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carousel Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- Products Section -->
    <section class="py-5" id="products">
        <div class="container px-5">
            <!-- Category Navigation -->
            <div class="category-section">
                <h2 class="text-center mb-4">Shop by Category</h2>
                <div class="category-cards">
                    <!-- First row -->
                    <a href="products.php?category=1" class="category-card vegetables">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="bi bi-flower1"></i>
                            <h3>Vegetables</h3>
                        </div>
                    </a>
                    <a href="products.php?category=2" class="category-card fruits">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="bi bi-apple"></i>
                            <h3>Fruits</h3>
                        </div>
                    </a>
                    <a href="products.php?category=4" class="category-card dairy">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="bi bi-cup-hot"></i>
                            <h3>Dairy</h3>
                        </div>
                    </a>

                    <!-- Second row -->
                    <a href="products.php?category=3" class="category-card grains">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="bi bi-basket" style="font-size: 3rem; text-shadow: 0 3px 5px rgba(0, 0, 0, 0.5);"></i>
                            <h3>Grains</h3>
                        </div>
                    </a>
                    <a href="products.php?category=6" class="category-card organic">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="bi bi-tree"></i>
                            <h3>Organic</h3>
                        </div>
                    </a>
                    <a href="products.php?category=5" class="category-card spices">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="bi bi-droplet"></i>
                            <h3>Spices</h3>
                        </div>
                    </a>

                    <!-- Third row - only Other -->
                    <a href="products.php?category=7" class="category-card other" style="grid-column: 1 / span 3;">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="bi bi-grid"></i>
                            <h3>Other</h3>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="py-5 bg-white" id="featured-products-section">
        <div class="container px-5">
            <h2 class="text-center mb-5">Featured Products</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 products-container" id="products-container">
                <?php
                // Clean featured products implementation
                try {
                    // Database connection - fixed path
                    require_once '../api/config/database.php';
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    // Get featured products
                    $query = "SELECT * FROM products WHERE is_featured = 1 AND status = 'active' ORDER BY id DESC LIMIT 6";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Fallback to all products if no featured products
                    if (empty($featured_products)) {
                        $query = "SELECT * FROM products ORDER BY id DESC LIMIT 6";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    
                    // Display products
                    if (!empty($featured_products)) {
                        foreach ($featured_products as $product) {
                            $name = isset($product['name']) ? $product['name'] : 'Unnamed Product';
                            $price = isset($product['price']) ? number_format($product['price'], 2) : '0.00';
                            $description = isset($product['description']) ? substr($product['description'], 0, 80) . '...' : 'No description available';
                            $discount_price = isset($product['discount_price']) && !empty($product['discount_price']) ? number_format($product['discount_price'], 2) : null;
                            
                            // Calculate discount
                            $discount_badge = '';
                            if ($discount_price && $product['price'] > $product['discount_price']) {
                                $discount = ((float)$product['price'] - (float)$product['discount_price']) / (float)$product['price'] * 100;
                                $discount_badge = '<span class="badge bg-danger position-absolute top-0 end-0 m-2">-' . round($discount) . '%</span>';
                            }
                            
                            // Image handling
                            $image_url = 'https://via.placeholder.com/300x200?text=Product+Image';
                            if (isset($product['image_url']) && !empty($product['image_url'])) {
                                $image_url = '../' . $product['image_url'];
                            } elseif (isset($product['image']) && !empty($product['image'])) {
                                $image_url = '../' . $product['image'];
                            }
                            ?>
                            <div class="col">
                                <div class="card product-card">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none d-block">
                                        <div class="position-relative">
                                            <?php echo $discount_badge; ?>
                                            <div class="image-container" style="width: 300px !important; height: 200px !important; overflow: hidden !important; position: relative !important; border: 1px solid #ddd !important; background-color: #f8f9fa !important; border-radius: 8px !important; margin: 0 auto !important; display: flex !important; align-items: center !important; justify-content: center !important;">
                                                <img src="<?php echo $image_url; ?>"
                                                    class="product-image" alt="<?php echo htmlspecialchars($name); ?>" loading="lazy" style="position: absolute !important; top: 50% !important; left: 50% !important; transform-origin: center center !important; transform: translate(calc(-50% + 0px), calc(-50% + 0px)) scale(1) !important; object-fit: contain !important; width: 100% !important; height: 100% !important; user-select: none !important; pointer-events: none !important; transition: transform 0.2s ease !important; padding: 0 !important; margin: 0 !important; max-width: none !important; max-height: none !important;"
                                                    onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2Yzc1N2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIwLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';">
                                            </div>
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title text-dark" style="height: 48px; overflow: hidden;"><?php echo htmlspecialchars($name); ?></h5>
                                            <p class="card-text text-muted small flex-grow-1" style="height: 60px; overflow: hidden;"><?php echo htmlspecialchars($description); ?></p>
                                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                                <div>
                                                    <?php if ($discount_price): ?>
                                                        <span class="text-decoration-line-through text-muted small">₹<?php echo $price; ?></span><br>
                                                        <span class="fs-5 fw-bold text-success">₹<?php echo $discount_price; ?></span>
                                                    <?php else: ?>
                                                        <span class="fs-5 fw-bold text-success">₹<?php echo $price; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="card-footer bg-transparent border-top-0">
                                        <!-- Buy Now Button -->
                                        <button class="btn btn-primary w-100 mb-2 buy-now-btn" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="bi bi-bag-check"></i> Buy Now
                                        </button>

                                        <!-- Add to Cart and Wishlist Buttons -->
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>" aria-label="Add <?php echo htmlspecialchars($name); ?> to cart">
                                                <i class="bi bi-cart-plus" aria-hidden="true"></i> Add to Cart
                                            </button>
                                            <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="<?php echo $product['id']; ?>" aria-label="Add <?php echo htmlspecialchars($name); ?> to wishlist">
                                                <i class="bi bi-heart" aria-hidden="true"></i>
                                                <span class="sr-only">Add to Wishlist</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="col-12 text-center">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No products found in database. Please check back later.
                            </div>
                            <a href="products.php" class="btn btn-success">Browse All Products</a>
                        </div>
                        <?php
                    }
                } catch (Exception $e) {
                    ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Database Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?><br>
                            <strong>File:</strong> <?php echo htmlspecialchars($e->getFile()); ?><br>
                            <strong>Line:</strong> <?php echo $e->getLine(); ?>
                        </div>
                        <a href="products.php" class="btn btn-success">Browse All Products</a>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="text-center mt-4">
                <a href="products.php" class="btn btn-success">View All Products</a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5 bg-light">
        <div class="container px-5">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature bg-success bg-gradient text-white rounded-circle mb-4 mx-auto d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-person-plus-fill fs-4"></i>
                            </div>
                            <h5 class="card-title">Register</h5>
                            <p class="card-text">Sign up as a farmer to sell your products or as a customer to buy fresh produce.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature bg-success bg-gradient text-white rounded-circle mb-4 mx-auto d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-cart-fill fs-4"></i>
                            </div>
                            <h5 class="card-title">Shop or Sell</h5>
                            <p class="card-text">Browse and purchase products as a customer, or list and manage your products as a farmer.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature bg-success bg-gradient text-white rounded-circle mb-4 mx-auto d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-truck fs-4"></i>
                            </div>
                            <h5 class="card-title">Delivery</h5>
                            <p class="card-text">Get your orders delivered to your doorstep or arrange pickup from the farmer.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5" id="about">
        <div class="container px-5">
            <div class="row gx-5 align-items-center">
                <div class="col-lg-6">
                    <div class="text-center position-relative">
                        <!-- Background Image -->
                        <img src="../images/1164050 (1).jpg" class="img-fluid rounded shadow" alt="Farmers working" style="max-height: 300px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/600x400?text=About+Kisan+Kart'">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ps-lg-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="../images/farmer-logo.png" alt="Farmer Icon" style="width: 40px; height: 40px; margin-right: 15px;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iMjAiIGZpbGw9IiMyOGE3NDUiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0id2hpdGUiIHN0eWxlPSJtYXJnaW46IDhweDsiPgo8cGF0aCBkPSJNMTIgMkM2LjQ4IDIgMiA2LjQ4IDIgMTJzNC40OCAxMCAxMCAxMCAxMC00LjQ4IDEwLTEwUzE3LjUyIDIgMTIgMnptMCAxOGMtNC40MSAwLTgtMy41OS04LTggMC0xLjA5LjIxLTIuMTQuNTgtMy4xMkM2LjQyIDkuNzIgOS4xNCA5IDEyIDlzNS41OC43MiA3LjQyIDEuODhDMTkuNzkgOS44NiAyMCAxMC45MSAyMCAxMmMwIDQuNDEtMy41OSA4LTggOHoiLz4KPC9zdmc+Cjwvc3ZnPgo='">
                            <h2 class="fw-bold mb-0">About Kisan Kart</h2>
                        </div>
                        <p class="lead">Kisan Kart facilitates direct engagement between farmers and customers, ensuring both benefit from improved pricing and quality assurance.</p>
                        <p>Our mission is to empower farmers by providing them with a digital platform to showcase and sell their products, while giving customers access to fresh, farm-direct produce at reasonable prices.</p>
                        
                        <!-- Key Features -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Direct Farmer-Customer Connection</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Quality Assured Products</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Fair Pricing for All</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Supporting Local Agriculture</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container px-5">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h5>Kisankart</h5>
                    <p class="mb-0">Connecting farmers directly with customers for fresher produce and better prices.</p>
                </div>
                <div class="col-lg-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="products.php" class="text-white-50 text-decoration-none">Products</a></li>
                        <li><a href="../customer_registration.php" class="text-white-50 text-decoration-none">Register</a></li>
                        <li><a href="../login.php" class="text-white-50 text-decoration-none">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li class="text-white-50">Email: info@kisankart.com</li>
                        <li class="text-white-50">Phone: +91 1234567890</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 Kisankart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/cart.js"></script>
    <script src="js/wishlist.js"></script>
    <script src="js/product-interactions.js"></script>
    
    <!-- Banner Carousel JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the banner carousel with automatic sliding
            const bannerCarousel = new bootstrap.Carousel(document.getElementById('bannerCarousel'), {
                interval: 5000, // 5 seconds between slides
                ride: 'carousel', // Start sliding automatically
                wrap: true, // Loop back to first slide after last
                pause: 'hover' // Pause on mouse hover
            });
            
            // Ensure the carousel starts automatically
            bannerCarousel.cycle();
            
            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    bannerCarousel.prev();
                } else if (e.key === 'ArrowRight') {
                    bannerCarousel.next();
                }
            });
        });
    </script>
</body>
</html>
