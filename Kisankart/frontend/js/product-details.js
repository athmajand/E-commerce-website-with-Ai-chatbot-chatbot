// Product Details JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Get product ID from URL
const urlParams = new URLSearchParams(window.location.search);
const productId = urlParams.get('id');

// Function to fetch product details
async function fetchProductDetails() {
    try {
        if (!productId) {
            displayError('Product ID is missing');
            return;
        }

        const response = await fetch(`${API_BASE_URL}/products/${productId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const product = await response.json();
        
        // Display product details
        displayProductDetails(product);
        
        // Fetch related products
        fetchRelatedProducts(product.categoryId);
        
        // Fetch reviews
        fetchProductReviews(productId);
        
        // Check if user can review
        checkUserCanReview(productId);
        
        // Update page title
        document.title = `${product.name} - Kisan Kart`;
    } catch (error) {
        console.error('Error fetching product details:', error);
        displayError('Failed to load product details. Please try again later.');
    }
}

// Function to display product details
function displayProductDetails(product) {
    const container = document.getElementById('product-details-container');
    
    // Calculate discount percentage if applicable
    let discountPercentage = '';
    if (product.discountPrice && product.price > product.discountPrice) {
        const discount = ((product.price - product.discountPrice) / product.price) * 100;
        discountPercentage = `<span class="badge bg-danger ms-2">-${Math.round(discount)}%</span>`;
    }
    
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
    
    // Prepare image gallery
    let imageGallery = '';
    if (product.images && product.images.length > 0) {
        const mainImage = product.images[0];
        const thumbnails = product.images.map((img, index) => `
            <div class="col-3 mb-2">
                <img src="${img}" class="img-thumbnail product-thumbnail ${index === 0 ? 'active' : ''}" 
                    alt="${product.name}" onclick="changeMainImage(this.src)">
            </div>
        `).join('');
        
        imageGallery = `
            <div class="mb-3">
                <img src="${mainImage}" class="img-fluid rounded main-product-image" id="main-product-image" alt="${product.name}">
            </div>
            <div class="row">
                ${thumbnails}
            </div>
        `;
    } else {
        imageGallery = `
            <div class="mb-3">
                <img src="https://via.placeholder.com/600x400?text=No+Image+Available" class="img-fluid rounded" alt="${product.name}">
            </div>
        `;
    }
    
    // Prepare stock status
    let stockStatus = '';
    if (product.stock > 10) {
        stockStatus = '<span class="text-success">In Stock</span>';
    } else if (product.stock > 0) {
        stockStatus = `<span class="text-warning">Only ${product.stock} left</span>`;
    } else {
        stockStatus = '<span class="text-danger">Out of Stock</span>';
    }
    
    // Prepare seller info
    const sellerName = product.Seller && product.Seller.User ? 
        `${product.Seller.User.firstName} ${product.Seller.User.lastName}` : 'Unknown Seller';
    
    // Prepare HTML
    const html = `
        <div class="col-md-6">
            ${imageGallery}
        </div>
        <div class="col-md-6">
            <h1 class="display-5 fw-bolder">${product.name}</h1>
            <div class="fs-5 mb-3">
                ${product.discountPrice ? 
                    `<span class="text-decoration-line-through text-muted">${formattedPrice}</span> 
                     <span class="text-success fw-bold">${formattedDiscountPrice}</span>${discountPercentage}` : 
                    `<span class="text-success fw-bold">${formattedPrice}</span>`}
            </div>
            <p class="lead">${product.description}</p>
            <div class="mb-3">
                <strong>Category:</strong> ${product.Category ? product.Category.name : 'Uncategorized'}
                ${product.Subcategory ? ` > ${product.Subcategory.name}` : ''}
            </div>
            <div class="mb-3">
                <strong>Seller:</strong> ${sellerName}
            </div>
            <div class="mb-3">
                <strong>Availability:</strong> ${stockStatus}
            </div>
            <div class="mb-3">
                <strong>Unit:</strong> ${product.unit}
            </div>
            <div class="d-flex">
                <div class="input-group me-3" style="width: 130px;">
                    <button class="btn btn-outline-secondary" type="button" id="decrease-quantity">-</button>
                    <input type="number" class="form-control text-center" id="product-quantity" value="1" min="1" max="${product.stock}">
                    <button class="btn btn-outline-secondary" type="button" id="increase-quantity">+</button>
                </div>
                <button class="btn btn-success flex-shrink-0 me-2" type="button" id="add-to-cart-btn" ${product.stock <= 0 ? 'disabled' : ''}>
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
                <button class="btn btn-outline-success flex-shrink-0" type="button" id="add-to-wishlist-btn">
                    <i class="bi bi-heart"></i>
                </button>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Add event listeners
    document.getElementById('decrease-quantity').addEventListener('click', decreaseQuantity);
    document.getElementById('increase-quantity').addEventListener('click', increaseQuantity);
    document.getElementById('add-to-cart-btn').addEventListener('click', () => addToCart(productId));
    document.getElementById('add-to-wishlist-btn').addEventListener('click', () => addToWishlist(productId));
    
    // Make thumbnails clickable
    window.changeMainImage = function(src) {
        document.getElementById('main-product-image').src = src;
        document.querySelectorAll('.product-thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
            if (thumb.src === src) {
                thumb.classList.add('active');
            }
        });
    };
}

// Function to decrease quantity
function decreaseQuantity() {
    const quantityInput = document.getElementById('product-quantity');
    let quantity = parseInt(quantityInput.value);
    if (quantity > 1) {
        quantityInput.value = quantity - 1;
    }
}

// Function to increase quantity
function increaseQuantity() {
    const quantityInput = document.getElementById('product-quantity');
    let quantity = parseInt(quantityInput.value);
    const max = parseInt(quantityInput.max);
    if (quantity < max) {
        quantityInput.value = quantity + 1;
    }
}

// Function to add product to cart
async function addToCart(productId) {
    try {
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=product-details.html?id=' + productId;
            return;
        }
        
        const quantity = parseInt(document.getElementById('product-quantity').value);
        
        const response = await fetch(`${API_BASE_URL}/cart/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({ productId, quantity })
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
            window.location.href = 'login.html?redirect=product-details.html?id=' + productId;
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
            document.getElementById('add-to-wishlist-btn').innerHTML = '<i class="bi bi-heart-fill"></i>';
        } else {
            showAlert('danger', data.message || 'Failed to add product to wishlist');
        }
    } catch (error) {
        console.error('Add to wishlist error:', error);
        showAlert('danger', 'Failed to add product to wishlist. Please try again.');
    }
}

// Function to display error message
function displayError(message) {
    const container = document.getElementById('product-details-container');
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> ${message}
            </div>
            <a href="index.html" class="btn btn-success mt-3">Back to Home</a>
        </div>
    `;
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

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    fetchProductDetails();
    updateNavigation();
});
