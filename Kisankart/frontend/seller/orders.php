<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include session helper
require_once '../../api/helpers/session_helper.php';

// Synchronize seller IDs in session
synchronizeSellerSessionIds();

// Check if user is logged in as a seller
if (!getSellerIdFromSession() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    // Redirect to login page if not logged in as a seller
    header("Location: ../../seller_login.php?redirect=frontend/seller/orders.php");
    exit;
}

// Include database configuration
include_once __DIR__ . '/../../api/config/database.php';
include_once __DIR__ . '/../../api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$seller_data = null;
$error_message = '';
$success_message = '';

// Check for messages in session
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Include session helper
require_once '../../api/helpers/session_helper.php';

// Synchronize seller IDs in session
synchronizeSellerSessionIds();

// Get seller data using the helper function
$seller_id = getSellerIdFromSession();
$seller = new SellerRegistration($db);
$seller->id = $seller_id;

// Fetch seller data
if (!$seller->readOne()) {
    $error_message = "Failed to load seller data.";
}

// Get orders for this seller
$orders = [];
try {
    // Check if orders table exists
    $check_table_query = "SHOW TABLES LIKE 'orders'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Check if order_items table exists
        $check_items_query = "SHOW TABLES LIKE 'order_items'";
        $check_items_stmt = $db->prepare($check_items_query);
        $check_items_stmt->execute();

        if ($check_items_stmt->rowCount() > 0) {
            // Both tables exist, proceed with query
            try {
                $orders_query = "SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.price,
                                    o.status, o.order_date, o.payment_status, p.name as product_name
                             FROM order_items oi
                             JOIN orders o ON oi.order_id = o.id
                             JOIN products p ON oi.product_id = p.id
                             WHERE oi.seller_id = ?
                             ORDER BY o.order_date DESC";
                $orders_stmt = $db->prepare($orders_query);
                $orders_stmt->bindParam(1, $seller_id);
                $orders_stmt->execute();

                while ($row = $orders_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $orders[] = $row;
                }
            } catch (PDOException $e) {
                $error_message = "Error in orders query: " . $e->getMessage();
                // Log the error for debugging
                error_log("Error in seller orders query: " . $e->getMessage());
            }
        } else {
            $error_message = "Order items table does not exist yet.";
        }
    } else {
        $error_message = "Orders table does not exist yet.";
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    // Log the error for debugging
    error_log("Database error in seller orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Seller Dashboard - Kisan Kart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../../images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="../../images/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/seller.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom bg-success text-white">
                <img src="../../images/farmer-logo.png" alt="Kisan Kart Logo" style="height: 24px; width: 24px; margin-right: 8px; filter: brightness(0) invert(1);"> Seller Center
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="products.php">
                    <i class="bi bi-box me-2"></i> Products
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 active" href="orders.php">
                    <i class="bi bi-cart me-2"></i> Orders
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="profile.php">
                    <i class="bi bi-person me-2"></i> Profile
                </a>

                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="../index.html">
                    <i class="bi bi-house me-2"></i> Back to Store
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 text-danger" href="../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
        </div>

        <!-- Page content wrapper -->
        <div id="page-content-wrapper">
            <!-- Top navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-sm btn-success" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-label="Toggle Navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                                    <span class="seller-name"><?php echo htmlspecialchars(trim($seller->first_name . (empty($seller->last_name) ? '' : ' ' . $seller->last_name))); ?></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="profile.php">Profile</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../../logout.php">Logout</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page content -->
            <div class="container-fluid p-4">
                <h1 class="mt-2 mb-4">Manage Orders</h1>

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

                <!-- Orders Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">Your Orders</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="bi bi-bag-x text-muted" style="font-size: 4rem;"></i>
                                </div>
                                <h5 class="text-muted">No Orders Yet</h5>
                                <p class="text-muted">You haven't received any orders yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Date</th>
                                            <th>Delivery Status</th>
                                            <th>Payment Status</th>
                                            <th>Update</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                <td><?php echo $order['quantity']; ?></td>
                                                <td>₹<?php echo number_format($order['price'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                                <td>
                                                    <?php
                                                        $status = isset($order['status']) ? $order['status'] : 'pending';
                                                        $status_class = '';
                                                        switch (strtolower($status)) {
                                                            case 'pending':
                                                                $status_class = 'bg-warning text-dark';
                                                                break;
                                                            case 'processing':
                                                                $status_class = 'bg-info text-white';
                                                                break;
                                                            case 'shipped':
                                                                $status_class = 'bg-primary text-white';
                                                                break;
                                                            case 'delivered':
                                                                $status_class = 'bg-success text-white';
                                                                break;
                                                            case 'cancelled':
                                                                $status_class = 'bg-danger text-white';
                                                                break;
                                                            default:
                                                                $status_class = 'bg-secondary text-white';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                        $payment_status = isset($order['payment_status']) ? $order['payment_status'] : 'pending';
                                                        $payment_status_class = '';
                                                        switch (strtolower($payment_status)) {
                                                            case 'paid':
                                                            case 'completed':
                                                                $payment_status_class = 'bg-success text-white';
                                                                break;
                                                            case 'pending':
                                                                $payment_status_class = 'bg-warning text-dark';
                                                                break;
                                                            case 'failed':
                                                                $payment_status_class = 'bg-danger text-white';
                                                                break;
                                                            case 'refunded':
                                                                $payment_status_class = 'bg-info text-white';
                                                                break;
                                                            default:
                                                                $payment_status_class = 'bg-secondary text-white';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $payment_status_class; ?>">
                                                        <?php echo ucfirst($payment_status); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Only show "Packed" button for pending orders
                                                    if (strtolower($status) == 'pending'):
                                                    ?>
                                                    <form method="post" action="update_order_status.php" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="processing">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-box"></i> Packed
                                                        </button>
                                                    </form>
                                                    <form method="post" action="update_order_status.php" class="d-inline ms-1">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="shipped">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-box"></i> Shipped
                                                        </button>
                                                    </form>
                                                    <?php
                                                    // Only show "Send" button for processing orders
                                                    elseif (strtolower($status) == 'processing'):
                                                    ?>
                                                    <form method="post" action="update_order_status.php" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="shipped">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-truck"></i> Send
                                                        </button>
                                                    </form>
                                                    <?php
                                                    // Only show "Delivered" button for shipped orders
                                                    elseif (strtolower($status) == 'shipped'):
                                                    ?>
                                                    <form method="post" action="update_order_status.php" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="delivered">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-check-circle"></i> Delivered
                                                        </button>
                                                    </form>
                                                    <form method="post" action="update_order_status.php" class="d-inline ms-1">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="shipped">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-box"></i> Shipped
                                                        </button>
                                                    </form>
                                                    <form method="post" action="update_order_status.php" class="d-inline ms-1">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-x-circle"></i> Cancel
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
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
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/main.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    </script>
</body>
</html>
