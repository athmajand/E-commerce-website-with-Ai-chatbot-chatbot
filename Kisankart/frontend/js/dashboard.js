// Dashboard JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to initialize dashboard page
async function initDashboard() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            window.location.href = 'login.html?redirect=dashboard.html';
            return;
        }

        // Fetch all required data in parallel
        const [
            profileResponse,
            ordersResponse,
            wishlistResponse,
            addressesResponse,
            recommendedProductsResponse
        ] = await Promise.all([
            fetch(`${API_BASE_URL}/users/profile`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            }),
            fetch(`${API_BASE_URL}/orders`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            }),
            fetch(`${API_BASE_URL}/wishlist`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            }),
            fetch(`${API_BASE_URL}/users/addresses`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            }),
            fetch(`${API_BASE_URL}/products/featured`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            })
        ]);
        
        // Check if all responses are OK
        if (!profileResponse.ok || !ordersResponse.ok || !wishlistResponse.ok || 
            !addressesResponse.ok || !recommendedProductsResponse.ok) {
            throw new Error('Failed to fetch dashboard data');
        }
        
        // Parse all responses
        const profileData = await profileResponse.json();
        const ordersData = await ordersResponse.json();
        const wishlistData = await wishlistResponse.json();
        const addressesData = await addressesResponse.json();
        const recommendedProductsData = await recommendedProductsResponse.json();
        
        // Update sidebar user info
        updateSidebarUserInfo(profileData);
        
        // Update dashboard stats
        updateDashboardStats(ordersData, wishlistData, addressesData);
        
        // Display recent orders
        displayRecentOrders(ordersData);
        
        // Display recommended products
        displayRecommendedProducts(recommendedProductsData);
    } catch (error) {
        console.error('Dashboard initialization error:', error);
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

// Function to update dashboard stats
function updateDashboardStats(orders, wishlist, addresses) {
    // Update order count
    document.getElementById('total-orders').textContent = orders.length;
    
    // Update wishlist count
    document.getElementById('wishlist-count').textContent = wishlist.items ? wishlist.items.length : 0;
    
    // Update address count
    document.getElementById('address-count').textContent = addresses.length;
}

// Function to display recent orders
function displayRecentOrders(orders) {
    const recentOrdersContainer = document.getElementById('recent-orders-container');
    
    if (!orders || orders.length === 0) {
        recentOrdersContainer.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">You haven't placed any orders yet.</p>
                <a href="products.html" class="btn btn-sm btn-success mt-2">
                    <i class="bi bi-cart"></i> Start Shopping
                </a>
            </div>
        `;
        return;
    }
    
    // Sort orders by date (newest first)
    orders.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    
    // Get only the 3 most recent orders
    const recentOrders = orders.slice(0, 3);
    
    let recentOrdersHTML = `
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
    
    recentOrders.forEach(order => {
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
        
        recentOrdersHTML += `
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
                </td>
            </tr>
        `;
    });
    
    recentOrdersHTML += `
                </tbody>
            </table>
        </div>
    `;
    
    recentOrdersContainer.innerHTML = recentOrdersHTML;
}

// Function to display recommended products
function displayRecommendedProducts(products) {
    const recommendedProductsContainer = document.getElementById('recommended-products-container');
    
    if (!products || products.length === 0) {
        recommendedProductsContainer.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">No recommended products available at the moment.</p>
            </div>
        `;
        return;
    }
    
    // Get only 3 products
    const recommendedProducts = products.slice(0, 3);
    
    let recommendedProductsHTML = `
        <div class="row row-cols-1 row-cols-md-3 g-4">
    `;
    
    recommendedProducts.forEach(product => {
        // Format prices
        const formattedPrice = new Intl.NumberFormat('en-IN', { 
            style: 'currency', 
            currency: 'INR',
            maximumFractionDigits: 2
        }).format(product.price);
        
        const formattedDiscountPrice = product.discountPrice ? new Intl.NumberFormat('en-IN', { 
            style: 'currency', 
            currency: 'INR',
            maximumFractionDigits: 2
        }).format(product.discountPrice) : '';
        
        // Get product image
        const productImage = product.images && product.images.length > 0 
            ? product.images[0] 
            : 'https://via.placeholder.com/300x200?text=No+Image';
        
        recommendedProductsHTML += `
            <div class="col">
                <div class="card h-100">
                    <a href="product-details.html?id=${product.id}" class="text-decoration-none">
                        <img src="${productImage}" class="card-img-top product-image" alt="${product.name}"
                            onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'" style="height: 150px; object-fit: cover;">
                        <div class="card-body">
                            <h6 class="card-title text-dark">${product.name}</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    ${product.discountPrice ? 
                                        `<span class="text-decoration-line-through text-muted small">${formattedPrice}</span><br>
                                        <span class="fw-bold text-success">${formattedDiscountPrice}</span>` : 
                                        `<span class="fw-bold text-success">${formattedPrice}</span>`}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        `;
    });
    
    recommendedProductsHTML += `
        </div>
    `;
    
    recommendedProductsContainer.innerHTML = recommendedProductsHTML;
}

// Function to display error message
function displayErrorMessage() {
    // Create error message for recent orders
    document.getElementById('recent-orders-container').innerHTML = `
        <div class="text-center py-3">
            <p class="text-danger">Failed to load recent orders. Please try again later.</p>
        </div>
    `;
    
    // Create error message for recommended products
    document.getElementById('recommended-products-container').innerHTML = `
        <div class="text-center py-3">
            <p class="text-danger">Failed to load recommended products. Please try again later.</p>
        </div>
    `;
    
    // Show refresh button
    const refreshButton = document.createElement('button');
    refreshButton.className = 'btn btn-outline-success mt-3';
    refreshButton.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Dashboard';
    refreshButton.addEventListener('click', initDashboard);
    
    document.querySelector('.col-lg-9').appendChild(refreshButton);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initDashboard();
    updateNavigation();
});
