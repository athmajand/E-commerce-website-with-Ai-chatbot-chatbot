// Script to fix sidebar link navigation issues

// Function to fix sidebar link navigation
function fixSidebarLinkNavigation() {
    console.log('Fixing sidebar link navigation');
    
    // Get all sidebar links
    const sidebarLinks = document.querySelectorAll('.list-group-item');
    
    sidebarLinks.forEach(link => {
        // Remove any existing event listeners by cloning and replacing the element
        const newLink = link.cloneNode(true);
        link.parentNode.replaceChild(newLink, link);
        
        // Add a new click event listener that ensures navigation works
        newLink.addEventListener('click', function(event) {
            // Only handle non-logout links (logout links have a special class)
            if (!this.classList.contains('logout-btn')) {
                console.log('Sidebar link clicked:', this.href);
                
                // Prevent any default handling that might be interfering
                event.preventDefault();
                
                // Navigate to the link's href
                window.location.href = this.getAttribute('href');
            }
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing sidebar link navigation fix');
    fixSidebarLinkNavigation();
});
