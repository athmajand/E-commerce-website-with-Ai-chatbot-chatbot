<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set a time limit to prevent long-running scripts
set_time_limit(30);

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';

// Get database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    // Log the error but don't display it
    error_log("Database connection error: " . $e->getMessage());

    // Output a simple error message
    echo '<div class="col-12 text-center">
            <p class="text-muted">Unable to connect to the database. Please try again later.</p>
          </div>';
    exit;
}

// Function to get featured products
function getFeaturedProducts($db, $limit = 6) {
    $products = [];

    try {
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

        // Construct query based on available columns
        if ($is_featured_exists && $status_exists) {
            // Use both is_featured and status
            $query = "SELECT p.* FROM products p WHERE p.is_featured = 1 AND p.status = 'active' ORDER BY $order_by LIMIT $limit";
        } elseif ($status_exists) {
            // Use only status
            $query = "SELECT p.* FROM products p WHERE p.status = 'active' ORDER BY $order_by LIMIT $limit";
        } elseif ($is_featured_exists) {
            // Use only is_featured
            $query = "SELECT p.* FROM products p WHERE p.is_featured = 1 ORDER BY $order_by LIMIT $limit";
        } else {
            // Use neither
            $query = "SELECT p.* FROM products p ORDER BY $order_by LIMIT $limit";
        }

        // Execute query
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
        error_log("Error fetching featured products: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General error in getFeaturedProducts: " . $e->getMessage());
    }

    return $products;
}

// Get featured products
$featured_products = getFeaturedProducts($db);

// Generate HTML for featured products
$html = '';

if (empty($featured_products)) {
    $html = '<div class="col-12 text-center">
                <p class="text-muted">No products available at the moment.</p>
             </div>';
} else {
    // Do NOT output any debug comments as they can cause issues with JavaScript parsing
    // and lead to continuous reloading

    foreach ($featured_products as $product) {
        // Do NOT output any debug comments as they can cause issues with JavaScript parsing

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
            $image_url = '../' . $product['image_url'];
        } elseif (isset($product['image']) && !empty($product['image'])) {
            $image_url = '../' . $product['image'];
        }

        // Create product card matching homepage style
        $html .= '<div class="col">
                    <div class="card product-card">
                        <a href="product_details.php?id=' . $product['id'] . '" class="text-decoration-none d-block">
                            <div class="position-relative">
                                ' . $discount_badge . '
                                <div class="image-container" style="width: 300px !important; height: 200px !important; overflow: hidden !important; position: relative !important; border: 1px solid #ddd !important; background-color: #f8f9fa !important; border-radius: 8px !important; margin: 0 auto !important; display: flex !important; align-items: center !important; justify-content: center !important;">
                                    <img src="' . $image_url . '"
                                        class="product-image" alt="' . htmlspecialchars($product['name']) . '" loading="lazy" data-size="default" data-padding="medium" data-fit="contain" data-background="light" style="position: absolute !important; top: 50% !important; left: 50% !important; transform-origin: center center !important; transform: translate(calc(-50% + 0px), calc(-50% + 0px)) scale(1) !important; object-fit: contain !important; width: 100% !important; height: 100% !important; user-select: none !important; pointer-events: none !important; transition: transform 0.2s ease !important; padding: 0 !important; margin: 0 !important; max-width: none !important; max-height: none !important;"
                                        onerror="this.src=\'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2Yzc1N2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIwLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+\'; console.error(\'Failed to load image:\', this.src);">
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-dark" style="height: 48px; overflow: hidden;">' . htmlspecialchars($product['name']) . '</h5>
                                <p class="card-text text-muted small flex-grow-1" style="height: 60px; overflow: hidden;">' .
                                    (isset($product['description']) ? htmlspecialchars(substr($product['description'], 0, 80)) . '...' : 'No description available') .
                                '</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <div>';

        if ($discount_price) {
            $html .= '<span class="text-decoration-line-through text-muted small">₹' . $price . '</span><br>
                                <span class="fs-5 fw-bold text-success">₹' . $discount_price . '</span>';
        } else {
            $html .= '<span class="fs-5 fw-bold text-success">₹' . $price . '</span>';
        }

        $html .= '                        </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0">
                            <!-- Buy Now Button -->
                            <button class="btn btn-primary w-100 mb-2 buy-now-btn" data-product-id="' . $product['id'] . '">
                                <i class="bi bi-bag-check"></i> Buy Now
                            </button>

                            <!-- Add to Cart and Wishlist Buttons -->
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="' . $product['id'] . '" aria-label="Add ' . htmlspecialchars($product['name']) . ' to cart">
                                    <i class="bi bi-cart-plus" aria-hidden="true"></i> Add to Cart
                                </button>
                                <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="' . $product['id'] . '" aria-label="Add ' . htmlspecialchars($product['name']) . ' to wishlist">
                                    <i class="bi bi-heart" aria-hidden="true"></i>
                                    <span class="sr-only">Add to Wishlist</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>';
    }
}

// Output the HTML
echo $html;
?> 