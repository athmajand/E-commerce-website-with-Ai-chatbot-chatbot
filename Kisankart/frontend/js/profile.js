// Profile JavaScript file for Kisan Kart

// API base URL is already defined in main.js
// Using the existing API_BASE_URL from main.js

// Function to initialize profile page
async function initProfile() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            // Get the current path to determine the correct login path
            const currentPath = window.location.pathname;
            console.log('Current path for login redirect:', currentPath);

            let loginPath;
            // Check if we're in the frontend directory
            if (currentPath.includes('/frontend/')) {
                loginPath = '../login.php?redirect=frontend/customer_profile.php';
            } else {
                loginPath = 'login.php?redirect=customer_profile.php';
            }

            console.log('Redirecting to login:', loginPath);
            window.location.href = loginPath;
            return;
        }

        // Get data from localStorage first as a fallback
        const firstName = localStorage.getItem('firstName');
        const lastName = localStorage.getItem('lastName');
        const email = localStorage.getItem('email');
        const phone = localStorage.getItem('phone') || '';
        const profileImage = localStorage.getItem('profileImage') || '';

        // Create a fallback profile object from localStorage
        const fallbackProfile = {
            firstName: firstName || '',
            lastName: lastName || '',
            email: email || '',
            phone: phone,
            profileImage: profileImage
        };

        // Always populate form with fallback data first
        populateProfileForm(fallbackProfile);
        console.log('Using initial fallback profile data from localStorage');

        // Try to fetch from API
        try {
            // Fetch user profile and addresses in parallel
            const [profileResponse, addressesResponse] = await Promise.all([
                fetch(`${API_BASE_URL}/simple_profile.php`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                    }
                }),
                fetch(`${API_BASE_URL}/simple_addresses.php`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                    }
                })
            ]);

            // Process profile data if available
            if (profileResponse.ok) {
                const profileData = await profileResponse.json();
                populateProfileForm(profileData);
                console.log('Profile data loaded from API');

                // Update localStorage with latest data
                localStorage.setItem('firstName', profileData.firstName || '');
                localStorage.setItem('lastName', profileData.lastName || '');
                localStorage.setItem('email', profileData.email || '');
                localStorage.setItem('phone', profileData.phone || '');
                if (profileData.profile_image) {
                    localStorage.setItem('profileImage', profileData.profile_image);
                }
            } else {
                console.warn('Failed to fetch profile data from API, using localStorage data');
            }

            // Process addresses if available
            if (addressesResponse.ok) {
                const addressesData = await addressesResponse.json();
                displayAddresses(addressesData);
                console.log('Addresses loaded from API');
            } else {
                // Show empty addresses
                displayAddresses([]);
                console.warn('Failed to fetch addresses from API');
            }
        } catch (apiError) {
            console.error('API fetch error:', apiError);
            // Already using fallback data, so just log the error
        }

        // Add event listeners
        addProfileEventListeners();
    } catch (error) {
        console.error('Profile initialization error:', error);
        showAlert('danger', 'Failed to load profile data. Please try again later.');
    }
}

// Function to populate profile form
function populateProfileForm(profileData) {
    console.log('Populating profile form with data:', profileData);

    // Set form values
    document.getElementById('firstName').value = profileData.firstName || '';
    document.getElementById('lastName').value = profileData.lastName || '';
    document.getElementById('email').value = profileData.email || '';
    document.getElementById('phone').value = profileData.phone || '';

    // Handle profile image field (API returns profile_image, localStorage uses profileImage)
    if (profileData.profile_image) {
        document.getElementById('profileImage').value = profileData.profile_image;
    } else if (profileData.profileImage) {
        document.getElementById('profileImage').value = profileData.profileImage;
    } else {
        document.getElementById('profileImage').value = '';
    }

    // Don't update sidebar user info - rely on PHP session data
    // This is commented out to prevent overriding PHP session data
    /*
    document.getElementById('sidebar-user-name').textContent = `${profileData.firstName || ''} ${profileData.lastName || ''}`;
    document.getElementById('sidebar-user-email').textContent = profileData.email || '';
    */

    // Update localStorage with latest data
    if (profileData.firstName) localStorage.setItem('firstName', profileData.firstName);
    if (profileData.lastName) localStorage.setItem('lastName', profileData.lastName);
    if (profileData.email) localStorage.setItem('email', profileData.email);
    if (profileData.phone) localStorage.setItem('phone', profileData.phone);

    // Update profile image if available
    if (profileData.profile_image) {
        document.getElementById('profile-image').src = profileData.profile_image;
        localStorage.setItem('profileImage', profileData.profile_image);
    } else if (profileData.profileImage) {
        document.getElementById('profile-image').src = profileData.profileImage;
    }

    console.log('Profile form populated successfully');
}

