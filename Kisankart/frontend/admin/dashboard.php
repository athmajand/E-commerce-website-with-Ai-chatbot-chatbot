<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to login page if not logged in as an admin
    header("Location: ../../admin_login.php?redirect=frontend/admin/dashboard.php");
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

// Get dashboard stats
$stats = [
    'total_users' => 0,
    'total_revenue' => 0,
    'total_products' => 0,
    'pending_approvals' => 0
];

try {
    // Get total users
    $users_query = "SELECT COUNT(*) as count FROM customer_registrations";
    $users_stmt = $db->prepare($users_query);
    $users_stmt->execute();
    if ($row = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_users'] = $row['count'];
    }

    // Get total revenue
    $revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'";
    $revenue_stmt = $db->prepare($revenue_query);
    $revenue_stmt->execute();
    if ($row = $revenue_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_revenue'] = $row['total'] ?? 0;
    }

    // Get total products
    $products_query = "SELECT COUNT(*) as count FROM products";
    $products_stmt = $db->prepare($products_query);
    $products_stmt->execute();
    if ($row = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_products'] = $row['count'];
    }

    // Get pending approvals
    $approvals_query = "SELECT COUNT(*) as count FROM seller_registrations WHERE status = 'pending'";
    $approvals_stmt = $db->prepare($approvals_query);
    $approvals_stmt->execute();
    if ($row = $approvals_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['pending_approvals'] = $row['count'];
    }
} catch (PDOException $e) {
    $error_message = "Error loading dashboard stats: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom bg-primary text-white">
                <i class="bi bi-gear-fill"></i> Admin Panel
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3 active" href="dashboard.php">
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
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="orders.php">
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
                <h1 class="mt-2 mb-4">Dashboard</h1>

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

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Revenue</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-currency-rupee fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Products</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_products']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-box fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Approvals</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_approvals']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock-history fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                        <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                            // Get recent orders
                            $recent_orders = [];
                            try {
                                $orders_query = "SELECT o.id, o.order_date, o.total_amount, o.status,
                                                    CONCAT(cr.first_name, ' ', cr.last_name) as customer_name
                                                FROM orders o
                                                LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                                                ORDER BY o.order_date DESC
                                                LIMIT 5";
                                $orders_stmt = $db->prepare($orders_query);
                                $orders_stmt->execute();

                                while ($row = $orders_stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $recent_orders[] = $row;
                                }
                            } catch (PDOException $e) {
                                // Silently handle error
                            }

                            if (empty($recent_orders)):
                            ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-bag-x text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="text-muted">No Orders Yet</h5>
                                    <p class="text-muted">There are no orders in the system yet.</p>
                                </div>
                            <?php else: ?>
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
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
                                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
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
