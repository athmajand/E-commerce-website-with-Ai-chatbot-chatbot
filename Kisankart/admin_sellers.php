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

// Handle seller actions
$message = '';
$message_type = '';

// Check if approval_status column exists in seller_registrations table
$column_exists = false;
$check_column_query = "SHOW COLUMNS FROM seller_registrations LIKE 'approval_status'";
$check_column_result = $conn->query($check_column_query);
if ($check_column_result && $check_column_result->num_rows > 0) {
    $column_exists = true;
}

// Approve seller
if (isset($_GET['approve']) && !empty($_GET['approve']) && $column_exists) {
    $seller_id = $_GET['approve'];

    // Check if seller exists
    $check_query = "SELECT id, approval_status FROM seller_registrations WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $seller = $check_result->fetch_assoc();

        if ($seller['approval_status'] == 'pending') {
            // Approve seller
            $approve_query = "UPDATE seller_registrations SET approval_status = 'approved' WHERE id = ?";
            $approve_stmt = $conn->prepare($approve_query);
            $approve_stmt->bind_param("i", $seller_id);

            if ($approve_stmt->execute()) {
                $message = "Seller approved successfully!";
                $message_type = "success";
            } else {
                $message = "Error approving seller: " . $conn->error;
                $message_type = "danger";
            }
        } else {
            $message = "Seller is already " . $seller['approval_status'] . "!";
            $message_type = "warning";
        }
    } else {
        $message = "Seller not found!";
        $message_type = "warning";
    }
} elseif (isset($_GET['approve']) && !empty($_GET['approve']) && !$column_exists) {
    $message = "Approval status feature is not available in this database.";
    $message_type = "warning";
}

// Reject seller
if (isset($_GET['reject']) && !empty($_GET['reject']) && $column_exists) {
    $seller_id = $_GET['reject'];

    // Check if seller exists
    $check_query = "SELECT id, approval_status FROM seller_registrations WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $seller = $check_result->fetch_assoc();

        if ($seller['approval_status'] == 'pending') {
            // Reject seller
            $reject_query = "UPDATE seller_registrations SET approval_status = 'rejected' WHERE id = ?";
            $reject_stmt = $conn->prepare($reject_query);
            $reject_stmt->bind_param("i", $seller_id);

            if ($reject_stmt->execute()) {
                $message = "Seller rejected successfully!";
                $message_type = "success";
            } else {
                $message = "Error rejecting seller: " . $conn->error;
                $message_type = "danger";
            }
        } else {
            $message = "Seller is already " . $seller['approval_status'] . "!";
            $message_type = "warning";
        }
    } else {
        $message = "Seller not found!";
        $message_type = "warning";
    }
} elseif (isset($_GET['reject']) && !empty($_GET['reject']) && !$column_exists) {
    $message = "Approval status feature is not available in this database.";
    $message_type = "warning";
}

