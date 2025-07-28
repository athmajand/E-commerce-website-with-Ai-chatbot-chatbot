<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=frontend/order_confirmation.php");
    exit;
}

// Initialize variables
$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$order = null;
$order_items = [];
$error_message = '';

// Get order ID from URL parameter
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $error_message = "Order ID is missing.";
} else {
    $order_id = intval($_GET['order_id']);

    // Get order details
    try {
        $order_query = "SELECT * FROM orders WHERE id = ? AND customer_id = ?";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->bindParam(1, $order_id);
        $order_stmt->bindParam(2, $userId);
        $order_stmt->execute();

        if ($order_stmt->rowCount() > 0) {
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

            // Get order items
            $items_query = "SELECT oi.*, p.name, p.image_url
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->bindParam(1, $order_id);
            $items_stmt->execute();

            while ($row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
                $order_items[] = $row;
            }
        } else {
            $error_message = "Order not found or you don't have permission to view it.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Page title
$page_title = "Order Confirmation - Kisan Kart";
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
                <div class="d-flex">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo $userName; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="customer_dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="customer_profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="customer_orders.php">Orders</a></li>
                            <li><a class="dropdown-item" href="customer_wishlist.php">Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Order Confirmation Section -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <div class="text-center">
                    <a href="customer_orders.php" class="btn btn-success">View Your Orders</a>
                </div>
            <?php elseif ($order): ?>
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Order Placed Successfully!</h2>
                    <p class="lead">Thank you for your order. Your order has been placed successfully.</p>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Order #<?php echo $order['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Order Details</h6>
                                <p><strong>Order Date:</strong>
                                    <?php
                                    // Check which date field exists in the order array
                                    if (isset($order['order_date']) && !empty($order['order_date'])) {
                                        echo date('F j, Y, g:i a', strtotime($order['order_date']));
                                    } elseif (isset($order['created_at']) && !empty($order['created_at'])) {
                                        echo date('F j, Y, g:i a', strtotime($order['created_at']));
                                    } else {
                                        echo date('F j, Y, g:i a'); // Current date/time as fallback
                                    }
                                    ?>
                                </p>
                                <p><strong>Order Status:</strong> <span class="badge bg-warning text-dark"><?php echo ucfirst($order['status']); ?></span></p>
                                <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                                <p><strong>Payment Status:</strong> <span class="badge bg-warning text-dark"><?php echo ucfirst($order['payment_status']); ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Shipping Address</h6>
                                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                <p><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_state']); ?> - <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="mb-3">Order Items</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['image_url'])): ?>
                                                <img src="<?php echo 'uploads/products/' . basename($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-center mt-4">
                    <a href="customer_orders.php" class="btn btn-success">View All Orders</a>
                    <a href="products.php" class="btn btn-outline-success ms-2">Continue Shopping</a>
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
</body>
</html>
