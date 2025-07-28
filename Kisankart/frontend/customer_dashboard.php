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
    header("Location: ../login.php?redirect=frontend/customer_dashboard.php");
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
$customer_id = $_SESSION['user_id'];
$customer = new CustomerRegistration($db);
$customer->id = $customer_id;

// Fetch customer data
if (!$customer->readOne()) {
    $error_message = "Failed to load customer data.";
}

// Get order count
$order_count = 0;
try {
    // Check if orders table exists
    $check_table_query = "SHOW TABLES LIKE 'orders'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Table exists, proceed with count query
        $order_query = "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->bindParam(1, $customer_id);
        $order_stmt->execute();
        if ($row = $order_stmt->fetch(PDO::FETCH_ASSOC)) {
            $order_count = $row['count'];
        }
    } else {
        // Table doesn't exist, log a message
        error_log("Orders table does not exist yet. Skipping order count query.");
    }
} catch (PDOException $e) {
    // Log the error but continue execution
    error_log("Error querying orders: " . $e->getMessage());
}

// Get wishlist count
$wishlist_count = 0;
try {
    // Check if wishlist table exists
    $check_table_query = "SHOW TABLES LIKE 'wishlist'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Table exists, proceed with count query
        $wishlist_query = "SELECT COUNT(*) as count FROM wishlist WHERE customer_id = ?";
        $wishlist_stmt = $db->prepare($wishlist_query);
        $wishlist_stmt->bindParam(1, $customer_id);
        $wishlist_stmt->execute();
        if ($row = $wishlist_stmt->fetch(PDO::FETCH_ASSOC)) {
            $wishlist_count = $row['count'];
        }
    } else {
        // Table doesn't exist, log a message
        error_log("Wishlist table does not exist yet. Skipping wishlist count query.");
    }
} catch (PDOException $e) {
    // Log the error but continue execution
    error_log("Error querying wishlist: " . $e->getMessage());
}

// Get recent orders (last 3)
$recent_orders = [];
try {
    // Check if orders table exists (reusing the check from above)
    if (isset($check_table_stmt) && $check_table_stmt->rowCount() > 0) {
        $recent_orders_query = "SELECT o.id, o.order_date, o.total_amount, o.status
                                FROM orders o
                                WHERE o.customer_id = ?
                                ORDER BY o.order_date DESC
                                LIMIT 3";
        $recent_orders_stmt = $db->prepare($recent_orders_query);
        $recent_orders_stmt->bindParam(1, $customer_id);
        $recent_orders_stmt->execute();
        while ($row = $recent_orders_stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_orders[] = $row;
        }
    } else {
        // Table doesn't exist, log a message
        error_log("Orders table does not exist yet. Skipping recent orders query.");
    }
} catch (PDOException $e) {
    // Log the error but continue execution
    error_log("Error querying recent orders: " . $e->getMessage());
}

