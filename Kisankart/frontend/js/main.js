// Main JavaScript file for Kisankart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to check if user is logged in
function isLoggedIn() {
    return localStorage.getItem('jwt_token') !== null;
}

// Function to get user role
function getUserRole() {
    return localStorage.getItem('user_role');
}

// Function to update navigation based on login status
function updateNavigation() {
    const authButtons = document.querySelector('.auth-buttons');
    const userMenu = document.querySelector('.user-menu');

    if (!authButtons || !userMenu) return;

    if (isLoggedIn()) {
        // Hide auth buttons and show user menu
        authButtons.style.display = 'none';
        userMenu.style.display = 'flex';
        userMenu.style.removeProperty('display');
        userMenu.setAttribute('style', 'display: flex !important');

        // Update user name in dropdown
        const userNameElement = document.querySelector('.user-name');
        if (userNameElement) {
            const firstName = localStorage.getItem('firstName') || '';
            const lastName = localStorage.getItem('lastName') || '';
            if (firstName && lastName) {
                userNameElement.textContent = `${firstName} ${lastName}`;
            } else {
                userNameElement.textContent = localStorage.getItem('username') || 'User';
            }
        }

        // Don't update sidebar user info - rely on PHP session data
        // This is commented out to prevent overriding PHP session data
        /*
        const sidebarUserName = document.getElementById('sidebar-user-name');
        const sidebarUserEmail = document.getElementById('sidebar-user-email');

        if (sidebarUserName && sidebarUserEmail) {
            const firstName = localStorage.getItem('firstName') || '';
            const lastName = localStorage.getItem('lastName') || '';
            const email = localStorage.getItem('email') || '';

            sidebarUserName.textContent = `${firstName} ${lastName}`;
            sidebarUserEmail.textContent = email;
        }
        */

        // Update cart count
        updateCartCount();

        // Add logout event listener to all logout buttons
        document.querySelectorAll('.logout-btn').forEach(btn => {
            // Remove any existing event listeners by cloning and replacing
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            // Add the logout event listener
            newBtn.addEventListener('click', logout);
        });

        // Make sure sidebar navigation links work correctly
        console.log('Ensuring sidebar links work correctly');
    } else {
        // Show auth buttons and hide user menu
        authButtons.style.display = 'flex';
        userMenu.style.display = 'none';
        userMenu.setAttribute('style', 'display: none !important');
    }
}

// Function to handle logout
function logout() {
    // Clear any notification polling interval if it exists
    if (window.notificationPollingInterval) {
        clearInterval(window.notificationPollingInterval);
        window.notificationPollingInterval = null;
    }

    // Clear local storage
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_role');
    localStorage.removeItem('username');
    localStorage.removeItem('firstName');
    localStorage.removeItem('lastName');
    localStorage.removeItem('email');
    localStorage.removeItem('phone');

    // Redirect to logout script to clear session
    window.location.href = '../logout.php';
}

// Function to handle API errors
function handleApiError(error) {
    console.error('API Error:', error);

    if (error.status === 401) {
        // Unauthorized - token expired or invalid
        localStorage.removeItem('jwt_token');
        alert('Your session has expired. Please login again.');
        window.location.href = 'login.html';
    } else {
        // Other errors
        alert('An error occurred. Please try again later.');
    }
}

// Initialize the application
function initApp() {
    // Update navigation based on login status
    updateNavigation();

    // Add event listeners for forms
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
}

// Function to update cart count
async function updateCartCount() {
    if (!isLoggedIn()) return;

    try {
        // Set a default cart count of 0
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = '0';
        });

        // Try to fetch cart data
        try {
            const response = await fetch(`${API_BASE_URL}/cart`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            });

            if (!response.ok) {
                // Just use the default count of 0
                return;
            }

            const cartData = await response.json();

            // Update cart count badge
            cartCountElements.forEach(element => {
                element.textContent = cartData.itemCount || 0;
            });
        } catch (fetchError) {
            // Silently fail - we already set a default count of 0
            console.log('Cart fetch failed, using default count of 0');
        }
    } catch (error) {
        console.error('Update cart count error:', error);
    }
}

