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
$categories = [];
$error_message = '';
$success_message = '';
$edit_id = 0;
$edit_name = '';
$edit_description = '';
$edit_image_url = '';

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $image_url = '';
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/categories/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            } else {
                $error_message = "Failed to upload image.";
            }
        }
        
        if (empty($name)) {
            $error_message = "Category name is required.";
        } else {
            // Check if category already exists
            $check_query = "SELECT id FROM categories WHERE name = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "A category with this name already exists.";
            } else {
                // Insert new category
                $insert_query = "INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sss", $name, $description, $image_url);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Category added successfully!";
                } else {
                    $error_message = "Failed to add category: " . $conn->error;
                }
            }
        }
    }
    
    // Update existing category
    if (isset($_POST['update_category'])) {
        $id = $_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $current_image = $_POST['current_image'];
        
        // Handle image upload
        $image_url = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/categories/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
                
                // Delete old image if it exists and is not the default
                if (!empty($current_image) && file_exists($current_image) && $current_image != 'uploads/categories/default.jpg') {
                    unlink($current_image);
                }
            } else {
                $error_message = "Failed to upload image.";
            }
        }
        
        if (empty($name)) {
            $error_message = "Category name is required.";
        } else {
            // Check if category name already exists for other categories
            $check_query = "SELECT id FROM categories WHERE name = ? AND id != ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $name, $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "A category with this name already exists.";
            } else {
                // Update category
                $update_query = "UPDATE categories SET name = ?, description = ?, image_url = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sssi", $name, $description, $image_url, $id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Category updated successfully!";
                } else {
                    $error_message = "Failed to update category: " . $conn->error;
                }
            }
        }
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        
        // Check if category has products
        $check_products_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $check_products_stmt = $conn->prepare($check_products_query);
        $check_products_stmt->bind_param("i", $id);
        $check_products_stmt->execute();
        $check_products_result = $check_products_stmt->get_result();
        $product_count = $check_products_result->fetch_assoc()['count'];
        
        if ($product_count > 0) {
            $error_message = "Cannot delete category because it has associated products. Please move or delete the products first.";
        } else {
            // Get image URL to delete file
            $image_query = "SELECT image_url FROM categories WHERE id = ?";
            $image_stmt = $conn->prepare($image_query);
            $image_stmt->bind_param("i", $id);
            $image_stmt->execute();
            $image_result = $image_stmt->get_result();
            
            if ($image_row = $image_result->fetch_assoc()) {
                $image_url = $image_row['image_url'];
                
                // Delete image file if it exists and is not the default
                if (!empty($image_url) && file_exists($image_url) && $image_url != 'uploads/categories/default.jpg') {
                    unlink($image_url);
                }
            }
            
            // Delete category
            $delete_query = "DELETE FROM categories WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $id);
            
            if ($delete_stmt->execute()) {
                $success_message = "Category deleted successfully!";
            } else {
                $error_message = "Failed to delete category: " . $conn->error;
            }
        }
    }
    
    // Update product category (AJAX)
    if (isset($_POST['update_product_category'])) {
        $product_id = $_POST['product_id'];
        $new_category_id = $_POST['new_category_id'];
        
        $update_query = "UPDATE products SET category_id = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $new_category_id, $product_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product category updated successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product category: ' . $conn->error]);
            exit;
        }
    }
}

