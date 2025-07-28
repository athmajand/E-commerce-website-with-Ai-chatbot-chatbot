// Orders JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to initialize orders page
async function initOrders() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=orders.html';
            return;
        }

        // Fetch user profile and orders in parallel
        const [profileResponse, ordersResponse] = await Promise.all([
            fetch(`${API_BASE_URL}/users/profile`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            }),
            fetch(`${API_BASE_URL}/orders`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            })
        ]);
        
        if (!profileResponse.ok || !ordersResponse.ok) {
            throw new Error('Failed to fetch data');
        }
        
        const profileData = await profileResponse.json();
        const ordersData = await ordersResponse.json();
        
        // Update sidebar user info
        updateSidebarUserInfo(profileData);
        
        // Display orders
        displayOrders(ordersData);
    } catch (error) {
        console.error('Orders initialization error:', error);
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

// Function to display orders
function displayOrders(orders) {
    const ordersContainer = document.getElementById('orders-container');
    
    if (!orders || orders.length === 0) {
        ordersContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-box fs-1 text-muted mb-3"></i>
                <h5>No Orders Yet</h5>
                <p class="text-muted">You haven't placed any orders yet.</p>
                <a href="products.html" class="btn btn-success mt-2">
                    <i class="bi bi-cart"></i> Start Shopping
                </a>
            </div>
        `;
        return;
    }
    
    // Sort orders by date (newest first)
    orders.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    
    let ordersHTML = `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Order ID</th>
                        <th scope="col">Date</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    orders.forEach(order => {
        // Format date
        const orderDate = new Date(order.createdAt);
        const formattedDate = orderDate.toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
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
        
        ordersHTML += `
            <tr>
                <td>#${order.id}</td>
                <td>${formattedDate}</td>
                <td>${formattedTotal}</td>
                <td>
                    <span class="badge ${statusBadgeClass}">
                        ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                    </span>
                </td>
                <td>
                    <a href="order-details.html?id=${order.id}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                    ${order.status === 'pending' ? `
                        <button class="btn btn-sm btn-outline-danger ms-2 cancel-order-btn" data-order-id="${order.id}">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });
    
    ordersHTML += `
                </tbody>
            </table>
        </div>
    `;
    
    ordersContainer.innerHTML = ordersHTML;
    
    // Add event listeners to cancel buttons
    document.querySelectorAll('.cancel-order-btn').forEach(button => {
        button.addEventListener('click', () => {
            const orderId = button.dataset.orderId;
            cancelOrder(orderId);
        });
    });
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
        
        // Refresh orders
        const ordersResponse = await fetch(`${API_BASE_URL}/orders`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!ordersResponse.ok) {
            throw new Error('Failed to fetch orders');
        }
        
        const ordersData = await ordersResponse.json();
        
        // Display orders
        displayOrders(ordersData);
    } catch (error) {
        console.error('Cancel order error:', error);
        showAlert('danger', 'Failed to cancel order. Please try again.');
    }
}

// Function to display error message
function displayErrorMessage() {
    const ordersContainer = document.getElementById('orders-container');
    
    ordersContainer.innerHTML = `
        <div class="text-center py-4">
            <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
            <h5>Oops! Something went wrong</h5>
            <p class="text-muted">We couldn't load your orders. Please try again later.</p>
            <button class="btn btn-outline-success mt-2" onclick="initOrders()">
                <i class="bi bi-arrow-clockwise"></i> Try Again
            </button>
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
    initOrders();
    updateNavigation();
});
