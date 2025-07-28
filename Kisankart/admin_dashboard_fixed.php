<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login_fixed.php");
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

// Get dashboard statistics
$stats = [
    'total_customers' => 0,
    'total_sellers' => 0,
    'total_products' => 0,
    'total_orders' => 0,
    'total_revenue' => 0
];

// Get total customers
$customer_query = "SELECT COUNT(*) as count FROM customer_registrations";
$result = $conn->query($customer_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_customers'] = $row['count'];
}

// Get total sellers
$seller_query = "SELECT COUNT(*) as count FROM seller_registrations";
$result = $conn->query($seller_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_sellers'] = $row['count'];
}

// Get total products
$product_query = "SELECT COUNT(*) as count FROM products";
$result = $conn->query($product_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_products'] = $row['count'];
}

// Get total orders
$order_query = "SELECT COUNT(*) as count FROM orders";
$result = $conn->query($order_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_orders'] = $row['count'];
}

// Get total revenue
$revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'";
$result = $conn->query($revenue_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_revenue'] = $row['total'] ?? 0;
}

// Get recent customers (5)
$recent_customers_query = "SELECT * FROM customer_registrations ORDER BY created_at DESC LIMIT 5";
$recent_customers_result = $conn->query($recent_customers_query);
$recent_customers = [];
if ($recent_customers_result) {
    while ($row = $recent_customers_result->fetch_assoc()) {
        $recent_customers[] = $row;
    }
}

// Get recent sellers (5)
$recent_sellers_query = "SELECT * FROM seller_registrations ORDER BY created_at DESC LIMIT 5";
$recent_sellers_result = $conn->query($recent_sellers_query);
$recent_sellers = [];
if ($recent_sellers_result) {
    while ($row = $recent_sellers_result->fetch_assoc()) {
        $recent_sellers[] = $row;
    }
}

// Get recent orders (5)
$recent_orders_query = "SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name 
                       FROM orders o 
                       LEFT JOIN customer_registrations c ON o.customer_id = c.id 
                       ORDER BY o.order_date DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_query);
$recent_orders = [];
if ($recent_orders_result) {
    while ($row = $recent_orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kisan Kart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e8449;
            --primary-dark: #166036;
            --accent-color: #FF9800;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: white;
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            transition: all 0.3s;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stats-card {
            text-align: center;
            padding: 20px;
        }

        .stats-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stats-card h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stats-card p {
            color: #666;
            margin-bottom: 0;
        }

        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 20px;
            background-color: white;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
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
            background-color: rgba(30, 132, 73, 0.1);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        .sidebar-menu i {
            margin-right: 10px;
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
            <a href="admin_dashboard_fixed.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="admin_customers.php"><i class="bi bi-people"></i> Customers</a>
            <a href="admin_sellers.php"><i class="bi bi-shop"></i> Sellers</a>
            <a href="admin_products.php"><i class="bi bi-box"></i> Products</a>
            <a href="admin_orders.php"><i class="bi bi-cart"></i> Orders</a>
            <a href="admin_categories.php"><i class="bi bi-tags"></i> Categories</a>
            <a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a>
            <a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <nav class="navbar navbar-expand-lg navbar-light mb-4">
            <div class="container-fluid">
                <button class="btn btn-outline-success me-3" id="sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand text-success">Dashboard</span>
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="admin_settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin_logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="bi bi-people"></i>
                    <h2><?php echo number_format($stats['total_customers']); ?></h2>
                    <p>Total Customers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="bi bi-shop"></i>
                    <h2><?php echo number_format($stats['total_sellers']); ?></h2>
                    <p>Total Sellers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="bi bi-box"></i>
                    <h2><?php echo number_format($stats['total_products']); ?></h2>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="bi bi-currency-rupee"></i>
                    <h2>₹<?php echo number_format($stats['total_revenue']); ?></h2>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <!-- Recent Orders -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Recent Orders</span>
                            <a href="admin_orders.php" class="btn btn-sm btn-light">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No orders found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                        $status_class = '';
                                                        switch ($order['status']) {
                                                            case 'pending':
                                                                $status_class = 'bg-warning';
                                                                break;
                                                            case 'processing':
                                                                $status_class = 'bg-info';
                                                                break;
                                                            case 'completed':
                                                                $status_class = 'bg-success';
                                                                break;
                                                            case 'cancelled':
                                                                $status_class = 'bg-danger';
                                                                break;
                                                            default:
                                                                $status_class = 'bg-secondary';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Customers -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Recent Customers</span>
                            <a href="admin_customers.php" class="btn btn-sm btn-light">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_customers)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No customers found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_customers as $customer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sellers -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Recent Sellers</span>
                            <a href="admin_sellers.php" class="btn btn-sm btn-light">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_sellers)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No sellers found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_sellers as $seller): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($seller['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });
    </script>
</body>
</html>
