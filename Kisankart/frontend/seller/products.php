<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    // Redirect to login page if not logged in as a seller
    header("Location: ../../seller_login.php?redirect=frontend/seller/products.php");
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
$products = [];

// Get seller data
$seller_id = $_SESSION['user_id'];
$seller = new SellerRegistration($db);
$seller->id = $seller_id;

// Fetch seller data
if (!$seller->readOne()) {
    $error_message = "Failed to load seller data.";
}

// Check if seller exists in seller_profiles
try {
    $check_profile_query = "SELECT id FROM seller_profiles WHERE seller_registration_id = ?";
    $check_profile_stmt = $db->prepare($check_profile_query);
    $check_profile_stmt->bindParam(1, $seller_id);
    $check_profile_stmt->execute();

    if ($check_profile_stmt->rowCount() > 0) {
        // Get the seller_profile_id to use for products
        $seller_profile = $check_profile_stmt->fetch(PDO::FETCH_ASSOC);
        $seller_profile_id = $seller_profile['id'];
    } else {
        // Create a seller profile if it doesn't exist
        $insert_profile_query = "INSERT INTO seller_profiles
            (seller_registration_id, shop_name, description, logo_url, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())";

        $insert_profile_stmt = $db->prepare($insert_profile_query);
        $insert_profile_stmt->bindParam(1, $seller_id);
        $insert_profile_stmt->bindParam(2, $seller->business_name);
        $insert_profile_stmt->bindParam(3, $seller->business_description);
        $insert_profile_stmt->bindParam(4, $seller->business_logo);

        if ($insert_profile_stmt->execute()) {
            $seller_profile_id = $db->lastInsertId();
        } else {
            $error_message = "Failed to create seller profile.";
        }
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Error checking/creating seller profile: " . $e->getMessage());
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new product
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $discount_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : null;
            $category_id = $_POST['category_id'] ?? null;
            $stock_quantity = $_POST['stock_quantity'] ?? 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $status = $_POST['status'] ?? 'active';

            // Handle main image upload
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../uploads/products/';

                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Always use the standardized path format
                    $image_url = 'uploads/products/' . $file_name;

                    // Debug information
                    error_log("Product image uploaded: " . $image_url);
                } else {
                    $error_message = "Failed to upload main image.";
                    error_log("Failed to upload product image: " . error_get_last()['message']);
                }
            }

            // Handle additional images upload
            $additional_images = [];
            if (isset($_FILES['additional_images'])) {
                $upload_dir = __DIR__ . '/../../uploads/products/';

                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Process each additional image
                $file_count = count($_FILES['additional_images']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    // Skip if there's an error or no file
                    if ($_FILES['additional_images']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $file_name = time() . '_additional_' . $i . '_' . basename($_FILES['additional_images']['name'][$i]);
                    $target_file = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                        // Always use the standardized path format
                        $additional_images[] = 'uploads/products/' . $file_name;

                        // Debug information
                        error_log("Additional product image uploaded: uploads/products/" . $file_name);
                    } else {
                        error_log("Failed to upload additional product image: " . error_get_last()['message']);
                    }
                }
            }

            // Convert additional images array to JSON
            $additional_images_json = !empty($additional_images) ? json_encode($additional_images) : null;

            try {
                // Insert product into database
                $query = "INSERT INTO products (name, description, price, discount_price, category_id, seller_id,
                            stock_quantity, image_url, additional_images, is_featured, status, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $name);
                $stmt->bindParam(2, $description);
                $stmt->bindParam(3, $price);
                $stmt->bindParam(4, $discount_price);
                $stmt->bindParam(5, $category_id);
                $stmt->bindParam(6, $seller_profile_id);
                $stmt->bindParam(7, $stock_quantity);
                $stmt->bindParam(8, $image_url);
                $stmt->bindParam(9, $additional_images_json);
                $stmt->bindParam(10, $is_featured);
                $stmt->bindParam(11, $status);

                if ($stmt->execute()) {
                    $success_message = "Product added successfully!";
                } else {
                    $error_message = "Failed to add product.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
                error_log("Error adding product: " . $e->getMessage());
            }
        }

        // Update existing product
        else if ($_POST['action'] === 'update' && isset($_POST['product_id'])) {
            $product_id = $_POST['product_id'];
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $discount_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : null;
            $category_id = $_POST['category_id'] ?? null;
            $stock_quantity = $_POST['stock_quantity'] ?? 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $status = $_POST['status'] ?? 'active';

            // Handle main image upload or removal
            $image_url = $_POST['current_image'] ?? null;

            // Check if remove_image checkbox is checked
            if (isset($_POST['remove_image'])) {
                // If the checkbox is checked, set image_url to null
                $image_url = null;

                // Optionally, delete the physical file if it exists
                if (!empty($_POST['current_image'])) {
                    $current_image_path = __DIR__ . '/../../' . $_POST['current_image'];
                    if (file_exists($current_image_path)) {
                        @unlink($current_image_path);
                    }
                }
            }
            // Only process new image upload if remove_image is not checked
            else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../uploads/products/';

                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // If there was a previous image, delete it
                    if (!empty($_POST['current_image'])) {
                        $current_image_path = __DIR__ . '/../../' . $_POST['current_image'];
                        if (file_exists($current_image_path)) {
                            @unlink($current_image_path);
                            error_log("Deleted previous product image: " . $_POST['current_image']);
                        }
                    }

                    // Always use the standardized path format
                    $image_url = 'uploads/products/' . $file_name;

                    // Debug information
                    error_log("Updated product image: " . $image_url);
                } else {
                    $error_message = "Failed to upload main image.";
                    error_log("Failed to upload updated product image: " . error_get_last()['message']);
                }
            }

            // Handle additional images upload
            // First, get existing additional images if any
            $additional_images = [];
            $current_additional_images = [];

            // Get current additional images from database
            try {
                $get_images_query = "SELECT additional_images FROM products WHERE id = ?";
                $get_images_stmt = $db->prepare($get_images_query);
                $get_images_stmt->bindParam(1, $_POST['product_id']);
                $get_images_stmt->execute();

                if ($get_images_stmt->rowCount() > 0) {
                    $product_data = $get_images_stmt->fetch(PDO::FETCH_ASSOC);
                    if (!empty($product_data['additional_images'])) {
                        $current_additional_images = json_decode($product_data['additional_images'], true) ?? [];
                    }
                }
            } catch (PDOException $e) {
                error_log("Error getting additional images: " . $e->getMessage());
            }

            // Keep existing additional images unless new ones are uploaded
            $additional_images = $current_additional_images;

            // Process new additional images if any
            if (isset($_FILES['additional_images'])) {
                $upload_dir = __DIR__ . '/../../uploads/products/';

                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Process each additional image
                $file_count = count($_FILES['additional_images']['name']);
                $new_additional_images = [];

                for ($i = 0; $i < $file_count; $i++) {
                    // Skip if there's an error or no file
                    if ($_FILES['additional_images']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $file_name = time() . '_additional_' . $i . '_' . basename($_FILES['additional_images']['name'][$i]);
                    $target_file = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                        // Always use the standardized path format
                        $new_additional_images[] = 'uploads/products/' . $file_name;

                        // Debug information
                        error_log("New additional product image uploaded: uploads/products/" . $file_name);
                    } else {
                        error_log("Failed to upload new additional product image: " . error_get_last()['message']);
                    }
                }

                // If new additional images were uploaded, replace the existing ones
                if (!empty($new_additional_images)) {
                    // Delete old additional images
                    foreach ($current_additional_images as $old_image) {
                        // Ensure we have the correct path format
                        $old_image_filename = basename($old_image);
                        $old_image_path = __DIR__ . '/../../uploads/products/' . $old_image_filename;

                        if (file_exists($old_image_path)) {
                            @unlink($old_image_path);
                            error_log("Deleted old additional image: " . $old_image);
                        } else {
                            error_log("Could not find old additional image to delete: " . $old_image);
                        }
                    }

                    // Use the new images
                    $additional_images = $new_additional_images;
                }
            }

            // Convert additional images array to JSON
            $additional_images_json = !empty($additional_images) ? json_encode($additional_images) : null;

            // Handle image settings
            $image_settings = [];
            if (isset($_POST['image_size'])) {
                $image_settings['size'] = $_POST['image_size'];
            }
            if (isset($_POST['image_padding'])) {
                $image_settings['padding'] = $_POST['image_padding'];
            }
            if (isset($_POST['image_fit'])) {
                $image_settings['fit'] = $_POST['image_fit'];
            }
            if (isset($_POST['image_background'])) {
                $image_settings['background'] = $_POST['image_background'];
            }
            $image_settings_json = !empty($image_settings) ? json_encode($image_settings) : null;

            try {
                // Update product in database
                $query = "UPDATE products
                          SET name = ?, description = ?, price = ?, discount_price = ?,
                              category_id = ?, stock_quantity = ?, image_url = ?, additional_images = ?,
                              image_settings = ?, is_featured = ?, status = ?, updated_at = NOW()
                          WHERE id = ? AND seller_id = ?";

                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $name);
                $stmt->bindParam(2, $description);
                $stmt->bindParam(3, $price);
                $stmt->bindParam(4, $discount_price);
                $stmt->bindParam(5, $category_id);
                $stmt->bindParam(6, $stock_quantity);
                $stmt->bindParam(7, $image_url);
                $stmt->bindParam(8, $additional_images_json);
                $stmt->bindParam(9, $image_settings_json);
                $stmt->bindParam(10, $is_featured);
                $stmt->bindParam(11, $status);
                $stmt->bindParam(12, $product_id);
                $stmt->bindParam(13, $seller_profile_id);

                if ($stmt->execute()) {
                    $success_message = "Product updated successfully!";
                } else {
                    $error_message = "Failed to update product.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
                error_log("Error updating product: " . $e->getMessage());
            }
        }

        // Delete product
        else if ($_POST['action'] === 'delete' && isset($_POST['product_id'])) {
            $product_id = $_POST['product_id'];

            try {
                // Delete product from database
                $query = "DELETE FROM products WHERE id = ? AND seller_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $product_id);
                $stmt->bindParam(2, $seller_profile_id);

                if ($stmt->execute()) {
                    $success_message = "Product deleted successfully!";
                } else {
                    $error_message = "Failed to delete product.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
                error_log("Error deleting product: " . $e->getMessage());
            }
        }
    }
}

