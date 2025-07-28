// Products JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Global variables for products page
let currentPage = 1;
let totalPages = 1;
let searchQuery = '';
let categoryFilter = '';
let minPrice = '';
let maxPrice = '';
let inStockOnly = true;
let sortBy = 'newest';

// Function to fetch featured products for homepage
async function fetchFeaturedProducts() {
    // Check if products are already loaded by PHP
    const productsContainer = document.getElementById('featured-products');

    // If the container has the data-php-loaded attribute, don't fetch products again
    if (productsContainer && productsContainer.getAttribute('data-php-loaded') === 'true') {
        console.log('Featured products already loaded by PHP, skipping API call');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/products/featured`);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Check if products were found
        if (!data || data.length === 0) {
            displayNoProductsMessage('featured-products');
            return;
        }

        // Display products
        displayFeaturedProducts(data);
    } catch (error) {
        console.error('Error fetching featured products:', error);
        displayErrorMessage('featured-products', fetchFeaturedProducts);
    }
}

// Function to display featured products on homepage
function displayFeaturedProducts(products) {
    const productsContainer = document.getElementById('featured-products');
    if (!productsContainer) return;

    // Check if products are already loaded by PHP
    if (productsContainer.getAttribute('data-php-loaded') === 'true') {
        console.log('Featured products already loaded by PHP, skipping JS load');
        return;
    }

    // Clear loading spinner
    productsContainer.innerHTML = '';

    // Limit to 6 products for featured section
    const featuredProducts = products.slice(0, 6);

    // Create product cards
    featuredProducts.forEach(product => {
        const productCard = createProductCard(product);
        productsContainer.appendChild(productCard);
    });
}

// Function to fetch all products for products page
async function fetchProducts() {
    try {
        // Show loading spinner
        document.getElementById('products-container').innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        // Build query parameters
        let queryParams = new URLSearchParams();
        queryParams.append('page', currentPage);
        queryParams.append('limit', 9); // 9 products per page

        if (searchQuery) {
            queryParams.append('search', searchQuery);
        }

        if (categoryFilter) {
            queryParams.append('categoryId', categoryFilter);
        }

        if (minPrice) {
            queryParams.append('minPrice', minPrice);
        }

        if (maxPrice) {
            queryParams.append('maxPrice', maxPrice);
        }

        if (inStockOnly) {
            queryParams.append('inStock', 'true');
        }

        if (sortBy) {
            queryParams.append('sortBy', sortBy);
        }

        const response = await fetch(`${API_BASE_URL}/products?${queryParams.toString()}`);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Update global variables
        totalPages = data.totalPages || 1;

        // Update products count
        document.getElementById('products-count').textContent = `${data.totalProducts || 0} products found`;

        // Check if products were found
        if (!data.products || data.products.length === 0) {
            displayNoProductsMessage('products-container');
            document.getElementById('pagination-container').innerHTML = '';
            return;
        }

        // Display products
        displayAllProducts(data.products);

        // Update pagination
        updatePagination();
    } catch (error) {
        console.error('Error fetching products:', error);
        displayErrorMessage('products-container', fetchProducts);
        document.getElementById('pagination-container').innerHTML = '';
    }
}

// Function to display all products on products page
function displayAllProducts(products) {
    const productsContainer = document.getElementById('products-container');
    if (!productsContainer) return;

    // Clear container
    productsContainer.innerHTML = '';

    // Create product cards
    products.forEach(product => {
        const productCard = createProductCard(product, true);
        productsContainer.appendChild(productCard);
    });
}

// Function to create a product card
function createProductCard(product, isProductsPage = false) {
    const col = document.createElement('div');
    col.className = isProductsPage ? 'col' : 'col-md-6 col-lg-4 mb-4';

    // Format prices
    const formattedPrice = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(product.price);

    const formattedDiscountPrice = product.discountPrice ? new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(product.discountPrice) : '';

    // Calculate discount percentage if applicable
    let discountBadge = '';
    if (product.discountPrice && product.price > product.discountPrice) {
        const discount = ((product.price - product.discountPrice) / product.price) * 100;
        discountBadge = `<span class="badge bg-danger position-absolute top-0 end-0 m-2">-${Math.round(discount)}%</span>`;
    }

    // Get first image or placeholder
    const productImage = product.images && product.images.length > 0
        ? product.images[0]
        : 'https://via.placeholder.com/300x200?text=No+Image';

    // Get seller name
    const sellerName = product.Seller && product.Seller.User
        ? `${product.Seller.User.firstName} ${product.Seller.User.lastName}`
        : 'Unknown Seller';

    // Create card HTML
    col.innerHTML = `
        <div class="card product-card h-100">
            <a href="product-details.html?id=${product.id}" class="text-decoration-none">
                ${discountBadge}
                <img src="${productImage}"
                    class="card-img-top product-image" alt="${product.name}"
                    onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                <div class="card-body">
                    <h5 class="card-title text-dark">${product.name}</h5>
                    <p class="card-text text-muted small">${product.description ? product.description.substring(0, 80) + '...' : 'No description available'}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            ${product.discountPrice ?
                                `<span class="text-decoration-line-through text-muted small">${formattedPrice}</span><br>
                                <span class="fs-5 fw-bold text-success">${formattedDiscountPrice}</span>` :
                                `<span class="fs-5 fw-bold text-success">${formattedPrice}</span>`}
                            <span class="text-muted small">/${product.unit}</span>
                        </div>
                    </div>
                </div>
            </a>
            <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                <button class="btn btn-success flex-grow-1 me-2 add-to-cart" data-product-id="${product.id}">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
                <button class="btn btn-outline-success add-to-wishlist" data-product-id="${product.id}">
                    <i class="bi bi-heart"></i>
                </button>
            </div>
        </div>
    `;

    // Add event listeners
    const addToCartBtn = col.querySelector('.add-to-cart');
    addToCartBtn.addEventListener('click', (e) => {
        e.preventDefault();
        addToCart(product.id);
    });

    const addToWishlistBtn = col.querySelector('.add-to-wishlist');
    addToWishlistBtn.addEventListener('click', (e) => {
        e.preventDefault();
        addToWishlist(product.id);
    });

    return col;
}

// Function to add product to cart
async function addToCart(productId) {
    try {
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=products.html';
            return;
        }

        const response = await fetch(`${API_BASE_URL}/cart/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({ productId, quantity: 1 })
        });

        const data = await response.json();

        if (response.ok) {
            showAlert('success', 'Product added to cart successfully!');
            updateCartCount();
        } else {
            showAlert('danger', data.message || 'Failed to add product to cart');
        }
    } catch (error) {
        console.error('Add to cart error:', error);
        showAlert('danger', 'Failed to add product to cart. Please try again.');
    }
}

