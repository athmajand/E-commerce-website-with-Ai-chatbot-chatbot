<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'home';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    // Redirect to login page
    header("Location: ../login.php?redirect=frontend/customer_wishlist.php");
    exit;
}

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';
include_once __DIR__ . '/../api/models/CustomerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$customer_data = null;
$error_message = '';
$success_message = '';

// Get customer data
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : $_SESSION['user_id'];
$customer = new CustomerRegistration($db);
$customer->id = $customer_id;

// Fetch customer data
if (!$customer->readOne()) {
    $error_message = "Failed to load customer data.";
}

// Get all wishlist items for the customer
$wishlist_items = [];
try {
    // Check if wishlist table exists
    $check_table_query = "SHOW TABLES LIKE 'wishlist'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Table exists, proceed with query
        $wishlist_query = "SELECT w.id, w.product_id, w.added_date,
                           p.name, p.price, p.discount_price, p.image_url, p.description, p.stock_quantity, p.image_settings
                           FROM wishlist w
                           LEFT JOIN products p ON w.product_id = p.id
                           WHERE w.customer_id = ?
                           ORDER BY w.added_date DESC";
        $wishlist_stmt = $db->prepare($wishlist_query);
        $wishlist_stmt->bindParam(1, $customer_id);
        $wishlist_stmt->execute();
        while ($row = $wishlist_stmt->fetch(PDO::FETCH_ASSOC)) {
            $wishlist_items[] = $row;
        }
    } else {
        // Table doesn't exist, log a message
        error_log("Wishlist table does not exist yet. Skipping wishlist query.");
    }
} catch (PDOException $e) {
    // Log the error but continue execution
    error_log("Error querying wishlist: " . $e->getMessage());
}

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist'])) {
    $wishlist_id = $_POST['wishlist_id'];

    try {
        $remove_query = "DELETE FROM wishlist WHERE id = ? AND customer_id = ?";
        $remove_stmt = $db->prepare($remove_query);
        $remove_stmt->bindParam(1, $wishlist_id);
        $remove_stmt->bindParam(2, $customer_id);

        if ($remove_stmt->execute()) {
            $success_message = "Item removed from wishlist successfully.";
            // Refresh the page to update the wishlist
            header("Location: customer_wishlist.php?success=removed");
            exit;
        } else {
            $error_message = "Failed to remove item from wishlist.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = 1; // Default quantity

    try {
        // Check if cart table exists
        $check_cart_table_query = "SHOW TABLES LIKE 'cart'";
        $check_cart_table_stmt = $db->prepare($check_cart_table_query);
        $check_cart_table_stmt->execute();

        if ($check_cart_table_stmt->rowCount() > 0) {
            // Check if product already in cart
            $check_cart_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
            $check_cart_stmt = $db->prepare($check_cart_query);
            $check_cart_stmt->bindParam(1, $customer_id);
            $check_cart_stmt->bindParam(2, $product_id);
            $check_cart_stmt->execute();

            if ($check_cart_stmt->rowCount() > 0) {
                // Update quantity
                $cart_item = $check_cart_stmt->fetch(PDO::FETCH_ASSOC);
                $new_quantity = $cart_item['quantity'] + $quantity;

                $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $new_quantity);
                $update_stmt->bindParam(2, $cart_item['id']);

                if ($update_stmt->execute()) {
                    $success_message = "Cart updated successfully.";
                    header("Location: customer_wishlist.php?success=added_to_cart");
                    exit;
                } else {
                    $error_message = "Failed to update cart.";
                }
            } else {
                // Add new item to cart
                $add_query = "INSERT INTO cart (customer_id, product_id, quantity, added_date) VALUES (?, ?, ?, NOW())";
                $add_stmt = $db->prepare($add_query);
                $add_stmt->bindParam(1, $customer_id);
                $add_stmt->bindParam(2, $product_id);
                $add_stmt->bindParam(3, $quantity);

                if ($add_stmt->execute()) {
                    $success_message = "Item added to cart successfully.";
                    header("Location: customer_wishlist.php?success=added_to_cart");
                    exit;
                } else {
                    $error_message = "Failed to add item to cart.";
                }
            }
        } else {
            $error_message = "Cart functionality is not available yet.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Check for success message in URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'removed') {
        $success_message = "Item removed from wishlist successfully.";
    } elseif ($_GET['success'] === 'added_to_cart') {
        $success_message = "Item added to cart successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Profile image styles */
        .profile-image-container {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            position: relative;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 3px solid #4CAF50;
        }
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Card styles */
        .dashboard-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* Button styles */
        .btn-success {
            background-color: #4CAF50;
            border-color: #4CAF50;
            border-radius: 8px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        .btn-success:hover {
            background-color: #388E3C;
            border-color: #388E3C;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Card styles */
        .card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        /* Sidebar styles */
        .list-group-item {
            border: none;
            border-radius: 8px !important;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .list-group-item.active {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }

        /* Product card */
        .product-card {
            transition: all 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .product-image {
            height: 180px;
            object-fit: cover;
        }

        /* Wishlist item */
        .wishlist-item {
            border-left: 4px solid #FF9800;
            transition: all 0.3s;
        }
        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* Stock badge */
        .badge-in-stock {
            background-color: #4CAF50;
            color: white;
        }
        .badge-low-stock {
            background-color: #FF9800;
            color: white;
        }
        .badge-out-of-stock {
            background-color: #F44336;
            color: white;
        }
    </style>
</head>
<body>
    <?php
    // Include the navigation bar
    include_once('../includes/navbar.php');
    ?>

    <!-- Wishlist Section -->
    <section class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3 mb-4 mb-lg-0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="profile-image-container mb-3">
                                    <img src="https://via.placeholder.com/150" class="rounded-circle profile-image" alt="Profile Image" id="profile-image">
                                </div>
                                <h5 class="mb-0" id="sidebar-user-name"><?php echo htmlspecialchars($customer->first_name . ' ' . $customer->last_name); ?></h5>
                                <p class="text-muted small" id="sidebar-user-email"><?php echo htmlspecialchars($customer->email); ?></p>
                            </div>
                            <hr>
                            <div class="list-group list-group-flush">
                                <a href="customer_dashboard.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                </a>
                                <a href="customer_profile_settings.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-gear me-2"></i> Profile Settings
                                </a>
                                <a href="customer_orders.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-box me-2"></i> My Orders
                                </a>
                                <a href="customer_wishlist.php" class="list-group-item list-group-item-action active">
                                    <i class="bi bi-heart me-2"></i> My Wishlist
                                </a>
                                <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-9">
                    <!-- Page Title -->
                    <div class="card border-0 shadow-sm mb-4 bg-success bg-opacity-10">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h4 class="mb-1 text-success"><i class="bi bi-heart me-2"></i>My Wishlist</h4>
                                    <p class="mb-0">Items you've saved for later</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Display error/success messages -->
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Wishlist Items -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <?php if (empty($wishlist_items)): ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="bi bi-heart text-muted" style="font-size: 4rem;"></i>
                                    </div>
                                    <h5 class="text-muted">Your Wishlist is Empty</h5>
                                    <p class="text-muted">Save items you like for later by clicking the heart icon on product pages.</p>
                                    <a href="products.php" class="btn btn-success mt-2">
                                        <i class="bi bi-shop me-1"></i> Browse Products
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row row-cols-1 row-cols-md-2 g-4">
                                    <?php foreach ($wishlist_items as $item): ?>
                                        <div class="col">
                                            <div class="card h-100 wishlist-item">
                                                <div class="row g-0">
                                                    <div class="col-md-4">
                                                        <?php
                                                        $settings = json_decode($item['image_settings'] ?? '', true);
                                                        $fit = $settings['fit'] ?? 'cover';
                                                        $zoom = isset($settings['zoom']) ? floatval($settings['zoom']) : 1;
                                                        $panX = isset($settings['panX']) ? intval($settings['panX']) : 0;
                                                        $panY = isset($settings['panY']) ? intval($settings['panY']) : 0;
                                                        $style = "object-fit: $fit; position: absolute; top: 50%; left: 50%; width: 100%; height: 100%; transform-origin: center center; transform: translate(calc(-50% + {$panX}px), calc(-50% + {$panY}px)) scale($zoom);";
                                                        ?>
                                                        <div style="width: 100%; height: 100px; overflow: hidden; position: relative; background: #f8f9fa; border-radius: 6px;">
                                                            <img src="<?php echo !empty($item['image_url']) ? $item['image_url'] : 'https://via.placeholder.com/300x200?text=No+Image'; ?>"
                                                                class="img-fluid rounded-start h-100 border-0 p-0" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                                style="<?php echo $style; ?>"
                                                                onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                            <p class="card-text small text-muted"><?php echo substr(htmlspecialchars($item['description']), 0, 80) . '...'; ?></p>

                                                            <div class="mb-2">
                                                                <?php if (!empty($item['discount_price'])): ?>
                                                                    <span class="text-decoration-line-through text-muted">₹<?php echo number_format($item['price'], 2); ?></span>
                                                                    <span class="fw-bold text-success ms-2">₹<?php echo number_format($item['discount_price'], 2); ?></span>
                                                                <?php else: ?>
                                                                    <span class="fw-bold text-success">₹<?php echo number_format($item['price'], 2); ?></span>
                                                                <?php endif; ?>

                                                                <?php
                                                                    $stock_badge = '';
                                                                    $stock_text = '';

                                                                    if ($item['stock_quantity'] > 10) {
                                                                        $stock_badge = 'badge-in-stock';
                                                                        $stock_text = 'In Stock';
                                                                    } elseif ($item['stock_quantity'] > 0) {
                                                                        $stock_badge = 'badge-low-stock';
                                                                        $stock_text = 'Low Stock';
                                                                    } else {
                                                                        $stock_badge = 'badge-out-of-stock';
                                                                        $stock_text = 'Out of Stock';
                                                                    }
                                                                ?>
                                                                <span class="badge <?php echo $stock_badge; ?> ms-2"><?php echo $stock_text; ?></span>
                                                            </div>

                                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                                <div class="btn-group">
                                                                    <a href="product-details.php?id=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                                        <i class="bi bi-eye"></i> View
                                                                    </a>

                                                                    <?php if ($item['stock_quantity'] > 0): ?>
                                                                        <form method="POST" class="d-inline">
                                                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                                            <button type="submit" name="add_to_cart" class="btn btn-sm btn-success">
                                                                                <i class="bi bi-cart-plus"></i> Add to Cart
                                                                            </button>
                                                                        </form>
                                                                    <?php else: ?>
                                                                        <button class="btn btn-sm btn-secondary" disabled>
                                                                            <i class="bi bi-cart-plus"></i> Out of Stock
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="wishlist_id" value="<?php echo $item['id']; ?>">
                                                                    <button type="submit" name="remove_wishlist" class="btn btn-sm btn-outline-danger"
                                                                            onclick="return confirm('Are you sure you want to remove this item from your wishlist?')">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>

                                                            <p class="card-text mt-2">
                                                                <small class="text-muted">Added on <?php echo date('M d, Y', strtotime($item['added_date'])); ?></small>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="index.html#about" class="text-white">About Us</a></li>
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
    <script src="js/fix-sidebar-links.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
