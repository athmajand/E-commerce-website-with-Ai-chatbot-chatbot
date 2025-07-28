// Fixed Products JavaScript file for Kisan Kart

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

// Flag to prevent multiple simultaneous API calls
let isLoadingFeaturedProducts = false;
let isLoadingProducts = false;

// Function to fetch featured products for homepage
async function fetchFeaturedProducts() {
    // Prevent multiple simultaneous calls
    if (isLoadingFeaturedProducts) return;
    
    // Check if products are already loaded by PHP
    const productsContainer = document.getElementById('featured-products');
    
    // If the container doesn't exist or has the data-php-loaded attribute, don't fetch products
    if (!productsContainer || productsContainer.getAttribute('data-php-loaded') === 'true') {
        console.log('Featured products already loaded by PHP or container not found');
        return;
    }
    
    try {
        isLoadingFeaturedProducts = true;
        
        // Show loading indicator
        productsContainer.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        const response = await fetch(`${API_BASE_URL}/products/featured`);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Check if products were found
        if (!data || data.length === 0) {
            productsContainer.innerHTML = `
                <div class="col-12 text-center py-4">
                    <p class="text-muted">No featured products available at the moment.</p>
                </div>
            `;
            return;
        }

        // Clear container before adding products
        productsContainer.innerHTML = '';
        
        // Limit to 6 products for featured section
        const featuredProducts = data.slice(0, 6);
        
        // Create product cards
        featuredProducts.forEach(product => {
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
            
            // Create product card HTML
            const productCardHTML = `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="product-details.html?id=${product.id}" class="text-decoration-none">
                            ${discountBadge}
                            <img src="${productImage}" class="card-img-top product-image" alt="${product.name}" 
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
                                    </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="${product.id}">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="${product.id}">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Append product card to container
            productsContainer.insertAdjacentHTML('beforeend', productCardHTML);
        });
        
        // Add event listeners to buttons
        addEventListenersToProductButtons();
        
    } catch (error) {
        console.error('Error fetching featured products:', error);
        productsContainer.innerHTML = `
            <div class="col-12 text-center py-4">
                <p class="text-danger">Failed to load featured products. Please try again later.</p>
                <button class="btn btn-outline-success mt-2" id="retry-featured-btn">Retry</button>
            </div>
        `;
        
        // Add event listener to retry button
        const retryButton = document.getElementById('retry-featured-btn');
        if (retryButton) {
            retryButton.addEventListener('click', fetchFeaturedProducts);
        }
    } finally {
        isLoadingFeaturedProducts = false;
    }
}

// Function to add event listeners to product buttons
function addEventListenersToProductButtons() {
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            addToCart(productId);
        });
    });
    
    // Add to wishlist buttons
    document.querySelectorAll('.add-to-wishlist-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            addToWishlist(productId);
        });
    });
}

// Function to add product to cart
async function addToCart(productId) {
    try {
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=index.php';
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
            window.location.href = 'login.html?redirect=index.php';
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

// Helper function to check if user is logged in
function isLoggedIn() {
    return localStorage.getItem('jwt_token') !== null;
}

// Helper function to update cart count
function updateCartCount() {
    // This function should be defined in main.js
    if (typeof window.updateCartCount === 'function') {
        window.updateCartCount();
    }
}

// Initialize when DOM is loaded - with debounce to prevent multiple calls
let domReadyFired = false;
document.addEventListener('DOMContentLoaded', () => {
    if (domReadyFired) return;
    domReadyFired = true;
    
    console.log('DOM loaded - initializing products.js');
    
    // Check if we're on the homepage and if the featured products section exists
    const featuredProductsContainer = document.getElementById('featured-products');
    if (featuredProductsContainer) {
        console.log('Featured products container found');
        
        // Only fetch if not already loaded by PHP
        if (featuredProductsContainer.getAttribute('data-php-loaded') !== 'true') {
            console.log('Featured products not loaded by PHP, fetching via API');
            setTimeout(fetchFeaturedProducts, 100); // Small delay to ensure DOM is fully ready
        } else {
            console.log('Featured products already loaded by PHP, skipping API call');
        }
    }
});
