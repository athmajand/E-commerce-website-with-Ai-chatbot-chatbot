// Script to fix profile link navigation issues

// Function to fix profile link navigation
function fixProfileLinkNavigation() {
    // Get the profile link
    const profileLink = document.getElementById('profile-link');

    if (profileLink) {
        console.log('Found profile link, fixing navigation');

        // Remove any existing event listeners by cloning and replacing the element
        const newProfileLink = profileLink.cloneNode(true);
        profileLink.parentNode.replaceChild(newProfileLink, profileLink);

        // Add a new click event listener that ensures navigation works
        newProfileLink.addEventListener('click', function(event) {
            console.log('Profile link clicked, navigating to profile.html');

            // Prevent any default handling that might be interfering
            event.preventDefault();

            // Get the current path to determine if we're in a subdirectory
            const currentPath = window.location.pathname;
            console.log('Current path:', currentPath);

            // Determine the correct path to profile.html
            let profilePath;

            // Check if we're already in the frontend directory
            if (currentPath.includes('/frontend/')) {
                profilePath = 'customer_profile.php';
            } else {
                // We might be at the root, so include the frontend directory
                profilePath = 'frontend/customer_profile.php';
            }

            console.log('Navigating to:', profilePath);

            // Navigate to the correct profile.html path
            window.location.href = profilePath;
        });
    } else {
        console.log('Profile link not found on this page');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing profile link navigation fix');
    fixProfileLinkNavigation();
});
