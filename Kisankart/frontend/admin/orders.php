<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to login page if not logged in as an admin
    header("Location: ../../admin_login.php?redirect=frontend/admin/orders.php");
    exit;
}

// Include database configuration
include_once __DIR__ . '/../../api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$error_message = '';
$success_message = '';

// Get all orders
$orders = [];
try {
    // Check if orders table exists
    $check_table_query = "SHOW TABLES LIKE 'orders'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Table exists, proceed with query
        $orders_query = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status,
                                o.payment_method, o.payment_status, o.shipping_address,
                                CONCAT(cr.first_name, ' ', cr.last_name) as customer_name
                         FROM orders o
                         LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                         ORDER BY o.order_date DESC";
        $orders_stmt = $db->prepare($orders_query);
        $orders_stmt->execute();

        while ($row = $orders_stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $row;
        }
    } else {
        $error_message = "Orders table does not exist yet.";
    }
} catch (PDOException $e) {
    $error_message = "Error loading orders: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin Dashboard - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom bg-primary text-white">
                <i class="bi bi-gear-fill"></i> Admin Panel
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="users.php">
                    <i class="bi bi-people me-2"></i> Users
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="sellers.php">
                    <i class="bi bi-shop me-2"></i> Sellers
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="products.php">
                    <i class="bi bi-box me-2"></i> Products
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 active" href="orders.php">
                    <i class="bi bi-cart me-2"></i> Orders
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="categories.php">
                    <i class="bi bi-tags me-2"></i> Categories
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="reports.php">
                    <i class="bi bi-graph-up me-2"></i> Reports
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="settings.php">
                    <i class="bi bi-gear me-2"></i> Settings
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
                    <button class="btn btn-sm btn-primary" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i> <span class="admin-name">Admin</span>
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
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">All Orders</h6>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2" id="export-csv">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
                            </button>
                            <button class="btn btn-sm btn-outline-primary" id="print-orders">
                                <i class="bi bi-printer me-1"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="bi bi-bag-x text-muted" style="font-size: 4rem;"></i>
                                </div>
                                <h5 class="text-muted">No Orders Found</h5>
                                <p class="text-muted">There are no orders in the system yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="ordersTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                        $status_class = '';
                                                        switch (strtolower($order['status'])) {
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
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                        $payment_class = '';
                                                        switch (strtolower($order['payment_status'])) {
                                                            case 'pending':
                                                                $payment_class = 'bg-warning text-dark';
                                                                break;
                                                            case 'completed':
                                                                $payment_class = 'bg-success text-white';
                                                                break;
                                                            case 'failed':
                                                                $payment_class = 'bg-danger text-white';
                                                                break;
                                                            case 'refunded':
                                                                $payment_class = 'bg-info text-white';
                                                                break;
                                                            default:
                                                                $payment_class = 'bg-secondary text-white';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $payment_class; ?>">
                                                        <?php echo ucfirst($order['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-success update-status-btn" data-order-id="<?php echo $order['id']; ?>">
                                                        <i class="bi bi-arrow-clockwise"></i> Update
                                                    </button>
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
    <script src="../js/admin/admin-common.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    </script>
</body>
</html>