// Delete seller
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $seller_id = $_GET['delete'];

    // Check if seller exists
    $check_query = "SELECT id FROM seller_registrations WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Delete seller
        $delete_query = "DELETE FROM seller_registrations WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $seller_id);

        if ($delete_stmt->execute()) {
            $message = "Seller deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting seller: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        $message = "Seller not found!";
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
    // Create a more flexible search condition that checks multiple possible column names
    $search_condition = " WHERE (";
    $search_condition .= "email LIKE '%$search%' OR ";
    $search_condition .= "phone LIKE '%$search%' OR ";

    // Check for different possible name columns
    $search_condition .= "full_name LIKE '%$search%' OR ";
    $search_condition .= "name LIKE '%$search%' OR ";
    $search_condition .= "first_name LIKE '%$search%' OR ";
    $search_condition .= "last_name LIKE '%$search%' OR ";
    $search_condition .= "username LIKE '%$search%' OR ";

    // Check for different possible business name columns
    $search_condition .= "business_name LIKE '%$search%' OR ";
    $search_condition .= "company_name LIKE '%$search%' OR ";
    $search_condition .= "shop_name LIKE '%$search%' OR ";
    $search_condition .= "store_name LIKE '%$search%'";

    $search_condition .= ")";
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
if (!empty($status_filter) && $column_exists) {
    $search_condition = empty($search_condition) ? " WHERE approval_status = '$status_filter'" : $search_condition . " AND approval_status = '$status_filter'";
}

// Get total number of sellers
$count_query = "SELECT COUNT(*) as total FROM seller_registrations" . $search_condition;
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get sellers with pagination
$sellers_query = "SELECT * FROM seller_registrations" . $search_condition . " ORDER BY id DESC LIMIT ?, ?";
$sellers_stmt = $conn->prepare($sellers_query);
$sellers_stmt->bind_param("ii", $offset, $records_per_page);
$sellers_stmt->execute();
$sellers_result = $sellers_stmt->get_result();
$sellers = [];

if ($sellers_result) {
    while ($row = $sellers_result->fetch_assoc()) {
        $sellers[] = $row;
    }
}

// Get counts by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

if ($column_exists) {
    // If approval_status column exists, get counts by status
    $pending_count_query = "SELECT COUNT(*) as count FROM seller_registrations WHERE approval_status = 'pending'";
    $pending_count_result = $conn->query($pending_count_query);
    if ($pending_count_result) {
        $pending_count = $pending_count_result->fetch_assoc()['count'];
    }

    $approved_count_query = "SELECT COUNT(*) as count FROM seller_registrations WHERE approval_status = 'approved'";
    $approved_count_result = $conn->query($approved_count_query);
    if ($approved_count_result) {
        $approved_count = $approved_count_result->fetch_assoc()['count'];
    }

    $rejected_count_query = "SELECT COUNT(*) as count FROM seller_registrations WHERE approval_status = 'rejected'";
    $rejected_count_result = $conn->query($rejected_count_query);
    if ($rejected_count_result) {
        $rejected_count = $rejected_count_result->fetch_assoc()['count'];
    }
} else {
    // If approval_status column doesn't exist, set all sellers as approved for display purposes
    $approved_count = $total_records;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - Kisan Kart Admin</title>
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #FFC107;
            color: #000;
        }

        .status-approved {
            background-color: #28A745;
            color: #fff;
        }

        .status-rejected {
            background-color: #DC3545;
            color: #fff;
        }

        .status-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .status-filter a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .status-filter a.active {
            background-color: var(--primary-color);
            color: white;
        }

        .status-filter a:not(.active) {
            background-color: white;
            color: var(--text-color);
        }

        .status-filter a:hover:not(.active) {
            background-color: #e9ecef;
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
            <a href="admin_sellers.php" class="active"><i class="bi bi-shop"></i> Sellers</a>
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
                <span class="navbar-brand">Manage Sellers</span>
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

        <!-- Status Filter -->
        <div class="status-filter">
            <a href="admin_sellers.php" class="<?php echo empty($status_filter) ? 'active' : ''; ?>">
                All Sellers (<?php echo $total_records; ?>)
            </a>
            <a href="admin_sellers.php?status=pending" class="<?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                Pending (<?php echo $pending_count; ?>)
            </a>
            <a href="admin_sellers.php?status=approved" class="<?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                Approved (<?php echo $approved_count; ?>)
            </a>
            <a href="admin_sellers.php?status=rejected" class="<?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                Rejected (<?php echo $rejected_count; ?>)
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Seller List</span>
                    <form class="search-form d-flex" method="GET" action="">
                        <?php if (!empty($status_filter)): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control me-2" placeholder="Search sellers..." value="<?php echo htmlspecialchars($search); ?>">
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
                                <th>Business Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Registered On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sellers)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No sellers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sellers as $seller): ?>
                                    <tr>
                                        <td><?php echo $seller['id']; ?></td>
                                        <td>
                                            <?php
                                                // Try different possible column names for seller name
                                                if (isset($seller['full_name'])) {
                                                    echo $seller['full_name'];
                                                } elseif (isset($seller['name'])) {
                                                    echo $seller['name'];
                                                } elseif (isset($seller['first_name']) && isset($seller['last_name'])) {
                                                    echo $seller['first_name'] . ' ' . $seller['last_name'];
                                                } elseif (isset($seller['first_name'])) {
                                                    echo $seller['first_name'];
                                                } elseif (isset($seller['username'])) {
                                                    echo $seller['username'];
                                                } elseif (isset($seller['business_name'])) {
                                                    echo $seller['business_name'] . ' (Owner)';
                                                } else {
                                                    echo 'Seller #' . $seller['id'];
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                // Try different possible column names for business name
                                                if (isset($seller['business_name'])) {
                                                    echo $seller['business_name'];
                                                } elseif (isset($seller['company_name'])) {
                                                    echo $seller['company_name'];
                                                } elseif (isset($seller['shop_name'])) {
                                                    echo $seller['shop_name'];
                                                } elseif (isset($seller['store_name'])) {
                                                    echo $seller['store_name'];
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo $seller['email']; ?></td>
                                        <td><?php echo $seller['phone']; ?></td>
                                        <td>
                                            <?php
                                                $status_class = '';
                                                $status = 'approved'; // Default status if column doesn't exist

                                                if ($column_exists && isset($seller['approval_status'])) {
                                                    $status = $seller['approval_status'];

                                                    switch ($status) {
                                                        case 'pending':
                                                            $status_class = 'status-pending';
                                                            break;
                                                        case 'approved':
                                                            $status_class = 'status-approved';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'status-rejected';
                                                            break;
                                                        default:
                                                            $status_class = 'status-approved';
                                                            $status = 'approved';
                                                    }
                                                } else {
                                                    $status_class = 'status-approved';
                                                }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                                // Try different possible column names for registration date
                                                $date = null;
                                                if (isset($seller['created_at'])) {
                                                    $date = $seller['created_at'];
                                                } elseif (isset($seller['registration_date'])) {
                                                    $date = $seller['registration_date'];
                                                } elseif (isset($seller['date_created'])) {
                                                    $date = $seller['date_created'];
                                                } elseif (isset($seller['registered_on'])) {
                                                    $date = $seller['registered_on'];
                                                } elseif (isset($seller['join_date'])) {
                                                    $date = $seller['join_date'];
                                                }

                                                if ($date) {
                                                    echo date('M d, Y', strtotime($date));
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="admin_seller_view.php?id=<?php echo $seller['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ($column_exists && isset($seller['approval_status']) && $seller['approval_status'] == 'pending'): ?>
                                                <a href="javascript:void(0);" onclick="confirmApprove(<?php echo $seller['id']; ?>)" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="confirmReject(<?php echo $seller['id']; ?>)" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="admin_seller_edit.php?id=<?php echo $seller['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $seller['id']; ?>)" class="btn btn-sm btn-danger">
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
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Last</a>
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
                    Are you sure you want to delete this seller? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Confirmation Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Confirm Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to approve this seller? They will be able to list products on the platform.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmApproveBtn" class="btn btn-success">Approve</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Confirm Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to reject this seller? They will not be able to list products on the platform.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmRejectBtn" class="btn btn-warning">Reject</a>
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
        function confirmDelete(sellerId) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = 'admin_sellers.php?delete=' + sellerId + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>';
            deleteModal.show();
        }

        // Approve confirmation
        function confirmApprove(sellerId) {
            const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
            document.getElementById('confirmApproveBtn').href = 'admin_sellers.php?approve=' + sellerId + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>';
            approveModal.show();
        }

        // Reject confirmation
        function confirmReject(sellerId) {
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
            document.getElementById('confirmRejectBtn').href = 'admin_sellers.php?reject=' + sellerId + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>';
            rejectModal.show();
        }
    </script>
</body>
</html>
