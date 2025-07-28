// Seller Common JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to check if user is logged in and is a seller
function checkSellerAuth() {
    if (!isLoggedIn()) {
        window.location.href = '../login.html?redirect=seller/dashboard.html&seller=true';
        return false;
    }

    const userRole = localStorage.getItem('user_role');
    if (userRole !== 'seller') {
        alert('You do not have permission to access the seller dashboard. Please register as a seller first.');
        window.location.href = '../index.html';
        return false;
    }

    return true;
}

// Function to update seller name in navigation
function updateSellerInfo() {
    const sellerNameElements = document.querySelectorAll('.seller-name');
    const firstName = localStorage.getItem('firstName') || '';
    const lastName = localStorage.getItem('lastName') || '';

    sellerNameElements.forEach(element => {
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

// Function to get order status badge class
function getOrderStatusBadgeClass(status) {
    switch (status) {
        case 'pending':
            return 'badge-pending';
        case 'processing':
            return 'badge-processing';
        case 'shipped':
            return 'badge-shipped';
        case 'delivered':
            return 'badge-delivered';
        case 'cancelled':
            return 'badge-cancelled';
        default:
            return 'bg-secondary';
    }
}

// Function to capitalize first letter
function capitalizeFirstLetter(string) {
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
        <div class="spinner-border text-success" role="status">
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

// Function to fetch seller profile
async function fetchSellerProfile() {
    try {
        const response = await fetch(`${API_BASE_URL}/sellers/profile`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch seller profile');
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch seller profile error:', error);
        showAlert('danger', 'Failed to fetch seller profile. Please try again.');
        return null;
    }
}

// Function to fetch notifications
async function fetchNotifications() {
    try {
        const response = await fetch(`${API_BASE_URL}/notifications`, {
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
                        await fetch(`${API_BASE_URL}/notifications/${notificationId}/read`, {
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

// Initialize common elements when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in and is a seller
    if (!checkSellerAuth()) {
        return;
    }

    // Update seller info
    updateSellerInfo();

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