// Function to display addresses
function displayAddresses(addresses) {
    const addressesContainer = document.getElementById('addresses-container');

    if (!addresses || addresses.length === 0) {
        addressesContainer.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">You don't have any saved addresses yet.</p>
            </div>
        `;
        return;
    }

    let addressesHTML = '';

    addresses.forEach(address => {
        addressesHTML += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${address.name}</h6>
                            <p class="mb-1">${address.street}, ${address.city}, ${address.state} ${address.postalCode}</p>
                            <p class="mb-0 text-muted">Phone: ${address.phone}</p>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2 edit-address-btn" data-address-id="${address.id}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-address-btn" data-address-id="${address.id}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    addressesContainer.innerHTML = addressesHTML;

    // Add event listeners to address buttons
    document.querySelectorAll('.edit-address-btn').forEach(button => {
        button.addEventListener('click', () => {
            const addressId = button.dataset.addressId;
            editAddress(addressId, addresses);
        });
    });

    document.querySelectorAll('.delete-address-btn').forEach(button => {
        button.addEventListener('click', () => {
            const addressId = button.dataset.addressId;
            deleteAddress(addressId);
        });
    });
}

// Function to add event listeners
function addProfileEventListeners() {
    // Profile form submission
    const profileForm = document.getElementById('profile-form');
    profileForm.addEventListener('submit', updateProfile);

    // Password form submission
    const passwordForm = document.getElementById('password-form');
    passwordForm.addEventListener('submit', updatePassword);

    // Add address button
    const addAddressBtn = document.getElementById('add-address-btn');
    addAddressBtn.addEventListener('click', () => {
        // Reset form
        document.getElementById('address-form').reset();
        document.getElementById('address-id').value = '';

        // Update modal title
        document.getElementById('addressModalLabel').textContent = 'Add New Address';

        // Show modal
        const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
        addressModal.show();
    });

    // Save address button
    const saveAddressBtn = document.getElementById('save-address-btn');
    saveAddressBtn.addEventListener('click', saveAddress);
}

// Function to update profile
async function updateProfile(event) {
    event.preventDefault();

    try {
        // Get form values
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const phone = document.getElementById('phone').value;
        const profileImage = document.getElementById('profileImage').value;

        // Validate form
        if (!firstName || !lastName) {
            showProfileAlert('error', 'Please fill in all required fields');
            return;
        }

        // Update profile
        const response = await fetch(`${API_BASE_URL}/simple_profile.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({
                firstName,
                lastName,
                phone,
                profile_image: profileImage // Use profile_image to match the backend field name
            })
        });

        if (!response.ok) {
            throw new Error('Failed to update profile');
        }

        const updatedProfile = await response.json();

        // Update form with new data
        populateProfileForm(updatedProfile);

        // Update localStorage with the new data
        localStorage.setItem('firstName', firstName);
        localStorage.setItem('lastName', lastName);
        localStorage.setItem('phone', phone);
        if (profileImage) localStorage.setItem('profileImage', profileImage);

        // Show success message
        showProfileAlert('success', 'Profile updated successfully');

        // Update user name in navigation
        updateUserName(`${firstName} ${lastName}`);
    } catch (error) {
        console.error('Update profile error:', error);
        showProfileAlert('error', 'Failed to update profile. Please try again.');
    }
}

// Function to update password
async function updatePassword(event) {
    event.preventDefault();

    try {
        // Get form values
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validate form
        if (!currentPassword || !newPassword || !confirmPassword) {
            showPasswordAlert('error', 'Please fill in all password fields');
            return;
        }

        if (newPassword !== confirmPassword) {
            showPasswordAlert('error', 'New passwords do not match');
            return;
        }

        // Update password
        const response = await fetch(`${API_BASE_URL}/users/change-password`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({
                currentPassword,
                newPassword
            })
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Failed to update password');
        }

        // Reset form
        document.getElementById('password-form').reset();

        // Show success message
        showPasswordAlert('success', 'Password updated successfully');
    } catch (error) {
        console.error('Update password error:', error);
        showPasswordAlert('error', error.message || 'Failed to update password. Please try again.');
    }
}

// Function to edit address
function editAddress(addressId, addresses) {
    // Find address
    const address = addresses.find(addr => addr.id.toString() === addressId.toString());

    if (!address) {
        showAlert('danger', 'Address not found');
        return;
    }

    // Set form values
    document.getElementById('address-id').value = address.id;
    document.getElementById('address-name').value = address.name;
    document.getElementById('address-phone').value = address.phone;
    document.getElementById('address-street').value = address.street;
    document.getElementById('address-city').value = address.city;
    document.getElementById('address-state').value = address.state;
    document.getElementById('address-postal-code').value = address.postalCode;

    // Update modal title
    document.getElementById('addressModalLabel').textContent = 'Edit Address';

    // Show modal
    const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
    addressModal.show();
}

