// Seller Dashboard JavaScript file for Kisan Kart

// Chart.js instances
let salesChart;
let orderStatusChart;

// Function to initialize dashboard
async function initDashboard() {
    try {
        // Fetch dashboard data
        const response = await fetch(`${API_BASE_URL}/sellers/dashboard`, {
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
        initSalesChart(dashboardData);
        initOrderStatusChart(dashboardData);
        
        // Display recent orders
        displayRecentOrders(dashboardData.recentOrders);
        
        // Display low stock products
        await displayLowStockProducts();
    } catch (error) {
        console.error('Dashboard initialization error:', error);
        showAlert('danger', 'Failed to load dashboard data. Please try again.');
    }
}

// Function to update stats
function updateStats(data) {
    document.getElementById('total-products').textContent = data.productStats.total;
    document.getElementById('total-orders').textContent = data.orderStats.total;
    document.getElementById('pending-orders').textContent = data.orderStats.pending;
    document.getElementById('total-revenue').textContent = formatCurrency(data.financialStats.totalRevenue);
}

// Function to initialize sales chart
function initSalesChart(data, period = 7) {
    // Get sales data for the selected period
    const salesData = data.salesData || generateDummySalesData(period);
    
    // Get canvas element
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (salesChart) {
        salesChart.destroy();
    }
    
    // Create new chart
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.labels,
            datasets: [{
                label: 'Sales',
                data: salesData.values,
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                borderColor: 'rgba(28, 200, 138, 1)',
                pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(28, 200, 138, 1)',
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
                            return 'Sales: ' + formatCurrency(context.raw);
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
            document.getElementById('salesChartDropdown').textContent = e.target.textContent;
            
            // Update chart with new period
            const newSalesData = generateDummySalesData(period);
            salesChart.data.labels = newSalesData.labels;
            salesChart.data.datasets[0].data = newSalesData.values;
            salesChart.update();
        });
    });
}

// Function to initialize order status chart
function initOrderStatusChart(data) {
    // Get order status data
    const orderStatusData = {
        labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
        values: [
            data.orderStats.pending,
            data.orderStats.processing,
            data.orderStats.shipped,
            data.orderStats.delivered,
            data.orderStats.cancelled
        ]
    };
    
    // Get canvas element
    const ctx = document.getElementById('orderStatusChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (orderStatusChart) {
        orderStatusChart.destroy();
    }
    
    // Create new chart
    orderStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: orderStatusData.labels,
            datasets: [{
                data: orderStatusData.values,
                backgroundColor: [
                    '#f6c23e', // Pending
                    '#4e73df', // Processing
                    '#1cc88a', // Shipped
                    '#36b9cc', // Delivered
                    '#e74a3b'  // Cancelled
                ],
                hoverBackgroundColor: [
                    '#e0b138',
                    '#4668c9',
                    '#19b67e',
                    '#31a8b8',
                    '#d44235'
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

// Function to display recent orders
function displayRecentOrders(orders) {
    const container = document.getElementById('recent-orders-container');
    
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
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    orders.forEach(order => {
        const orderDate = formatDate(order.createdAt);
        const orderAmount = formatCurrency(order.price * order.quantity);
        const statusClass = getOrderStatusBadgeClass(order.status);
        const customerName = order.Order.User ? `${order.Order.User.firstName} ${order.Order.User.lastName}` : 'Unknown';
        
        html += `
            <tr>
                <td>${order.orderId}</td>
                <td>${customerName}</td>
                <td>${order.Product.name}</td>
                <td>${orderAmount}</td>
                <td>${orderDate}</td>
                <td><span class="badge ${statusClass}">${capitalizeFirstLetter(order.status)}</span></td>
                <td>
                    <a href="order-details.html?id=${order.id}" class="btn btn-sm btn-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
}

// Function to display low stock products
async function displayLowStockProducts() {
    try {
        const response = await fetch(`${API_BASE_URL}/sellers/products?stock=low`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch low stock products');
        }
        
        const products = await response.json();
        const container = document.getElementById('low-stock-container');
        
        if (!products || products.length === 0) {
            container.innerHTML = `
                <div class="text-center py-3">
                    <p class="text-muted">No low stock products found.</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        products.forEach(product => {
            const price = formatCurrency(product.price);
            
            html += `
                <tr>
                    <td>${product.name}</td>
                    <td>${price}</td>
                    <td><span class="badge bg-danger">${product.stock}</span></td>
                    <td>
                        <a href="edit-product.html?id=${product.id}" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> Update Stock
                        </a>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        container.innerHTML = html;
    } catch (error) {
        console.error('Display low stock products error:', error);
        document.getElementById('low-stock-container').innerHTML = `
            <div class="text-center py-3">
                <p class="text-danger">Failed to load low stock products. Please try again.</p>
            </div>
        `;
    }
}

// Function to generate dummy sales data for chart
function generateDummySalesData(days) {
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
        values.push(Math.floor(Math.random() * 10000) + 1000); // Random value between 1000 and 11000
    }
    
    return { labels, values };
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', initDashboard);
