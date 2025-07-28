// Admin Common JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to check if user is logged in and is an admin
function checkAdminAuth() {
    if (!isLoggedIn()) {
        window.location.href = '../login.html?redirect=admin/dashboard.html';
        return false;
    }
    
    const userRole = localStorage.getItem('user_role');
    if (userRole !== 'admin') {
        alert('You do not have permission to access the admin dashboard.');
        window.location.href = '../index.html';
        return false;
    }
    
    return true;
}

// Function to update admin name in navigation
function updateAdminInfo() {
    const adminNameElements = document.querySelectorAll('.admin-name');
    const firstName = localStorage.getItem('firstName') || '';
    const lastName = localStorage.getItem('lastName') || '';
    
    adminNameElements.forEach(element => {
        element.textContent = `${firstName} ${lastName}`;
    });
}

// Function to toggle sidebar
function toggleSidebar() {
    document.body.classList.toggle('sb-sidenav-toggled');
}

// Function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', { 
        style: 'currency', 
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(amount);
}

// Function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Function to format date and time
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Function to get status badge class
function getStatusBadgeClass(status) {
    switch (status) {
        case 'pending':
            return 'badge-pending';
        case 'approved':
        case 'active':
            return 'badge-approved';
        case 'rejected':
        case 'inactive':
            return 'badge-rejected';
        default:
            return 'bg-secondary';
    }
}

// Function to capitalize first letter
function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Function to show alert
function showAlert(type, message) {
    // Remove any existing alerts
    const existingAlerts = document.querySelectorAll('.alert-float');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-float`;
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

// Function to show loading overlay
function showLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.body.appendChild(loadingOverlay);
}

// Function to hide loading overlay
function hideLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

// Function to fetch admin profile
async function fetchAdminProfile() {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/profile`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch admin profile');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch admin profile error:', error);
        showAlert('danger', 'Failed to fetch admin profile. Please try again.');
        return null;
    }
}

// Function to fetch notifications
async function fetchNotifications() {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/notifications`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch notifications');
        }
        
        const notifications = await response.json();
        
        // Update notification badge
        const unreadCount = notifications.filter(notification => !notification.isRead).length;
        const notificationBadge = document.querySelector('.notification-badge');
        
        if (unreadCount > 0) {
            notificationBadge.textContent = unreadCount;
            notificationBadge.style.display = 'inline-block';
        } else {
            notificationBadge.style.display = 'none';
        }
        
        // Update notification dropdown
        const notificationList = document.getElementById('notification-list');
        if (notificationList) {
            if (notifications.length === 0) {
                notificationList.innerHTML = `<a class="dropdown-item" href="#">No new notifications</a>`;
            } else {
                notificationList.innerHTML = notifications.slice(0, 5).map(notification => `
                    <a class="dropdown-item notification-item ${notification.isRead ? '' : 'unread'}" href="${notification.actionUrl || '#'}" data-id="${notification.id}">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${formatDateTime(notification.createdAt)}</div>
                    </a>
                `).join('');
                
                // Add "View All" link
                notificationList.innerHTML += `
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center" href="notifications.html">View All Notifications</a>
                `;
                
                // Add click event to mark as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', async (e) => {
                        const notificationId = e.currentTarget.dataset.id;
                        
                        // Mark notification as read
                        await fetch(`${API_BASE_URL}/admin/notifications/${notificationId}/read`, {
                            method: 'PUT',
                            headers: {
                                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                            }
                        });
                    });
                });
            }
        }
    } catch (error) {
        console.error('Fetch notifications error:', error);
    }
}

// Function to get URL parameters
function getUrlParams() {
    const params = {};
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    
    for (const [key, value] of urlParams.entries()) {
        params[key] = value;
    }
    
    return params;
}

// Function to update pagination
function updatePagination(currentPage, totalPages, containerId, callback) {
    const paginationContainer = document.getElementById(containerId);
    
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
    document.querySelectorAll(`#${containerId} .page-link`).forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(e.target.closest('.page-link').dataset.page);
            if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                callback(page);
            }
        });
    });
}

// Initialize common elements when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in and is an admin
    if (!checkAdminAuth()) {
        return;
    }
    
    // Update admin info
    updateAdminInfo();
    
    // Add sidebar toggle event
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Add logout event listener
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', logout);
    });
    
    // Fetch notifications
    fetchNotifications();
    
    // Set active sidebar item based on current page
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('#sidebar-wrapper .list-group-item').forEach(item => {
        if (item.getAttribute('href') === currentPage) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
});