// Function to save address
async function saveAddress() {
    try {
        // Get form values
        const addressId = document.getElementById('address-id').value;
        const name = document.getElementById('address-name').value;
        const phone = document.getElementById('address-phone').value;
        const street = document.getElementById('address-street').value;
        const city = document.getElementById('address-city').value;
        const state = document.getElementById('address-state').value;
        const postalCode = document.getElementById('address-postal-code').value;

        // Validate form
        if (!name || !phone || !street || !city || !state || !postalCode) {
            showAddressAlert('error', 'Please fill in all address fields');
            return;
        }

        const addressData = {
            name,
            phone,
            street,
            city,
            state,
            postalCode
        };

        let response;

        if (addressId) {
            // Update existing address
            response = await fetch(`${API_BASE_URL}/simple_addresses.php?id=${addressId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                },
                body: JSON.stringify(addressData)
            });
        } else {
            // Create new address
            response = await fetch(`${API_BASE_URL}/simple_addresses.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                },
                body: JSON.stringify(addressData)
            });
        }

        if (!response.ok) {
            throw new Error('Failed to save address');
        }

        // Hide modal
        const addressModal = bootstrap.Modal.getInstance(document.getElementById('addressModal'));
        addressModal.hide();

        // Refresh addresses
        const addressesResponse = await fetch(`${API_BASE_URL}/simple_addresses.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!addressesResponse.ok) {
            throw new Error('Failed to fetch addresses');
        }

        const addressesData = await addressesResponse.json();

        // Display addresses
        displayAddresses(addressesData);

        // Show success message
        showAddressAlert('success', addressId ? 'Address updated successfully' : 'Address added successfully');
    } catch (error) {
        console.error('Save address error:', error);
        showAddressAlert('error', 'Failed to save address. Please try again.');
    }
}

// Function to delete address
async function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/simple_addresses.php?id=${addressId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to delete address');
        }

        // Refresh addresses
        const addressesResponse = await fetch(`${API_BASE_URL}/simple_addresses.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });

        if (!addressesResponse.ok) {
            throw new Error('Failed to fetch addresses');
        }

        const addressesData = await addressesResponse.json();

        // Display addresses
        displayAddresses(addressesData);

        // Show success message
        showAddressAlert('success', 'Address deleted successfully');
    } catch (error) {
        console.error('Delete address error:', error);
        showAddressAlert('error', 'Failed to delete address. Please try again.');
    }
}

// Function to show profile alert
function showProfileAlert(type, message) {
    const successAlert = document.getElementById('profile-success-alert');
    const errorAlert = document.getElementById('profile-error-alert');

    if (type === 'success') {
        successAlert.textContent = message;
        successAlert.style.display = 'block';
        errorAlert.style.display = 'none';

        setTimeout(() => {
            successAlert.style.display = 'none';
        }, 3000);
    } else {
        errorAlert.textContent = message;
        errorAlert.style.display = 'block';
        successAlert.style.display = 'none';

        setTimeout(() => {
            errorAlert.style.display = 'none';
        }, 3000);
    }
}

// Function to show password alert
function showPasswordAlert(type, message) {
    const successAlert = document.getElementById('password-success-alert');
    const errorAlert = document.getElementById('password-error-alert');

    if (type === 'success') {
        successAlert.textContent = message;
        successAlert.style.display = 'block';
        errorAlert.style.display = 'none';

        setTimeout(() => {
            successAlert.style.display = 'none';
        }, 3000);
    } else {
        errorAlert.textContent = message;
        errorAlert.style.display = 'block';
        successAlert.style.display = 'none';

        setTimeout(() => {
            errorAlert.style.display = 'none';
        }, 3000);
    }
}

// Function to show address alert
function showAddressAlert(type, message) {
    const successAlert = document.getElementById('address-success-alert');
    const errorAlert = document.getElementById('address-error-alert');

    if (type === 'success') {
        successAlert.textContent = message;
        successAlert.style.display = 'block';
        errorAlert.style.display = 'none';

        setTimeout(() => {
            successAlert.style.display = 'none';
        }, 3000);
    } else {
        errorAlert.textContent = message;
        errorAlert.style.display = 'block';
        successAlert.style.display = 'none';

        setTimeout(() => {
            errorAlert.style.display = 'none';
        }, 3000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('Profile page initialized');
    initProfile();
    updateNavigation();

    // Add specific event listener for sidebar links to ensure they work
    document.querySelectorAll('.list-group-item').forEach(link => {
        link.addEventListener('click', function(event) {
            // Log the navigation attempt
            console.log('Clicked on sidebar link:', this.href);

            // Let the default navigation happen
            // No preventDefault() call here
        });
    });
});
