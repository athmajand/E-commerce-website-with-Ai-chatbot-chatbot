<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_products.php");
    exit();
}

$product_id = $_GET['id'];

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

// Build the seller phone part of the query
$seller_phone_part = "NULL";
if (in_array('phone', $seller_columns)) {
    $seller_phone_part = "sr.phone";
}

// Get product details
$product_query = "SELECT p.*, c.name as category_name,
                 " . $seller_name_part . " as seller_name,
                 " . $seller_email_part . " as seller_email,
                 " . $seller_phone_part . " as seller_phone
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN seller_registrations sr ON p.seller_id = sr.id
                 WHERE p.id = ?";
$product_stmt = $conn->prepare($product_query);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows == 0) {
    header("Location: admin_products.php");
    exit();
}

$product = $product_result->fetch_assoc();

// Get product images from product_images table
$images = [];

// Check if product_images table exists first
$table_check = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($table_check && $table_check->num_rows > 0) {
    // Table exists, try to get images
    $images_query = $conn->prepare("SELECT image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
    if ($images_query) {
        $images_query->bind_param("i", $product_id);
        $images_query->execute();
        $images_result = $images_query->get_result();
        
        while ($image = $images_result->fetch_assoc()) {
            if (file_exists($image['image_path'])) {
                $images[] = $image['image_path'];
            }
        }
    }
} else {
    // Table doesn't exist, log it for debugging
    error_log("product_images table doesn't exist in admin_product_view.php");
}

// If no images found in product_images table, check legacy image fields
if (empty($images)) {
    $image_path = isset($product['image_path']) ? $product['image_path'] : (isset($product['image']) ? $product['image'] : (isset($product['image_url']) ? $product['image_url'] : ''));
    if (!empty($image_path) && file_exists($image_path)) {
        $images[] = $image_path;
    }
}

// If still no images found, use placeholder
if (empty($images)) {
    $images[] = 'assets/images/product-placeholder.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - Kisan Kart Admin</title>
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

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #166036;
            border-color: #166036;
        }

        .product-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .product-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .product-thumbnail:hover {
            transform: scale(1.05);
        }

        .product-thumbnail.active {
            border: 2px solid var(--primary-color);
        }

        .status-active {
            color: #28A745;
        }

        .status-inactive {
            color: #DC3545;
        }

        .product-info {
            margin-bottom: 20px;
        }

        .product-info h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .product-info .price {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .product-info .description {
            margin-bottom: 20px;
        }

        .product-meta {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .product-meta p {
            margin-bottom: 10px;
        }

        .seller-info {
            background-color: rgba(30, 132, 73, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .seller-info h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
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
                <span class="navbar-brand">View Product</span>
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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Product Details</span>
                            <a href="admin_products.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-left"></i> Back to Products
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <img src="<?php echo $images[0]; ?>" id="mainImage" class="product-image" alt="<?php echo $product['name']; ?>">

                                <?php if (count($images) > 1): ?>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <?php foreach ($images as $index => $image): ?>
                                            <img src="<?php echo $image; ?>" class="product-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                                 onclick="changeMainImage('<?php echo $image; ?>', this)" alt="Thumbnail <?php echo $index + 1; ?>">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Photo Upload Section -->
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" onclick="openPhotoUploadModal()">
                                        <i class="bi bi-camera"></i> Upload Photo
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="openPhotoManagerModal()">
                                        <i class="bi bi-images"></i> Manage Photos
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="product-info">
                                    <h2><?php echo $product['name']; ?></h2>
                                    <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>

                                    <?php
                                        $status = isset($product['status']) ? $product['status'] : 1;
                                        $status_class = ($status == 1 || $status == 'active') ? 'status-active' : 'status-inactive';
                                        $status_text = ($status == 1 || $status == 'active') ? 'Active' : 'Inactive';
                                    ?>
                                    <p>
                                        <strong>Status:</strong>
                                        <span class="<?php echo $status_class; ?>">
                                            <i class="bi <?php echo ($status == 1 || $status == 'active') ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </p>

                                    <p><strong>Category:</strong> <?php echo $product['category_name'] ?? 'Uncategorized'; ?></p>

                                    <?php if (isset($product['stock']) || isset($product['quantity'])): ?>
                                        <p><strong>Stock:</strong> <?php echo $product['stock'] ?? $product['quantity'] ?? 'N/A'; ?> units</p>
                                    <?php endif; ?>

                                    <?php if (isset($product['unit'])): ?>
                                        <p><strong>Unit:</strong> <?php echo $product['unit']; ?></p>
                                    <?php endif; ?>

                                    <div class="description">
                                        <h5>Description</h5>
                                        <p><?php echo nl2br($product['description'] ?? 'No description available.'); ?></p>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="javascript:void(0);" onclick="confirmToggleStatus(<?php echo $product['id']; ?>)" class="btn <?php echo ($status == 1 || $status == 'active') ? 'btn-warning' : 'btn-success'; ?>">
                                            <i class="bi <?php echo ($status == 1 || $status == 'active') ? 'bi-toggle-on' : 'bi-toggle-off'; ?>"></i>
                                            <?php echo ($status == 1 || $status == 'active') ? 'Disable' : 'Enable'; ?> Product
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $product['id']; ?>)" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Delete Product
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="product-meta">
                            <h5>Additional Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Product ID:</strong> <?php echo $product['id']; ?></p>

                                    <?php if (isset($product['sku'])): ?>
                                        <p><strong>SKU:</strong> <?php echo $product['sku']; ?></p>
                                    <?php endif; ?>

                                    <?php if (isset($product['created_at'])): ?>
                                        <p><strong>Added On:</strong> <?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if (isset($product['discount'])): ?>
                                        <p><strong>Discount:</strong> <?php echo $product['discount']; ?>%</p>
                                    <?php endif; ?>

                                    <?php if (isset($product['original_price']) && $product['original_price'] > 0): ?>
                                        <p><strong>Original Price:</strong> ₹<?php echo number_format($product['original_price'], 2); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="seller-info">
                            <h4>Seller Information</h4>
                            <p><strong>Name:</strong> <?php echo $product['seller_name']; ?></p>
                            <p><strong>Email:</strong> <?php echo $product['seller_email'] ?? 'N/A'; ?></p>
                            <p><strong>Phone:</strong> <?php echo $product['seller_phone'] ?? 'N/A'; ?></p>

                            <a href="javascript:void(0);" onclick="openMessageModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', '<?php echo addslashes($product['seller_name']); ?>', '<?php echo addslashes($product['seller_email']); ?>')" class="btn btn-primary mt-2">
                                <i class="bi bi-envelope"></i> Message Seller
                            </a>
                            <a href="admin_seller_view.php?id=<?php echo $product['seller_id']; ?>" class="btn btn-outline-primary mt-2">
                                <i class="bi bi-shop"></i> View Seller Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <span>Quick Actions</span>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="admin_products.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Back to Products
                            </a>
                            <a href="javascript:void(0);" onclick="confirmToggleStatus(<?php echo $product['id']; ?>)" class="btn <?php echo ($status == 1 || $status == 'active') ? 'btn-warning' : 'btn-success'; ?>">
                                <i class="bi <?php echo ($status == 1 || $status == 'active') ? 'bi-toggle-on' : 'bi-toggle-off'; ?>"></i>
                                <?php echo ($status == 1 || $status == 'active') ? 'Disable' : 'Enable'; ?> Product
                            </a>
                            <a href="javascript:void(0);" onclick="openMessageModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', '<?php echo addslashes($product['seller_name']); ?>', '<?php echo addslashes($product['seller_email']); ?>')" class="btn btn-primary">
                                <i class="bi bi-envelope"></i> Message Seller
                            </a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $product['id']; ?>)" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Delete Product
                            </a>
                        </div>
                    </div>
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
                    Are you sure you want to <?php echo ($status == 1 || $status == 'active') ? 'disable' : 'enable'; ?> this product?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="admin_products.php?toggle_status=<?php echo $product['id']; ?>" class="btn btn-primary">Confirm</a>
                </div>
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
                    <a href="admin_products.php?delete=<?php echo $product['id']; ?>" class="btn btn-danger">Delete</a>
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
                        <input type="hidden" id="product_id" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="mb-3">
                            <label for="seller_name" class="form-label">Seller</label>
                            <input type="text" class="form-control" id="seller_name" value="<?php echo $product['seller_name']; ?>" readonly>
                            <input type="hidden" id="seller_email" name="seller_email" value="<?php echo $product['seller_email']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product</label>
                            <input type="text" class="form-control" id="product_name" value="<?php echo $product['name']; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="Regarding your product: <?php echo $product['name']; ?>" required>
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

    <!-- Photo Upload Modal -->
    <div class="modal fade" id="photoUploadModal" tabindex="-1" aria-labelledby="photoUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoUploadModalLabel">Upload Product Photos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="photoUploadForm" action="admin_upload_product_photo.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="mb-3">
                            <label for="product_photos" class="form-label">Select Photos</label>
                            <input type="file" class="form-control" id="product_photos" name="product_photos[]" multiple accept="image/*" required>
                            <div class="form-text">You can select multiple images. Supported formats: JPG, PNG, GIF, WEBP. Max size: 5MB per image.</div>
                        </div>
                        <div class="mb-3">
                            <label for="photo_description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="photo_description" name="photo_description" rows="3" placeholder="Brief description of the photos..."></textarea>
                        </div>
                        <div id="photoPreview" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload Photos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Photo Manager Modal -->
    <div class="modal fade" id="photoManagerModal" tabindex="-1" aria-labelledby="photoManagerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoManagerModalLabel">Manage Product Photos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="photoManagerContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Loading photos...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openPhotoUploadModal()">
                        <i class="bi bi-plus"></i> Add More Photos
                    </button>
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

        // Change main image
        function changeMainImage(src, thumbnail) {
            document.getElementById('mainImage').src = src;

            // Remove active class from all thumbnails
            document.querySelectorAll('.product-thumbnail').forEach(function(thumb) {
                thumb.classList.remove('active');
            });

            // Add active class to clicked thumbnail
            thumbnail.classList.add('active');
        }

        // Delete confirmation
        function confirmDelete(productId) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Toggle status confirmation
        function confirmToggleStatus(productId) {
            const toggleStatusModal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
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

        // Photo upload functionality
        function openPhotoUploadModal() {
            const photoUploadModal = new bootstrap.Modal(document.getElementById('photoUploadModal'));
            photoUploadModal.show();
        }

        function openPhotoManagerModal() {
            const photoManagerModal = new bootstrap.Modal(document.getElementById('photoManagerModal'));
            photoManagerModal.show();
            loadProductPhotos();
        }

        // File preview functionality
        document.getElementById('product_photos').addEventListener('change', function(e) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';
            
            const files = e.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100px';
                        img.style.height = '100px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '5px';
                        img.style.border = '1px solid #ddd';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Load product photos for management
        function loadProductPhotos() {
            const content = document.getElementById('photoManagerContent');
            const productId = <?php echo $product['id']; ?>;
            
            fetch(`admin_get_product_photos.php?product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayProductPhotos(data.photos);
                    } else {
                        content.innerHTML = '<div class="alert alert-info">No photos found for this product.</div>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger">Error loading photos: ' + error.message + '</div>';
                });
        }

        function displayProductPhotos(photos) {
            const content = document.getElementById('photoManagerContent');
            
            if (photos.length === 0) {
                content.innerHTML = '<div class="alert alert-info">No photos found for this product.</div>';
                return;
            }

            let html = '<div class="row">';
            photos.forEach((photo, index) => {
                html += `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="${photo.image_path}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Product Photo">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="setPrimaryPhoto(${photo.id})">
                                        ${photo.is_primary ? '<i class="bi bi-star-fill"></i> Primary' : '<i class="bi bi-star"></i> Set Primary'}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deletePhoto(${photo.id})">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            content.innerHTML = html;
        }

        function setPrimaryPhoto(photoId) {
            if (confirm('Set this photo as the primary image?')) {
                fetch('admin_set_primary_photo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        photo_id: photoId,
                        product_id: <?php echo $product['id']; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Primary photo updated successfully!');
                        loadProductPhotos();
                        location.reload(); // Refresh to show updated primary image
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }

        function deletePhoto(photoId) {
            if (confirm('Are you sure you want to delete this photo?')) {
                fetch('admin_delete_product_photo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        photo_id: photoId,
                        product_id: <?php echo $product['id']; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Photo deleted successfully!');
                        loadProductPhotos();
                        location.reload(); // Refresh to show updated images
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }

        // Handle photo upload form submission
        document.getElementById('photoUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
            submitBtn.disabled = true;
            
            fetch('admin_upload_product_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Photos uploaded successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('photoUploadModal'));
                    modal.hide();
                    location.reload(); // Refresh to show new images
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
