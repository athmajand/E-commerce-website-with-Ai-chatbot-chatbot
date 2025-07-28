// Customer Registration JavaScript for Kisan Kart

document.addEventListener('DOMContentLoaded', function() {
    // Clear form fields on page load to prevent autofill
    function clearFormFields() {
        // Clear all input fields
        document.querySelectorAll('input').forEach(function(input) {
            if (input.type === 'text' || input.type === 'email' || input.type === 'password' || input.type === 'tel') {
                input.value = '';
            }
        });
    }

    // Clear fields immediately
    clearFormFields();

    // Add a slight delay and clear again (for some browsers)
    setTimeout(clearFormFields, 100);
    setTimeout(clearFormFields, 500);

    // Prevent autofill by changing input types temporarily
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="tel"]');
    inputs.forEach(function(input) {
        input.dataset.originalType = input.type;
        input.type = 'text';

        // Change back to original type on focus
        input.addEventListener('focus', function() {
            this.type = this.dataset.originalType;
        });
    });

    // Get all form containers
    const loginFormContainer = document.getElementById('login-form-container');
    const registerFormContainer = document.getElementById('register-form-container');
    const forgotFormContainer = document.getElementById('forgot-form-container');

    // Get all links and buttons
    const registerBtn = document.getElementById('register-btn');
    const registerLink = document.getElementById('register-link');
    const loginLink = document.getElementById('login-link');
    const forgotLink = document.getElementById('forgot-link');
    const backToLoginLink = document.getElementById('back-to-login');

    // Add event listeners for switching between forms
    if (registerBtn) {
        registerBtn.addEventListener('click', function() {
            showForm(registerFormContainer);
        });
    }

    if (registerLink) {
        registerLink.addEventListener('click', function(e) {
            e.preventDefault();
            showForm(registerFormContainer);
        });
    }

    if (loginLink) {
        loginLink.addEventListener('click', function(e) {
            // Allow the default action (navigate to login.php)
            // No need to prevent default or show the form
        });
    }

    if (forgotLink) {
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            showForm(forgotFormContainer);
        });
    }

    if (backToLoginLink) {
        backToLoginLink.addEventListener('click', function(e) {
            e.preventDefault();
            showForm(loginFormContainer);
        });
    }

    // Function to show a specific form and hide others
    function showForm(formToShow) {
        // Hide all forms
        if (loginFormContainer) loginFormContainer.classList.add('hidden');
        if (registerFormContainer) registerFormContainer.classList.add('hidden');
        if (forgotFormContainer) forgotFormContainer.classList.add('hidden');

        // Show the selected form
        formToShow.classList.remove('hidden');
    }

    // Email/Phone tab switching for login form
    const emailTab = document.getElementById('emailTab');
    const mobileTab = document.getElementById('mobileTab');
    const emailLoginContent = document.getElementById('emailLoginContent');
    const phoneLoginContent = document.getElementById('phoneLoginContent');

    if (emailTab && mobileTab && emailLoginContent && phoneLoginContent) {
        emailTab.addEventListener('click', function() {
            // Switch tabs
            emailTab.classList.add('active');
            mobileTab.classList.remove('active');

            // Switch content
            emailLoginContent.classList.add('active');
            phoneLoginContent.classList.remove('active');

            // Update required attributes
            document.getElementById('login-email').setAttribute('required', '');
            document.getElementById('login-phone').removeAttribute('required');
        });

        mobileTab.addEventListener('click', function() {
            // Switch tabs
            mobileTab.classList.add('active');
            emailTab.classList.remove('active');

            // Switch content
            phoneLoginContent.classList.add('active');
            emailLoginContent.classList.remove('active');

            // Update required attributes
            document.getElementById('login-phone').setAttribute('required', '');
            document.getElementById('login-email').removeAttribute('required');
        });
    }

    // Toggle password visibility
    const togglePasswordElements = document.querySelectorAll('.toggle-password');

    togglePasswordElements.forEach(function(element) {
        element.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);

            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });

    // Form validation
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const isValid = validateForm(this);
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const isValid = validateForm(this);
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Simple form validation function
    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required]');

        inputs.forEach(function(input) {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });

        return isValid;
    }

    // Add ripple effect to buttons
    const rippleButtons = document.querySelectorAll('.btn-ripple');

    rippleButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const x = e.clientX - e.target.getBoundingClientRect().left;
            const y = e.clientY - e.target.getBoundingClientRect().top;

            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            this.appendChild(ripple);

            setTimeout(function() {
                ripple.remove();
            }, 600);
        });
    });
});