// Handle edit request
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    
    $edit_query = "SELECT * FROM categories WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    
    if ($edit_row = $edit_result->fetch_assoc()) {
        $edit_name = $edit_row['name'];
        $edit_description = $edit_row['description'];
        $edit_image_url = $edit_row['image_url'];
    }
}

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

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
    <title>Manage Categories - Kisan Kart Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
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

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #166036;
            border-color: #166036;
        }

        .category-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .product-item {
            padding: 10px;
            margin: 5px 0;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            cursor: move;
        }

        .product-item:hover {
            background-color: var(--light-gray);
        }

        .products-container {
            min-height: 50px;
            border: 1px dashed var(--border-color);
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }

        .category-card {
            margin-bottom: 20px;
        }

        .category-card .card-header {
            background-color: var(--primary-color);
            color: white;
        }

        .ui-state-highlight {
            height: 40px;
            background-color: #fcf8e3;
            border: 1px dashed #faebcc;
            margin: 5px 0;
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
            <a href="admin_orders.php"><i class="bi bi-cart"></i> Orders</a>
            <a href="admin_categories.php" class="active"><i class="bi bi-tags"></i> Categories</a>
            <a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a>
            <a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">Manage Categories</h1>

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

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo $edit_id ? 'Edit Category' : 'Add New Category'; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <?php if ($edit_id): ?>
                                    <input type="hidden" name="category_id" value="<?php echo $edit_id; ?>">
                                    <input type="hidden" name="current_image" value="<?php echo $edit_image_url; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_description); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Category Image</label>
                                    <?php if (!empty($edit_image_url)): ?>
                                        <div class="mb-2">
                                            <img src="<?php echo $edit_image_url; ?>" alt="Category Image" class="img-thumbnail" style="max-width: 100px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <?php if ($edit_id): ?>
                                        <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                                        <a href="admin_categories.php" class="btn btn-outline-secondary">Cancel</a>
                                    <?php else: ?>
                                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Categories List</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Products</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No categories found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories as $category): ?>
                                                <?php
                                                    // Count products in this category
                                                    $product_count_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
                                                    $product_count_stmt = $conn->prepare($product_count_query);
                                                    $product_count_stmt->bind_param("i", $category['id']);
                                                    $product_count_stmt->execute();
                                                    $product_count_result = $product_count_stmt->get_result();
                                                    $product_count = $product_count_result->fetch_assoc()['count'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $category['id']; ?></td>
                                                    <td>
                                                        <?php if (!empty($category['image_url']) && file_exists($category['image_url'])): ?>
                                                            <img src="<?php echo $category['image_url']; ?>" class="category-image" alt="<?php echo htmlspecialchars($category['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="category-image bg-light d-flex align-items-center justify-content-center">
                                                                <i class="bi bi-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($category['description'], 0, 50) . (strlen($category['description']) > 50 ? '...' : '')); ?></td>
                                                    <td><?php echo $product_count; ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="admin_categories.php?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
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

            <!-- Product Management Section -->
            <h2 class="mt-4 mb-3">Manage Products in Categories</h2>
            <p class="text-muted mb-4">Drag and drop products between categories to reorganize them.</p>
            
            <div class="row" id="product-categories-container">
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-4 category-card">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                                <span class="badge bg-primary" id="category-count-<?php echo $category['id']; ?>">
                                    <?php
                                        $product_count_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
                                        $product_count_stmt = $conn->prepare($product_count_query);
                                        $product_count_stmt->bind_param("i", $category['id']);
                                        $product_count_stmt->execute();
                                        $product_count_result = $product_count_stmt->get_result();
                                        echo $product_count_result->fetch_assoc()['count'];
                                    ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="products-container" id="category-<?php echo $category['id']; ?>" data-category-id="<?php echo $category['id']; ?>">
                                    <?php
                                        $products_query = "SELECT id, name, price, image_url FROM products WHERE category_id = ? ORDER BY name LIMIT 10";
                                        $products_stmt = $conn->prepare($products_query);
                                        $products_stmt->bind_param("i", $category['id']);
                                        $products_stmt->execute();
                                        $products_result = $products_stmt->get_result();
                                        
                                        if ($products_result->num_rows === 0) {
                                            echo '<div class="text-center text-muted">No products in this category</div>';
                                        } else {
                                            while ($product = $products_result->fetch_assoc()) {
                                                echo '<div class="product-item" data-product-id="' . $product['id'] . '">';
                                                echo '<div class="d-flex align-items-center">';
                                                
                                                // Product image
                                                if (!empty($product['image_url']) && file_exists($product['image_url'])) {
                                                    echo '<img src="' . $product['image_url'] . '" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;" alt="' . htmlspecialchars($product['name']) . '">';
                                                } else {
                                                    echo '<div class="me-2 bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 4px;">';
                                                    echo '<i class="bi bi-image text-muted"></i>';
                                                    echo '</div>';
                                                }
                                                
                                                // Product details
                                                echo '<div>';
                                                echo '<div class="fw-medium">' . htmlspecialchars($product['name']) . '</div>';
                                                echo '<div class="small text-muted">₹' . number_format($product['price'], 2) . '</div>';
                                                echo '</div>';
                                                
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                    Are you sure you want to delete the category "<span id="categoryName"></span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        // Delete confirmation
        function confirmDelete(categoryId, categoryName) {
            document.getElementById('categoryName').textContent = categoryName;
            document.getElementById('deleteCategoryId').value = categoryId;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Initialize drag and drop
        $(function() {
            $(".products-container").sortable({
                connectWith: ".products-container",
                placeholder: "ui-state-highlight",
                update: function(event, ui) {
                    // Only trigger if this is the receiving container
                    if (this === ui.item.parent()[0]) {
                        const productId = ui.item.data('product-id');
                        const newCategoryId = $(this).data('category-id');
                        
                        // Update product category via AJAX
                        $.ajax({
                            url: 'admin_categories.php',
                            type: 'POST',
                            data: {
                                update_product_category: 1,
                                product_id: productId,
                                new_category_id: newCategoryId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // Update category counts
                                    updateCategoryCounts();
                                } else {
                                    alert('Error: ' + response.message);
                                    // Revert the drag if there was an error
                                    $(".products-container").sortable('cancel');
                                }
                            },
                            error: function() {
                                alert('An error occurred while updating the product category.');
                                // Revert the drag if there was an error
                                $(".products-container").sortable('cancel');
                            }
                        });
                    }
                }
            }).disableSelection();
            
            // Function to update category counts
            function updateCategoryCounts() {
                $('.products-container').each(function() {
                    const categoryId = $(this).data('category-id');
                    const productCount = $(this).children('.product-item').length;
                    $('#category-count-' + categoryId).text(productCount);
                });
            }
        });
    </script>
</body>
</html>