// Function to update user name in navigation
function updateUserName(name) {
    const userNameElements = document.querySelectorAll('.user-name');
    userNameElements.forEach(element => {
        element.textContent = name;
    });

    // Also update in localStorage
    const nameParts = name.split(' ');
    if (nameParts.length >= 2) {
        localStorage.setItem('firstName', nameParts[0]);
        localStorage.setItem('lastName', nameParts.slice(1).join(' '));
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

// Function to handle login form submission
async function handleLogin(event) {
    event.preventDefault();

    const usernameOrEmail = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    try {
        // Determine if input is email or username
        const isEmail = usernameOrEmail.includes('@');

        // Prepare request body based on input type
        const requestBody = isEmail
            ? { email: usernameOrEmail, password }
            : { username: usernameOrEmail, password };

        const response = await fetch(`${API_BASE_URL}/auth/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();

        if (response.ok) {
            // Save user data to local storage
            localStorage.setItem('jwt_token', data.jwt || data.token);
            localStorage.setItem('user_id', data.id);
            localStorage.setItem('user_role', data.role);
            localStorage.setItem('username', data.username);
            localStorage.setItem('firstName', data.firstName);
            localStorage.setItem('lastName', data.lastName);
            localStorage.setItem('email', data.email);

            // Check if there's a redirect parameter
            const params = getUrlParams();
            if (params.redirect) {
                window.location.href = params.redirect;
            } else {
                // Redirect to dashboard
                window.location.href = 'customer_dashboard.php';
            }
        } else {
            // Display error message
            document.getElementById('login-error').textContent = data.message || 'Invalid credentials';
            document.getElementById('login-error').style.display = 'block';
        }
    } catch (error) {
        console.error('Login error:', error);
        document.getElementById('login-error').textContent = 'An error occurred. Please try again.';
        document.getElementById('login-error').style.display = 'block';
    }
}

// Function to handle register form submission
async function handleRegister(event) {
    event.preventDefault();

    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const role = document.getElementById('role').value;
    const phone = document.getElementById('phone')?.value || '';

    // Validate passwords match
    if (password !== confirmPassword) {
        document.getElementById('register-error').textContent = 'Passwords do not match';
        document.getElementById('register-error').style.display = 'block';
        return;
    }

    try {
        // Generate a username from firstName and lastName
        const username = (firstName + lastName).toLowerCase().replace(/[^a-z0-9]/g, '');

        const response = await fetch(`${API_BASE_URL}/auth/register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username, // Add username field
                firstName,
                lastName,
                email,
                password,
                role: role || 'customer',
                phone
            })
        });

        // Store firstName and lastName in localStorage for later use
        localStorage.setItem('firstName', firstName);
        localStorage.setItem('lastName', lastName);

        const data = await response.json();

        if (response.ok) {
            // Show success message and redirect to login
            alert('Registration successful! Please login.');
            window.location.href = 'login.html';
        } else {
            // Display error message
            document.getElementById('register-error').textContent = data.message || 'Registration failed';
            document.getElementById('register-error').style.display = 'block';
        }
    } catch (error) {
        console.error('Registration error:', error);
        document.getElementById('register-error').textContent = 'An error occurred. Please try again.';
        document.getElementById('register-error').style.display = 'block';
    }
}

// Function to add cache busting parameter to a URL
function addCacheBustingParam(url) {
    // Skip URLs that already have cache busting or are external
    if (url.includes('?v=') || url.includes('http') || url.includes('data:')) {
        return url;
    }

    // Generate a random cache busting value
    const cacheBuster = new Date().getTime();

    // Add cache busting parameter
    if (url.includes('?')) {
        return `${url}&v=${cacheBuster}`;
    } else {
        return `${url}?v=${cacheBuster}`;
    }
}

// Function to add cache busting to all images
function addCacheBustingToImages() {
    // Get all images
    const images = document.querySelectorAll('img');

    // Add cache busting parameter to each image
    images.forEach(img => {
        // Skip images that already have cache busting or are external
        if (img.src.includes('?v=') || img.src.includes('http') || img.src.includes('data:')) {
            return;
        }

        // Add cache busting parameter
        img.src = addCacheBustingParam(img.src);
    });
}

// Function to enhance accessibility for icon-only buttons and other elements
function enhanceAccessibility() {
    // Add aria-label to icon-only buttons
    const iconButtons = document.querySelectorAll('button:not([aria-label])');

    iconButtons.forEach(button => {
        // Check if button only has an icon and no text
        if (button.querySelector('i, .bi, .fa, .fas, .far') &&
            button.textContent.trim() === '') {

            // Try to determine a meaningful label
            let label = '';

            // Check for common icon classes
            if (button.querySelector('.bi-heart, .fa-heart')) {
                label = 'Add to wishlist';
            } else if (button.querySelector('.bi-cart, .fa-cart, .bi-cart-plus, .fa-cart-plus')) {
                label = 'Add to cart';
            } else if (button.querySelector('.bi-search, .fa-search')) {
                label = 'Search';
            } else if (button.querySelector('.bi-x, .fa-times, .btn-close')) {
                label = 'Close';
            } else {
                // Use the button's title or a generic label
                label = button.title || 'Button';
            }

            // Add aria-label
            button.setAttribute('aria-label', label);

            // Mark icons as decorative
            button.querySelectorAll('i, .bi, .fa, .fas, .far').forEach(icon => {
                icon.setAttribute('aria-hidden', 'true');
            });
        }
    });

    // Add title to select elements without labels
    const selects = document.querySelectorAll('select:not([title]):not([aria-label])');

    selects.forEach(select => {
        // Check if select has an associated label
        const id = select.id;
        const hasLabel = id && document.querySelector(`label[for="${id}"]`);

        if (!hasLabel) {
            // Try to determine a meaningful title
            let title = '';

            // Check for common select IDs
            if (id.includes('sort')) {
                title = 'Sort by';
            } else if (id.includes('filter')) {
                title = 'Filter by';
            } else if (id.includes('category')) {
                title = 'Select category';
            } else {
                // Use a generic title
                title = 'Select an option';
            }

            // Add title and aria-label
            select.setAttribute('title', title);
            select.setAttribute('aria-label', title);
        }
    });
}

// Extended initialization function
function extendedInit() {
    // Run the original initialization
    initApp();

    // Add cache busting to all images
    addCacheBustingToImages();

    // Enhance accessibility
    enhanceAccessibility();
}

// Initialize the app when the DOM is loaded
document.addEventListener('DOMContentLoaded', extendedInit);
