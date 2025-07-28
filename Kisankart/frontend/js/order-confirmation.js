// Order Confirmation JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Get order ID from URL
const urlParams = new URLSearchParams(window.location.search);
const orderId = urlParams.get('id');

// Function to initialize order confirmation page
async function initOrderConfirmation() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=order-confirmation.html?id=' + orderId;
            return;
        }

        // Check if order ID is provided
        if (!orderId) {
            displayError('Order ID is missing');
            return;
        }

        // Fetch order details
        const response = await fetch(`${API_BASE_URL}/orders/${orderId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const orderData = await response.json();
        
        // Display order confirmation
        displayOrderConfirmation(orderData);
        
        // Update page title
        document.title = `Order #${orderData.id} Confirmation - Kisan Kart`;
        
        // Update cart count (should be 0 after successful order)
        updateCartCount();
    } catch (error) {
        console.error('Error fetching order details:', error);
        displayError('Failed to load order details. Please try again later.');
    }
}

// Function to display order confirmation
function displayOrderConfirmation(order) {
    const container = document.getElementById('confirmation-container');
    
    // Format dates
    const orderDate = new Date(order.createdAt);
    const formattedOrderDate = orderDate.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Format total
    const formattedTotal = new Intl.NumberFormat('en-IN', { 
        style: 'currency', 
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(order.total);
    
    // Create HTML for order items
    let orderItemsHTML = '';
    order.OrderItems.forEach(item => {
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
        }).format(item.price * item.quantity);
        
        // Get product image
        const productImage = item.Product.images && item.Product.images.length > 0 
            ? item.Product.images[0] 
            : 'https://via.placeholder.com/80x80?text=No+Image';
        
        orderItemsHTML += `
            <div class="d-flex mb-3">
                <div class="flex-shrink-0">
                    <img src="${productImage}" alt="${item.Product.name}" class="img-thumbnail" width="80" height="80"
                        onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                </div>
                <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0">${item.Product.name}</h6>
                    <p class="text-muted small mb-0">
                        ${formattedPrice} x ${item.quantity} = ${formattedSubtotal}
                    </p>
                </div>
            </div>
        `;
    });
    
    // Create HTML for shipping address
    const address = order.Address;
    const addressHTML = `
        <p class="mb-1">${address.name}</p>
        <p class="mb-1">${address.street}</p>
        <p class="mb-1">${address.city}, ${address.state} ${address.postalCode}</p>
        <p class="mb-0">Phone: ${address.phone}</p>
    `;
    
    // Create HTML for payment info
    const payment = order.Payment;
    let paymentHTML = '';
    
    if (payment) {
        paymentHTML = `
            <p class="mb-0"><strong>Method:</strong> ${payment.method === 'cod' ? 'Cash on Delivery' : 'Online Payment'}</p>
        `;
    } else {
        paymentHTML = `
            <p class="text-muted">Payment information not available</p>
        `;
    }
    
    // Create main HTML
    const html = `
        <div class="text-center mb-5">
            <i class="bi bi-check-circle-fill text-success display-1 mb-3"></i>
            <h1 class="display-5 fw-bold">Thank You for Your Order!</h1>
            <p class="lead">Your order has been placed successfully.</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Order #${order.id}</h4>
                            <span class="badge bg-warning text-dark fs-6">Pending</span>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6>Order Date</h6>
                                <p class="mb-0">${formattedOrderDate}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Total Amount</h6>
                                <p class="mb-0 fs-5 fw-bold text-success">${formattedTotal}</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <h5 class="mb-3">Shipping Address</h5>
                                ${addressHTML}
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Payment Information</h5>
                                ${paymentHTML}
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Order Items</h5>
                        ${orderItemsHTML}
                    </div>
                </div>
                
                <div class="text-center mb-4">
                    <p>We've sent a confirmation email with all the details to your registered email address.</p>
                    <p class="mb-0">If you have any questions, please <a href="customer-service.html?order=${order.id}" class="text-decoration-none">contact our support team</a>.</p>
                </div>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="order-details.html?id=${order.id}" class="btn btn-success">
                        <i class="bi bi-eye"></i> View Order Details
                    </a>
                    <a href="products.html" class="btn btn-outline-success">
                        <i class="bi bi-cart"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

// Function to display error
function displayError(message) {
    const container = document.getElementById('confirmation-container');
    
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
            <h4>Oops! Something went wrong</h4>
            <p class="text-muted">${message}</p>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="orders.html" class="btn btn-success">
                    <i class="bi bi-box"></i> View My Orders
                </a>
                <a href="index.html" class="btn btn-outline-success">
                    <i class="bi bi-house"></i> Go to Homepage
                </a>
            </div>
        </div>
    `;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initOrderConfirmation();
    updateNavigation();
});
