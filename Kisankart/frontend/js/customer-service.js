// Customer Service JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to initialize customer service page
async function initCustomerService() {
    try {
        if (!isLoggedIn()) {
            displayLoginMessage();
            return;
        }
        
        // Show loading spinner
        const container = document.getElementById('customer-service-container');
        if (!container) return;
        
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Fetch user's customer service requests
        const response = await fetch(`${API_BASE_URL}/customer-service`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Display customer service requests
        displayCustomerServiceRequests(data.requests, data.totalPages, data.currentPage);
        
        // Initialize new request form
        initNewRequestForm();
        
        // Add event listeners
        setupCustomerServiceEventListeners();
    } catch (error) {
        console.error('Initialize customer service error:', error);
        displayErrorMessage('Failed to load customer service requests');
    }
}

// Function to display customer service requests
function displayCustomerServiceRequests(requests, totalPages, currentPage) {
    const container = document.getElementById('customer-service-container');
    if (!container) return;
    
    let html = `
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">New Request</h5>
                    </div>
                    <div class="card-body">
                        <form id="new-request-form">
                            <div class="mb-3">
                                <label for="request-type" class="form-label">Request Type</label>
                                <select class="form-select" id="request-type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="complaint">Complaint</option>
                                    <option value="inquiry">Inquiry</option>
                                    <option value="return">Return Request</option>
                                    <option value="refund">Refund Request</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="request-subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="request-subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="request-description" class="form-label">Description</label>
                                <textarea class="form-control" id="request-description" name="description" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="request-order" class="form-label">Related Order (Optional)</label>
                                <select class="form-select" id="request-order" name="orderId">
                                    <option value="">Select Order</option>
                                    <!-- Orders will be loaded dynamically -->
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Submit Request</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">My Requests</h5>
                    </div>
                    <div class="card-body p-0">
    `;
    
    if (requests.length === 0) {
        html += `
            <div class="text-center py-5">
                <i class="bi bi-chat-square-text fs-1 text-muted mb-3"></i>
                <h5>No Requests Yet</h5>
                <p class="text-muted">You haven't submitted any customer service requests yet.</p>
            </div>
        `;
    } else {
        html += `<div class="list-group list-group-flush">`;
        
        requests.forEach(request => {
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
            
            // Format date
            const date = new Date(request.createdAt);
            const formattedDate = formatDateTime(date);
            
            html += `
                <a href="customer-service-detail.html?id=${request.id}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-1">${request.subject}</h6>
                        <small class="text-muted">${formattedDate}</small>
                    </div>
                    <p class="mb-1 text-truncate">${request.description}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge ${statusBadgeClass}">${capitalizeFirstLetter(request.status)}</span>
                            <span class="badge ${priorityBadgeClass}">${capitalizeFirstLetter(request.priority)}</span>
                            <span class="badge bg-dark">${capitalizeFirstLetter(request.type)}</span>
                        </div>
                        ${request.Order ? `<small class="text-muted">Order #${request.Order.orderNumber}</small>` : ''}
                    </div>
                </a>
            `;
        });
        
        html += `</div>`;
        
        // Add pagination if needed
        if (totalPages > 1) {
            html += `
                <nav aria-label="Customer service requests pagination" class="mt-3 p-3">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
            `;
            
            for (let i = 1; i <= totalPages; i++) {
                html += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
            
            html += `
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            `;
        }
    }
    
    html += `
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

// Function to initialize new request form
async function initNewRequestForm() {
    try {
        // Load user's orders for the dropdown
        const response = await fetch(`${API_BASE_URL}/orders`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Populate order dropdown
        const orderSelect = document.getElementById('request-order');
        if (!orderSelect) return;
        
        // Add orders to dropdown
        data.orders.forEach(order => {
            const option = document.createElement('option');
            option.value = order.id;
            option.textContent = `Order #${order.orderNumber} (${formatDate(order.createdAt)})`;
            orderSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Initialize new request form error:', error);
    }
}

// Function to setup customer service event listeners
function setupCustomerServiceEventListeners() {
    // New request form submission
    const form = document.getElementById('new-request-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(form);
            const requestData = {
                type: formData.get('type'),
                subject: formData.get('subject'),
                description: formData.get('description'),
                orderId: formData.get('orderId') || null
            };
            
            try {
                // Show loading state
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
                
                // Submit request
                const response = await fetch(`${API_BASE_URL}/customer-service`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                    },
                    body: JSON.stringify(requestData)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                // Show success message
                showToast('Request submitted successfully', 'success');
                
                // Reset form
                form.reset();
                
                // Reload page to show new request
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } catch (error) {
                console.error('Submit request error:', error);
                showToast('Failed to submit request', 'danger');
                
                // Reset button
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Request';
            }
        });
    }
    
    // Pagination
    const container = document.getElementById('customer-service-container');
    if (container) {
        container.addEventListener('click', async (e) => {
            // Handle pagination click
            if (e.target.closest('.page-link')) {
                e.preventDefault();
                
                const link = e.target.closest('.page-link');
                const page = link.dataset.page;
                
                // Load requests for the selected page
                await loadCustomerServicePage(page);
            }
        });
    }
}

// Function to load customer service page
async function loadCustomerServicePage(page) {
    try {
        // Show loading spinner
        const container = document.getElementById('customer-service-container');
        if (!container) return;
        
        // Only update the requests list, not the form
        const listContainer = container.querySelector('.col-lg-8 .card-body');
        if (!listContainer) return;
        
        listContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Fetch requests for the selected page
        const response = await fetch(`${API_BASE_URL}/customer-service?page=${page}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update requests list
        if (data.requests.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-chat-square-text fs-1 text-muted mb-3"></i>
                    <h5>No Requests Yet</h5>
                    <p class="text-muted">You haven't submitted any customer service requests yet.</p>
                </div>
            `;
        } else {
            let html = `<div class="list-group list-group-flush">`;
            
            data.requests.forEach(request => {
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
                
                // Format date
                const date = new Date(request.createdAt);
                const formattedDate = formatDateTime(date);
                
                html += `
                    <a href="customer-service-detail.html?id=${request.id}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-1">${request.subject}</h6>
                            <small class="text-muted">${formattedDate}</small>
                        </div>
                        <p class="mb-1 text-truncate">${request.description}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge ${statusBadgeClass}">${capitalizeFirstLetter(request.status)}</span>
                                <span class="badge ${priorityBadgeClass}">${capitalizeFirstLetter(request.priority)}</span>
                                <span class="badge bg-dark">${capitalizeFirstLetter(request.type)}</span>
                            </div>
                            ${request.Order ? `<small class="text-muted">Order #${request.Order.orderNumber}</small>` : ''}
                        </div>
                    </a>
                `;
            });
            
            html += `</div>`;
            
            // Add pagination if needed
            if (data.totalPages > 1) {
                html += `
                    <nav aria-label="Customer service requests pagination" class="mt-3 p-3">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item ${data.currentPage === 1 ? 'disabled' : ''}">
                                <a class="page-link" href="#" data-page="${data.currentPage - 1}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                `;
                
                for (let i = 1; i <= data.totalPages; i++) {
                    html += `
                        <li class="page-item ${i === data.currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }
                
                html += `
                            <li class="page-item ${data.currentPage === data.totalPages ? 'disabled' : ''}">
                                <a class="page-link" href="#" data-page="${data.currentPage + 1}" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                `;
            }
            
            listContainer.innerHTML = html;
        }
    } catch (error) {
        console.error('Load customer service page error:', error);
        displayErrorMessage('Failed to load customer service requests');
    }
}

// Function to display login message
function displayLoginMessage() {
    const container = document.getElementById('customer-service-container');
    if (!container) return;
    
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-person-lock fs-1 text-muted mb-3"></i>
            <h4>Please login to access customer service</h4>
            <p class="text-muted">You need to be logged in to submit and view customer service requests.</p>
            <a href="login.html?redirect=customer-service.html" class="btn btn-success mt-3">
                Login Now
            </a>
        </div>
    `;
}

// Function to display error message
function displayErrorMessage(message) {
    const container = document.getElementById('customer-service-container');
    if (!container) return;
    
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> ${message}
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
function formatDate(dateString) {
    const date = new Date(dateString);
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

// Initialize customer service when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize customer service page
    if (window.location.pathname.includes('customer-service.html')) {
        initCustomerService();
    }
});
