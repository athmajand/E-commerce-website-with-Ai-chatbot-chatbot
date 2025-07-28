<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <!-- Sidebar with filters -->
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <form id="search-form" class="mb-3">
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control" placeholder="Search products...">
                            <button class="btn btn-success" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Categories -->
                    <div class="mb-3">
                        <h6 class="fw-bold">Categories</h6>
                        <select id="category-filter" class="form-select">
                            <option value="0">All Categories</option>
                            <?php
                            // Include database configuration
                            include_once __DIR__ . '/api/config/database.php';

                            // Get database connection
                            $database = new Database();
                            $db = $database->getConnection();

                            if ($db) {
                                // Get categories
                                $query = "SELECT id, name FROM categories ORDER BY name";
                                $stmt = $db->prepare($query);
                                $stmt->execute();

                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-3">
                        <h6 class="fw-bold">Price Range</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" id="min-price" class="form-control" placeholder="Min">
                            </div>
                            <div class="col-6">
                                <input type="number" id="max-price" class="form-control" placeholder="Max">
                            </div>
                        </div>
                        <button id="apply-price" class="btn btn-sm btn-success w-100 mt-2">Apply</button>
                    </div>

                    <!-- Sort By -->
                    <div class="mb-3">
                        <h6 class="fw-bold">Sort By</h6>
                        <select id="sort-by" class="form-select">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name_asc">Name: A to Z</option>
                            <option value="name_desc">Name: Z to A</option>
                        </select>
                    </div>

                    <!-- Reset Filters -->
                    <button id="reset-filters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 id="products-heading">All Products</h4>
                <div class="text-muted" id="products-count">Loading...</div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="products-container">
                <!-- Products will be loaded here -->
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <nav class="mt-5" aria-label="Products pagination">
                <ul class="pagination justify-content-center" id="pagination-container">
                    <!-- Pagination will be loaded here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Add to Cart Modal -->
<div class="modal fade" id="addToCartModal" tabindex="-1" aria-labelledby="addToCartModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addToCartModalLabel">Product Added to Cart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>The product has been added to your cart successfully!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                <a href="cart.php" class="btn btn-success">View Cart</a>
            </div>
        </div>
    </div>
</div>

<!-- Add to Wishlist Modal -->
<div class="modal fade" id="addToWishlistModal" tabindex="-1" aria-labelledby="addToWishlistModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addToWishlistModalLabel">Product Added to Wishlist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>The product has been added to your wishlist successfully!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                <a href="wishlist.php" class="btn btn-success">View Wishlist</a>
            </div>
        </div>
    </div>
</div>

<!-- Include product loader script -->
<script src="product_loader.js"></script>

<!-- Add to cart and wishlist functionality -->
<script>
// Function to add product to cart
function addToCart(productId) {
    // Check if user is logged in
    <?php if (isset($_SESSION['user_id'])): ?>
    // Send AJAX request to add product to cart
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success modal
            const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
            modal.show();
        } else {
            alert(data.message || 'Failed to add product to cart');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        alert('Failed to add product to cart. Please try again.');
    });
    <?php else: ?>
    // Redirect to login page
    window.location.href = 'login.php?redirect=products_page.php';
    <?php endif; ?>
}

// Function to add product to wishlist
function addToWishlist(productId) {
    // Check if user is logged in
    <?php if (isset($_SESSION['user_id'])): ?>
    // Send AJAX request to add product to wishlist
    fetch('add_to_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success modal
            const modal = new bootstrap.Modal(document.getElementById('addToWishlistModal'));
            modal.show();
        } else {
            alert(data.message || 'Failed to add product to wishlist');
        }
    })
    .catch(error => {
        console.error('Error adding to wishlist:', error);
        alert('Failed to add product to wishlist. Please try again.');
    });
    <?php else: ?>
    // Redirect to login page
    window.location.href = 'login.php?redirect=products_page.php';
    <?php endif; ?>
}

// Reset filters button
document.getElementById('reset-filters').addEventListener('click', function() {
    document.getElementById('search-input').value = '';
    document.getElementById('category-filter').value = '0';
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    document.getElementById('sort-by').value = 'newest';
    
    // Reset global variables and reload products
    searchQuery = '';
    categoryFilter = 0;
    minPrice = 0;
    maxPrice = 0;
    sortBy = 'newest';
    currentPage = 1;
    
    loadProducts();
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
