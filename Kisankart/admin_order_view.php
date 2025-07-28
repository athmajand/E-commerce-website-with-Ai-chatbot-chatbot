<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
$order_items = [];
$error_message = '';
$success_message = '';

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE orders SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $order_id);

        if ($update_stmt->execute()) {
            $success_message = "Order status updated successfully!";
        } else {
            $error_message = "Failed to update order status: " . $conn->error;
        }
    } else {
        $error_message = "Invalid status value!";
    }
}

// Get order details
if ($order_id > 0) {
    // Get order information
    $order_query = "SELECT o.*, CONCAT(cr.first_name, ' ', cr.last_name) as customer_name,
                    cr.email as customer_email, cr.phone as customer_phone
                    FROM orders o
                    LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                    WHERE o.id = ?";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();

    if ($order_result && $order_result->num_rows > 0) {
        $order = $order_result->fetch_assoc();

        // Get order items
        $items_query = "SELECT oi.*, p.name as product_name, p.image as product_image,
                        CONCAT(sr.first_name, ' ', sr.last_name) as seller_name
                        FROM order_items oi
                        LEFT JOIN products p ON oi.product_id = p.id
                        LEFT JOIN seller_registrations sr ON p.seller_id = sr.id
                        WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        if ($items_result) {
            while ($item = $items_result->fetch_assoc()) {
                $order_items[] = $item;
            }
        }
    } else {
        $error_message = "Order not found!";
    }
} else {
    $error_message = "Invalid order ID!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Kisan Kart Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e8449;
            --accent-color: #FF9800;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-color);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--light-gray);
            color: var(--primary-color);
        }

        .sidebar-menu a.active {
            border-left: 4px solid var(--primary-color);
        }

        .sidebar-menu i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #166036;
            border-color: #166036;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .order-info-item {
            margin-bottom: 15px;
        }

        .order-info-item .label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                width: 250px;
            }

            .main-content.active {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Kisan Kart Admin</h3>
        </div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="admin_customers.php"><i class="bi bi-people"></i> Customers</a>
            <a href="admin_sellers.php"><i class="bi bi-shop"></i> Sellers</a>
            <a href="admin_products.php"><i class="bi bi-box"></i> Products</a>
            <a href="admin_orders.php" class="active"><i class="bi bi-cart"></i> Orders</a>
            <a href="admin_categories.php"><i class="bi bi-tags"></i> Categories</a>
            <a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a>
            <a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Order Details</h1>
                <a href="admin_orders.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($order): ?>
                <div class="row">
                    <div class="col-md-8">
                        <!-- Order Items -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Order #<?php echo $order['id']; ?></h5>
                                <span class="badge <?php
                                    echo match($order['status']) {
                                        'pending' => 'bg-warning',
                                        'processing' => 'bg-info',
                                        'shipped' => 'bg-primary',
                                        'delivered' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($order_items)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No items found for this order</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($item['product_image']) && file_exists($item['product_image'])): ?>
                                                                    <img src="<?php echo $item['product_image']; ?>" class="product-image me-3" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                                <?php else: ?>
                                                                    <div class="product-image me-3 bg-light d-flex align-items-center justify-content-center">
                                                                        <i class="bi bi-image text-muted"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                                    <small class="text-muted">Seller: <?php echo htmlspecialchars($item['seller_name']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                        <td><?php echo $item['quantity']; ?></td>
                                                        <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr>
                                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                                    <td class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="order-info-item">
                                    <div class="label">Name</div>
                                    <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                </div>
                                <?php if (!empty($order['customer_email'])): ?>
                                <div class="order-info-item">
                                    <div class="label">Email</div>
                                    <div><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($order['customer_phone'])): ?>
                                <div class="order-info-item">
                                    <div class="label">Phone</div>
                                    <div><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Order Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="order-info-item">
                                    <div class="label">Order Date</div>
                                    <div><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></div>
                                </div>
                                <div class="order-info-item">
                                    <div class="label">Payment Method</div>
                                    <div><?php echo htmlspecialchars($order['payment_method']); ?></div>
                                </div>
                                <div class="order-info-item">
                                    <div class="label">Payment Status</div>
                                    <div>
                                        <span class="badge <?php
                                            echo match($order['payment_status']) {
                                                'paid' => 'bg-success',
                                                'pending' => 'bg-warning',
                                                'failed' => 'bg-danger',
                                                'refunded' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="order-info-item">
                                    <div class="label">Shipping Address</div>
                                    <div><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                                </div>
                                <?php if (!empty($order['tracking_number'])): ?>
                                <div class="order-info-item">
                                    <div class="label">Tracking Number</div>
                                    <div><?php echo htmlspecialchars($order['tracking_number']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Update Order Status -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Update Order Status</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="new_status" class="form-label">Status</label>
                                        <select class="form-select" id="new_status" name="new_status">
                                            <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <h4>Order Not Found</h4>
                        <p class="text-muted">The order you are looking for does not exist or has been removed.</p>
                        <a href="admin_orders.php" class="btn btn-primary mt-3">Go Back to Orders</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
