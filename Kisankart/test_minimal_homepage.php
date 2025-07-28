<?php
// Start the session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the active page for the navbar
$active_page = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisankart - Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body>
    <h1>Kisankart Test Homepage</h1>
    
    <!-- Test Database Connection -->
    <div class="container">
        <h2>Testing Database Connection</h2>
        <?php
        try {
            // Include database configuration
            include_once __DIR__ . '/api/config/database.php';
            
            // Get database connection
            $database = new Database();
            $db = $database->getConnection();
            echo "<p style='color: green;'>✓ Database connection successful</p>";
            
            // Test products query
            $query = "SELECT COUNT(*) as count FROM products";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Total products in database: <strong>{$result['count']}</strong></p>";
            
            // Test featured products
            $query = "SELECT COUNT(*) as count FROM products WHERE is_featured = 1 AND status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Featured products: <strong>{$result['count']}</strong></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <!-- Test Featured Products Display -->
    <div class="container mt-4">
        <h2>Testing Featured Products Display</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 products-container" id="products-container">
            <?php
            try {
                // Get featured products directly
                $query = "SELECT * FROM products WHERE is_featured = 1 AND status = 'active' ORDER BY id DESC LIMIT 6";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($featured_products)) {
                    // Try a simpler query
                    $query = "SELECT * FROM products ORDER BY id DESC LIMIT 6";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                if (!empty($featured_products)) {
                    foreach ($featured_products as $product) {
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

                        // Output product card
                        echo '<div class="col">
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
                            echo '<span class="text-decoration-line-through text-muted small">₹' . $price . '</span><br>
                                    <span class="fs-5 fw-bold text-success">₹' . $discount_price . '</span>';
                        } else {
                            echo '<span class="fs-5 fw-bold text-success">₹' . $price . '</span>';
                        }

                        echo '                        </div>
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
                } else {
                    echo '<div class="col-12 text-center">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Featured products are currently unavailable. Please check back later.
                            </div>
                            <a href="products.php" class="btn btn-success">Browse All Products</a>
                          </div>';
                }
            } catch (Exception $e) {
                // Fallback content if featured products can't be loaded
                echo '<div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Featured products are currently unavailable. Please check back later.
                        </div>
                        <a href="products.php" class="btn btn-success">Browse All Products</a>
                      </div>';
            }
            ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 