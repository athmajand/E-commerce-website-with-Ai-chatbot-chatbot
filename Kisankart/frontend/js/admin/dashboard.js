// Admin Dashboard JavaScript file for Kisan Kart

// Chart.js instances
let revenueChart;
let userDistributionChart;

// Function to initialize dashboard
async function initDashboard() {
    try {
        // Fetch dashboard data
        const response = await fetch(`${API_BASE_URL}/admin/dashboard`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch dashboard data');
        }
        
        const dashboardData = await response.json();
        
        // Update stats
        updateStats(dashboardData);
        
        // Initialize charts
        initRevenueChart(dashboardData);
        initUserDistributionChart(dashboardData);
        
        // Display recent activities
        displayRecentActivities(dashboardData.recentActivities);
        
        // Display pending approvals
        displayPendingSellers(dashboardData.pendingSellers);
        displayPendingProducts(dashboardData.pendingProducts);
    } catch (error) {
        console.error('Dashboard initialization error:', error);
        showAlert('danger', 'Failed to load dashboard data. Please try again.');
    }
}

// Function to update stats
function updateStats(data) {
    document.getElementById('total-users').textContent = data.stats.totalUsers;
    document.getElementById('total-revenue').textContent = formatCurrency(data.stats.totalRevenue);
    document.getElementById('total-products').textContent = data.stats.totalProducts;
    document.getElementById('pending-approvals').textContent = data.stats.pendingApprovals;
}

// Function to initialize revenue chart
function initRevenueChart(data, period = 7) {
    // Get revenue data for the selected period
    const revenueData = data.revenueData || generateDummyRevenueData(period);
    
    // Get canvas element
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (revenueChart) {
        revenueChart.destroy();
    }
    
    // Create new chart
    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: revenueData.labels,
            datasets: [{
                label: 'Revenue',
                data: revenueData.values,
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: ' + formatCurrency(context.raw);
                        }
                    }
                }
            }
        }
    });
    
    // Add event listeners to period dropdown
    document.querySelectorAll('[data-period]').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const period = parseInt(e.target.dataset.period);
            document.getElementById('revenueChartDropdown').textContent = e.target.textContent;
            
            // Update chart with new period
            const newRevenueData = generateDummyRevenueData(period);
            revenueChart.data.labels = newRevenueData.labels;
            revenueChart.data.datasets[0].data = newRevenueData.values;
            revenueChart.update();
        });
    });
}

// Function to initialize user distribution chart
function initUserDistributionChart(data) {
    // Get user distribution data
    const userDistributionData = {
        labels: ['Customers', 'Sellers', 'Admins'],
        values: [
            data.stats.customerCount || 0,
            data.stats.sellerCount || 0,
            data.stats.adminCount || 0
        ]
    };
    
    // Get canvas element
    const ctx = document.getElementById('userDistributionChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (userDistributionChart) {
        userDistributionChart.destroy();
    }
    
    // Create new chart
    userDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: userDistributionData.labels,
            datasets: [{
                data: userDistributionData.values,
                backgroundColor: [
                    '#36b9cc', // Customers
                    '#1cc88a', // Sellers
                    '#4e73df'  // Admins
                ],
                hoverBackgroundColor: [
                    '#31a8b8',
                    '#19b67e',
                    '#4668c9'
                ],
                hoverBorderColor: 'rgba(234, 236, 244, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

// Function to display recent activities
function displayRecentActivities(activities) {
    const container = document.getElementById('recent-activities-container');
    
    if (!activities || activities.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">No recent activities found.</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <ul class="activity-feed">
    `;
    
    activities.forEach(activity => {
        const activityDate = formatDateTime(activity.createdAt);
        
        html += `
            <li class="feed-item">
                <span class="date">${activityDate}</span>
                <div class="text">
                    <strong>${activity.user ? activity.user.firstName + ' ' + activity.user.lastName : 'System'}</strong> 
                    ${activity.action}
                </div>
            </li>
        `;
    });
    
    html += `
        </ul>
    `;
    
    container.innerHTML = html;
}

// Function to display pending sellers
function displayPendingSellers(sellers) {
    const container = document.getElementById('pending-sellers-container');
    
    if (!sellers || sellers.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">No pending seller verifications.</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="list-group">
    `;
    
    sellers.slice(0, 5).forEach(seller => {
        const joinedDate = formatDate(seller.createdAt);
        
        html += `
            <a href="seller-details.html?id=${seller.id}" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${seller.User.firstName} ${seller.User.lastName}</h6>
                    <small class="text-muted">${joinedDate}</small>
                </div>
                <p class="mb-1">${seller.businessName || 'No business name'}</p>
                <small class="text-muted">${seller.User.email}</small>
            </a>
        `;
    });
    
    html += `
        </div>
    `;
    
    container.innerHTML = html;
}

// Function to display pending products
function displayPendingProducts(products) {
    const container = document.getElementById('pending-products-container');
    
    if (!products || products.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">No pending product approvals.</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="list-group">
    `;
    
    products.slice(0, 5).forEach(product => {
        const submittedDate = formatDate(product.createdAt);
        const productImage = product.images && product.images.length > 0 
            ? product.images[0] 
            : 'https://via.placeholder.com/50x50?text=No+Image';
        
        html += `
            <a href="product-details.html?id=${product.id}" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <div class="d-flex align-items-center">
                        <img src="${productImage}" alt="${product.name}" class="product-thumbnail me-3"
                            onerror="this.src='https://via.placeholder.com/50x50?text=No+Image'">
                        <div>
                            <h6 class="mb-1">${product.name}</h6>
                            <small class="text-muted">By ${product.Seller.User.firstName} ${product.Seller.User.lastName}</small>
                        </div>
                    </div>
                    <small class="text-muted">${submittedDate}</small>
                </div>
            </a>
        `;
    });
    
    html += `
        </div>
    `;
    
    container.innerHTML = html;
}

// Function to generate dummy revenue data for chart
function generateDummyRevenueData(days) {
    const labels = [];
    const values = [];
    
    const today = new Date();
    
    for (let i = days - 1; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        
        const formattedDate = date.toLocaleDateString('en-IN', {
            month: 'short',
            day: 'numeric'
        });
        
        labels.push(formattedDate);
        values.push(Math.floor(Math.random() * 20000) + 5000); // Random value between 5000 and 25000
    }
    
    return { labels, values };
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', initDashboard);
