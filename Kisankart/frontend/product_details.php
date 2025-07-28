<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Debug database connection
if (!$db) {
    die("Database connection failed. Please check your database settings.");
}

// Initialize variables
$product = null;
$related_products = [];
$reviews = [];
$error_message = '';
$success_message = '';
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$userName = $isLoggedIn ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : 'Guest';

// Get product ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error_message = "Product ID is missing.";
} else {
    $product_id = intval($_GET['id']);

    // Debug product ID
    echo "<!-- Debug: Product ID = " . $product_id . " -->";

    // Fetch product details
    try {
        // Check if products table exists
        $check_table_query = "SHOW TABLES LIKE 'products'";
        $check_table_stmt = $db->prepare($check_table_query);
        $check_table_stmt->execute();

        if ($check_table_stmt->rowCount() === 0) {
            $error_message = "Products table does not exist.";
            throw new PDOException("Products table does not exist");
        }

        // Check if product exists
        $check_product_query = "SELECT COUNT(*) as count FROM products WHERE id = ?";
        $check_product_stmt = $db->prepare($check_product_query);
        $check_product_stmt->bindParam(1, $product_id);
        $check_product_stmt->execute();
        $product_count = $check_product_stmt->fetch(PDO::FETCH_ASSOC);

        if ($product_count['count'] == 0) {
            $error_message = "Product with ID " . $product_id . " not found.";
            throw new PDOException("Product not found");
        }

        // Simplified query without seller joins to avoid potential issues
        $product_query = "SELECT p.*, c.name as category_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          WHERE p.id = ?";
        $product_stmt = $db->prepare($product_query);
        $product_stmt->bindParam(1, $product_id);
        $product_stmt->execute();

        if ($product_stmt->rowCount() > 0) {
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

            // Get related products (same category)
            $related_query = "SELECT p.*, c.name as category_name
                             FROM products p
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE p.category_id = ? AND p.id != ?
                             LIMIT 4";
            $related_stmt = $db->prepare($related_query);
            $related_stmt->bindParam(1, $product['category_id']);
            $related_stmt->bindParam(2, $product_id);
            $related_stmt->execute();

            while ($row = $related_stmt->fetch(PDO::FETCH_ASSOC)) {
                $related_products[] = $row;
            }

            // Get product reviews
            $reviews_query = "SHOW TABLES LIKE 'product_reviews'";
            $reviews_table_stmt = $db->prepare($reviews_query);
            $reviews_table_stmt->execute();

            if ($reviews_table_stmt->rowCount() > 0) {
                $reviews_query = "SELECT pr.*, cr.first_name, cr.last_name
                                 FROM product_reviews pr
                                 LEFT JOIN customer_registrations cr ON pr.user_id = cr.id
                                 WHERE pr.product_id = ?
                                 ORDER BY pr.created_at DESC";
                $reviews_stmt = $db->prepare($reviews_query);
                $reviews_stmt->bindParam(1, $product_id);
                $reviews_stmt->execute();

                while ($row = $reviews_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $reviews[] = $row;
                }
            }
        } else {
            $error_message = "Product not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle buy now
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!$isLoggedIn) {
        header("Location: ../login.php?redirect=frontend/product_details.php?id=" . $product_id);
        exit;
    }

    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    try {
        // Check if cart table exists and create it if needed
        $check_table_query = "SHOW TABLES LIKE 'cart'";
        $check_table_stmt = $db->prepare($check_table_query);
        $check_table_stmt->execute();

        if ($check_table_stmt->rowCount() === 0) {
            // Create cart table if it doesn't exist
            $create_table_query = "CREATE TABLE `cart` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `product_id` int(11) NOT NULL,
                `quantity` int(11) NOT NULL DEFAULT 1,
                `added_date` datetime DEFAULT CURRENT_TIMESTAMP,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `customer_product` (`customer_id`, `product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $db->exec($create_table_query);
        }

        // Clear existing cart items for this user (for buy now we want just this item)
        $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->bindParam(1, $userId);
        $clear_cart_stmt->execute();

        // Add this item to cart
        $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(1, $userId);
        $insert_stmt->bindParam(2, $product_id);
        $insert_stmt->bindParam(3, $quantity);
        $insert_stmt->execute();

        // Redirect to checkout page
        header("Location: checkout.php");
        exit;
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$isLoggedIn) {
        header("Location: ../login.php?redirect=frontend/product_details.php?id=" . $product_id);
        exit;
    }

    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    try {
        // Check if cart table exists
        $check_table_query = "SHOW TABLES LIKE 'cart'";
        $check_table_stmt = $db->prepare($check_table_query);
        $check_table_stmt->execute();

        if ($check_table_stmt->rowCount() === 0) {
            // Create cart table if it doesn't exist
            $create_table_query = "CREATE TABLE `cart` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `product_id` int(11) NOT NULL,
                `quantity` int(11) NOT NULL DEFAULT 1,
                `added_date` datetime DEFAULT CURRENT_TIMESTAMP,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `customer_product` (`customer_id`, `product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $db->exec($create_table_query);
        }

        // Check if product already in cart
        $check_cart_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
        $check_cart_stmt = $db->prepare($check_cart_query);
        $check_cart_stmt->bindParam(1, $userId);
        $check_cart_stmt->bindParam(2, $product_id);
        $check_cart_stmt->execute();

        if ($check_cart_stmt->rowCount() > 0) {
            // Update quantity
            $cart_item = $check_cart_stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $cart_item['quantity'] + $quantity;

            $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $new_quantity);
            $update_stmt->bindParam(2, $cart_item['id']);
            $update_stmt->execute();
        } else {
            // Add new item to cart
            $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $userId);
            $insert_stmt->bindParam(2, $product_id);
            $insert_stmt->bindParam(3, $quantity);
            $insert_stmt->execute();
        }

        $success_message = "Product added to cart successfully.";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle add to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    if (!$isLoggedIn) {
        header("Location: ../login.php?redirect=frontend/product_details.php?id=" . $product_id);
        exit;
    }

    try {
        // Check if wishlist table exists
        $check_table_query = "SHOW TABLES LIKE 'wishlist'";
        $check_table_stmt = $db->prepare($check_table_query);
        $check_table_stmt->execute();

        if ($check_table_stmt->rowCount() === 0) {
            // Create wishlist table if it doesn't exist
            $create_table_query = "CREATE TABLE `wishlist` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `product_id` int(11) NOT NULL,
                `added_date` datetime DEFAULT CURRENT_TIMESTAMP,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `customer_product` (`customer_id`, `product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $db->exec($create_table_query);
        }

        // Check if product already in wishlist
        $check_wishlist_query = "SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?";
        $check_wishlist_stmt = $db->prepare($check_wishlist_query);
        $check_wishlist_stmt->bindParam(1, $userId);
        $check_wishlist_stmt->bindParam(2, $product_id);
        $check_wishlist_stmt->execute();

        if ($check_wishlist_stmt->rowCount() === 0) {
            // Add to wishlist
            $insert_query = "INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $userId);
            $insert_stmt->bindParam(2, $product_id);
            $insert_stmt->execute();

            $success_message = "Product added to wishlist successfully.";
        } else {
            $success_message = "Product is already in your wishlist.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get cart count for navigation
$cart_count = 0;
if ($isLoggedIn) {
    try {
        // Check if cart table exists
        $check_table_query = "SHOW TABLES LIKE 'cart'";
        $check_table_stmt = $db->prepare($check_table_query);
        $check_table_stmt->execute();

        if ($check_table_stmt->rowCount() > 0) {
            $cart_query = "SELECT COUNT(*) as count FROM cart WHERE customer_id = ?";
            $cart_stmt = $db->prepare($cart_query);
            $cart_stmt->bindParam(1, $userId);
            $cart_stmt->execute();
            if ($row = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
                $cart_count = $row['count'];
            }
        }
    } catch (PDOException $e) {
        // Silently fail
    }
}

// Page title
$page_title = $product ? $product['name'] . ' - Kisan Kart' : 'Product Details - Kisan Kart';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .thumbnail.active {
            border-color: #4CAF50;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .quantity-selector button {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            width: 40px;
            height: 40px;
        }
        .quantity-selector input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            height: 40px;
        }
        .review-card {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .star-rating {
            color: #FFD700;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-leaf text-success"></i> Kisankart
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">
                            <i class="fas fa-shopping-basket"></i> Products
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="customer_cart.php">
                                <i class="fas fa-shopping-cart"></i> Cart
                                <?php if ($cart_count > 0): ?>
                                    <span class="badge bg-danger"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $userName; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="customer_dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="customer_profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="customer_orders.php">Orders</a></li>
                                <li><a class="dropdown-item" href="customer_wishlist.php">Wishlist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
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
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">
                            <i class="fas fa-info-circle"></i> About Us
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Product Details Section -->
    <section class="py-5">
        <div class="container px-4 px-lg-5">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <div class="text-center">
                    <a href="products.php" class="btn btn-success">Browse Products</a>
                </div>
            <?php elseif (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($product): ?>
                <div class="row gx-4 gx-lg-5 align-items-start">
                    <!-- Product Images -->
                    <div class="col-md-6 mb-4">
                        <?php
                        // Prepare image gallery
                        $main_image = 'https://via.placeholder.com/600x400?text=No+Image+Available';

                        // Handle main image path
                        if (!empty($product['image_url'])) {
                            // Get just the filename from the path
                            $filename = basename($product['image_url']);

                            // Since we're in the frontend folder, we need to go up one level to reach uploads
                            $main_image = '/Kisankart/uploads/products/' . $filename;

                            // Debug information
                            echo "<!-- Debug: Original image_url = " . $product['image_url'] . " -->";
                            echo "<!-- Debug: Constructed main_image path = " . $main_image . " -->";
                        }

                        $additional_images = [];

                        // Handle additional images
                        if (!empty($product['additional_images'])) {
                            $additional_images_data = json_decode($product['additional_images'], true) ?: [];

                            foreach ($additional_images_data as $img) {
                                // Get just the filename from the path
                                $filename = basename($img);

                                // Since we're in the frontend folder, we need to go up one level to reach uploads
                                $img_path = '/Kisankart/uploads/products/' . $filename;

                                $additional_images[] = $img_path;

                                // Debug information
                                echo "<!-- Debug: Original additional image = " . $img . " -->";
                                echo "<!-- Debug: Constructed additional image path = " . $img_path . " -->";
                            }
                        }

                        // Combine main image with additional images
                        $all_images = array_merge([$main_image], $additional_images);

                        // Add hidden debug comments
                        echo "<!-- Debug: Main image path = " . $main_image . " -->";
                        echo "<!-- Debug: Additional images count = " . count($additional_images) . " -->";
                        ?>

                        <!-- Main Image -->
                        <div class="mb-3">
                            <img id="main-product-image" src="<?php echo htmlspecialchars($main_image); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>

                        <!-- Thumbnails -->
                        <div class="d-flex gap-2">
                            <?php foreach ($additional_images as $img_path): ?>
                                <?php
                                $settings = json_decode($product['image_settings'] ?? '', true);
                                $fit = $settings['fit'] ?? 'cover';
                                $zoom = isset($settings['zoom']) ? floatval($settings['zoom']) : 1;
                                $panX = isset($settings['panX']) ? intval($settings['panX']) : 0;
                                $panY = isset($settings['panY']) ? intval($settings['panY']) : 0;
                                $style = "object-fit: $fit; position: absolute; top: 50%; left: 50%; width: 100%; height: 100%; transform-origin: center center; transform: translate(calc(-50% + {$panX}px), calc(-50% + {$panY}px)) scale($zoom);";
                                ?>
                                <div style="width: 60px; height: 60px; overflow: hidden; position: relative; background: #f8f9fa; border-radius: 6px;">
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" class="border-0 p-0" alt="Thumbnail" style="<?php echo $style; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="col-md-6">
                        <h1 class="display-5 fw-bolder"><?php echo htmlspecialchars($product['name']); ?></h1>

                        <!-- Price -->
                        <div class="fs-5 mb-3">
                            <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                <span class="text-decoration-line-through text-muted">₹<?php echo number_format($product['price'], 2); ?></span>
                                <span class="text-success fw-bold">₹<?php echo number_format($product['discount_price'], 2); ?></span>
                                <?php
                                $discount_percent = round(($product['price'] - $product['discount_price']) / $product['price'] * 100);
                                ?>
                                <span class="badge bg-danger ms-2">-<?php echo $discount_percent; ?>%</span>
                            <?php else: ?>
                                <span class="text-success fw-bold">₹<?php echo number_format($product['price'], 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <p class="lead"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                        <!-- Product Details -->
                        <div class="mb-3">
                            <strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                        </div>

                        <div class="mb-3">
                            <strong>Seller:</strong> <?php echo !empty($product['seller_id']) ? 'Seller #' . $product['seller_id'] : 'Kisan Kart'; ?>
                        </div>

                        <div class="mb-3">
                            <strong>Availability:</strong>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <span class="text-success">In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                            <?php else: ?>
                                <span class="text-danger">Out of Stock</span>
                            <?php endif; ?>
                        </div>

                        <!-- Add to Cart Form -->
                        <form method="post" action="product_details.php?id=<?php echo $product_id; ?>">
                            <div class="quantity-selector mb-3">
                                <button type="button" class="btn" onclick="decreaseQuantity()">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="form-control mx-2">
                                <button type="button" class="btn" onclick="increaseQuantity()">+</button>
                            </div>

                            <div class="d-flex mb-2">
                                <button type="submit" name="buy_now" class="btn btn-primary flex-grow-1 me-2" <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-bolt me-1"></i> Buy Now
                                </button>
                                <button type="submit" name="add_to_wishlist" class="btn btn-outline-success">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                            <div class="d-flex">
                                <button type="submit" name="add_to_cart" class="btn btn-success flex-grow-1" <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Related Products Section -->
                <?php if (!empty($related_products)): ?>
                    <div class="row mt-5">
                        <div class="col-12">
                            <h3 class="mb-4">Related Products</h3>
                        </div>

                        <?php foreach ($related_products as $related): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100">
                                    <a href="product_details.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                        <?php if (!empty($related['discount_price']) && $related['discount_price'] < $related['price']): ?>
                                            <?php
                                            $discount_percent = round(($related['price'] - $related['discount_price']) / $related['price'] * 100);
                                            ?>
                                            <div class="badge bg-danger position-absolute" style="top: 0.5rem; right: 0.5rem">
                                                -<?php echo $discount_percent; ?>%
                                            </div>
                                        <?php endif; ?>

                                        <img src="<?php echo !empty($related['image_url']) ? '../uploads/products/' . basename($related['image_url']) : 'https://via.placeholder.com/300x200?text=No+Image'; ?>"
                                            class="card-img-top" style="height: 200px; object-fit: contain;"
                                            alt="<?php echo htmlspecialchars($related['name']); ?>"
                                            onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">

                                        <div class="card-body">
                                            <h5 class="card-title text-dark"><?php echo htmlspecialchars($related['name']); ?></h5>
                                            <p class="card-text text-muted small">
                                                <?php echo htmlspecialchars(substr($related['description'] ?? '', 0, 80)) . '...'; ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?php if (!empty($related['discount_price']) && $related['discount_price'] < $related['price']): ?>
                                                        <span class="text-decoration-line-through text-muted small">₹<?php echo number_format($related['price'], 2); ?></span><br>
                                                        <span class="text-success fw-bold">₹<?php echo number_format($related['discount_price'], 2); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-success fw-bold">₹<?php echo number_format($related['price'], 2); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($related['category_name'] ?? 'Uncategorized'); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

    <!-- Custom JavaScript -->
    <script>
        // Function to change main image when thumbnail is clicked
        function changeMainImage(thumbnail, src) {
            document.getElementById('main-product-image').src = src;

            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }

        // Functions to increase/decrease quantity
        function increaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const maxQuantity = parseInt(quantityInput.getAttribute('max'));
            let currentValue = parseInt(quantityInput.value);

            if (currentValue < maxQuantity) {
                quantityInput.value = currentValue + 1;
            }
        }

        function decreaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            let currentValue = parseInt(quantityInput.value);

            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        }
    </script>
</body>
</html>
