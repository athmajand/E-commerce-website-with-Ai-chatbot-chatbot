// Notifications JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Global variable to store the polling interval (attached to window object so it can be accessed from other scripts)
window.notificationPollingInterval = null;

// Function to initialize notifications
async function initNotifications() {
    try {
        if (!isLoggedIn()) {
            return;
        }

        // Clear any existing polling interval
        if (window.notificationPollingInterval) {
            clearInterval(window.notificationPollingInterval);
        }

        // Fetch notifications
        await fetchNotifications();

        // Set up notification polling - every 5 minutes instead of every minute
        // This reduces the frequency of API calls
        window.notificationPollingInterval = setInterval(fetchNotifications, 300000); // Poll every 5 minutes

        // Add event listeners
        setupNotificationEventListeners();
    } catch (error) {
        console.error('Initialize notifications error:', error);
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
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Update notification badge
        updateNotificationBadge(data.unreadCount);

        // Update notification dropdown
        updateNotificationDropdown(data.notifications);

        return data;
    } catch (error) {
        console.error('Fetch notifications error:', error);
        return null;
    }
}

// Function to update notification badge
function updateNotificationBadge(unreadCount) {
    const badge = document.querySelector('.notification-badge');
    if (!badge) return;

    if (unreadCount > 0) {
        badge.textContent = unreadCount;
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

// Function to update notification dropdown
function updateNotificationDropdown(notifications) {
    const dropdown = document.getElementById('notification-dropdown');
    if (!dropdown) return;

    // Get notification list container
    const listContainer = document.getElementById('notification-list');
    if (!listContainer) return;

    if (notifications.length === 0) {
        listContainer.innerHTML = `
            <div class="dropdown-item text-center py-3">
                <i class="bi bi-bell-slash text-muted"></i>
                <p class="mb-0 mt-2">No notifications</p>
            </div>
        `;
        return;
    }

    // Create notification items
    let html = '';

    // Show only the latest 5 notifications in the dropdown
    const recentNotifications = notifications.slice(0, 5);

    recentNotifications.forEach(notification => {
        const isUnread = !notification.isRead;
        const date = new Date(notification.createdAt);
        const formattedDate = formatTimeAgo(date);

        let iconClass = 'bi-bell';

        // Set icon based on notification type
        switch (notification.type) {
            case 'order':
                iconClass = 'bi-box';
                break;
            case 'payment':
                iconClass = 'bi-credit-card';
                break;
            case 'delivery':
                iconClass = 'bi-truck';
                break;
            case 'promotion':
                iconClass = 'bi-megaphone';
                break;
            default:
                iconClass = 'bi-bell';
        }

        html += `
            <a href="${notification.actionUrl || '#'}" class="dropdown-item notification-item ${isUnread ? 'unread' : ''}" data-id="${notification.id}">
                <div class="d-flex align-items-center">
                    <div class="notification-icon me-3">
                        <i class="bi ${iconClass} ${isUnread ? 'text-primary' : 'text-muted'}"></i>
                    </div>
                    <div class="notification-content flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 notification-title">${notification.title}</h6>
                            <small class="text-muted notification-time">${formattedDate}</small>
                        </div>
                        <p class="mb-0 notification-message text-truncate">${notification.message}</p>
                    </div>
                    ${isUnread ? '<div class="ms-2"><span class="badge bg-primary rounded-circle">&nbsp;</span></div>' : ''}
                </div>
            </a>
        `;
    });

    // Add view all and mark all as read buttons
    html += `
        <div class="dropdown-divider"></div>
        <div class="d-flex justify-content-between px-3 py-2">
            <button class="btn btn-sm btn-link text-decoration-none" id="mark-all-read-btn">Mark all as read</button>
            <a href="notifications.html" class="btn btn-sm btn-link text-decoration-none">View all</a>
        </div>
    `;

    listContainer.innerHTML = html;
}

// Function to setup notification event listeners
function setupNotificationEventListeners() {
    // Delegation for notification items
    document.addEventListener('click', async (e) => {
        // Handle notification item click
        if (e.target.closest('.notification-item')) {
            const item = e.target.closest('.notification-item');
            const notificationId = item.dataset.id;

            // Mark notification as read
            await markNotificationAsRead(notificationId);
        }

        // Handle mark all as read button click
        if (e.target.id === 'mark-all-read-btn' || e.target.closest('#mark-all-read-btn')) {
            e.preventDefault();
            await markAllNotificationsAsRead();
        }
    });
}

// Function to mark notification as read
async function markNotificationAsRead(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/notifications/${id}/read`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Update notification badge
        updateNotificationBadge(data.unreadCount);

        return data;
    } catch (error) {
        console.error('Mark notification as read error:', error);
        return null;
    }
}

// Function to mark all notifications as read
async function markAllNotificationsAsRead() {
    try {
        const response = await fetch(`${API_BASE_URL}/notifications/read-all`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        // Refresh notifications
        await fetchNotifications();

        return true;
    } catch (error) {
        console.error('Mark all notifications as read error:', error);
        return false;
    }
}

// Function to initialize notifications page
async function initNotificationsPage() {
    try {
        if (!isLoggedIn()) {
            displayLoginMessage();
            return;
        }

        // Show loading spinner
        const container = document.getElementById('notifications-container');
        if (!container) return;

        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        // Fetch all notifications
        const response = await fetch(`${API_BASE_URL}/notifications?limit=20`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Display notifications
        displayNotifications(data.notifications, data.totalPages, data.currentPage);

        // Add event listeners
        setupNotificationsPageEventListeners();
    } catch (error) {
        console.error('Initialize notifications page error:', error);
        displayErrorMessage('Failed to load notifications');
    }
}

// Function to display notifications on the notifications page
function displayNotifications(notifications, totalPages, currentPage) {
    const container = document.getElementById('notifications-container');
    if (!container) return;

    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-bell-slash fs-1 text-muted mb-3"></i>
                <h4>No Notifications</h4>
                <p class="text-muted">You don't have any notifications yet.</p>
            </div>
        `;
        return;
    }

    // Create notifications list
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Notifications</h4>
            <button id="clear-all-btn" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash"></i> Clear All
            </button>
        </div>
        <div class="list-group notification-list">
    `;

    notifications.forEach(notification => {
        const isUnread = !notification.isRead;
        const date = new Date(notification.createdAt);
        const formattedDate = formatDateTime(date);

        let iconClass = 'bi-bell';
        let bgClass = 'bg-light';

        // Set icon and background based on notification type
        switch (notification.type) {
            case 'order':
                iconClass = 'bi-box';
                bgClass = isUnread ? 'bg-light-success' : 'bg-light';
                break;
            case 'payment':
                iconClass = 'bi-credit-card';
                bgClass = isUnread ? 'bg-light-primary' : 'bg-light';
                break;
            case 'delivery':
                iconClass = 'bi-truck';
                bgClass = isUnread ? 'bg-light-info' : 'bg-light';
                break;
            case 'promotion':
                iconClass = 'bi-megaphone';
                bgClass = isUnread ? 'bg-light-warning' : 'bg-light';
                break;
            default:
                iconClass = 'bi-bell';
                bgClass = isUnread ? 'bg-light-secondary' : 'bg-light';
        }

        html += `
            <div class="list-group-item notification-item ${bgClass} ${isUnread ? 'unread' : ''}" data-id="${notification.id}">
                <div class="d-flex">
                    <div class="notification-icon me-3">
                        <i class="bi ${iconClass} fs-4 ${isUnread ? 'text-primary' : 'text-muted'}"></i>
                    </div>
                    <div class="notification-content flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 notification-title">${notification.title}</h6>
                            <div>
                                <small class="text-muted notification-time me-2">${formattedDate}</small>
                                <button class="btn btn-sm btn-link text-danger delete-notification-btn p-0" data-id="${notification.id}">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </div>
                        <p class="mb-2 notification-message">${notification.message}</p>
                        ${notification.actionUrl ? `
                            <a href="${notification.actionUrl}" class="btn btn-sm btn-outline-primary">View Details</a>
                        ` : ''}
                    </div>
                    ${isUnread ? '<div class="ms-2"><span class="badge bg-primary rounded-circle">&nbsp;</span></div>' : ''}
                </div>
            </div>
        `;
    });

    html += '</div>';

    // Add pagination if needed
    if (totalPages > 1) {
        html += `
            <nav aria-label="Notifications pagination" class="mt-4">
                <ul class="pagination justify-content-center">
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

    container.innerHTML = html;
}

// Function to setup notifications page event listeners
function setupNotificationsPageEventListeners() {
    const container = document.getElementById('notifications-container');
    if (!container) return;

    // Delegation for notification items
    container.addEventListener('click', async (e) => {
        // Handle notification item click
        if (e.target.closest('.notification-item') && !e.target.closest('.delete-notification-btn') && !e.target.closest('a')) {
            const item = e.target.closest('.notification-item');
            const notificationId = item.dataset.id;

            if (item.classList.contains('unread')) {
                // Mark notification as read
                await markNotificationAsRead(notificationId);

                // Update UI
                item.classList.remove('unread', 'bg-light-success', 'bg-light-primary', 'bg-light-info', 'bg-light-warning', 'bg-light-secondary');
                item.classList.add('bg-light');
                item.querySelector('.badge')?.remove();
            }
        }

        // Handle delete notification button click
        if (e.target.closest('.delete-notification-btn')) {
            e.preventDefault();
            e.stopPropagation();

            const button = e.target.closest('.delete-notification-btn');
            const notificationId = button.dataset.id;

            // Confirm deletion
            if (confirm('Are you sure you want to delete this notification?')) {
                await deleteNotification(notificationId);

                // Remove notification from UI
                button.closest('.notification-item').remove();

                // Check if there are no more notifications
                if (container.querySelectorAll('.notification-item').length === 0) {
                    // Reload page to show empty state
                    window.location.reload();
                }
            }
        }

        // Handle clear all button click
        if (e.target.id === 'clear-all-btn' || e.target.closest('#clear-all-btn')) {
            e.preventDefault();

            // Confirm deletion
            if (confirm('Are you sure you want to delete all notifications?')) {
                await deleteAllNotifications();

                // Reload page to show empty state
                window.location.reload();
            }
        }

        // Handle pagination click
        if (e.target.closest('.page-link')) {
            e.preventDefault();

            const link = e.target.closest('.page-link');
            const page = link.dataset.page;

            // Load notifications for the selected page
            await loadNotificationsPage(page);
        }
    });
}

// Function to load notifications page
async function loadNotificationsPage(page) {
    try {
        // Show loading spinner
        const container = document.getElementById('notifications-container');
        if (!container) return;

        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        // Fetch notifications for the selected page
        const response = await fetch(`${API_BASE_URL}/notifications?page=${page}&limit=20`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Display notifications
        displayNotifications(data.notifications, data.totalPages, data.currentPage);

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        console.error('Load notifications page error:', error);
        displayErrorMessage('Failed to load notifications');
    }
}

// Function to delete notification
async function deleteNotification(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/notifications/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Update notification badge
        updateNotificationBadge(data.unreadCount);

        return data;
    } catch (error) {
        console.error('Delete notification error:', error);
        return null;
    }
}

// Function to delete all notifications
async function deleteAllNotifications() {
    try {
        const response = await fetch(`${API_BASE_URL}/notifications`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        // Update notification badge
        updateNotificationBadge(0);

        return true;
    } catch (error) {
        console.error('Delete all notifications error:', error);
        return false;
    }
}

// Function to display login message
function displayLoginMessage() {
    const container = document.getElementById('notifications-container');
    if (!container) return;

    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-person-lock fs-1 text-muted mb-3"></i>
            <h4>Please login to view your notifications</h4>
            <p class="text-muted">You need to be logged in to access your notifications.</p>
            <a href="login.html?redirect=notifications.html" class="btn btn-success mt-3">
                Login Now
            </a>
        </div>
    `;
}

// Function to display error message
function displayErrorMessage(message) {
    const container = document.getElementById('notifications-container');
    if (!container) return;

    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> ${message}
        </div>
    `;
}

// Helper function to format time ago
function formatTimeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) {
        return 'Just now';
    }

    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} ${diffInMinutes === 1 ? 'minute' : 'minutes'} ago`;
    }

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} ${diffInHours === 1 ? 'hour' : 'hours'} ago`;
    }

    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) {
        return `${diffInDays} ${diffInDays === 1 ? 'day' : 'days'} ago`;
    }

    return formatDateTime(date);
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

// Helper function to check if user is logged in
function isLoggedIn() {
    return !!localStorage.getItem('jwt_token');
}

// Initialize notifications when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize notifications for all pages
    initNotifications();

    // Initialize notifications page if on notifications.html
    if (window.location.pathname.includes('notifications.html')) {
        initNotificationsPage();
    }
});