// Get all products for this seller
try {
    // Check if products table exists
    $check_table_query = "SHOW TABLES LIKE 'products'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
        // Table exists, proceed with query
        // First, try to get products using seller_id (seller_profile_id)
        $products_query = "SELECT p.*, c.name as category_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          WHERE p.seller_id = ?
                          ORDER BY p.created_at DESC";
        $products_stmt = $db->prepare($products_query);
        $products_stmt->bindParam(1, $seller_profile_id);
        $products_stmt->execute();

        while ($row = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = $row;
        }

        // Debug information
        error_log("Products found using seller_id: " . count($products));
    } else {
        $error_message = "Products table does not exist yet.";
        error_log("Products table does not exist");
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Error fetching products: " . $e->getMessage());
}

// Get all categories for dropdown
$categories = [];
try {
    // Check if categories table exists
    $check_categories_query = "SHOW TABLES LIKE 'categories'";
    $check_categories_stmt = $db->prepare($check_categories_query);
    $check_categories_stmt->execute();

    if ($check_categories_stmt->rowCount() > 0) {
        // Table exists, proceed with query
        $categories_query = "SELECT id, name FROM categories ORDER BY name";
        $categories_stmt = $db->prepare($categories_query);
        $categories_stmt->execute();

        while ($row = $categories_stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = $row;
        }

        // If no categories found, add some sample categories
        if (empty($categories)) {
            $sample_categories = [
                ['name' => 'Vegetables', 'description' => 'Fresh vegetables directly from farms'],
                ['name' => 'Fruits', 'description' => 'Seasonal and exotic fruits'],
                ['name' => 'Grains', 'description' => 'Rice, wheat, and other grains'],
                ['name' => 'Dairy', 'description' => 'Milk, cheese, and other dairy products'],
                ['name' => 'Spices', 'description' => 'Organic spices and herbs'],
                ['name' => 'Organic', 'description' => 'Certified organic products']
            ];

            foreach ($sample_categories as $category) {
                $insert_sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
                $insert_stmt = $db->prepare($insert_sql);
                $insert_stmt->bindParam(1, $category['name']);
                $insert_stmt->bindParam(2, $category['description']);
                $insert_stmt->execute();

                // Add the newly inserted category to the array
                $categories[] = [
                    'id' => $db->lastInsertId(),
                    'name' => $category['name']
                ];
            }
        }
    } else {
        // Create categories table if it doesn't exist
        $create_table_sql = "
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT NULL,
            image_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $db->exec($create_table_sql);

        // Add sample categories
        $sample_categories = [
            ['name' => 'Vegetables', 'description' => 'Fresh vegetables directly from farms'],
            ['name' => 'Fruits', 'description' => 'Seasonal and exotic fruits'],
            ['name' => 'Grains', 'description' => 'Rice, wheat, and other grains'],
            ['name' => 'Dairy', 'description' => 'Milk, cheese, and other dairy products'],
            ['name' => 'Spices', 'description' => 'Organic spices and herbs'],
            ['name' => 'Organic', 'description' => 'Certified organic products']
        ];

        foreach ($sample_categories as $category) {
            $insert_sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
            $insert_stmt = $db->prepare($insert_sql);
            $insert_stmt->bindParam(1, $category['name']);
            $insert_stmt->bindParam(2, $category['description']);
            $insert_stmt->execute();

            // Add the newly inserted category to the array
            $categories[] = [
                'id' => $db->lastInsertId(),
                'name' => $category['name']
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    // Add error message to be displayed to the user
    $error_message = "Error loading categories: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Seller Dashboard - Kisan Kart</title>
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
    <style>
        #additional_images_preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .img-thumbnail {
            object-fit: cover;
        }
        .position-relative {
            position: relative;
        }
        
        /* Interactive Image Preview Styles */
        .preview-container {
            width: 300px;
            height: 200px;
            overflow: hidden;
            position: relative;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .preview-container img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform-origin: center center;
            transform: translate(-50%, -50%) scale(1);
            object-fit: cover;
            width: 100%;
            height: 100%;
            user-select: none;
            pointer-events: none;
            transition: transform 0.2s ease;
        }
        
        .preview-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .preview-controls .form-select,
        .preview-controls .form-range {
            flex-shrink: 0;
        }
        
        .preview-controls .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .zoom-value {
            min-width: 40px;
            text-align: center;
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .pan-controls {
            display: flex;
            gap: 2px;
        }
        
        .pan-controls .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
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
                <a class="list-group-item list-group-item-action list-group-item-light p-3 active" href="products.php">
                    <i class="bi bi-box me-2"></i> Products
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="orders.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mt-2">Manage Products</h1>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle"></i> Add New Product
                    </button>
                </div>

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

                <!-- Products Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">Your Products</h6>
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
                            <button class="btn btn-outline-success" type="button" id="searchButton">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="bi bi-box text-muted" style="font-size: 4rem;"></i>
                                </div>
                                <h5 class="text-muted">No Products Yet</h5>
                                <p class="text-muted">You haven't added any products yet. Click the "Add New Product" button to get started.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($product['image_url'])): ?>
                                                        <?php
                                                        $settings = json_decode($product['image_settings'] ?? '', true);
                                                        $fit = $settings['fit'] ?? 'cover';
                                                        $zoom = isset($settings['zoom']) ? floatval($settings['zoom']) : 1;
                                                        $panX = isset($settings['panX']) ? intval($settings['panX']) : 0;
                                                        $panY = isset($settings['panY']) ? intval($settings['panY']) : 0;
                                                        $style = "object-fit: $fit; position: absolute; top: 50%; left: 50%; width: 100%; height: 100%; transform-origin: center center; transform: translate(calc(-50% + {$panX}px), calc(-50% + {$panY}px)) scale($zoom);";
                                                        ?>
                                                        <div style="width: 50px; height: 50px; overflow: hidden; position: relative; background: #f8f9fa; border-radius: 6px;">
                                                            <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail border-0 p-0" style="<?php echo $style; ?>">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td>
                                                    <?php if (!empty($product['discount_price'])): ?>
                                                        <span class="text-decoration-line-through text-muted">₹<?php echo number_format($product['price'], 2); ?></span>
                                                        <span class="text-success">₹<?php echo number_format($product['discount_price'], 2); ?></span>
                                                    <?php else: ?>
                                                        ₹<?php echo number_format($product['price'], 2); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $product['stock_quantity']; ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                                <td>
                                                    <?php
                                                        $status = $product['status'] ?? 'active';
                                                        $status_class = '';
                                                        switch ($status) {
                                                            case 'active':
                                                                $status_class = 'bg-success';
                                                                break;
                                                            case 'inactive':
                                                                $status_class = 'bg-secondary';
                                                                break;
                                                            case 'out_of_stock':
                                                                $status_class = 'bg-danger';
                                                                break;
                                                            default:
                                                                $status_class = 'bg-secondary';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-product-btn"
                                                            data-bs-toggle="modal" data-bs-target="#editProductModal"
                                                            data-id="<?php echo $product['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                            data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                                            data-price="<?php echo $product['price']; ?>"
                                                            data-discount-price="<?php echo $product['discount_price']; ?>"
                                                            data-category-id="<?php echo $product['category_id']; ?>"
                                                            data-stock="<?php echo $product['stock_quantity']; ?>"
                                                            data-image="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
                                                            data-additional-images="<?php echo htmlspecialchars($product['additional_images'] ?? ''); ?>"
                                                            data-image-settings="<?php echo htmlspecialchars($product['image_settings'] ?? ''); ?>"
                                                            data-featured="<?php echo $product['is_featured']; ?>"
                                                            data-status="<?php echo $product['status']; ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-product-btn"
                                                            data-bs-toggle="modal" data-bs-target="#deleteProductModal"
                                                            data-id="<?php echo $product['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                        <i class="bi bi-trash"></i> Delete
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name*</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Price (₹)*</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="discount_price" class="form-label">Discount Price (₹)</label>
                                <input type="number" class="form-control" id="discount_price" name="discount_price" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stock_quantity" class="form-label">Stock Quantity*</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Main Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Recommended size: 800x800 pixels. Max file size: 2MB.</div>
                        </div>

                        <!-- Interactive Image Preview & Adjustment Tool for Add Product -->
                        <div class="mb-3" id="add-image-preview-section" style="display: none;">
                            <label class="form-label">🎯 Interactive Image Preview & Adjustment</label>
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="preview-container" id="add-image-preview-container">
                                        <img id="add-preview-image" src="" alt="Product Preview">
                                    </div>
                                    <div class="preview-controls">
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="mb-0 fw-bold">Fit:</label>
                                            <select id="add-fit-mode" class="form-select form-select-sm" style="width: auto;">
                                                <option value="cover">Cover (Fill container)</option>
                                                <option value="contain">Contain (Show full image)</option>
                                                <option value="fill">Fill (Stretch to fit)</option>
                                            </select>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="mb-0 fw-bold">Zoom:</label>
                                            <input type="range" id="add-zoom-slider" min="0.5" max="3" step="0.1" value="1" class="form-range" style="width: 100px;">
                                            <span class="zoom-value" id="add-zoom-value">1.0x</span>
                                        </div>
                                        <div class="pan-controls">
                                            <button type="button" id="add-pan-left" class="btn btn-outline-secondary btn-sm" title="Pan Left">←</button>
                                            <button type="button" id="add-pan-up" class="btn btn-outline-secondary btn-sm" title="Pan Up">↑</button>
                                            <button type="button" id="add-pan-down" class="btn btn-outline-secondary btn-sm" title="Pan Down">↓</button>
                                            <button type="button" id="add-pan-right" class="btn btn-outline-secondary btn-sm" title="Pan Right">→</button>
                                        </div>
                                        <button type="button" id="add-reset-preview" class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </button>
                                        <button type="button" id="add-save-preview" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-check-circle"></i> Save
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i> Preview exactly how your product image will appear in the product cards. Adjust zoom, position, and fit mode for the best presentation.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="add_image_preview_settings" name="add_image_preview_settings">
                        </div>

                        <div class="mb-3">
                            <label for="add_additional_images" class="form-label">Additional Images (Max 4)</label>
                            <input type="file" class="form-control" id="add_additional_images" name="additional_images[]" accept="image/*" multiple>
                            <div class="form-text">Upload up to 4 additional product images. Recommended size: 800x800 pixels. Max file size: 2MB per image.</div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured">
                            <label class="form-check-label" for="is_featured">Feature this product on homepage</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" id="editProductForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <input type="hidden" name="current_image" id="edit_current_image">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Product Name*</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_price" class="form-label">Price (₹)*</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_discount_price" class="form-label">Discount Price (₹)</label>
                                <input type="number" class="form-control" id="edit_discount_price" name="discount_price" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_stock_quantity" class="form-label">Stock Quantity*</label>
                                <input type="number" class="form-control" id="edit_stock_quantity" name="stock_quantity" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Main Product Image</label>
                            <div id="current_image_preview" class="mb-2"></div>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                            <div class="form-text">Leave empty to keep current image. Recommended size: 800x800 pixels. Max file size: 2MB.</div>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image">
                                <label class="form-check-label" for="remove_image">Remove current image</label>
                            </div>
                        </div>

                        <!-- Interactive Image Preview & Adjustment Tool -->
                        <div class="mb-3" id="image-preview-section" style="display: none;">
                            <label class="form-label">🎯 Interactive Image Preview & Adjustment</label>
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="preview-container" id="image-preview-container">
                                        <img id="preview-image" src="" alt="Product Preview">
                                    </div>
                                    <div class="preview-controls">
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="mb-0 fw-bold">Fit:</label>
                                            <select id="fit-mode" class="form-select form-select-sm" style="width: auto;">
                                                <option value="cover">Cover (Fill container)</option>
                                                <option value="contain">Contain (Show full image)</option>
                                                <option value="fill">Fill (Stretch to fit)</option>
                                            </select>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="mb-0 fw-bold">Zoom:</label>
                                            <input type="range" id="zoom-slider" min="0.5" max="3" step="0.1" value="1" class="form-range" style="width: 100px;">
                                            <span class="zoom-value" id="zoom-value">1.0x</span>
                                        </div>
                                        <div class="pan-controls">
                                            <button type="button" id="pan-left" class="btn btn-outline-secondary btn-sm" title="Pan Left">←</button>
                                            <button type="button" id="pan-up" class="btn btn-outline-secondary btn-sm" title="Pan Up">↑</button>
                                            <button type="button" id="pan-down" class="btn btn-outline-secondary btn-sm" title="Pan Down">↓</button>
                                            <button type="button" id="pan-right" class="btn btn-outline-secondary btn-sm" title="Pan Right">→</button>
                                        </div>
                                        <button type="button" id="reset-preview" class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </button>
                                        <button type="button" id="save-preview" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-check-circle"></i> Save
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i> Preview exactly how your product image will appear in the product cards. Adjust zoom, position, and fit mode for the best presentation.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="image_preview_settings" name="image_preview_settings">
                        </div>

                        <!-- Image Display Settings -->
                        <div class="mb-3">
                            <label class="form-label">Image Display Settings</label>
                            <div class="card border-light">
                                <div class="card-body p-3">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="edit_image_size" class="form-label">Image Size</label>
                                            <select class="form-select" id="edit_image_size" name="image_size">
                                                <option value="default">Default (Auto-fit)</option>
                                                <option value="small">Small (80% size)</option>
                                                <option value="medium">Medium (90% size)</option>
                                                <option value="large">Large (100% size)</option>
                                                <option value="extra-large">Extra Large (110% size)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit_image_padding" class="form-label">Image Padding</label>
                                            <select class="form-select" id="edit_image_padding" name="image_padding">
                                                <option value="none">No Padding</option>
                                                <option value="small">Small Padding (10px)</option>
                                                <option value="medium">Medium Padding (20px)</option>
                                                <option value="large">Large Padding (30px)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="edit_image_fit" class="form-label">Image Fit</label>
                                            <select class="form-select" id="edit_image_fit" name="image_fit">
                                                <option value="contain">Contain (Show full image)</option>
                                                <option value="cover">Cover (Fill container)</option>
                                                <option value="fill">Fill (Stretch to fit)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit_image_background" class="form-label">Background Color</label>
                                            <select class="form-select" id="edit_image_background" name="image_background">
                                                <option value="light">Light Gray (#f8f9fa)</option>
                                                <option value="white">White (#ffffff)</option>
                                                <option value="transparent">Transparent</option>
                                                <option value="dark">Dark Gray (#6c757d)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i> These settings control how your product image appears in the product cards on the website.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="additional_images" class="form-label">Additional Images (Max 4)</label>
                            <div id="additional_images_preview" class="mb-2 d-flex flex-wrap gap-2"></div>
                            <input type="file" class="form-control" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                            <div class="form-text">Upload up to 4 additional product images. Recommended size: 800x800 pixels. Max file size: 2MB per image.</div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_featured" name="is_featured">
                            <label class="form-check-label" for="edit_is_featured">Feature this product on homepage</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the product: <strong id="delete_product_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" id="delete_product_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
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

        // Search functionality
        document.getElementById('searchButton').addEventListener('click', function() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('productsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const productName = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                if (productName.includes(searchInput)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });

        // Reset search when input is cleared
        document.getElementById('searchInput').addEventListener('input', function() {
            if (this.value === '') {
                const table = document.getElementById('productsTable');
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
            }
        });

        // Edit product modal
        const editButtons = document.querySelectorAll('.edit-product-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const description = this.getAttribute('data-description');
                const price = this.getAttribute('data-price');
                const discountPrice = this.getAttribute('data-discount-price');
                const categoryId = this.getAttribute('data-category-id');
                const stock = this.getAttribute('data-stock');
                const image = this.getAttribute('data-image');
                const imageSettings = this.getAttribute('data-image-settings');
                const featured = this.getAttribute('data-featured') === '1';
                const status = this.getAttribute('data-status');

                document.getElementById('edit_product_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_price').value = price;
                document.getElementById('edit_discount_price').value = discountPrice;
                document.getElementById('edit_category_id').value = categoryId;
                document.getElementById('edit_stock_quantity').value = stock;
                document.getElementById('edit_current_image').value = image;
                document.getElementById('edit_is_featured').checked = featured;
                document.getElementById('edit_status').value = status;

                // Handle image settings
                if (imageSettings) {
                    try {
                        const settings = JSON.parse(imageSettings);
                        document.getElementById('edit_image_size').value = settings.size || 'default';
                        document.getElementById('edit_image_padding').value = settings.padding || 'medium';
                        document.getElementById('edit_image_fit').value = settings.fit || 'contain';
                        document.getElementById('edit_image_background').value = settings.background || 'light';
                    } catch (e) {
                        console.error('Error parsing image settings:', e);
                        // Set default values if parsing fails
                        document.getElementById('edit_image_size').value = 'default';
                        document.getElementById('edit_image_padding').value = 'medium';
                        document.getElementById('edit_image_fit').value = 'contain';
                        document.getElementById('edit_image_background').value = 'light';
                    }
                } else {
                    // Set default values if no settings exist
                    document.getElementById('edit_image_size').value = 'default';
                    document.getElementById('edit_image_padding').value = 'medium';
                    document.getElementById('edit_image_fit').value = 'contain';
                    document.getElementById('edit_image_background').value = 'light';
                }

                // Show current image preview
                const imagePreview = document.getElementById('current_image_preview');
                const additionalImagesPreview = document.getElementById('additional_images_preview');
                const removeImageCheckbox = document.getElementById('remove_image');

                // Reset the remove image checkbox
                removeImageCheckbox.checked = false;

                // Clear additional images preview
                additionalImagesPreview.innerHTML = '';

                if (image) {
                    imagePreview.innerHTML = `<img src="../../${image}" alt="${name}" class="img-thumbnail" style="max-height: 100px;">`;
                    // Only show the remove checkbox if there's an image
                    removeImageCheckbox.parentElement.style.display = 'block';
                } else {
                    imagePreview.innerHTML = '<p class="text-muted">No image available</p>';
                    // Hide the remove checkbox if there's no image
                    removeImageCheckbox.parentElement.style.display = 'none';
                }

                // Get additional images if any
                const additionalImagesAttr = this.getAttribute('data-additional-images');
                if (additionalImagesAttr) {
                    try {
                        const additionalImages = JSON.parse(additionalImagesAttr);
                        if (additionalImages && additionalImages.length > 0) {
                            additionalImages.forEach(imgUrl => {
                                const imgContainer = document.createElement('div');
                                imgContainer.className = 'position-relative';
                                imgContainer.innerHTML = `
                                    <img src="../../${imgUrl}" alt="Additional image" class="img-thumbnail" style="max-height: 80px; margin-right: 5px;">
                                `;
                                additionalImagesPreview.appendChild(imgContainer);
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing additional images:', e);
                    }
                }

                // Add event listener to the file input to disable remove checkbox when a new file is selected
                document.getElementById('edit_image').addEventListener('change', function() {
                    if (this.files.length > 0) {
                        removeImageCheckbox.checked = false;
                        removeImageCheckbox.disabled = true;
                    } else {
                        removeImageCheckbox.disabled = false;
                    }
                });

                // Add event listener to the remove checkbox to clear the file input when checked
                removeImageCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        document.getElementById('edit_image').value = '';
                        document.getElementById('edit_image').disabled = true;
                    } else {
                        document.getElementById('edit_image').disabled = false;
                    }
                });

                // Add event listener to the additional images input
                document.getElementById('additional_images').addEventListener('change', function() {
                    if (this.files.length > 4) {
                        alert('You can upload a maximum of 4 additional images.');
                        this.value = '';
                        return;
                    }

                    // Preview the selected additional images
                    additionalImagesPreview.innerHTML = '';
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        const reader = new FileReader();

                        reader.onload = function(e) {
                            const imgContainer = document.createElement('div');
                            imgContainer.className = 'position-relative';
                            imgContainer.innerHTML = `
                                <img src="${e.target.result}" alt="New additional image" class="img-thumbnail" style="max-height: 80px; margin-right: 5px;">
                            `;
                            additionalImagesPreview.appendChild(imgContainer);
                        };

                        reader.readAsDataURL(file);
                    }
                });

                // Initialize image preview after setting current image
                if (image) {
                    initializeImagePreview(`../../${image}`);
                } else {
                    document.getElementById('image-preview-section').style.display = 'none';
                }
            });
        });

        // Delete product modal
        const deleteButtons = document.querySelectorAll('.delete-product-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                document.getElementById('delete_product_id').value = id;
                document.getElementById('delete_product_name').textContent = name;
            });
        });

        // Interactive Image Preview & Adjustment Tool
        let previewSettings = {
            fit: 'cover',
            zoom: 1,
            panX: 0,
            panY: 0
        };

        // Initialize preview tool when edit modal opens
        function initializeImagePreview(imageSrc) {
            console.log('initializeImagePreview called with:', imageSrc);
            const previewSection = document.getElementById('image-preview-section');
            const previewImage = document.getElementById('preview-image');
            const fitMode = document.getElementById('fit-mode');
            const zoomSlider = document.getElementById('zoom-slider');
            const zoomValue = document.getElementById('zoom-value');

            if (imageSrc) {
                previewImage.src = imageSrc;
                previewSection.style.display = 'block';
                console.log('Preview section shown');
                
                // Load saved settings if available
                const savedSettings = document.getElementById('image_preview_settings').value;
                if (savedSettings) {
                    try {
                        const settings = JSON.parse(savedSettings);
                        previewSettings = { ...previewSettings, ...settings };
                        console.log('Loaded saved settings:', settings);
                    } catch (e) {
                        console.error('Error parsing saved preview settings:', e);
                    }
                }

                // Apply settings to controls
                fitMode.value = previewSettings.fit;
                zoomSlider.value = previewSettings.zoom;
                zoomValue.textContent = previewSettings.zoom.toFixed(1) + 'x';
                
                // Apply settings to image
                updatePreviewImage();
                console.log('Preview initialized successfully');
            } else {
                previewSection.style.display = 'none';
                console.log('No image source, hiding preview section');
            }
        }

        // Update preview image with current settings
        function updatePreviewImage() {
            const previewImage = document.getElementById('preview-image');
            const { fit, zoom, panX, panY } = previewSettings;
            
            console.log('Updating preview image with settings:', { fit, zoom, panX, panY });
            
            previewImage.style.objectFit = fit;
            previewImage.style.transform = `translate(calc(-50% + ${panX}px), calc(-50% + ${panY}px)) scale(${zoom})`;
            
            console.log('Preview image updated');
        }

        // Save current settings
        function savePreviewSettings() {
            document.getElementById('image_preview_settings').value = JSON.stringify(previewSettings);
            
            // Show success feedback
            const saveBtn = document.getElementById('save-preview');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Saved!';
            saveBtn.classList.remove('btn-outline-success');
            saveBtn.classList.add('btn-success');
            
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-outline-success');
            }, 2000);
        }

        // Reset to defaults
        function resetPreviewSettings() {
            previewSettings = {
                fit: 'cover',
                zoom: 1,
                panX: 0,
                panY: 0
            };
            
            const fitMode = document.getElementById('fit-mode');
            const zoomSlider = document.getElementById('zoom-slider');
            const zoomValue = document.getElementById('zoom-value');
            
            fitMode.value = previewSettings.fit;
            zoomSlider.value = previewSettings.zoom;
            zoomValue.textContent = previewSettings.zoom.toFixed(1) + 'x';
            
            updatePreviewImage();
        }

        // Event listeners for preview controls
        document.addEventListener('DOMContentLoaded', function() {
            const fitMode = document.getElementById('fit-mode');
            const zoomSlider = document.getElementById('zoom-slider');
            const zoomValue = document.getElementById('zoom-value');
            const panLeft = document.getElementById('pan-left');
            const panRight = document.getElementById('pan-right');
            const panUp = document.getElementById('pan-up');
            const panDown = document.getElementById('pan-down');
            const resetBtn = document.getElementById('reset-preview');
            const saveBtn = document.getElementById('save-preview');

            // Fit mode change
            fitMode.addEventListener('change', function() {
                previewSettings.fit = this.value;
                updatePreviewImage();
            });

            // Zoom slider change
            zoomSlider.addEventListener('input', function() {
                previewSettings.zoom = parseFloat(this.value);
                zoomValue.textContent = previewSettings.zoom.toFixed(1) + 'x';
                updatePreviewImage();
            });

            // Pan controls
            panLeft.addEventListener('click', function() {
                previewSettings.panX -= 10;
                updatePreviewImage();
            });

            panRight.addEventListener('click', function() {
                previewSettings.panX += 10;
                updatePreviewImage();
            });

            panUp.addEventListener('click', function() {
                previewSettings.panY -= 10;
                updatePreviewImage();
            });

            panDown.addEventListener('click', function() {
                previewSettings.panY += 10;
                updatePreviewImage();
            });

            // Reset button
            resetBtn.addEventListener('click', resetPreviewSettings);

            // Save button
            saveBtn.addEventListener('click', savePreviewSettings);
        });

        // Event delegation for preview controls (works even when elements are in modals)
        document.addEventListener('click', function(e) {
            // Edit modal preview controls
            if (e.target.id === 'pan-left') {
                previewSettings.panX -= 10;
                updatePreviewImage();
            } else if (e.target.id === 'pan-right') {
                previewSettings.panX += 10;
                updatePreviewImage();
            } else if (e.target.id === 'pan-up') {
                previewSettings.panY -= 10;
                updatePreviewImage();
            } else if (e.target.id === 'pan-down') {
                previewSettings.panY += 10;
                updatePreviewImage();
            } else if (e.target.id === 'reset-preview') {
                resetPreviewSettings();
            } else if (e.target.id === 'save-preview') {
                savePreviewSettings();
            }
            
            // Add modal preview controls
            else if (e.target.id === 'add-pan-left') {
                addPreviewSettings.panX -= 10;
                updateAddPreviewImage();
            } else if (e.target.id === 'add-pan-right') {
                addPreviewSettings.panX += 10;
                updateAddPreviewImage();
            } else if (e.target.id === 'add-pan-up') {
                addPreviewSettings.panY -= 10;
                updateAddPreviewImage();
            } else if (e.target.id === 'add-pan-down') {
                addPreviewSettings.panY += 10;
                updateAddPreviewImage();
            } else if (e.target.id === 'add-reset-preview') {
                resetAddPreviewSettings();
            } else if (e.target.id === 'add-save-preview') {
                saveAddPreviewSettings();
            }
        });

        // Event delegation for form controls
        document.addEventListener('change', function(e) {
            // Edit modal controls
            if (e.target.id === 'fit-mode') {
                previewSettings.fit = e.target.value;
                updatePreviewImage();
            } else if (e.target.id === 'zoom-slider') {
                previewSettings.zoom = parseFloat(e.target.value);
                const zoomValue = document.getElementById('zoom-value');
                if (zoomValue) {
                    zoomValue.textContent = previewSettings.zoom.toFixed(1) + 'x';
                }
                updatePreviewImage();
            }
            
            // Add modal controls
            else if (e.target.id === 'add-fit-mode') {
                addPreviewSettings.fit = e.target.value;
                updateAddPreviewImage();
            } else if (e.target.id === 'add-zoom-slider') {
                addPreviewSettings.zoom = parseFloat(e.target.value);
                const addZoomValue = document.getElementById('add-zoom-value');
                if (addZoomValue) {
                    addZoomValue.textContent = addPreviewSettings.zoom.toFixed(1) + 'x';
                }
                updateAddPreviewImage();
            }
            
            // File input handling
            else if (e.target.id === 'edit_image' && e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    initializeImagePreview(e.target.result);
                    // Reset preview settings for new image
                    previewSettings = {
                        fit: 'cover',
                        zoom: 1,
                        panX: 0,
                        panY: 0
                    };
                    resetPreviewSettings();
                };
                
                reader.readAsDataURL(file);
            } else if (e.target.id === 'image' && e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    initializeAddImagePreview(e.target.result);
                    // Reset preview settings for new image
                    addPreviewSettings = {
                        fit: 'cover',
                        zoom: 1,
                        panX: 0,
                        panY: 0
                    };
                    resetAddPreviewSettings();
                };
                
                reader.readAsDataURL(file);
            }
        });

        // Add Product Modal Interactive Image Preview & Adjustment Tool
        let addPreviewSettings = {
            fit: 'cover',
            zoom: 1,
            panX: 0,
            panY: 0
        };

        // Initialize preview tool for Add Product modal
        function initializeAddImagePreview(imageSrc) {
            const previewSection = document.getElementById('add-image-preview-section');
            const previewImage = document.getElementById('add-preview-image');
            const fitMode = document.getElementById('add-fit-mode');
            const zoomSlider = document.getElementById('add-zoom-slider');
            const zoomValue = document.getElementById('add-zoom-value');

            if (imageSrc) {
                previewImage.src = imageSrc;
                previewSection.style.display = 'block';
                
                // Load saved settings if available
                const savedSettings = document.getElementById('add_image_preview_settings').value;
                if (savedSettings) {
                    try {
                        const settings = JSON.parse(savedSettings);
                        addPreviewSettings = { ...addPreviewSettings, ...settings };
                    } catch (e) {
                        console.error('Error parsing saved add preview settings:', e);
                    }
                }

                // Apply settings to controls
                fitMode.value = addPreviewSettings.fit;
                zoomSlider.value = addPreviewSettings.zoom;
                zoomValue.textContent = addPreviewSettings.zoom.toFixed(1) + 'x';
                
                // Apply settings to image
                updateAddPreviewImage();
            } else {
                previewSection.style.display = 'none';
            }
        }

        // Update add preview image with current settings
        function updateAddPreviewImage() {
            const previewImage = document.getElementById('add-preview-image');
            const { fit, zoom, panX, panY } = addPreviewSettings;
            
            previewImage.style.objectFit = fit;
            previewImage.style.transform = `translate(calc(-50% + ${panX}px), calc(-50% + ${panY}px)) scale(${zoom})`;
        }

        // Save current add settings
        function saveAddPreviewSettings() {
            document.getElementById('add_image_preview_settings').value = JSON.stringify(addPreviewSettings);
            
            // Show success feedback
            const saveBtn = document.getElementById('add-save-preview');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Saved!';
            saveBtn.classList.remove('btn-outline-success');
            saveBtn.classList.add('btn-success');
            
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-outline-success');
            }, 2000);
        }

        // Reset add preview to defaults
        function resetAddPreviewSettings() {
            addPreviewSettings = {
                fit: 'cover',
                zoom: 1,
                panX: 0,
                panY: 0
            };
            
            const fitMode = document.getElementById('add-fit-mode');
            const zoomSlider = document.getElementById('add-zoom-slider');
            const zoomValue = document.getElementById('add-zoom-value');
            
            fitMode.value = addPreviewSettings.fit;
            zoomSlider.value = addPreviewSettings.zoom;
            zoomValue.textContent = addPreviewSettings.zoom.toFixed(1) + 'x';
            
            updateAddPreviewImage();
        }
    </script>
</body>
</html>
