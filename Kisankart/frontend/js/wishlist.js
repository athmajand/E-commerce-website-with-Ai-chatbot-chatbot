// Wishlist JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to initialize wishlist page
async function initWishlist() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=wishlist.html';
            return;
        }

        // Fetch user profile and wishlist in parallel
        const [profileResponse, wishlistResponse] = await Promise.all([
            fetch(`${API_BASE_URL}/users/profile`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            }),
            fetch(`${API_BASE_URL}/wishlist`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            })
        ]);
        
        if (!profileResponse.ok || !wishlistResponse.ok) {
            throw new Error('Failed to fetch data');
        }
        
        const profileData = await profileResponse.json();
        const wishlistData = await wishlistResponse.json();
        
        // Update sidebar user info
        updateSidebarUserInfo(profileData);
        
        // Display wishlist items
        displayWishlist(wishlistData);
        
        // Add event listener to clear wishlist button
        document.getElementById('clear-wishlist-btn').addEventListener('click', clearWishlist);
    } catch (error) {
        console.error('Wishlist initialization error:', error);
        displayErrorMessage();
    }
}

// Function to update sidebar user info
function updateSidebarUserInfo(profileData) {
    document.getElementById('sidebar-user-name').textContent = `${profileData.firstName} ${profileData.lastName}`;
    document.getElementById('sidebar-user-email').textContent = profileData.email;
    
    // Update profile image if available
    if (profileData.profileImage) {
        document.getElementById('profile-image').src = profileData.profileImage;
    }
}

// Function to display wishlist
function displayWishlist(wishlist) {
    const wishlistContainer = document.getElementById('wishlist-container');
    
    if (!wishlist || !wishlist.items || wishlist.items.length === 0) {
        wishlistContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-heart fs-1 text-muted mb-3"></i>
                <h5>Your Wishlist is Empty</h5>
                <p class="text-muted">You haven't added any products to your wishlist yet.</p>
                <a href="products.html" class="btn btn-success mt-2">
                    <i class="bi bi-cart"></i> Browse Products
                </a>
            </div>
        `;
        
        // Hide clear wishlist button
        document.getElementById('clear-wishlist-btn').style.display = 'none';
        
        return;
    }
    
    // Show clear wishlist button
    document.getElementById('clear-wishlist-btn').style.display = 'block';
    
    // Create wishlist items HTML
    let wishlistHTML = `
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    `;
    
    wishlist.items.forEach(item => {
        const product = item.Product;
        
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
        
        // Get product image
        const productImage = product.images && product.images.length > 0 
            ? product.images[0] 
            : 'https://via.placeholder.com/300x200?text=No+Image';
        
        // Get seller name
        const sellerName = product.Seller && product.Seller.User 
            ? `${product.Seller.User.firstName} ${product.Seller.User.lastName}`
            : 'Unknown Seller';
        
        wishlistHTML += `
            <div class="col">
                <div class="card h-100 position-relative">
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
                                    <span class="text-muted small">/${product.unit}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                        <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="${product.id}">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                        <button class="btn btn-outline-danger remove-from-wishlist-btn" data-item-id="${item.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    wishlistHTML += `
        </div>
    `;
    
    wishlistContainer.innerHTML = wishlistHTML;
    
    // Add event listeners to buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = e.target.closest('.add-to-cart-btn').dataset.productId;
            addToCart(productId);
        });
    });
    
    document.querySelectorAll('.remove-from-wishlist-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const itemId = e.target.closest('.remove-from-wishlist-btn').dataset.itemId;
            removeFromWishlist(itemId);
        });
    });
}

// Function to add product to cart
async function addToCart(productId) {
    try {
        const response = await fetch(`${API_BASE_URL}/cart/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({ productId, quantity: 1 })
        });
        
        if (!response.ok) {
            throw new Error('Failed to add product to cart');
        }
        
        // Show success message
        showAlert('success', 'Product added to cart successfully');
        
        // Update cart count
        updateCartCount();
    } catch (error) {
        console.error('Add to cart error:', error);
        showAlert('danger', 'Failed to add product to cart. Please try again.');
    }
}

// Function to remove item from wishlist
async function removeFromWishlist(itemId) {
    try {
        const response = await fetch(`${API_BASE_URL}/wishlist/${itemId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to remove item from wishlist');
        }
        
        // Show success message
        showAlert('success', 'Product removed from wishlist');
        
        // Refresh wishlist
        const wishlistResponse = await fetch(`${API_BASE_URL}/wishlist`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!wishlistResponse.ok) {
            throw new Error('Failed to fetch wishlist');
        }
        
        const wishlistData = await wishlistResponse.json();
        
        // Display wishlist
        displayWishlist(wishlistData);
    } catch (error) {
        console.error('Remove from wishlist error:', error);
        showAlert('danger', 'Failed to remove product from wishlist. Please try again.');
    }
}

// Function to clear wishlist
async function clearWishlist() {
    if (!confirm('Are you sure you want to clear your wishlist?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/wishlist`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to clear wishlist');
        }
        
        // Show success message
        showAlert('success', 'Wishlist cleared successfully');
        
        // Display empty wishlist
        displayWishlist({ items: [] });
    } catch (error) {
        console.error('Clear wishlist error:', error);
        showAlert('danger', 'Failed to clear wishlist. Please try again.');
    }
}

// Function to display error message
function displayErrorMessage() {
    const wishlistContainer = document.getElementById('wishlist-container');
    
    wishlistContainer.innerHTML = `
        <div class="text-center py-4">
            <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
            <h5>Oops! Something went wrong</h5>
            <p class="text-muted">We couldn't load your wishlist. Please try again later.</p>
            <button class="btn btn-outline-success mt-2" onclick="initWishlist()">
                <i class="bi bi-arrow-clockwise"></i> Try Again
            </button>
        </div>
    `;
    
    // Hide clear wishlist button
    document.getElementById('clear-wishlist-btn').style.display = 'none';
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
    initWishlist();
    updateNavigation();
});
