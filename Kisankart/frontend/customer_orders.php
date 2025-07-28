<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'home';

// Session validation already handled above

// Check if user is logged in - more lenient check
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../login.php?redirect=frontend/customer_orders.php");
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

// Get all orders for the customer
$orders = [];
try {
    // Check if orders table exists
    $check_table_query = "SHOW TABLES LIKE 'orders'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Table exists, proceed with query
        $orders_query = "SELECT o.id, o.order_date, o.total_amount, o.status, o.tracking_number, o.shipping_address
                        FROM orders o
                        WHERE o.customer_id = ?
                        ORDER BY o.order_date DESC";
        $orders_stmt = $db->prepare($orders_query);
        $orders_stmt->bindParam(1, $customer_id);
        $orders_stmt->execute();
        while ($row = $orders_stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $row;
        }
    } else {
        // Table doesn't exist, log a message
        error_log("Orders table does not exist yet. Skipping orders query.");
    }
} catch (PDOException $e) {
    // Log the error but continue execution
    error_log("Error querying orders: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Kisan Kart</title>
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

        /* Order card */
        .order-card {
            border-left: 4px solid #4CAF50;
            transition: all 0.3s;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .order-card.pending {
            border-left-color: #FFC107;
        }
        .order-card.processing {
            border-left-color: #17A2B8;
        }
        .order-card.shipped {
            border-left-color: #007BFF;
        }
        .order-card.delivered {
            border-left-color: #28A745;
        }
        .order-card.cancelled {
            border-left-color: #DC3545;
        }
    </style>
</head>
<body>
    <?php
    // Include the navigation bar
    include_once('../includes/navbar.php');
    ?>

    <!-- Orders Section -->
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
                    <!-- Page Title -->
                    <div class="card border-0 shadow-sm mb-4 bg-success bg-opacity-10">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h4 class="mb-1 text-success"><i class="bi bi-box me-2"></i>My Orders</h4>
                                    <p class="mb-0">View and track all your orders</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger mb-4">
                        <strong>Error:</strong> <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Orders List -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="bi bi-bag-x text-muted" style="font-size: 4rem;"></i>
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
                                            <?php foreach ($orders as $order): ?>
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
</body>
</html>
