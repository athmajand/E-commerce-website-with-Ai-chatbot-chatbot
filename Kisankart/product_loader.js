// Product Loader JavaScript for Kisan Kart

// Global variables
let currentPage = 1;
let totalPages = 1;
let productsPerPage = 12;
let categoryFilter = 0;
let minPrice = 0;
let maxPrice = 0;
let inStockOnly = true;
let searchQuery = '';
let sortBy = 'newest';

// DOM elements
const productsContainer = document.getElementById('products-container');
const productsCount = document.getElementById('products-count');
const paginationContainer = document.getElementById('pagination-container');

// Initialize product loader
function initProductLoader() {
    // Load products on page load
    loadProducts();

    // Add event listeners for filters if they exist
    setupEventListeners();
}

// Setup event listeners for filters
function setupEventListeners() {
    // Search form
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            searchQuery = document.getElementById('search-input').value;
            currentPage = 1; // Reset to first page
            loadProducts();
        });
    }

    // Category filter
    const categorySelect = document.getElementById('category-filter');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            categoryFilter = this.value;
            currentPage = 1; // Reset to first page
            loadProducts();
        });
    }

    // Price range filters
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    const applyPriceBtn = document.getElementById('apply-price');

    if (applyPriceBtn) {
        applyPriceBtn.addEventListener('click', function() {
            minPrice = minPriceInput.value || 0;
            maxPrice = maxPriceInput.value || 0;
            currentPage = 1; // Reset to first page
            loadProducts();
        });
    }

    // Sort by filter
    const sortBySelect = document.getElementById('sort-by');
    if (sortBySelect) {
        sortBySelect.addEventListener('change', function() {
            sortBy = this.value;
            loadProducts();
        });
    }
}

