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
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;
$orders = [];
$total_orders = 0;
$error_message = '';
$success_message = '';

// Handle status update
if (isset($_GET['update_status']) && isset($_GET['order_id']) && isset($_GET['new_status'])) {
    $order_id = $_GET['order_id'];
    $new_status = $_GET['new_status'];

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

// Build search and filter conditions
$search_condition = "";
$params = [];
$param_types = "";

if (!empty($search)) {
    $search_condition .= " AND (o.id LIKE ? OR CONCAT(cr.first_name, ' ', cr.last_name) LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if (!empty($status_filter)) {
    $search_condition .= " AND o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

// Count total orders for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o
                LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                WHERE 1=1" . $search_condition;
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_orders = $count_row['total'];
$total_pages = ceil($total_orders / $records_per_page);

// Get orders with pagination
$orders_query = "SELECT o.*, CONCAT(cr.first_name, ' ', cr.last_name) as customer_name
                FROM orders o
                LEFT JOIN customer_registrations cr ON o.customer_id = cr.id
                WHERE 1=1" . $search_condition . "
                ORDER BY o.order_date DESC LIMIT ?, ?";

$stmt = $conn->prepare($orders_query);

if (!empty($params)) {
    $params[] = $offset;
    $params[] = $records_per_page;
    $param_types .= "ii";
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $records_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Kisan Kart Admin</title>
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

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        .search-form {
            max-width: 300px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-processing {
            background-color: #D1ECF1;
            color: #0C5460;
        }

        .status-shipped {
            background-color: #D4EDDA;
            color: #155724;
        }

        .status-delivered {
            background-color: #C3E6CB;
            color: #155724;
        }

        .status-cancelled {
            background-color: #F8D7DA;
            color: #721C24;
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
            <h1 class="mb-4">Manage Orders</h1>

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

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Order List</span>
                        <div class="d-flex">
                            <form class="search-form d-flex me-2" method="GET" action="">
                                <?php if (!empty($status_filter)): ?>
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                <?php endif; ?>
                                <input type="text" name="search" class="form-control me-2" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo !empty($status_filter) ? ucfirst($status_filter) : 'All Statuses'; ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                    <li><a class="dropdown-item" href="?<?php echo !empty($search) ? 'search=' . urlencode($search) : ''; ?>">All Statuses</a></li>
                                    <li><a class="dropdown-item" href="?status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Pending</a></li>
                                    <li><a class="dropdown-item" href="?status=processing<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Processing</a></li>
                                    <li><a class="dropdown-item" href="?status=shipped<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Shipped</a></li>
                                    <li><a class="dropdown-item" href="?status=delivered<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Delivered</a></li>
                                    <li><a class="dropdown-item" href="?status=cancelled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Cancelled</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
                                    <th>Payment Status</th>
                                    <th>Delivery Status</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No orders found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                            <td>
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
                                            </td>
                                            <td>
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
                                            </td>
                                            <td>
                                                <a href="admin_order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-box"></i> View Items
                                                </a>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="admin_order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="admin_orders.php?update_status=1&order_id=<?php echo $order['id']; ?>&new_status=pending">Mark as Pending</a></li>
                                                        <li><a class="dropdown-item" href="admin_orders.php?update_status=1&order_id=<?php echo $order['id']; ?>&new_status=processing">Mark as Processing</a></li>
                                                        <li><a class="dropdown-item" href="admin_orders.php?update_status=1&order_id=<?php echo $order['id']; ?>&new_status=shipped">Mark as Shipped</a></li>
                                                        <li><a class="dropdown-item" href="admin_orders.php?update_status=1&order_id=<?php echo $order['id']; ?>&new_status=delivered">Mark as Delivered</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="admin_orders.php?update_status=1&order_id=<?php echo $order['id']; ?>&new_status=cancelled">Cancel Order</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            Showing <?php echo min(($page - 1) * $records_per_page + 1, $total_orders); ?> to <?php echo min($page * $records_per_page, $total_orders); ?> of <?php echo $total_orders; ?> orders
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
