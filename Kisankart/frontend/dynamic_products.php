<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Set a time limit to prevent long-running scripts
set_time_limit(30);

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';

// Get database connection
try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    // Log the error but don't display it
    error_log("Database connection error: " . $e->getMessage());

    // Output a simple error message
    echo '<div class="col-12 text-center">
            <p class="text-muted">Unable to connect to the database. Please try again later.</p>
          </div>';
    exit;
}

// Function to get products
function getProducts($db, $limit = 6, $featured_only = true) {
    $products = [];

    // Check if database connection is valid
    if (!$db) {
        error_log("Database connection is null in getProducts");
        return $products;
    }

    try {
        // Set a timeout for database queries
        $db->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 seconds timeout

        // Use a simple query first to check if the products table exists
        try {
            $check_table = $db->query("SHOW TABLES LIKE 'products'");
            $table_exists = $check_table && $check_table->rowCount() > 0;

            if (!$table_exists) {
                error_log("Products table does not exist");
                return $products;
            }
        } catch (PDOException $e) {
            error_log("Error checking products table: " . $e->getMessage());
            return $products;
        }

        // First, check if is_featured column exists
        $check_column = $db->query("SHOW COLUMNS FROM products LIKE 'is_featured'");
        $is_featured_exists = $check_column && $check_column->rowCount() > 0;

        // Check if status column exists
        $check_status = $db->query("SHOW COLUMNS FROM products LIKE 'status'");
        $status_exists = $check_status && $check_status->rowCount() > 0;

        // Check if created_at column exists
        $check_created_at = $db->query("SHOW COLUMNS FROM products LIKE 'created_at'");
        $created_at_exists = $check_created_at && $check_created_at->rowCount() > 0;

        // Determine the ORDER BY clause
        $order_by = $created_at_exists ? "p.created_at DESC" : "p.id DESC";

        // Construct query based on available columns and whether to get featured products only
        if ($featured_only && $is_featured_exists && $status_exists) {
            // Use both is_featured and status
            $query = "SELECT p.* FROM products p WHERE p.is_featured = 1 AND p.status = 'active' ORDER BY $order_by LIMIT $limit";
        } elseif ($featured_only && $is_featured_exists) {
            // Use only is_featured
            $query = "SELECT p.* FROM products p WHERE p.is_featured = 1 ORDER BY $order_by LIMIT $limit";
        } elseif ($status_exists) {
            // Use only status
            $query = "SELECT p.* FROM products p WHERE p.status = 'active' ORDER BY $order_by LIMIT $limit";
        } else {
            // Use neither
            $query = "SELECT p.* FROM products p ORDER BY $order_by LIMIT $limit";
        }

        // Execute query with timeout
        $stmt = $db->prepare($query);
        $stmt->execute();

        // If no products found, try a simpler query
        if ($stmt->rowCount() == 0) {
            $query = "SELECT * FROM products ORDER BY id DESC LIMIT $limit";
            $stmt = $db->prepare($query);
            $stmt->execute();
        }

        // Fetch all products
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching products: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General error in getProducts: " . $e->getMessage());
    }

    return $products;
}

// Get products (featured only by default)
$products = getProducts($db);

// If no products were found and the database connection failed, use sample products
if (empty($products) && !$db) {
    // Sample products for display when database connection fails
    $products = [
        [
            'id' => 1,
            'name' => 'Organic Tomatoes',
            'description' => 'Fresh organic tomatoes from local farms',
            'price' => 120.00,
            'discount_price' => 99.00,
            'image_url' => '',
            'stock_quantity' => 50
        ],
        [
            'id' => 2,
            'name' => 'Premium Rice',
            'description' => 'High-quality basmati rice',
            'price' => 350.00,
            'discount_price' => 320.00,
            'image_url' => '',
            'stock_quantity' => 100
        ],
        [
            'id' => 3,
            'name' => 'Fresh Apples',
            'description' => 'Crisp and juicy apples',
            'price' => 180.00,
            'discount_price' => 150.00,
            'image_url' => '',
            'stock_quantity' => 75
        ]
    ];
}

// Generate HTML for products
$html = '';

if (empty($products)) {
    $html = '<div class="col-12 text-center">
                <p class="text-muted">No products available at the moment.</p>
             </div>';
} else {
    foreach ($products as $product) {
        // Check if required fields exist
        if (!isset($product['name'])) {
            $product['name'] = 'Unnamed Product';
        }

        if (!isset($product['price'])) {
            $product['price'] = 0;
        }

        // Format prices
        $price = number_format($product['price'], 2);
        $discount_price = isset($product['discount_price']) && !empty($product['discount_price'])
            ? number_format($product['discount_price'], 2)
            : null;

        // Calculate discount percentage if applicable
        $discount_badge = '';
        if ($discount_price && $product['price'] > $product['discount_price']) {
            $discount = ((float)$product['price'] - (float)$product['discount_price']) / (float)$product['price'] * 100;
            $discount_badge = '<span class="badge bg-danger position-absolute top-0 end-0 m-2">-' . round($discount) . '%</span>';
        }

        // Get image or placeholder
        $image_url = 'https://via.placeholder.com/300x200?text=Product+Image';

        if (isset($product['image_url']) && !empty($product['image_url'])) {
            // Get just the filename from the path
            $filename = basename($product['image_url']);
            $image_url = '../uploads/products/' . $filename;
        } elseif (isset($product['image']) && !empty($product['image'])) {
            // Get just the filename from the path
            $filename = basename($product['image']);
            $image_url = '../uploads/products/' . $filename;
        }

        // Create product card
        $html .= '<div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="product_details.php?id=' . $product['id'] . '" class="text-decoration-none">
                            ' . $discount_badge . '
                            <img src="' . $image_url . '"
                                class="card-img-top product-image" alt="' . htmlspecialchars($product['name']) . '"
                                onerror="this.src=\'https://via.placeholder.com/300x200?text=No+Image\'">
                            <div class="card-body">
                                <h5 class="card-title text-dark">' . htmlspecialchars($product['name']) . '</h5>
                                <p class="card-text text-muted small">' .
                                    (isset($product['description']) ? htmlspecialchars(substr($product['description'], 0, 80)) . '...' : 'No description available') .
                                '</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="price-container">';

        if ($discount_price) {
            $html .= '<span class="text-decoration-line-through text-muted small">₹' . $price . '</span><br>
                      <span class="fs-5 fw-bold text-success">₹' . $discount_price . '</span>';
        } else {
            $html .= '<span class="fs-5 fw-bold text-success">₹' . $price . '</span>';
        }

        $html .= '      </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <a href="../login.php" class="btn btn-success flex-grow-1 me-2">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </a>
                            <a href="../login.php" class="btn btn-outline-success">
                                <i class="bi bi-heart"></i>
                            </a>
                        </div>
                    </div>
                </div>';
    }
}

// Output the HTML
echo $html;
?>
