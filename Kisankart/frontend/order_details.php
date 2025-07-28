<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    // Redirect to login page
    header("Location: ../login.php?redirect=frontend/order_details.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to orders page
    header("Location: customer_orders.php");
    exit;
}

$order_id = $_GET['id'];

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

// Get order details
$order = null;
$order_items = [];

try {
    // Check if orders table exists
    $check_table_query = "SHOW TABLES LIKE 'orders'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Table exists, proceed with query
        $order_query = "SELECT o.id, o.order_date, o.total_amount, o.status, o.tracking_number,
                               o.shipping_address, o.billing_address, o.payment_method, o.payment_status,
                               o.notes
                        FROM orders o
                        WHERE o.id = ? AND o.customer_id = ?
                        LIMIT 1";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->bindParam(1, $order_id);
        $order_stmt->bindParam(2, $customer_id);
        $order_stmt->execute();

        if ($order_stmt->rowCount() > 0) {
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

            // Get order items
            $items_query = "SELECT oi.id, oi.product_id, oi.quantity, oi.price, oi.discount, oi.total,
                                  p.name as product_name, p.image_url
                           FROM order_items oi
                           LEFT JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->bindParam(1, $order_id);
            $items_stmt->execute();

            while ($row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
                $order_items[] = $row;
            }
        } else {
            // Order not found or doesn't belong to this customer
            header("Location: customer_orders.php");
            exit;
        }
    } else {
        // Table doesn't exist, log a message
        error_log("Orders table does not exist yet. Skipping order details query.");
        header("Location: customer_orders.php");
        exit;
    }
} catch (PDOException $e) {
    // Log the error but continue execution
    error_log("Error querying order details: " . $e->getMessage());
    header("Location: customer_orders.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Kisan Kart</title>
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

        /* Product image */
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Timeline styles */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-dot {
            position: absolute;
            left: -30px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #4CAF50;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
        }
        .timeline-dot.pending {
            background-color: #FFC107;
        }
        .timeline-dot.processing {
            background-color: #17A2B8;
        }
        .timeline-dot.shipped {
            background-color: #007BFF;
        }
        .timeline-dot.delivered {
            background-color: #28A745;
        }
        .timeline-dot.cancelled {
            background-color: #DC3545;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">Kisan Kart</a>
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
                <div class="d-flex user-menu">
                    <a href="cart.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-cart"></i> Cart <span class="cart-count badge bg-light text-dark">0</span>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <span class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="customer_dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="customer_profile.php">Profile</a></li>
                            <li><a class="dropdown-item active" href="customer_orders.php">Orders</a></li>
                            <li><a class="dropdown-item" href="customer_wishlist.php">Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item logout-btn" href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Order Details Section -->
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
                                <a href="customer_profile.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-person me-2"></i> My Profile
                                </a>
                                <a href="customer_orders.php" class="list-group-item list-group-item-action active">
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
                    <!-- Back Button -->
                    <div class="mb-3">
                        <a href="customer_orders.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Orders
                        </a>
                    </div>

                    <!-- Order Summary -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-success">
                                    <i class="bi bi-receipt me-2"></i> Order #<?php echo $order['id']; ?>
                                </h5>
                                <span class="badge <?php
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
                                    echo $status_class;
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Order Date</h6>
                                    <p><?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Order Total</h6>
                                    <p class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Payment Method</h6>
                                    <p><?php echo $order['payment_method']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Payment Status</h6>
                                    <p><?php echo ucfirst($order['payment_status']); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($order['tracking_number'])): ?>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h6 class="text-muted">Tracking Number</h6>
                                    <p><?php echo $order['tracking_number']; ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Shipping Address</h6>
                                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Billing Address</h6>
                                    <p><?php echo nl2br(htmlspecialchars($order['billing_address'] ?? $order['shipping_address'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 text-success">
                                <i class="bi bi-box-seam me-2"></i> Order Items
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($order_items)): ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="text-muted">No Items Found</h5>
                                    <p class="text-muted">No items were found for this order. This might be because the order items table is not properly set up or populated.</p>
                                    <p class="text-muted small">Order Total: ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Product</th>
                                                <th scope="col">Price</th>
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo !empty($item['image_url']) ? $item['image_url'] : 'https://via.placeholder.com/60x60?text=No+Image'; ?>"
                                                                class="product-image me-3" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td>₹<?php echo number_format($item['total'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                                <td class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Timeline -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 text-success">
                                <i class="bi bi-clock-history me-2"></i> Order Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php
                                    // Define the order status timeline
                                    $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                                    $current_status = strtolower($order['status']);

                                    // If order is cancelled, show only that status
                                    if ($current_status === 'cancelled') {
                                        echo '<div class="timeline-item">
                                                <div class="timeline-dot cancelled"><i class="bi bi-x-lg"></i></div>
                                                <div class="timeline-content">
                                                    <h6 class="mb-0">Order Cancelled</h6>
                                                    <p class="text-muted small mb-0">'.date('M d, Y', strtotime($order['created_at'])).'</p>
                                                </div>
                                              </div>';
                                    } else {
                                        // Show the normal order flow
                                        $reached_current = false;
                                        foreach ($statuses as $status) {
                                            $is_active = false;
                                            $is_completed = false;

                                            if ($status === $current_status) {
                                                $is_active = true;
                                                $reached_current = true;
                                            } else if (!$reached_current) {
                                                $is_completed = true;
                                            }

                                            $status_icon = '';
                                            $status_date = '';

                                            switch ($status) {
                                                case 'pending':
                                                    $status_icon = '<i class="bi bi-hourglass-split"></i>';
                                                    $status_title = 'Order Placed';
                                                    $status_date = date('M d, Y', strtotime($order['created_at']));
                                                    break;
                                                case 'processing':
                                                    $status_icon = '<i class="bi bi-gear"></i>';
                                                    $status_title = 'Processing';
                                                    $status_date = $is_completed || $is_active ? date('M d, Y', strtotime($order['created_at'])) : '';
                                                    break;
                                                case 'shipped':
                                                    $status_icon = '<i class="bi bi-truck"></i>';
                                                    $status_title = 'Shipped';
                                                    $status_date = $is_completed || $is_active ? date('M d, Y', strtotime($order['created_at'])) : '';
                                                    break;
                                                case 'delivered':
                                                    $status_icon = '<i class="bi bi-check-lg"></i>';
                                                    $status_title = 'Delivered';
                                                    $status_date = $is_completed || $is_active ? date('M d, Y', strtotime($order['created_at'])) : '';
                                                    break;
                                            }

                                            echo '<div class="timeline-item">
                                                    <div class="timeline-dot '.($is_active ? $status : '').($is_completed ? ' bg-success' : '').(!$is_active && !$is_completed ? ' bg-light text-dark' : '').'">
                                                        '.$status_icon.'
                                                    </div>
                                                    <div class="timeline-content">
                                                        <h6 class="mb-0 '.(!$is_active && !$is_completed ? 'text-muted' : '').'">'.$status_title.'</h6>
                                                        <p class="text-muted small mb-0">'.($is_completed || $is_active ? $status_date : 'Pending').'</p>
                                                    </div>
                                                  </div>';
                                        }
                                    }
                                ?>
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
</body>
</html>
