// Script to update sidebar user name with data from customer_registrations table

// Function to update sidebar user name
async function updateSidebarUserName() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            return;
        }

        // Get the sidebar user name element
        const sidebarUserName = document.getElementById('sidebar-user-name');
        const sidebarUserEmail = document.getElementById('sidebar-user-email');

        if (!sidebarUserName) {
            return;
        }

        // Don't override the PHP session data that's already in the sidebar
        // This is the key change - we're not modifying the content that PHP has already set
        console.log('Using PHP session data for sidebar user information');

        // Only update localStorage with the current sidebar values for consistency
        if (sidebarUserName && sidebarUserName.textContent && sidebarUserName.textContent !== 'User Name') {
            // Try to split the full name into first and last name
            const nameParts = sidebarUserName.textContent.trim().split(' ');
            if (nameParts.length > 0) {
                localStorage.setItem('firstName', nameParts[0]);
                if (nameParts.length > 1) {
                    localStorage.setItem('lastName', nameParts.slice(1).join(' '));
                }
            }
        }

        if (sidebarUserEmail && sidebarUserEmail.textContent && sidebarUserEmail.textContent !== 'user@example.com') {
            localStorage.setItem('email', sidebarUserEmail.textContent);
        }
    } catch (error) {
        console.error('Error updating sidebar user name:', error);
    }
}

// Add event listener to sidebar user name
document.addEventListener('DOMContentLoaded', function() {
    // Update sidebar user name when page loads
    updateSidebarUserName();
});
