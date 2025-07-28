// Customer Service Detail JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to initialize customer service detail page
async function initCustomerServiceDetail() {
    try {
        if (!isLoggedIn()) {
            displayLoginMessage();
            return;
        }
        
        // Get request ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const requestId = urlParams.get('id');
        
        if (!requestId) {
            displayErrorMessage('Request ID is missing');
            return;
        }
        
        // Show loading spinner
        const container = document.getElementById('request-detail-container');
        if (!container) return;
        
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Fetch request details
        const response = await fetch(`${API_BASE_URL}/customer-service/${requestId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const request = await response.json();
        
        // Display request details
        displayRequestDetails(request);
        
        // Add event listeners
        setupEventListeners(request);
    } catch (error) {
        console.error('Initialize customer service detail error:', error);
        displayErrorMessage('Failed to load request details');
    }
}

// Function to display request details
function displayRequestDetails(request) {
    const container = document.getElementById('request-detail-container');
    if (!container) return;
    
    // Get status badge class
    let statusBadgeClass = 'bg-secondary';
    switch (request.status) {
        case 'open':
            statusBadgeClass = 'bg-danger';
            break;
        case 'in-progress':
            statusBadgeClass = 'bg-warning text-dark';
            break;
        case 'resolved':
            statusBadgeClass = 'bg-success';
            break;
        case 'closed':
            statusBadgeClass = 'bg-secondary';
            break;
    }
    
    // Get priority badge class
    let priorityBadgeClass = 'bg-secondary';
    switch (request.priority) {
        case 'low':
            priorityBadgeClass = 'bg-info text-dark';
            break;
        case 'medium':
            priorityBadgeClass = 'bg-primary';
            break;
        case 'high':
            priorityBadgeClass = 'bg-warning text-dark';
            break;
        case 'urgent':
            priorityBadgeClass = 'bg-danger';
            break;
    }
    
    // Format dates
    const createdDate = new Date(request.createdAt);
    const formattedCreatedDate = formatDateTime(createdDate);
    const updatedDate = new Date(request.updatedAt);
    const formattedUpdatedDate = formatDateTime(updatedDate);
    
    // Create HTML
    let html = `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">${request.subject}</h4>
                    <a href="customer-service.html" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Requests
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="badge ${statusBadgeClass}">${capitalizeFirstLetter(request.status)}</span></p>
                        <p><strong>Priority:</strong> <span class="badge ${priorityBadgeClass}">${capitalizeFirstLetter(request.priority)}</span></p>
                        <p><strong>Type:</strong> <span class="badge bg-dark">${capitalizeFirstLetter(request.type)}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Created:</strong> ${formattedCreatedDate}</p>
                        <p><strong>Last Updated:</strong> ${formattedUpdatedDate}</p>
                        <p><strong>Request ID:</strong> #${request.id}</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5>Description</h5>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-0">${request.description}</p>
                    </div>
                </div>
    `;
    
    // Add related order information if available
    if (request.Order) {
        const orderDate = new Date(request.Order.createdAt);
        const formattedOrderDate = formatDate(orderDate);
        
        html += `
            <div class="mb-4">
                <h5>Related Order</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Order #${request.Order.orderNumber}</h6>
                                <p class="mb-0 text-muted">Placed on ${formattedOrderDate}</p>
                            </div>
                            <a href="order-detail.html?id=${request.Order.id}" class="btn btn-outline-success btn-sm">
                                View Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Add related product information if available
    if (request.Product) {
        html += `
            <div class="mb-4">
                <h5>Related Product</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <img src="${request.Product.images && request.Product.images.length > 0 ? request.Product.images[0] : 'https://via.placeholder.com/80x80?text=No+Image'}" 
                                    class="img-thumbnail" width="80" height="80" alt="${request.Product.name}">
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-1">${request.Product.name}</h6>
                                    <a href="product-detail.html?id=${request.Product.id}" class="btn btn-outline-success btn-sm">
                                        View Product
                                    </a>
                                </div>
                                <p class="mb-0 text-muted">${request.Product.description.substring(0, 100)}${request.Product.description.length > 100 ? '...' : ''}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Add resolution if available
    if (request.resolution) {
        html += `
            <div class="mb-4">
                <h5>Resolution</h5>
                <div class="p-3 bg-light rounded">
                    <p class="mb-0">${request.resolution}</p>
                </div>
            </div>
        `;
    }
    
    // Add update form if request is not resolved or closed
    if (!['resolved', 'closed'].includes(request.status)) {
        html += `
            <div class="mt-4">
                <h5>Update Request</h5>
                <form id="update-request-form">
                    <div class="mb-3">
                        <label for="update-description" class="form-label">Additional Information</label>
                        <textarea class="form-control" id="update-description" name="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Update Request</button>
                </form>
            </div>
        `;
    }
    
    html += `</div></div>`;
    
    container.innerHTML = html;
}

// Function to setup event listeners
function setupEventListeners(request) {
    // Update request form submission
    const form = document.getElementById('update-request-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Get form data
            const description = document.getElementById('update-description').value;
            
            try {
                // Show loading state
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                
                // Submit update
                const response = await fetch(`${API_BASE_URL}/customer-service/${request.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                    },
                    body: JSON.stringify({ description })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                // Show success message
                showToast('Request updated successfully', 'success');
                
                // Reset form
                form.reset();
                
                // Reload page to show updated request
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } catch (error) {
                console.error('Update request error:', error);
                showToast('Failed to update request', 'danger');
                
                // Reset button
                submitButton.disabled = false;
                submitButton.textContent = 'Update Request';
            }
        });
    }
}

// Function to display login message
function displayLoginMessage() {
    const container = document.getElementById('request-detail-container');
    if (!container) return;
    
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-person-lock fs-1 text-muted mb-3"></i>
            <h4>Please login to view request details</h4>
            <p class="text-muted">You need to be logged in to access customer service request details.</p>
            <a href="login.html?redirect=${encodeURIComponent(window.location.href)}" class="btn btn-success mt-3">
                Login Now
            </a>
        </div>
    `;
}

// Function to display error message
function displayErrorMessage(message) {
    const container = document.getElementById('request-detail-container');
    if (!container) return;
    
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> ${message}
        </div>
        <div class="text-center mt-3">
            <a href="customer-service.html" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Requests
            </a>
        </div>
    `;
}

// Helper function to format date and time
function formatDateTime(date) {
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Helper function to format date
function formatDate(date) {
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Helper function to capitalize first letter
function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Helper function to show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        // Create toast container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '5';
        document.body.appendChild(container);
    }
    
    const toastId = `toast-${Date.now()}`;
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.id = toastId;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    document.getElementById('toast-container').appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return !!localStorage.getItem('jwt_token');
}

// Initialize customer service detail when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initCustomerServiceDetail();
});
