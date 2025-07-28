// Seller Registration JavaScript for Kisan Kart

document.addEventListener('DOMContentLoaded', function() {
    // Get all form steps
    const formSteps = document.querySelectorAll('.form-step');

    // Get all progress steps
    const progressSteps = document.querySelectorAll('.progress-steps li');

    // Get all next buttons
    const nextButtons = document.querySelectorAll('.next-btn');

    // Get all previous buttons
    const prevButtons = document.querySelectorAll('.prev-btn');

    // Get all form fields
    const formFields = document.querySelectorAll('.form-control');

    // Get password toggle buttons
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');

    // Get file inputs
    const fileInputs = document.querySelectorAll('.file-input');

    // Get file upload boxes
    const fileUploadBoxes = document.querySelectorAll('.file-upload-box');

    // Initialize current step
    let currentStep = 1;

    // Function to show a specific step
    function showStep(stepNumber) {
        // Hide all steps
        formSteps.forEach(step => {
            step.classList.remove('active');
        });

        // Show the current step
        document.getElementById(`step${stepNumber}`).classList.add('active');

        // Update progress steps
        progressSteps.forEach((step, index) => {
            if (index + 1 <= stepNumber) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });

        // Update current step
        currentStep = stepNumber;
    }

    // Function to validate step
    function validateStep(stepNumber) {
        const step = document.getElementById(`step${stepNumber}`);
        const requiredFields = step.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            // Reset error message
            const errorMessage = field.parentElement.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('error-message')) {
                errorMessage.style.display = 'none';
            }

            // Check if field is empty
            if (field.value.trim() === '') {
                isValid = false;

                // Show error message
                if (errorMessage && errorMessage.classList.contains('error-message')) {
                    errorMessage.textContent = 'This field is required';
                    errorMessage.style.display = 'block';
                }

                // Add error class to input
                field.classList.add('error-input');
            } else {
                // Remove error class
                field.classList.remove('error-input');
            }

            // Validate email format
            if (field.type === 'email' && field.value.trim() !== '') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value.trim())) {
                    isValid = false;

                    // Show error message
                    if (errorMessage && errorMessage.classList.contains('error-message')) {
                        errorMessage.textContent = 'Please enter a valid email address';
                        errorMessage.style.display = 'block';
                    }

                    // Add error class to input
                    field.classList.add('error-input');
                }
            }

            // Validate password match
            if (field.id === 'confirm_password') {
                const password = document.getElementById('password').value;
                if (field.value !== password) {
                    isValid = false;

                    // Show error message
                    if (errorMessage && errorMessage.classList.contains('error-message')) {
                        errorMessage.textContent = 'Passwords do not match';
                        errorMessage.style.display = 'block';
                    }

                    // Add error class to input
                    field.classList.add('error-input');
                }
            }

            // Check for existing email/phone error
            if ((field.id === 'email' || field.id === 'mobile') && field.classList.contains('error-input')) {
                isValid = false;
            }
        });

        return isValid;
    }

    // Add event listeners to next buttons
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const nextStep = parseInt(this.getAttribute('data-next'));

            // Validate current step
            if (validateStep(currentStep)) {
                showStep(nextStep);
            }
        });
    });

    // Add event listeners to previous buttons
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            const prevStep = parseInt(this.getAttribute('data-prev'));
            showStep(prevStep);
        });
    });

    // Add event listeners to form fields
    formFields.forEach(field => {
        field.addEventListener('input', function() {
            // Reset error message
            const errorMessage = this.parentElement.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('error-message')) {
                errorMessage.style.display = 'none';
            }

            // Check email and phone in real-time
            if (field.id === 'email' || field.id === 'mobile') {
                // Debounce the API call
                clearTimeout(field.timer);
                field.timer = setTimeout(() => {
                    // Only check if the field has a value
                    if (field.value.trim() !== '') {
                        // Prepare data for API call
                        const data = {};
                        if (field.id === 'email') {
                            data.email = field.value.trim();
                        } else if (field.id === 'mobile') {
                            data.phone = field.value.trim();
                        }

                        // Make API call to check if email/phone exists
                        fetch('api/check_seller_credentials.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                // Show error message
                                if (errorMessage && errorMessage.classList.contains('error-message')) {
                                    errorMessage.textContent = data.message;
                                    errorMessage.style.display = 'block';

                                    // Add error class to input
                                    field.classList.add('error-input');
                                }
                            } else {
                                // Remove error class
                                field.classList.remove('error-input');
                            }
                        })
                        .catch(error => {
                            console.error('Error checking credentials:', error);
                        });
                    }
                }, 500); // Wait 500ms after user stops typing
            }
        });
    });

    // Add event listeners to password toggle buttons
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordField = this.previousElementSibling;

            // Toggle password visibility
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    // Add event listeners to file upload boxes
    fileUploadBoxes.forEach((box, index) => {
        box.addEventListener('click', function() {
            fileInputs[index].click();
        });
    });

    // Add event listeners to file inputs
    fileInputs.forEach((input, index) => {
        input.addEventListener('change', function() {
            const filePreview = this.parentElement.querySelector('.file-preview');

            // Clear previous previews
            filePreview.innerHTML = '';

            // Check if files are selected
            if (this.files.length > 0) {
                // Create preview for each file
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.classList.add('file-preview-item');

                        // Check if file is an image
                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            previewItem.appendChild(img);
                        } else {
                            // For non-image files
                            const fileIcon = document.createElement('i');
                            fileIcon.classList.add('fas', 'fa-file');
                            fileIcon.style.fontSize = '48px';
                            fileIcon.style.color = '#4CAF50';
                            fileIcon.style.display = 'flex';
                            fileIcon.style.alignItems = 'center';
                            fileIcon.style.justifyContent = 'center';
                            fileIcon.style.height = '100%';
                            previewItem.appendChild(fileIcon);
                        }

                        // Add remove button
                        const removeButton = document.createElement('div');
                        removeButton.classList.add('remove-file');
                        removeButton.innerHTML = '<i class="fas fa-times"></i>';
                        removeButton.addEventListener('click', function(event) {
                            event.stopPropagation();
                            previewItem.remove();

                            // Clear file input
                            input.value = '';
                        });

                        previewItem.appendChild(removeButton);
                        filePreview.appendChild(previewItem);
                    };

                    reader.readAsDataURL(file);
                }
            }
        });
    });

    // Password strength meter
    const passwordField = document.getElementById('password');
    const strengthMeter = document.querySelector('.strength-meter');
    const strengthText = document.querySelector('.strength-text span');

    if (passwordField && strengthMeter && strengthText) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // Check password length
            if (password.length >= 8) {
                strength += 1;
            }

            // Check for uppercase letters
            if (/[A-Z]/.test(password)) {
                strength += 1;
            }

            // Check for lowercase letters
            if (/[a-z]/.test(password)) {
                strength += 1;
            }

            // Check for numbers
            if (/[0-9]/.test(password)) {
                strength += 1;
            }

            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 1;
            }

            // Update strength meter
            strengthMeter.style.width = (strength * 20) + '%';

            // Update strength text
            if (strength === 0) {
                strengthText.textContent = 'Weak';
                strengthMeter.style.backgroundColor = '#f44336';
            } else if (strength <= 2) {
                strengthText.textContent = 'Medium';
                strengthMeter.style.backgroundColor = '#ff9800';
            } else if (strength <= 4) {
                strengthText.textContent = 'Strong';
                strengthMeter.style.backgroundColor = '#4caf50';
            } else {
                strengthText.textContent = 'Very Strong';
                strengthMeter.style.backgroundColor = '#2e7d32';
            }
        });
    }

    // Form submission
    const form = document.getElementById('sellerRegistrationForm');

    if (form) {
        form.addEventListener('submit', function(event) {
            // Validate current step
            if (!validateStep(currentStep)) {
                event.preventDefault();
            }
        });
    }
});