// Get recommended products (featured products)
$recommended_products = [];
try {
    // Check if products table exists
    $check_products_query = "SHOW TABLES LIKE 'products'";
    $check_products_stmt = $db->prepare($check_products_query);
    $check_products_stmt->execute();

    if ($check_products_stmt->rowCount() > 0) {
        // Table exists, proceed with query
        $recommended_products_query = "SELECT p.id, p.name, p.price, p.discount_price, p.image_url, p.image_settings
                                      FROM products p
                                      WHERE p.is_featured = 1
                                      LIMIT 3";
        $recommended_products_stmt = $db->prepare($recommended_products_query);
        $recommended_products_stmt->execute();
        while ($row = $recommended_products_stmt->fetch(PDO::FETCH_ASSOC)) {
            $recommended_products[] = $row;
        }
    } else {
        // Table doesn't exist, log a message
        error_log("Products table does not exist yet. Skipping recommended products query.");
    }
} catch (PDOException $e) {
    // Log the error but continue execution
    error_log("Error querying recommended products: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kisan Kart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="../images/favicon.png">
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

        /* Stats icon container */
        .stats-icon-container {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
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

        /* Order status badges */
        .badge-pending {
            background-color: #FFC107;
            color: #212529;
        }
        .badge-processing {
            background-color: #17A2B8;
            color: #fff;
        }
        .badge-shipped {
            background-color: #007BFF;
            color: #fff;
        }
        .badge-delivered {
            background-color: #28A745;
            color: #fff;
        }
        .badge-cancelled {
            background-color: #DC3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php
    // Include the navigation bar
    include_once('../includes/navbar.php');
    ?>

    <!-- Dashboard Section -->
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
                                <a href="customer_dashboard.php" class="list-group-item list-group-item-action active">
                                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                </a>
                                <a href="customer_profile_settings.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-gear me-2"></i> Profile Settings
                                </a>
                                <a href="customer_orders.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-box me-2"></i> My Orders
                                </a>
                                <a href="customer_wishlist.php" class="list-group-item list-group-item-action">
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
                    <!-- Welcome Banner -->
                    <div class="card border-0 shadow-sm mb-4 bg-success bg-opacity-10">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h4 class="mb-1 text-success">Welcome back, <?php echo htmlspecialchars($customer->first_name); ?>!</h4>
                                    <p class="mb-0">Here's what's happening with your Kisan Kart account today.</p>
                                </div>
                                <div class="ms-auto">
                                    <img src="https://via.placeholder.com/100x100?text=KK" alt="Kisan Kart" class="img-fluid" style="max-width: 80px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Stats -->
                    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                        <!-- Orders Stats -->
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon-container bg-success bg-opacity-10">
                                            <i class="bi bi-box fs-1 text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-0">Total Orders</h6>
                                            <h2 class="mb-0"><?php echo $order_count; ?></h2>
                                        </div>
                                    </div>
                                    <hr>
                                    <a href="customer_orders.php" class="btn btn-sm btn-outline-success w-100">
                                        <i class="bi bi-eye me-1"></i> View All Orders
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Wishlist Stats -->
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon-container bg-danger bg-opacity-10">
                                            <i class="bi bi-heart fs-1 text-danger"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-0">Wishlist Items</h6>
                                            <h2 class="mb-0"><?php echo $wishlist_count; ?></h2>
                                        </div>
                                    </div>
                                    <hr>
                                    <a href="customer_wishlist.php" class="btn btn-sm btn-outline-danger w-100">
                                        <i class="bi bi-eye me-1"></i> View Wishlist
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Completion -->
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon-container bg-primary bg-opacity-10">
                                            <i class="bi bi-person-check fs-1 text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-0">Profile Status</h6>
                                            <h2 class="mb-0">
                                                <?php
                                                    // Calculate profile completion percentage
                                                    $fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'postal_code'];
                                                    $filled = 0;
                                                    foreach ($fields as $field) {
                                                        if (!empty($customer->$field)) {
                                                            $filled++;
                                                        }
                                                    }
                                                    $completion = round(($filled / count($fields)) * 100);
                                                    echo $completion . '%';
                                                ?>
                                            </h2>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="progress mb-3" style="height: 10px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $completion; ?>%;"
                                            aria-valuenow="<?php echo $completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <a href="../profile_static.html" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-pencil-square me-1"></i> Complete Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-success">
                                    <i class="bi bi-clock-history me-2"></i> Recent Orders
                                </h5>
                                <a href="customer_orders.php" class="btn btn-sm btn-outline-success">
                                    View All <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_orders)): ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-bag-x text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="text-muted">No Orders Yet</h5>
                                    <p class="text-muted">You haven't placed any orders yet.</p>
                                    <a href="products.php" class="btn btn-success mt-2">
                                        <i class="bi bi-cart me-1"></i> Start Shopping
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Order ID</th>
                                                <th scope="col">Date</th>
                                                <th scope="col">Total</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['id']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>
                                                        <?php
                                                            $status_class = '';
                                                            switch (strtolower($order['status'])) {
                                                                case 'pending':
                                                                    $status_class = 'badge-pending';
                                                                    break;
                                                                case 'processing':
                                                                    $status_class = 'badge-processing';
                                                                    break;
                                                                case 'shipped':
                                                                    $status_class = 'badge-shipped';
                                                                    break;
                                                                case 'delivered':
                                                                    $status_class = 'badge-delivered';
                                                                    break;
                                                                case 'cancelled':
                                                                    $status_class = 'badge-cancelled';
                                                                    break;
                                                                default:
                                                                    $status_class = 'bg-secondary';
                                                            }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recommended Products -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-success">
                                    <i class="bi bi-stars me-2"></i> Recommended For You
                                </h5>
                                <a href="products.html" class="btn btn-sm btn-outline-success">
                                    View All <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recommended_products)): ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-basket text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="text-muted">No Recommendations Yet</h5>
                                    <p class="text-muted">We'll have personalized recommendations for you soon!</p>
                                    <a href="products.html" class="btn btn-success mt-2">
                                        <i class="bi bi-shop me-1"></i> Browse Products
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row row-cols-1 row-cols-md-3 g-4">
                                    <?php foreach ($recommended_products as $product): ?>
                                        <div class="col">
                                            <div class="card h-100 product-card">
                                                <img src="<?php echo !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300x200?text=No+Image'; ?>"
                                                    class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                    onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'"
                                                    <?php
                                                    $settings = json_decode($product['image_settings'] ?? '', true);
                                                    $fit = $settings['fit'] ?? 'cover';
                                                    $zoom = isset($settings['zoom']) ? floatval($settings['zoom']) : 1;
                                                    $panX = isset($settings['panX']) ? intval($settings['panX']) : 0;
                                                    $panY = isset($settings['panY']) ? intval($settings['panY']) : 0;
                                                    $style = "object-fit: $fit; position: absolute; top: 50%; left: 50%; width: 100%; height: 100%; transform-origin: center center; transform: translate(calc(-50% + {$panX}px), calc(-50% + {$panY}px)) scale($zoom);";
                                                    echo 'style="' . $style . '"';
                                                    ?>>
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                                        <div>
                                                            <?php if (!empty($product['discount_price'])): ?>
                                                                <span class="text-decoration-line-through text-muted small">₹<?php echo number_format($product['price'], 2); ?></span><br>
                                                                <span class="fw-bold text-success">₹<?php echo number_format($product['discount_price'], 2); ?></span>
                                                            <?php else: ?>
                                                                <span class="fw-bold text-success">₹<?php echo number_format($product['price'], 2); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <a href="product-details.html?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
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
    <script src="js/customer-dashboard.js"></script>
    <script src="js/fix-sidebar-links.js"></script>
    <!-- Chatbot Integration -->
    <script src="js/chatbot-integration.js"></script>
    <script>
        // Configure chatbot for customer dashboard
        window.kisanKartChatbotConfig = {
            apiBaseUrl: window.location.origin + '/Kisankart/api/chatbot',
            position: 'bottom-right',
            theme: 'green',
            autoOpen: false,
            enableOnPages: ['all'],
            excludePages: ['/admin'],
            enableForRoles: ['all'],
            debug: true
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Add initial opacity and transform for animation
            document.querySelectorAll('.dashboard-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            });

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
