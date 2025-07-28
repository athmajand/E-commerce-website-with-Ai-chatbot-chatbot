// Customer Dashboard JavaScript file for Kisan Kart

document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in via PHP session
    // This is handled by the PHP code in customer_dashboard.php
    
    // Initialize cart count
    updateCartCount();
    
    // Add event listeners for logout buttons
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // If it's a link with href="#", prevent default
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
                logout();
            }
            // Otherwise, let the link work normally (it will go to logout.php)
        });
    });
    
    // Add animation to dashboard cards
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    dashboardCards.forEach((card, index) => {
        // Add a slight delay to each card for a staggered animation effect
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});

// Function to update cart count
function updateCartCount() {
    const cartCountElements = document.querySelectorAll('.cart-count');
    
    // Try to get cart count from localStorage
    let cartCount = localStorage.getItem('cartCount') || 0;
    
    // Update all cart count elements
    cartCountElements.forEach(element => {
        element.textContent = cartCount;
        
        // Show/hide badge based on count
        if (parseInt(cartCount) > 0) {
            element.style.display = 'inline-block';
        } else {
            element.style.display = 'none';
        }
    });
}

// Function to handle logout
function logout() {
    // Clear localStorage
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('user_id');
    localStorage.removeItem('username');
    localStorage.removeItem('email');
    localStorage.removeItem('firstName');
    localStorage.removeItem('lastName');
    localStorage.removeItem('role');
    
    // Redirect to login page
    window.location.href = '../logout.php';
}
