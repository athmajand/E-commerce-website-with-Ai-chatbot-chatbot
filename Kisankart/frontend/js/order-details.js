// Order Details JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Get order ID from URL
const urlParams = new URLSearchParams(window.location.search);
const orderId = urlParams.get('id');

// Function to initialize order details page
async function initOrderDetails() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=order-details.html?id=' + orderId;
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
        
        // Display order details
        displayOrderDetails(orderData);
        
        // Update page title
        document.title = `Order #${orderData.id} - Kisan Kart`;
    } catch (error) {
        console.error('Error fetching order details:', error);
        displayError('Failed to load order details. Please try again later.');
    }
}

// Function to display order details
function displayOrderDetails(order) {
    const container = document.getElementById('order-details-container');
    
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
    
    // Get status badge class
    let statusBadgeClass = 'bg-secondary';
    switch (order.status) {
        case 'pending':
            statusBadgeClass = 'bg-warning text-dark';
            break;
        case 'processing':
            statusBadgeClass = 'bg-info text-dark';
            break;
        case 'shipped':
            statusBadgeClass = 'bg-primary';
            break;
        case 'delivered':
            statusBadgeClass = 'bg-success';
            break;
        case 'cancelled':
            statusBadgeClass = 'bg-danger';
            break;
    }
    
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
        
        // Get seller name
        const sellerName = item.Product.Seller && item.Product.Seller.User 
            ? `${item.Product.Seller.User.firstName} ${item.Product.Seller.User.lastName}`
            : 'Unknown Seller';
        
        // Get item status badge
        let itemStatusBadgeClass = 'bg-secondary';
        switch (item.status) {
            case 'pending':
                itemStatusBadgeClass = 'bg-warning text-dark';
                break;
            case 'processing':
                itemStatusBadgeClass = 'bg-info text-dark';
                break;
            case 'shipped':
                itemStatusBadgeClass = 'bg-primary';
                break;
            case 'delivered':
                itemStatusBadgeClass = 'bg-success';
                break;
            case 'cancelled':
                itemStatusBadgeClass = 'bg-danger';
                break;
        }
        
        orderItemsHTML += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 col-4 mb-3 mb-md-0">
                            <img src="${productImage}" alt="${item.Product.name}" class="img-fluid rounded"
                                onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                        </div>
                        <div class="col-md-4 col-8 mb-3 mb-md-0">
                            <h6 class="mb-1">
                                <a href="product-details.html?id=${item.Product.id}" class="text-decoration-none text-dark">
                                    ${item.Product.name}
                                </a>
                            </h6>
                            <p class="text-muted small mb-0">Seller: ${sellerName}</p>
                        </div>
                        <div class="col-md-2 col-4">
                            <p class="mb-0">${formattedPrice}</p>
                            <p class="text-muted small mb-0">per unit</p>
                        </div>
                        <div class="col-md-1 col-4">
                            <p class="mb-0">${item.quantity}</p>
                            <p class="text-muted small mb-0">units</p>
                        </div>
                        <div class="col-md-2 col-4">
                            <p class="mb-0 fw-bold">${formattedSubtotal}</p>
                        </div>
                        <div class="col-md-1 col-12 mt-3 mt-md-0 text-md-end">
                            <span class="badge ${itemStatusBadgeClass}">
                                ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                            </span>
                        </div>
                    </div>
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
        const paymentDate = new Date(payment.createdAt);
        const formattedPaymentDate = paymentDate.toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        let paymentStatusBadgeClass = 'bg-secondary';
        switch (payment.status) {
            case 'pending':
                paymentStatusBadgeClass = 'bg-warning text-dark';
                break;
            case 'completed':
                paymentStatusBadgeClass = 'bg-success';
                break;
            case 'failed':
                paymentStatusBadgeClass = 'bg-danger';
                break;
        }
        
        paymentHTML = `
            <p class="mb-1"><strong>Method:</strong> ${payment.method === 'cod' ? 'Cash on Delivery' : 'Online Payment'}</p>
            <p class="mb-1"><strong>Status:</strong> <span class="badge ${paymentStatusBadgeClass}">${payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}</span></p>
            <p class="mb-0"><strong>Date:</strong> ${formattedPaymentDate}</p>
        `;
    } else {
        paymentHTML = `
            <p class="text-muted">Payment information not available</p>
        `;
    }
    
    // Create main HTML
    const html = `
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Order #${order.id}</h4>
                            <span class="badge ${statusBadgeClass} fs-6">
                                ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                            </span>
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
                        
                        <h5 class="mb-3">Order Items</h5>
                        ${orderItemsHTML}
                        
                        ${order.status === 'pending' ? `
                            <div class="mt-4">
                                <button class="btn btn-danger" id="cancel-order-btn">
                                    <i class="bi bi-x-circle"></i> Cancel Order
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Order Timeline</h5>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Order Placed</h6>
                                    <p class="text-muted small mb-0">${formattedOrderDate}</p>
                                </div>
                            </div>
                            
                            ${order.status !== 'pending' && order.status !== 'cancelled' ? `
                                <div class="timeline-item">
                                    <div class="timeline-marker ${order.status === 'processing' || order.status === 'shipped' || order.status === 'delivered' ? 'bg-success' : 'bg-secondary'}"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-0">Processing</h6>
                                        <p class="text-muted small mb-0">Your order is being processed</p>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${order.status === 'shipped' || order.status === 'delivered' ? `
                                <div class="timeline-item">
                                    <div class="timeline-marker ${order.status === 'shipped' || order.status === 'delivered' ? 'bg-success' : 'bg-secondary'}"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-0">Shipped</h6>
                                        <p class="text-muted small mb-0">Your order has been shipped</p>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${order.status === 'delivered' ? `
                                <div class="timeline-item">
                                    <div class="timeline-marker ${order.status === 'delivered' ? 'bg-success' : 'bg-secondary'}"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-0">Delivered</h6>
                                        <p class="text-muted small mb-0">Your order has been delivered</p>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${order.status === 'cancelled' ? `
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-danger"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-0">Cancelled</h6>
                                        <p class="text-muted small mb-0">Your order has been cancelled</p>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Shipping Address</h5>
                        ${addressHTML}
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Payment Information</h5>
                        ${paymentHTML}
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Need Help?</h5>
                        <p>If you have any questions or issues with your order, please contact our customer support.</p>
                        <a href="customer-service.html?order=${order.id}" class="btn btn-outline-success w-100">
                            <i class="bi bi-headset"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Add event listener to cancel button
    const cancelBtn = document.getElementById('cancel-order-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => cancelOrder(order.id));
    }
    
    // Add CSS for timeline
    addTimelineStyles();
}

// Function to add timeline styles
function addTimelineStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-marker {
            position: absolute;
            left: -30px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }
        .timeline-content {
            padding-left: 10px;
        }
    `;
    document.head.appendChild(style);
}

// Function to cancel order
async function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/orders/${orderId}/cancel`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to cancel order');
        }
        
        // Show success message
        showAlert('success', 'Order cancelled successfully');
        
        // Refresh order details
        initOrderDetails();
    } catch (error) {
        console.error('Cancel order error:', error);
        showAlert('danger', 'Failed to cancel order. Please try again.');
    }
}

// Function to display error
function displayError(message) {
    const container = document.getElementById('order-details-container');
    
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
            <h4>Oops! Something went wrong</h4>
            <p class="text-muted">${message}</p>
            <a href="orders.html" class="btn btn-success mt-3">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
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
    initOrderDetails();
    updateNavigation();
});