// Load products from the server
function loadProducts() {
    // Show loading spinner
    productsContainer.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    // Update products count
    productsCount.textContent = 'Loading...';

    // Build query parameters
    const params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('limit', 9); // 9 products per page (3x3 grid)

    if (searchQuery) {
        params.append('search', searchQuery);
    }

    if (categoryFilter && categoryFilter > 0) {
        params.append('category_id', categoryFilter);
    }

    if (minPrice > 0) {
        params.append('min_price', minPrice);
    }

    if (maxPrice > 0) {
        params.append('max_price', maxPrice);
    }

    if (sortBy) {
        params.append('sort_by', sortBy);
    }

    // Log the URL we're fetching
    // Use override URL if available, otherwise use direct_db_test.php
    const url = window.overrideProductsUrl || `direct_db_test.php`;
    console.log('Fetching products from:', url);

    // Fetch products from the server with query parameters
    const fetchUrl = `${url}?${params.toString()}`;
    console.log('Fetching products from:', fetchUrl);

    fetch(fetchUrl)
        .then(response => {
            console.log('Response status:', response.status);

            // Try to get the response text first to debug any issues
            return response.text().then(text => {
                console.log('Response text:', text);

                try {
                    // Try to parse the response as JSON
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    throw new Error(`Invalid JSON response: ${text}`);
                }
            });
        })
        .then(data => {
            console.log('Parsed data:', data);

            // Handle different response formats
            let products = [];
            let totalProductsCount = 0;

            if (data.success && data.products) {
                // Format from direct_db_test.php
                products = data.products;
                totalProductsCount = data.total_products || products.length;
                totalPages = data.total_pages || 1;
                currentPage = data.current_page || 1;
            } else if (data.products) {
                // Format from get_products.php
                products = data.products;
                totalProductsCount = data.total_products || 0;
                totalPages = data.total_pages || 1;
                currentPage = data.current_page || 1;
            } else {
                // Unknown format
                console.warn('Unknown data format:', data);
                products = [];
                totalProductsCount = 0;
                totalPages = 1;
            }

            // Update products count
            productsCount.textContent = `${totalProductsCount} products found`;

            // Preload images before displaying products to prevent layout shifts
            const imagesToPreload = [];
            products.forEach(product => {
                if (product.image_url && product.image_url.trim() !== '') {
                    // Get image URL using the same logic as in createProductCard
                    let imageUrl = product.image_url;
                    if (!imageUrl.includes('uploads/products/')) {
                        imageUrl = 'uploads/products/' + imageUrl.split('/').pop();
                    }

                    // Add path prefix if needed
                    if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
                        if (window.location.pathname.includes('/frontend/')) {
                            imageUrl = '../' + imageUrl;
                        }
                    }

                    imagesToPreload.push(imageUrl);
                }
            });

            // Function to preload images
            const preloadImages = (urls) => {
                const promises = urls.map(url => {
                    return new Promise((resolve) => {
                        const img = new Image();
                        img.onload = () => resolve();
                        img.onerror = () => resolve(); // Resolve even on error to continue
                        img.src = url;
                    });
                });

                // Wait for all images to preload or timeout after 2 seconds
                return Promise.race([
                    Promise.all(promises),
                    new Promise(resolve => setTimeout(resolve, 2000))
                ]);
            };

            // Preload images then display products
            preloadImages(imagesToPreload).then(() => {
                // Display products
                displayProducts(products);

                // Update pagination
                updatePagination();

                // Add event listeners to product buttons
                addEventListenersToProductButtons();
            });
        })
        .catch(error => {
            console.error('Error loading products:', error);
            productsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Failed to load products. Please try again later.
                        <br><small class="text-muted">${error.message}</small>
                    </div>
                    <button class="btn btn-success mt-3" onclick="loadProducts()">
                        <i class="bi bi-arrow-clockwise"></i> Retry
                    </button>
                </div>
            `;
            productsCount.textContent = 'Error loading products';
            paginationContainer.innerHTML = '';
        });
}

// Display products in the container
function displayProducts(products) {
    // Clear container
    productsContainer.innerHTML = '';

    // Add products-container class to enable specific CSS targeting
    productsContainer.classList.add('products-container');

    // Filter out products with no seller_id
    const filteredProducts = products.filter(
        product => product.seller_id && product.seller_id !== 0 && product.seller_id !== null
    );

    // Check if products were found
    if (!filteredProducts || filteredProducts.length === 0) {
        productsContainer.innerHTML = `
            <div class="col-12 text-center py-5">
                <p class="text-muted">No products found matching your criteria.</p>
            </div>
        `;
        return;
    }

    // Create product cards
    filteredProducts.forEach(product => {
        const productCard = createProductCard(product);
        productsContainer.appendChild(productCard);
    });

    // Force layout recalculation to prevent shaking
    // This triggers a reflow which helps stabilize the layout
    void productsContainer.offsetHeight;
}

// Create a product card element
function createProductCard(product) {
    // Create column element
    const col = document.createElement('div');
    col.className = 'col';

    // Format prices
    const price = parseFloat(product.price);
    const discountPrice = product.discount_price ? parseFloat(product.discount_price) : null;

    const formattedPrice = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(price);

    const formattedDiscountPrice = discountPrice ? new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(discountPrice) : '';

    // Calculate discount percentage if applicable
    let discountBadge = '';
    if (discountPrice && price > discountPrice) {
        const discount = ((price - discountPrice) / price) * 100;
        discountBadge = `<span class="badge bg-danger position-absolute top-0 end-0 m-2">-${Math.round(discount)}%</span>`;
    }

    // Get image URL or use placeholder
    let imageUrl = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2Yzc1N2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIwLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';

    // Check for different image URL formats in the product data
    if (product.image_url && product.image_url.trim() !== '') {
        // Get just the filename from the path
        const filename = product.image_url.split('/').pop();

        // If the image_url already includes the full path, use it directly
        if (product.image_url.includes('uploads/products/')) {
            imageUrl = product.image_url;
        } else {
            // Otherwise, construct the path using the filename
            imageUrl = 'uploads/products/' + filename;
        }
    } else if (product.image && product.image.trim() !== '') {
        // Get just the filename from the path
        const filename = product.image.split('/').pop();

        // Construct the path using the filename
        imageUrl = 'uploads/products/' + filename;
    }

    // If the image URL doesn't start with http or /, add a relative path
    if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
        // When in frontend folder, need to go up one level
        if (window.location.pathname.includes('/frontend/')) {
            imageUrl = '../' + imageUrl;
        }
    }

    console.log('Product image URL:', imageUrl);

    // Parse image settings if available
    let imageSettings = {};
    if (product.image_settings) {
        try {
            imageSettings = JSON.parse(product.image_settings);
        } catch (e) {
            console.error('Error parsing image settings for product', product.id, ':', e);
        }
    }

    // Set default values if not specified
    const imageSize = imageSettings.size || 'default';
    const imagePadding = imageSettings.padding || 'medium';
    const imageFit = imageSettings.fit || 'contain';
    const imageBackground = imageSettings.background || 'light';
    const imageZoom = imageSettings.zoom || 1;
    const imagePanX = imageSettings.panX || 0;
    const imagePanY = imageSettings.panY || 0;

    // Map background values to actual colors
    const backgroundColors = {
        'light': '#f8f9fa',
        'white': '#ffffff',
        'transparent': 'transparent',
        'dark': '#6c757d'
    };

    // Apply image settings to the img element
    const imageStyle = `
        object-fit: ${imageFit};
        transform: translate(calc(-50% + ${imagePanX}px), calc(-50% + ${imagePanY}px)) scale(${imageZoom});
    `;

    // Create card HTML with fixed dimensions to prevent shaking
    col.innerHTML = `
        <div class="card product-card">
            <a href="product_details.php?id=${product.id}" class="text-decoration-none d-block">
                <div class="position-relative">
                    ${discountBadge}
                    <div class="image-container" style="width: 300px !important; height: 200px !important; overflow: hidden !important; position: relative !important; border: 1px solid #ddd !important; background-color: ${backgroundColors[imageBackground]} !important; border-radius: 8px !important; margin: 0 auto !important; display: flex !important; align-items: center !important; justify-content: center !important;">
                        <img
                            src="${imageUrl}"
                            class="product-image"
                            alt="${product.name}"
                            loading="lazy"
                            data-size="${imageSize}"
                            data-padding="${imagePadding}"
                            data-fit="${imageFit}"
                            data-background="${imageBackground}"
                            style="position: absolute !important; top: 50% !important; left: 50% !important; transform-origin: center center !important; transform: translate(calc(-50% + ${imagePanX}px), calc(-50% + ${imagePanY}px)) scale(${imageZoom}) !important; object-fit: ${imageFit} !important; width: 100% !important; height: 100% !important; user-select: none !important; pointer-events: none !important; transition: transform 0.2s ease !important; padding: 0 !important; margin: 0 !important; max-width: none !important; max-height: none !important;"
                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2Yzc1N2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIwLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+'; console.error('Failed to load image:', this.src);">
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-dark" style="height: 48px; overflow: hidden;">${product.name}</h5>
                    <p class="card-text text-muted small flex-grow-1" style="height: 60px; overflow: hidden;">
                        ${product.description ? product.description.substring(0, 80) + '...' : 'No description available'}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <div>
                            ${discountPrice ?
                                `<span class="text-decoration-line-through text-muted small">${formattedPrice}</span><br>
                                <span class="fs-5 fw-bold text-success">${formattedDiscountPrice}</span>` :
                                `<span class="fs-5 fw-bold text-success">${formattedPrice}</span>`}
                        </div>
                    </div>
                </div>
            </a>
            <div class="card-footer bg-transparent border-top-0">
                <!-- Buy Now Button -->
                <button class="btn btn-primary w-100 mb-2 buy-now-btn" data-product-id="${product.id}">
                    <i class="bi bi-bag-check"></i> Buy Now
                </button>

                <!-- Add to Cart and Wishlist Buttons -->
                <div class="d-flex justify-content-between">
                    <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="${product.id}" aria-label="Add ${product.name} to cart">
                        <i class="bi bi-cart-plus" aria-hidden="true"></i> Add to Cart
                    </button>
                    <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="${product.id}" aria-label="Add ${product.name} to wishlist">
                        <i class="bi bi-heart" aria-hidden="true"></i>
                        <span class="sr-only">Add to Wishlist</span>
                    </button>
                </div>
            </div>
        </div>
    `;

    return col;
}

// Update pagination controls
function updatePagination() {
    // Clear pagination container
    paginationContainer.innerHTML = '';

    // Don't show pagination if there's only one page
    if (totalPages <= 1) {
        return;
    }

    // Create pagination HTML
    let paginationHTML = '';

    // Previous button
    paginationHTML += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;

    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    // Adjust start page if we're near the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page
    if (startPage > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="1">1</a>
            </li>
        `;

        if (startPage > 2) {
            paginationHTML += `
                <li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>
            `;
        }
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `
                <li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>
            `;
        }

        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
            </li>
        `;
    }

    // Next button
    paginationHTML += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;

    // Set pagination HTML
    paginationContainer.innerHTML = paginationHTML;

    // Add event listeners to pagination links
    document.querySelectorAll('#pagination-container .page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            if (page && page !== currentPage) {
                currentPage = page;
                loadProducts();
                // Scroll to top of products
                document.getElementById('products-heading').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

// Function to check if customer is logged in
async function checkLoginStatus() {
    try {
        console.log('Checking login status...');
        const response = await fetch('../api/check_login.php');
        console.log('Response status:', response.status);

        if (response.ok) {
            const data = await response.json();
            console.log('Login check response:', data);
            return data.logged_in === true;
        }

        console.log('Response not OK');
        return false;
    } catch (error) {
        console.error('Error checking login status:', error);
        return false;
    }
}

// Function to show login required modal
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

// Function to show toast notification
function showToast(message, type = 'success') {
    const toastDiv = document.createElement('div');
    toastDiv.className = 'position-fixed bottom-0 end-0 p-3';
    toastDiv.style.zIndex = '5';

    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle';

    toastDiv.innerHTML = `
        <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${icon} me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    document.body.appendChild(toastDiv);
    const toast = new bootstrap.Toast(toastDiv.querySelector('.toast'));
    toast.show();

    // Remove toast element after it's hidden
    toastDiv.querySelector('.toast').addEventListener('hidden.bs.toast', function() {
        document.body.removeChild(toastDiv);
    });
}

