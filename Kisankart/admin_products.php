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

// Handle product actions
$message = '';
$message_type = '';

// Delete product
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $product_id = $_GET['delete'];

    // Check if product exists
    $check_query = "SELECT id FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Delete product
        $delete_query = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $product_id);

        if ($delete_stmt->execute()) {
            $message = "Product deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting product: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        $message = "Product not found!";
        $message_type = "warning";
    }
}

// Enable/Disable product
if (isset($_GET['toggle_status']) && !empty($_GET['toggle_status'])) {
    $product_id = $_GET['toggle_status'];

    // Check if product exists and get current status
    $check_query = "SELECT id, status FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $product = $check_result->fetch_assoc();
        $new_status = ($product['status'] == 1 || $product['status'] == 'active') ? 0 : 1;

        // Update product status
        $update_query = "UPDATE products SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $new_status, $product_id);

        if ($update_stmt->execute()) {
            $status_text = $new_status ? "enabled" : "disabled";
            $message = "Product $status_text successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating product status: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        $message = "Product not found!";
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
    // Create a flexible search condition
    $search_condition = " WHERE (
        name LIKE '%$search%' OR
        description LIKE '%$search%' OR
        price LIKE '%$search%' OR
        category_id LIKE '%$search%' OR
        id LIKE '%$search%'
    )";
}

// Filter by category
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
if (!empty($category_filter)) {
    $search_condition = empty($search_condition) ?
        " WHERE category_id = '$category_filter'" :
        $search_condition . " AND category_id = '$category_filter'";
}

// Get total number of products
$count_query = "SELECT COUNT(*) as total FROM products" . $search_condition;
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Check if seller_registrations table has name or business_name columns
$seller_columns = [];
$check_columns_query = "SHOW COLUMNS FROM seller_registrations";
$check_columns_result = $conn->query($check_columns_query);
if ($check_columns_result) {
    while ($column = $check_columns_result->fetch_assoc()) {
        $seller_columns[] = $column['Field'];
    }
}

// Build the seller name part of the query based on available columns
$seller_name_part = "CONCAT('Seller #', p.seller_id)";
if (in_array('name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.name, " . $seller_name_part . ")";
}
if (in_array('full_name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.full_name, " . $seller_name_part . ")";
}
if (in_array('business_name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.business_name, " . $seller_name_part . ")";
}
if (in_array('company_name', $seller_columns)) {
    $seller_name_part = "COALESCE(sr.company_name, " . $seller_name_part . ")";
}
if (in_array('first_name', $seller_columns) && in_array('last_name', $seller_columns)) {
    $seller_name_part = "COALESCE(CONCAT(sr.first_name, ' ', sr.last_name), " . $seller_name_part . ")";
}

// Build the seller email part of the query
$seller_email_part = "NULL";
if (in_array('email', $seller_columns)) {
    $seller_email_part = "sr.email";
}

// Get products with pagination
$products_query = "SELECT p.*, c.name as category_name,
                    " . $seller_name_part . " as seller_name,
                    " . $seller_email_part . " as seller_email
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN seller_registrations sr ON p.seller_id = sr.id
                  " . $search_condition . "
                  ORDER BY p.id DESC LIMIT ?, ?";
$products_stmt = $conn->prepare($products_query);
$products_stmt->bind_param("ii", $offset, $records_per_page);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$products = [];

if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get all categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Kisan Kart Admin</title>
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

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .status-active {
            color: #28A745;
        }

        .status-inactive {
            color: #DC3545;
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
            <a href="admin_products.php" class="active"><i class="bi bi-box"></i> Products</a>
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
                <span class="navbar-brand">Manage Products</span>
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
                    <span>Product List</span>
                    <div class="d-flex">
                        <form class="search-form d-flex me-2" method="GET" action="">
                            <?php if (!empty($category_filter)): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                            <?php endif; ?>
                            <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="categoryFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo !empty($category_filter) ? 'Category: ' . $category_filter : 'Filter by Category'; ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="categoryFilterDropdown">
                                <li><a class="dropdown-item" href="admin_products.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>">All Categories</a></li>
                                <?php foreach ($categories as $category): ?>
                                    <li><a class="dropdown-item" href="admin_products.php?category=<?php echo $category['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $category['name']; ?></a></li>
                                <?php endforeach; ?>
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
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Seller</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No products found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <?php
                                                $image_path = isset($product['image']) ? $product['image'] : (isset($product['image_url']) ? $product['image_url'] : '');
                                                if (!empty($image_path) && file_exists($image_path)) {
                                                    echo '<img src="' . $image_path . '" class="product-image" alt="' . $product['name'] . '">';
                                                } else {
                                                    echo '<img src="assets/images/product-placeholder.jpg" class="product-image" alt="No Image">';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo $product['name']; ?></td>
                                        <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['category_name'] ?? 'Uncategorized'; ?></td>
                                        <td><?php echo $product['seller_name']; ?></td>
                                        <td>
                                            <?php
                                                $status = isset($product['status']) ? $product['status'] : 1;
                                                $status_class = ($status == 1 || $status == 'active') ? 'status-active' : 'status-inactive';
                                                $status_text = ($status == 1 || $status == 'active') ? 'Active' : 'Inactive';
                                            ?>
                                            <span class="<?php echo $status_class; ?>">
                                                <i class="bi <?php echo ($status == 1 || $status == 'active') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_product_view.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" title="View Product">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmToggleStatus(<?php echo $product['id']; ?>)" class="btn btn-sm <?php echo ($status == 1 || $status == 'active') ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo ($status == 1 || $status == 'active') ? 'Disable' : 'Enable'; ?> Product">
                                                <i class="bi <?php echo ($status == 1 || $status == 'active') ? 'bi-toggle-on' : 'bi-toggle-off'; ?>"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="openMessageModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', '<?php echo addslashes($product['seller_name']); ?>', '<?php echo addslashes($product['seller_email']); ?>')" class="btn btn-sm btn-primary" title="Message Seller">
                                                <i class="bi bi-envelope"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $product['id']; ?>)" class="btn btn-sm btn-danger" title="Delete Product">
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
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>">Last</a>
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
                    Are you sure you want to delete this product? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="toggleStatusModalLabel">Confirm Status Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to change the status of this product?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmToggleStatusBtn" class="btn btn-primary">Confirm</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Seller Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message to Seller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="messageForm" action="admin_send_message.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="product_id" name="product_id">
                        <div class="mb-3">
                            <label for="seller_name" class="form-label">Seller</label>
                            <input type="text" class="form-control" id="seller_name" readonly>
                            <input type="hidden" id="seller_email" name="seller_email">
                        </div>
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product</label>
                            <input type="text" class="form-control" id="product_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
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
        function confirmDelete(productId) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = 'admin_products.php?delete=' + productId + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>';
            deleteModal.show();
        }

        // Toggle status confirmation
        function confirmToggleStatus(productId) {
            const toggleStatusModal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
            document.getElementById('confirmToggleStatusBtn').href = 'admin_products.php?toggle_status=' + productId + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>';
            toggleStatusModal.show();
        }

        // Open message modal
        function openMessageModal(productId, productName, sellerName, sellerEmail) {
            document.getElementById('product_id').value = productId;
            document.getElementById('product_name').value = productName;
            document.getElementById('seller_name').value = sellerName;
            document.getElementById('seller_email').value = sellerEmail;
            document.getElementById('subject').value = 'Regarding your product: ' + productName;

            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
        }
    </script>
</body>
</html>
