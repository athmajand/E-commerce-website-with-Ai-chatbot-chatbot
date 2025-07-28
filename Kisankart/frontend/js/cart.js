// Cart JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to fetch cart items
async function fetchCart() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            displayLoginMessage();
            return;
        }

        const response = await fetch(`${API_BASE_URL}/cart`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Check if cart is empty
        if (!data.items || data.items.length === 0) {
            displayEmptyCartMessage();
            return;
        }
        
        // Display cart items
        displayCart(data);
    } catch (error) {
        console.error('Error fetching cart:', error);
        displayErrorMessage();
    }
}

// Function to display cart
function displayCart(cartData) {
    const cartContainer = document.getElementById('cart-container');
    
    // Format total price
    const formattedTotal = new Intl.NumberFormat('en-IN', { 
        style: 'currency', 
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(cartData.total);
    
    // Create cart HTML
    let cartHTML = `
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Cart Items (${cartData.itemCount})</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" width="100">Product</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Subtotal</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;
    
    // Add cart items
    cartData.items.forEach(item => {
        // Format prices
        const formattedPrice = new Intl.NumberFormat('en-IN', { 
            style: 'currency', 
            currency: 'INR',
            maximumFractionDigits: 2
        }).format(item.price);
        
        const formattedSubtotal = new Intl.NumberFormat('en-IN', { 
            style: 'currency', 
            currency: 'INR',
            maximumFractionDigits: 2
        }).format(item.total);
        
        // Get product image
        const productImage = item.image 
            ? item.image 
            : 'https://via.placeholder.com/80x80?text=No+Image';
        
        cartHTML += `
            <tr>
                <td>
                    <a href="product_details.php?id=${item.product_id}">
                        <img src="${productImage}" alt="${item.name}" class="img-thumbnail" width="80" height="80"
                            onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                    </a>
                </td>
                <td>
                    <a href="product_details.php?id=${item.product_id}" class="text-decoration-none text-dark">
                        ${item.name}
                    </a>
                    <div class="text-muted small">
                        Seller: ${item.seller_name || 'Unknown Seller'}
                    </div>
                </td>
                <td>${formattedPrice}</td>
                <td>
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <button class="btn btn-outline-secondary decrease-quantity" type="button" data-item-id="${item.id}">-</button>
                        <input type="number" class="form-control text-center item-quantity" value="${item.quantity}" min="1" data-item-id="${item.id}">
                        <button class="btn btn-outline-secondary increase-quantity" type="button" data-item-id="${item.id}">+</button>
                    </div>
                </td>
                <td>${formattedSubtotal}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger remove-item" data-item-id="${item.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    cartHTML += `
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <a href="products.php" class="btn btn-outline-success">
                                <i class="bi bi-arrow-left"></i> Continue Shopping
                            </a>
                            <button class="btn btn-outline-danger" id="clear-cart-btn">
                                <i class="bi bi-trash"></i> Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>${formattedTotal}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong class="text-success">${formattedTotal}</strong>
                        </div>
                        <button class="btn btn-success w-100 py-2" id="checkout-btn">
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    cartContainer.innerHTML = cartHTML;
    
    // Add event listeners
    addCartEventListeners();
}

// Function to add event listeners to cart elements
function addCartEventListeners() {
    // Decrease quantity buttons
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', (e) => {
            const itemId = e.target.dataset.itemId;
            const quantityInput = document.querySelector(`.item-quantity[data-item-id="${itemId}"]`);
            let quantity = parseInt(quantityInput.value);
            
            if (quantity > 1) {
                quantity--;
                quantityInput.value = quantity;
                updateCartItem(itemId, quantity);
            }
        });
    });
    
    // Increase quantity buttons
    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', (e) => {
            const itemId = e.target.dataset.itemId;
            const quantityInput = document.querySelector(`.item-quantity[data-item-id="${itemId}"]`);
            let quantity = parseInt(quantityInput.value);
            
            quantity++;
            quantityInput.value = quantity;
            updateCartItem(itemId, quantity);
        });
    });
    
    // Quantity input fields
    document.querySelectorAll('.item-quantity').forEach(input => {
        input.addEventListener('change', (e) => {
            const itemId = e.target.dataset.itemId;
            let quantity = parseInt(e.target.value);
            
            // Validate quantity
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
                e.target.value = quantity;
            }
            
            updateCartItem(itemId, quantity);
        });
    });
    
    // Remove item buttons
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', (e) => {
            const itemId = e.target.closest('button').dataset.itemId;
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                removeCartItem(itemId);
            }
        });
    });
    
    // Clear cart button
    const clearCartBtn = document.getElementById('clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to clear your entire cart?')) {
                clearCart();
            }
        });
    }
    
    // Checkout button
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            window.location.href = 'checkout.php';
        });
    }
}

// Function to update cart item quantity
async function updateCartItem(itemId, quantity) {
    try {
        const response = await fetch(`${API_BASE_URL}/cart`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({
                item_id: itemId,
                quantity: quantity
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to update cart item');
        }
        
        // Refresh cart display
        fetchCart();
        
        // Update cart count in navigation
        updateCartCount();
        
        showAlert('success', 'Cart updated successfully');
    } catch (error) {
        console.error('Error updating cart item:', error);
        showAlert('error', error.message);
        
        // Refresh cart to show correct state
        fetchCart();
    }
}

// Function to remove cart item
async function removeCartItem(itemId) {
    try {
        const response = await fetch(`${API_BASE_URL}/cart?id=${itemId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to remove cart item');
        }
        
        // Refresh cart display
        fetchCart();
        
        // Update cart count in navigation
        updateCartCount();
        
        showAlert('success', 'Item removed from cart');
    } catch (error) {
        console.error('Error removing cart item:', error);
        showAlert('error', error.message);
    }
}

// Function to clear cart
async function clearCart() {
    try {
        // Remove all items one by one
        const cartContainer = document.getElementById('cart-container');
        const removeButtons = cartContainer.querySelectorAll('.remove-item');
        
        for (const button of removeButtons) {
            const itemId = button.dataset.itemId;
            await removeCartItem(itemId);
        }
        
        showAlert('success', 'Cart cleared successfully');
    } catch (error) {
        console.error('Error clearing cart:', error);
        showAlert('error', 'Failed to clear cart');
    }
}

// Function to display empty cart message
function displayEmptyCartMessage() {
    const cartContainer = document.getElementById('cart-container');
    
    cartContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-cart-x display-1 text-muted"></i>
            </div>
            <h3 class="text-muted mb-3">Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
            <a href="products.php" class="btn btn-success btn-lg">
                <i class="bi bi-shop"></i> Start Shopping
            </a>
        </div>
    `;
}

// Function to display login message
function displayLoginMessage() {
    const cartContainer = document.getElementById('cart-container');
    
    cartContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-person-x display-1 text-muted"></i>
            </div>
            <h3 class="text-muted mb-3">Please login to view your cart</h3>
            <p class="text-muted mb-4">You need to be logged in to access your shopping cart.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="../login.php" class="btn btn-success btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
                <a href="../customer_registration.php" class="btn btn-outline-success btn-lg">
                    <i class="bi bi-person-plus"></i> Register
                </a>
            </div>
        </div>
    `;
}

// Function to display error message
function displayErrorMessage() {
    const cartContainer = document.getElementById('cart-container');
    
    cartContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
            </div>
            <h3 class="text-warning mb-3">Something went wrong</h3>
            <p class="text-muted mb-4">We couldn't load your cart. Please try again later.</p>
            <button class="btn btn-success btn-lg" onclick="fetchCart()">
                <i class="bi bi-arrow-clockwise"></i> Try Again
            </button>
        </div>
    `;
}

// Function to show alert
function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Initialize cart when page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchCart();
});