// Function to add product to wishlist
function addToWishlist(productId) {
    console.log('Adding product to wishlist:', productId);

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
    .then(response => {
        console.log('Wishlist response status:', response.status);
        return response.text().then(text => {
            console.log('Wishlist response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error parsing wishlist response:', e);
                throw new Error('Invalid JSON response from wishlist API');
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Show success message
            showToast('Product added to wishlist successfully!', 'success');

            // Change heart icon to filled
            const wishlistBtn = document.querySelector(`.add-to-wishlist-btn[data-product-id="${productId}"]`);
            if (wishlistBtn) {
                wishlistBtn.innerHTML = '<i class="bi bi-heart-fill"></i>';
            }
        } else {
            if (data.message === 'User not logged in') {
                showLoginRequiredModal();
            } else {
                showToast(data.message || 'Failed to add product to wishlist', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error adding to wishlist:', error);
        showToast('Failed to add product to wishlist. Please try again.', 'error');
    });
}

// Function to add product to cart
function addToCart(productId) {
    console.log('Adding product to cart:', productId);

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
    .then(response => {
        console.log('Cart response status:', response.status);
        return response.text().then(text => {
            console.log('Cart response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error parsing cart response:', e);
                throw new Error('Invalid JSON response from cart API');
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Show success message
            showToast('Product added to cart successfully!', 'success');

            // Update cart count if available
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                const currentCount = parseInt(cartCount.textContent) || 0;
                cartCount.textContent = currentCount + 1;
            }
        } else {
            if (data.message === 'User not logged in') {
                showLoginRequiredModal();
            } else {
                showToast(data.message || 'Failed to add product to cart', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showToast('Failed to add product to cart. Please try again.', 'error');
    });
}

// Buy Now functionality
function buyNow(productId) {
    // Navigate directly to product_details.php with the product ID
    window.location.href = `product_details.php?id=${productId}`;
}

// Add event listeners to product buttons
function addEventListenersToProductButtons() {
    // Buy now buttons
    document.querySelectorAll('.buy-now-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            buyNow(productId);
        });
    });

    // Add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            addToCart(productId);
        });
    });

    // Add to wishlist buttons
    document.querySelectorAll('.add-to-wishlist-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            addToWishlist(productId);
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize product loader
    initProductLoader();

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