// Function to add product to wishlist
async function addToWishlist(productId) {
    try {
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=products.html';
            return;
        }

        const response = await fetch(`${API_BASE_URL}/wishlist/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({ productId })
        });

        const data = await response.json();

        if (response.ok) {
            showAlert('success', 'Product added to wishlist successfully!');
        } else {
            showAlert('danger', data.message || 'Failed to add product to wishlist');
        }
    } catch (error) {
        console.error('Add to wishlist error:', error);
        showAlert('danger', 'Failed to add product to wishlist. Please try again.');
    }
}

// Function to display no products message
function displayNoProductsMessage(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
        <div class="col-12 text-center py-4">
            <i class="bi bi-search fs-1 text-muted mb-3"></i>
            <p class="text-muted">No products found. Try adjusting your filters or search criteria.</p>
        </div>
    `;
}

// Function to display error message
function displayErrorMessage(containerId, retryFunction) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
        <div class="col-12 text-center py-4">
            <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
            <p class="text-danger">Failed to load products. Please try again later.</p>
            <button class="btn btn-outline-success mt-2" id="retry-btn">Retry</button>
        </div>
    `;

    document.getElementById('retry-btn').addEventListener('click', retryFunction);
}

// Function to update pagination
function updatePagination() {
    const paginationContainer = document.getElementById('pagination-container');
    if (!paginationContainer) return;

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
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, startPage + 4);

    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
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

    paginationContainer.innerHTML = paginationHTML;

    // Add event listeners to pagination links
    const pageLinks = paginationContainer.querySelectorAll('.page-link');
    pageLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(e.target.closest('.page-link').dataset.page);
            if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                currentPage = page;
                fetchProducts();
                // Scroll to top of products section
                document.querySelector('.py-5').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

// Function to fetch categories for filter
async function fetchCategories() {
    try {
        const response = await fetch(`${API_BASE_URL}/products/categories`);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const categories = await response.json();

        // Display categories in filter
        displayCategoriesFilter(categories);
    } catch (error) {
        console.error('Error fetching categories:', error);
        document.getElementById('categories-filter').innerHTML = '<p class="text-danger small">Failed to load categories</p>';
    }
}

// Function to display categories in filter
function displayCategoriesFilter(categories) {
    const categoriesContainer = document.getElementById('categories-filter');
    if (!categoriesContainer) return;

    let html = '';

    // All categories option
    html += `
        <div class="form-check">
            <input class="form-check-input category-filter" type="radio" name="category" id="category-all" value="" checked>
            <label class="form-check-label" for="category-all">
                All Categories
            </label>
        </div>
    `;

    // Category options
    categories.forEach(category => {
        html += `
            <div class="form-check">
                <input class="form-check-input category-filter" type="radio" name="category" id="category-${category.id}" value="${category.id}">
                <label class="form-check-label" for="category-${category.id}">
                    ${category.name}
                </label>
            </div>
        `;
    });

    categoriesContainer.innerHTML = html;

    // Add event listeners to category filters
    const categoryInputs = document.querySelectorAll('.category-filter');
    categoryInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            categoryFilter = e.target.value;
        });
    });
}

// Function to show alert
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '1050';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);

    // Auto dismiss after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Initialize products page
function initProductsPage() {
    // Add event listeners
    const searchButton = document.getElementById('search-button');
    if (searchButton) {
        searchButton.addEventListener('click', () => {
            searchQuery = document.getElementById('search-input').value.trim();
            currentPage = 1;
            fetchProducts();
        });
    }

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchQuery = e.target.value.trim();
                currentPage = 1;
                fetchProducts();
            }
        });
    }

    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            sortBy = sortSelect.value;
            currentPage = 1;
            fetchProducts();
        });
    }

    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            minPrice = document.getElementById('min-price').value;
            maxPrice = document.getElementById('max-price').value;
            inStockOnly = document.getElementById('in-stock-filter').checked;
            currentPage = 1;
            fetchProducts();
        });
    }

    const clearFiltersLink = document.getElementById('clear-filters-link');
    if (clearFiltersLink) {
        clearFiltersLink.addEventListener('click', (e) => {
            e.preventDefault();

            // Reset all filters
            searchQuery = '';
            categoryFilter = '';
            minPrice = '';
            maxPrice = '';
            inStockOnly = true;
            sortBy = 'newest';
            currentPage = 1;

            // Reset form elements
            document.getElementById('search-input').value = '';
            document.getElementById('min-price').value = '';
            document.getElementById('max-price').value = '';
            document.getElementById('in-stock-filter').checked = true;
            document.getElementById('sort-select').value = 'newest';
            document.getElementById('category-all').checked = true;

            // Fetch products with reset filters
            fetchProducts();
        });
    }

    // Fetch categories for filter
    fetchCategories();

    // Fetch products
    fetchProducts();
}

// Load products when the page is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the homepage and if the featured products are not already loaded by PHP
    const featuredProductsContainer = document.getElementById('featured-products');
    if (featuredProductsContainer && featuredProductsContainer.getAttribute('data-php-loaded') !== 'true') {
        fetchFeaturedProducts();
    }

    // Check if we're on the products page
    if (document.getElementById('products-container')) {
        initProductsPage();
    }
});
