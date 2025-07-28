// Seller Orders JavaScript file for Kisan Kart

// Global variables
let currentPage = 1;
let totalPages = 1;
let searchQuery = '';
let dateFilter = '';
let sortFilter = 'newest';
let statusFilter = '';
let orderToUpdate = null;

// Function to initialize orders page
async function initOrders() {
    try {
        // Fetch orders
        await fetchOrders();
        
        // Add event listeners
        addEventListeners();
    } catch (error) {
        console.error('Orders initialization error:', error);
        showAlert('danger', 'Failed to load orders. Please try again.');
    }
}

// Function to fetch orders
async function fetchOrders() {
    try {
        // Show loading spinner
        document.getElementById('orders-container').innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Build query parameters
        let queryParams = new URLSearchParams();
        queryParams.append('page', currentPage);
        queryParams.append('limit', 10);
        
        if (searchQuery) {
            queryParams.append('search', searchQuery);
        }
        
        if (statusFilter) {
            queryParams.append('status', statusFilter);
        }
        
        if (dateFilter) {
            queryParams.append('dateFilter', dateFilter);
        }
        
        if (sortFilter) {
            queryParams.append('sortBy', sortFilter);
        }
        
        const response = await fetch(`${API_BASE_URL}/sellers/orders?${queryParams.toString()}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch orders');
        }
        
        const data = await response.json();
        
        // Update global variables
        totalPages = data.totalPages || 1;
        
        // Display orders
        displayOrders(data.orders);
        
        // Update pagination
        updatePagination();
        
        // Update tab counts
        updateTabCounts(data.counts);
    } catch (error) {
        console.error('Fetch orders error:', error);
        document.getElementById('orders-container').innerHTML = `
            <div class="text-center py-3">
                <p class="text-danger">Failed to load orders. Please try again.</p>
                <button class="btn btn-outline-success mt-2" onclick="fetchOrders()">
                    <i class="bi bi-arrow-clockwise"></i> Retry
                </button>
            </div>
        `;
        document.getElementById('pagination-container').innerHTML = '';
    }
}

// Function to display orders
function displayOrders(orders) {
    const container = document.getElementById('orders-container');
    
    if (!orders || orders.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">No orders found.</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    orders.forEach(order => {
        // Format date
        const orderDate = formatDate(order.createdAt);
        
        // Format amount
        const amount = formatCurrency(order.price * order.quantity);
        
        // Get status badge class
        const statusClass = getOrderStatusBadgeClass(order.status);
        
        // Get customer name
        const customerName = order.Order.User ? `${order.Order.User.firstName} ${order.Order.User.lastName}` : 'Unknown';
        
        html += `
            <tr data-id="${order.id}">
                <td data-label="Order ID">${order.orderId}</td>
                <td data-label="Product">${order.Product.name}</td>
                <td data-label="Customer">${customerName}</td>
                <td data-label="Amount">${amount}</td>
                <td data-label="Date">${orderDate}</td>
                <td data-label="Status"><span class="badge ${statusClass}">${capitalizeFirstLetter(order.status)}</span></td>
                <td data-label="Actions">
                    <div class="btn-group">
                        <a href="order-details.html?id=${order.id}" class="btn btn-sm btn-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-success update-status-btn" data-id="${order.id}" data-status="${order.status}">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
    
    // Add event listeners to update status buttons
    document.querySelectorAll('.update-status-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const orderId = e.currentTarget.dataset.id;
            const currentStatus = e.currentTarget.dataset.status;
            showUpdateStatusModal(orderId, currentStatus);
        });
    });
}

// Function to update pagination
function updatePagination() {
    const paginationContainer = document.getElementById('pagination-container');
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
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
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(e.target.closest('.page-link').dataset.page);
            if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                currentPage = page;
                fetchOrders();
                // Scroll to top of orders section
                document.querySelector('.card.shadow').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

// Function to update tab counts
function updateTabCounts(counts) {
    if (!counts) return;
    
    // Update tab counts
    document.getElementById('all-tab').textContent = `All Orders (${counts.total || 0})`;
    document.getElementById('pending-tab').textContent = `Pending (${counts.pending || 0})`;
    document.getElementById('processing-tab').textContent = `Processing (${counts.processing || 0})`;
    document.getElementById('shipped-tab').textContent = `Shipped (${counts.shipped || 0})`;
    document.getElementById('delivered-tab').textContent = `Delivered (${counts.delivered || 0})`;
    document.getElementById('cancelled-tab').textContent = `Cancelled (${counts.cancelled || 0})`;
}

// Function to add event listeners
function addEventListeners() {
    // Search button
    document.getElementById('search-button').addEventListener('click', () => {
        searchQuery = document.getElementById('search-input').value.trim();
        currentPage = 1;
        fetchOrders();
    });
    
    // Search input (Enter key)
    document.getElementById('search-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchQuery = e.target.value.trim();
            currentPage = 1;
            fetchOrders();
        }
    });
    
    // Filter button
    document.getElementById('filter-button').addEventListener('click', () => {
        dateFilter = document.getElementById('date-filter').value;
        sortFilter = document.getElementById('sort-filter').value;
        currentPage = 1;
        fetchOrders();
    });
    
    // Tab buttons
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
            statusFilter = e.target.dataset.status || '';
            currentPage = 1;
            fetchOrders();
        });
    });
    
    // Order status change
    document.getElementById('order-status').addEventListener('change', (e) => {
        const status = e.target.value;
        const trackingContainer = document.getElementById('tracking-info-container');
        
        if (status === 'shipped') {
            trackingContainer.style.display = 'block';
        } else {
            trackingContainer.style.display = 'none';
        }
    });
    
    // Update status confirmation
    document.getElementById('confirm-update-btn').addEventListener('click', updateOrderStatus);
}

// Function to show update status modal
function showUpdateStatusModal(orderId, currentStatus) {
    orderToUpdate = orderId;
    
    // Reset form
    document.getElementById('update-status-form').reset();
    
    // Set current status
    document.getElementById('order-status').value = currentStatus;
    
    // Show/hide tracking info
    const trackingContainer = document.getElementById('tracking-info-container');
    if (currentStatus === 'shipped') {
        trackingContainer.style.display = 'block';
    } else {
        trackingContainer.style.display = 'none';
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

// Function to update order status
async function updateOrderStatus() {
    if (!orderToUpdate) return;
    
    try {
        // Get form data
        const status = document.getElementById('order-status').value;
        const notes = document.getElementById('status-notes').value;
        const trackingNumber = document.getElementById('tracking-number').value;
        
        // Validate form
        if (!status) {
            showAlert('warning', 'Please select a status');
            return;
        }
        
        if (status === 'shipped' && !trackingNumber) {
            showAlert('warning', 'Please enter a tracking number');
            return;
        }
        
        // Show loading
        showLoading();
        
        // Prepare data
        const data = { status, notes };
        if (status === 'shipped') {
            data.trackingNumber = trackingNumber;
        }
        
        // Send request
        const response = await fetch(`${API_BASE_URL}/sellers/orders/${orderToUpdate}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error('Failed to update order status');
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
        modal.hide();
        
        // Show success message
        showAlert('success', 'Order status updated successfully');
        
        // Refresh orders
        fetchOrders();
    } catch (error) {
        console.error('Update order status error:', error);
        showAlert('danger', 'Failed to update order status. Please try again.');
    } finally {
        hideLoading();
        orderToUpdate = null;
    }
}

// Initialize orders page when DOM is loaded
document.addEventListener('DOMContentLoaded', initOrders);
