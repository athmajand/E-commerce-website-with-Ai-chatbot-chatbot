<?php
// Start session if not already started
session_start();

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$isLoggedIn = false;
$userName = 'User';

// Check if user is logged in by cross-checking with customer_registrations table
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Check if user exists in customer_registrations table
    $query = "SELECT id, first_name, last_name FROM customer_registrations WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $userId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $isLoggedIn = true;
        $userName = $customer['first_name'] . ' ' . $customer['last_name'];
    }
}

// Get category from URL parameter if it exists
$selectedCategory = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Check for error message in URL
$errorMessage = '';
if (isset($_GET['message'])) {
    $errorMessage = htmlspecialchars($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/product-cards.css">
    <link rel="stylesheet" href="css/dynamic-image-styles.css">
</head>
<body>
<?php
// Set the active page
$active_page = 'products';

// Include the navigation bar
include_once('../includes/navbar.php');
?>

    <!-- Search and Filter Section -->
    <section class="py-4 bg-light">
        <div class="container">
            <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                <?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search-input" placeholder="Search products...">
                        <button class="btn btn-success" type="button" id="search-button">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="sort-select" class="visually-hidden">Sort products by</label>
                    <select class="form-select" id="sort-select" title="Sort products by" aria-label="Sort products by">
                        <option value="newest">Newest First</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="name_asc">Name: A to Z</option>
                        <option value="name_desc">Name: Z to A</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-lg-3 mb-4 mb-lg-0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Filters</h5>

                            <!-- Categories Filter -->
                            <div class="mb-4">
                                <h5 class="text-success mb-2">Farm fresh vegetables</h5>
                                <h6 class="mb-2">Shop by Category</h6>
                                <div id="categories-filter">
                                    <div class="form-check">
                                        <input class="form-check-input category-filter" type="checkbox" id="category-1" value="1" <?php echo ($selectedCategory == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-1">
                                            Vegetables
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input category-filter" type="checkbox" id="category-2" value="2" <?php echo ($selectedCategory == 2) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-2">
                                            Fruits
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input category-filter" type="checkbox" id="category-4" value="4" <?php echo ($selectedCategory == 4) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-4">
                                            Dairy
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input category-filter" type="checkbox" id="category-3" value="3" <?php echo ($selectedCategory == 3) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-3">
                                            Grains
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input category-filter" type="checkbox" id="category-5" value="5" <?php echo ($selectedCategory == 5) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-5">
                                            Spices
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input category-filter" type="checkbox" id="category-7" value="7" <?php echo ($selectedCategory == 7) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-7">
                                            Other
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Range Filter -->
                            <div class="mb-4">
                                <h6 class="mb-2">Price Range</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" class="form-control form-control-sm" id="min-price" placeholder="Min">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control form-control-sm" id="max-price" placeholder="Max">
                                    </div>
                                </div>
                            </div>

                            <!-- Availability Filter -->
                            <div class="mb-4">
                                <h6 class="mb-2">Availability</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="in-stock-filter" checked>
                                    <label class="form-check-label" for="in-stock-filter">
                                        In Stock
                                    </label>
                                </div>
                            </div>

                            <!-- Apply Filters Button -->
                            <button class="btn btn-success w-100" id="apply-filters-btn">
                                Apply Filters
                            </button>

                            <!-- Clear Filters Link -->
                            <div class="text-center mt-2">
                                <a href="#" id="clear-filters-link" class="text-decoration-none">Clear all filters</a>
                            </div>
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
    </section>

    <!-- Modal dialogs will be created dynamically by JavaScript -->

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container px-5">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Kisan Kart</h5>
                    <p>Connecting farmers and customers for a better agricultural ecosystem.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="index.php#about" class="text-white">About Us</a></li>
                        <li><a href="../login.php" class="text-white">Login</a></li>
                        <li><a href="register.html" class="text-white">Register</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5>Contact Us</h5>
                    <p>Email: info@kisankart.com<br>
                    Phone: +91 1234567890</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="small mb-0">© 2025 Kisan Kart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>
    <!-- Chatbot Integration -->
    <script src="js/chatbot-integration.js"></script>
    <script>
    // Configure chatbot for products page
    window.kisanKartChatbotConfig = {
        apiBaseUrl: window.location.origin + '/Kisankart/api/chatbot',
        position: 'bottom-right',
        theme: 'green',
        autoOpen: false,
        enableOnPages: ['all'],
        excludePages: ['/admin'],
        enableForRoles: ['all'],
        debug: true
    };

    // Override the URL in product_loader.js to use direct_db_test.php
    window.overrideProductsUrl = '../direct_db_test.php';
    </script>
    <script src="../product_loader.js"></script>
    <script>
    // Auto-dismiss alerts after 5 seconds and clean up any existing modals
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Clean up any existing modals
        const existingModals = document.querySelectorAll('#loginRequiredModal');
        existingModals.forEach(modal => {
            try {
                const bsInstance = bootstrap.Modal.getInstance(modal);
                if (bsInstance) {
                    bsInstance.dispose();
                }
            } catch (e) {
                console.error('Error disposing modal:', e);
            }

            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        });
    });

    // Show login required modal
    function showLoginRequiredModal() {
        // First, remove any existing modals with the same ID
        const existingModals = document.querySelectorAll('#loginRequiredModal');
        existingModals.forEach(modal => {
            // Try to dispose of Bootstrap modal instance if it exists
            try {
                const bsInstance = bootstrap.Modal.getInstance(modal);
                if (bsInstance) {
                    bsInstance.dispose();
                }
            } catch (e) {
                console.error('Error disposing modal:', e);
            }

            // Remove the element
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        });

        // Create a new modal
        const modalDiv = document.createElement('div');
        modalDiv.className = 'modal fade';
        modalDiv.id = 'loginRequiredModal';
        modalDiv.tabIndex = '-1';
        modalDiv.setAttribute('aria-labelledby', 'loginRequiredModalLabel');
        modalDiv.setAttribute('aria-hidden', 'true');

        // Message for non-logged in users
        modalDiv.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="loginRequiredModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Login Required</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>You need to be logged in to perform this action.</p>
                        <p>Please log in to continue.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="../login.php?redirect=frontend/products.php" class="btn btn-success">Login Now</a>
                    </div>
                </div>
            </div>
        `;

        // Add to document
        document.body.appendChild(modalDiv);

        // Add event listener to remove modal from DOM when hidden
        modalDiv.addEventListener('hidden.bs.modal', function() {
            if (modalDiv.parentNode) {
                modalDiv.parentNode.removeChild(modalDiv);
            }
        });

        // Show the modal
        const bsModal = new bootstrap.Modal(modalDiv);
        bsModal.show();
    }

    // Add to cart functionality
    function addToCart(productId) {
        // Check if user is logged in
        <?php if ($isLoggedIn): ?>
        // Send AJAX request to add product to cart
        fetch('../add_to_cart.php', {
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
                // Show success message
                const successToast = document.createElement('div');
                successToast.className = 'position-fixed bottom-0 end-0 p-3';
                successToast.style.zIndex = '5';
                successToast.innerHTML = `
                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="bi bi-check-circle me-2"></i> Product added to cart successfully!
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                document.body.appendChild(successToast);
                const toast = new bootstrap.Toast(successToast.querySelector('.toast'));
                toast.show();

                // Update cart count if available
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    const currentCount = parseInt(cartCount.textContent) || 0;
                    cartCount.textContent = currentCount + 1;
                }
            } else {
                alert(data.message || 'Failed to add product to cart');
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            alert('Failed to add product to cart. Please try again.');
        });
        <?php else: ?>
        // Show login required modal
        showLoginRequiredModal();
        <?php endif; ?>
    }

    // Add to wishlist functionality
    function addToWishlist(productId) {
        // Check if user is logged in
        <?php if ($isLoggedIn): ?>
        // Send AJAX request to add product to wishlist
        fetch('../add_to_wishlist.php', {
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
                // Show success message
                const successToast = document.createElement('div');
                successToast.className = 'position-fixed bottom-0 end-0 p-3';
                successToast.style.zIndex = '5';
                successToast.innerHTML = `
                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="bi bi-check-circle me-2"></i> Product added to wishlist successfully!
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                document.body.appendChild(successToast);
                const toast = new bootstrap.Toast(successToast.querySelector('.toast'));
                toast.show();
            } else {
                alert(data.message || 'Failed to add product to wishlist');
            }
        })
        .catch(error => {
            console.error('Error adding to wishlist:', error);
            alert('Failed to add product to wishlist. Please try again.');
        });
        <?php else: ?>
        // Show login required modal
        showLoginRequiredModal();
        <?php endif; ?>
    }

    // Buy Now functionality
    function buyNow(productId) {
        // Navigate directly to product_details.php with the product ID
        window.location.href = `product_details.php?id=${productId}`;
    }

    // Initialize with category filter if provided in URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');

        if (category) {
            categoryFilter = parseInt(category);
        }

        // Set up event listeners for the existing filter UI
        document.querySelectorAll('.category-filter').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Uncheck other category checkboxes
                document.querySelectorAll('.category-filter').forEach(cb => {
                    if (cb !== this) cb.checked = false;
                });

                // Set category filter
                categoryFilter = this.checked ? parseInt(this.value) : 0;
            });
        });

        // Apply filters button
        document.getElementById('apply-filters-btn').addEventListener('click', function() {
            // Get min and max price
            minPrice = document.getElementById('min-price').value || 0;
            maxPrice = document.getElementById('max-price').value || 0;

            // Get in-stock filter
            inStockOnly = document.getElementById('in-stock-filter').checked;

            // Reset to first page and load products
            currentPage = 1;
            loadProducts();
        });

        // Clear filters link
        document.getElementById('clear-filters-link').addEventListener('click', function(e) {
            e.preventDefault();

            // Clear all filters
            document.querySelectorAll('.category-filter').forEach(cb => {
                cb.checked = false;
            });

            document.getElementById('min-price').value = '';
            document.getElementById('max-price').value = '';
            document.getElementById('in-stock-filter').checked = true;
            document.getElementById('search-input').value = '';

            // Reset filter variables
            categoryFilter = 0;
            minPrice = 0;
            maxPrice = 0;
            inStockOnly = true;
            searchQuery = '';
            currentPage = 1;

            // Load products
            loadProducts();
        });

        // Search button
        document.getElementById('search-button').addEventListener('click', function() {
            searchQuery = document.getElementById('search-input').value;
            currentPage = 1;
            loadProducts();
        });

        // Sort select
        document.getElementById('sort-select').addEventListener('change', function() {
            sortBy = this.value;
            loadProducts();
        });
    });
    </script>
</body>
</html>
