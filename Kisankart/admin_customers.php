<?php
session_start();

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

// Handle customer actions
$message = '';
$message_type = '';

// Delete customer
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $customer_id = $_GET['delete'];

    // Check if customer exists
    $check_query = "SELECT id FROM customer_registrations WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Delete customer
        $delete_query = "DELETE FROM customer_registrations WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $customer_id);

        if ($delete_stmt->execute()) {
            $message = "Customer deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting customer: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        $message = "Customer not found!";
        $message_type = "warning";
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    // Create a more flexible search condition that checks multiple possible name columns
    $search_condition = " WHERE (";
    $search_condition .= "email LIKE '%$search%' OR ";
    $search_condition .= "phone LIKE '%$search%' OR ";
    $search_condition .= "full_name LIKE '%$search%' OR ";
    $search_condition .= "name LIKE '%$search%' OR ";
    $search_condition .= "first_name LIKE '%$search%' OR ";
    $search_condition .= "last_name LIKE '%$search%' OR ";
    $search_condition .= "username LIKE '%$search%'";
    $search_condition .= ")";
}

// Get total number of customers
$count_query = "SELECT COUNT(*) as total FROM customer_registrations" . $search_condition;
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get customers with pagination
$customers_query = "SELECT * FROM customer_registrations" . $search_condition . " ORDER BY id DESC LIMIT ?, ?";
$customers_stmt = $conn->prepare($customers_query);
$customers_stmt->bind_param("ii", $offset, $records_per_page);
$customers_stmt->execute();
$customers_result = $customers_stmt->get_result();
$customers = [];

if ($customers_result) {
    while ($row = $customers_result->fetch_assoc()) {
        $customers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Kisan Kart Admin</title>
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
            background-color: rgba(30, 132, 73, 0.1);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        .sidebar-menu a.active {
            font-weight: 500;
        }

        .sidebar-menu i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 500;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
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
            <a href="admin_customers.php" class="active"><i class="bi bi-people"></i> Customers</a>
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
                <button class="btn btn-outline-secondary me-3" id="sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand">Manage Customers</span>
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['admin_name']; ?>
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

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Customer List</span>
                    <form class="search-form d-flex" method="GET" action="">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Registered On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No customers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['id']; ?></td>
                                        <td>
                                            <?php
                                                // Try different possible column names for customer name
                                                if (isset($customer['full_name'])) {
                                                    echo $customer['full_name'];
                                                } elseif (isset($customer['name'])) {
                                                    echo $customer['name'];
                                                } elseif (isset($customer['first_name']) && isset($customer['last_name'])) {
                                                    echo $customer['first_name'] . ' ' . $customer['last_name'];
                                                } elseif (isset($customer['first_name'])) {
                                                    echo $customer['first_name'];
                                                } elseif (isset($customer['username'])) {
                                                    echo $customer['username'];
                                                } else {
                                                    echo 'Customer #' . $customer['id'];
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo $customer['email']; ?></td>
                                        <td><?php echo $customer['phone']; ?></td>
                                        <td>
                                            <?php
                                                // Try different possible column names for registration date
                                                $date = null;
                                                if (isset($customer['created_at'])) {
                                                    $date = $customer['created_at'];
                                                } elseif (isset($customer['registration_date'])) {
                                                    $date = $customer['registration_date'];
                                                } elseif (isset($customer['date_created'])) {
                                                    $date = $customer['date_created'];
                                                } elseif (isset($customer['registered_on'])) {
                                                    $date = $customer['registered_on'];
                                                } elseif (isset($customer['join_date'])) {
                                                    $date = $customer['join_date'];
                                                }

                                                if ($date) {
                                                    echo date('M d, Y', strtotime($date));
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="admin_customer_view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="admin_customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $customer['id']; ?>)" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this customer? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Delete confirmation
        function confirmDelete(customerId) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = 'admin_customers.php?delete=' + customerId;
            deleteModal.show();
        }
    </script>
</body>
</html>
