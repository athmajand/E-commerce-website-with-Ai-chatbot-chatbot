// Seller Profile JavaScript file for Kisan Kart

// Global variables
let sellerProfile = null;
let documentFiles = {
    identityProof: null,
    addressProof: null,
    businessProof: null,
    bankProof: null
};

// Function to initialize profile page
async function initProfile() {
    try {
        // Fetch seller profile
        await fetchSellerProfileData();
        
        // Add event listeners
        addEventListeners();
    } catch (error) {
        console.error('Profile initialization error:', error);
        showAlert('danger', 'Failed to load profile data. Please try again.');
    }
}

// Function to fetch seller profile data
async function fetchSellerProfileData() {
    try {
        // Show loading
        showLoading();
        
        const response = await fetch(`${API_BASE_URL}/sellers/profile`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch seller profile');
        }
        
        sellerProfile = await response.json();
        
        // Update profile information
        updateProfileInfo();
    } catch (error) {
        console.error('Fetch seller profile error:', error);
        showAlert('danger', 'Failed to load profile data. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to update profile information
function updateProfileInfo() {
    if (!sellerProfile) return;
    
    // Update basic info
    document.getElementById('seller-name').textContent = `${sellerProfile.User.firstName} ${sellerProfile.User.lastName}`;
    document.getElementById('seller-email').textContent = sellerProfile.User.email;
    document.getElementById('seller-id').textContent = sellerProfile.id;
    document.getElementById('business-name').textContent = sellerProfile.businessName || '-';
    document.getElementById('phone-number').textContent = sellerProfile.User.phone || '-';
    document.getElementById('address').textContent = sellerProfile.address || '-';
    
    // Format joined date
    const joinedDate = new Date(sellerProfile.createdAt);
    document.getElementById('joined-date').textContent = joinedDate.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    // Update verification status
    const verificationStatus = document.getElementById('verification-status');
    verificationStatus.textContent = capitalizeFirstLetter(sellerProfile.verificationStatus || 'pending');
    verificationStatus.className = `verification-status ${sellerProfile.verificationStatus || 'pending'}`;
    
    // Update account status
    const accountStatus = document.getElementById('account-status');
    accountStatus.textContent = sellerProfile.isActive ? 'Active' : 'Inactive';
    accountStatus.className = `badge ${sellerProfile.isActive ? 'bg-success' : 'bg-danger'}`;
    
    // Update profile image
    if (sellerProfile.profileImage) {
        document.getElementById('profile-image').src = sellerProfile.profileImage;
    }
    
    // Update business information
    document.getElementById('display-business-name').textContent = sellerProfile.businessName || '-';
    document.getElementById('display-business-type').textContent = capitalizeFirstLetter(sellerProfile.businessType || '-');
    document.getElementById('display-gst-number').textContent = sellerProfile.gstNumber || '-';
    document.getElementById('display-pan-number').textContent = sellerProfile.panNumber || '-';
    document.getElementById('display-business-description').textContent = sellerProfile.businessDescription || '-';
    document.getElementById('display-business-address').textContent = sellerProfile.address || '-';
    
    // Update form fields
    document.getElementById('business-name-input').value = sellerProfile.businessName || '';
    document.getElementById('business-type').value = sellerProfile.businessType || 'individual';
    document.getElementById('gst-number').value = sellerProfile.gstNumber || '';
    document.getElementById('pan-number').value = sellerProfile.panNumber || '';
    document.getElementById('business-description').value = sellerProfile.businessDescription || '';
    document.getElementById('business-address').value = sellerProfile.address || '';
    document.getElementById('business-city').value = sellerProfile.city || '';
    document.getElementById('business-state').value = sellerProfile.state || '';
    document.getElementById('business-pincode').value = sellerProfile.pincode || '';
    
    // Update bank information
    document.getElementById('display-account-holder').textContent = sellerProfile.accountHolderName || '-';
    document.getElementById('display-account-number').textContent = sellerProfile.accountNumber ? maskAccountNumber(sellerProfile.accountNumber) : '-';
    document.getElementById('display-bank-name').textContent = sellerProfile.bankName || '-';
    document.getElementById('display-ifsc-code').textContent = sellerProfile.ifscCode || '-';
    document.getElementById('display-account-type').textContent = capitalizeFirstLetter(sellerProfile.accountType || '-');
    document.getElementById('display-branch').textContent = sellerProfile.branch || '-';
    
    // Update bank form fields
    document.getElementById('account-holder').value = sellerProfile.accountHolderName || '';
    document.getElementById('account-number').value = sellerProfile.accountNumber || '';
    document.getElementById('bank-name').value = sellerProfile.bankName || '';
    document.getElementById('ifsc-code').value = sellerProfile.ifscCode || '';
    document.getElementById('account-type').value = sellerProfile.accountType || 'savings';
    document.getElementById('branch').value = sellerProfile.branch || '';
    
    // Update document previews
    if (sellerProfile.identityProof) {
        showDocumentPreview('identity-proof', sellerProfile.identityProof);
    }
    
    if (sellerProfile.addressProof) {
        showDocumentPreview('address-proof', sellerProfile.addressProof);
    }
    
    if (sellerProfile.businessProof) {
        showDocumentPreview('business-proof', sellerProfile.businessProof);
    }
    
    if (sellerProfile.bankProof) {
        showDocumentPreview('bank-proof', sellerProfile.bankProof);
    }
    
    // Update 2FA status
    document.getElementById('enable-2fa').checked = sellerProfile.twoFactorEnabled || false;
}

// Function to add event listeners
function addEventListeners() {
    // Profile image upload
    document.getElementById('profile-image-upload').addEventListener('click', () => {
        document.getElementById('profile-image-input').click();
    });
    
    document.getElementById('profile-image-input').addEventListener('change', handleProfileImageUpload);
    
    // Edit profile button
    document.getElementById('edit-profile-btn').addEventListener('click', () => {
        // Redirect to edit profile page
        window.location.href = 'edit-profile.html';
    });
    
    // Business form
    document.getElementById('edit-business-btn').addEventListener('click', () => {
        document.getElementById('business-info').style.display = 'none';
        document.getElementById('business-form').style.display = 'block';
    });
    
    document.getElementById('cancel-business-btn').addEventListener('click', () => {
        document.getElementById('business-form').style.display = 'none';
        document.getElementById('business-info').style.display = 'block';
    });
    
    document.getElementById('business-form').addEventListener('submit', handleBusinessFormSubmit);
    
    // Bank form
    document.getElementById('edit-bank-btn').addEventListener('click', () => {
        document.getElementById('bank-info').style.display = 'none';
        document.getElementById('bank-form').style.display = 'block';
    });
    
    document.getElementById('cancel-bank-btn').addEventListener('click', () => {
        document.getElementById('bank-form').style.display = 'none';
        document.getElementById('bank-info').style.display = 'block';
    });
    
    document.getElementById('bank-form').addEventListener('submit', handleBankFormSubmit);
    
    // Document uploads
    setupDocumentUpload('identity-proof');
    setupDocumentUpload('address-proof');
    setupDocumentUpload('business-proof');
    setupDocumentUpload('bank-proof');
    
    // Submit documents button
    document.getElementById('submit-documents-btn').addEventListener('click', handleDocumentsSubmit);
    
    // Password form
    document.getElementById('password-form').addEventListener('submit', handlePasswordFormSubmit);
    
    // 2FA toggle
    document.getElementById('enable-2fa').addEventListener('change', handle2FAToggle);
    
    // Deactivate account button
    document.getElementById('deactivate-account-btn').addEventListener('click', handleDeactivateAccount);
}

// Function to handle profile image upload
async function handleProfileImageUpload(e) {
    const file = e.target.files[0];
    
    if (!file) return;
    
    // Check if file is an image
    if (!file.type.match('image.*')) {
        showAlert('warning', 'Please upload only image files.');
        return;
    }
    
    // Check file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        showAlert('warning', 'Image size should not exceed 2MB.');
        return;
    }
    
    try {
        // Show loading
        showLoading();
        
        // Create form data
        const formData = new FormData();
        formData.append('profileImage', file);
        
        // Upload image
        const response = await fetch(`${API_BASE_URL}/sellers/profile/image`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Failed to upload profile image');
        }
        
        const data = await response.json();
        
        // Update profile image
        document.getElementById('profile-image').src = data.profileImage;
        
        // Show success message
        showAlert('success', 'Profile image updated successfully');
    } catch (error) {
        console.error('Profile image upload error:', error);
        showAlert('danger', 'Failed to upload profile image. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to handle business form submit
async function handleBusinessFormSubmit(e) {
    e.preventDefault();
    
    try {
        // Show loading
        showLoading();
        
        // Get form data
        const businessData = {
            businessName: document.getElementById('business-name-input').value,
            businessType: document.getElementById('business-type').value,
            gstNumber: document.getElementById('gst-number').value,
            panNumber: document.getElementById('pan-number').value,
            businessDescription: document.getElementById('business-description').value,
            address: document.getElementById('business-address').value,
            city: document.getElementById('business-city').value,
            state: document.getElementById('business-state').value,
            pincode: document.getElementById('business-pincode').value
        };
        
        // Update business information
        const response = await fetch(`${API_BASE_URL}/sellers/profile/business`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify(businessData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to update business information');
        }
        
        // Refresh profile data
        await fetchSellerProfileData();
        
        // Hide form
        document.getElementById('business-form').style.display = 'none';
        document.getElementById('business-info').style.display = 'block';
        
        // Show success message
        showAlert('success', 'Business information updated successfully');
    } catch (error) {
        console.error('Business form submit error:', error);
        showAlert('danger', 'Failed to update business information. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to handle bank form submit
async function handleBankFormSubmit(e) {
    e.preventDefault();
    
    try {
        // Show loading
        showLoading();
        
        // Get form data
        const bankData = {
            accountHolderName: document.getElementById('account-holder').value,
            accountNumber: document.getElementById('account-number').value,
            bankName: document.getElementById('bank-name').value,
            ifscCode: document.getElementById('ifsc-code').value,
            accountType: document.getElementById('account-type').value,
            branch: document.getElementById('branch').value
        };
        
        // Update bank information
        const response = await fetch(`${API_BASE_URL}/sellers/profile/bank`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify(bankData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to update bank information');
        }
        
        // Refresh profile data
        await fetchSellerProfileData();
        
        // Hide form
        document.getElementById('bank-form').style.display = 'none';
        document.getElementById('bank-info').style.display = 'block';
        
        // Show success message
        showAlert('success', 'Bank information updated successfully');
    } catch (error) {
        console.error('Bank form submit error:', error);
        showAlert('danger', 'Failed to update bank information. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to setup document upload
function setupDocumentUpload(documentType) {
    const uploadContainer = document.getElementById(`${documentType}-upload`);
    const fileInput = document.getElementById(`${documentType}-input`);
    const previewContainer = document.getElementById(`${documentType}-preview`);
    const previewImage = document.getElementById(`${documentType}-image`);
    const filenameElement = document.getElementById(`${documentType}-filename`);
    const removeButton = document.getElementById(`remove-${documentType}`);
    
    // Upload button click
    uploadContainer.addEventListener('click', () => {
        fileInput.click();
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        
        if (!file) return;
        
        // Check file type
        if (!file.type.match('image.*') && !file.type.match('application/pdf')) {
            showAlert('warning', 'Please upload only image or PDF files.');
            return;
        }
        
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('warning', 'File size should not exceed 5MB.');
            return;
        }
        
        // Store file
        documentFiles[documentType.replace('-', '')] = file;
        
        // Show preview
        if (file.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
                uploadContainer.style.display = 'none';
                filenameElement.textContent = file.name;
            };
            reader.readAsDataURL(file);
        } else {
            // For PDF files
            previewImage.src = '../img/pdf-icon.png';
            previewContainer.style.display = 'block';
            uploadContainer.style.display = 'none';
            filenameElement.textContent = file.name;
        }
    });
    
    // Remove button click
    removeButton.addEventListener('click', () => {
        documentFiles[documentType.replace('-', '')] = null;
        previewContainer.style.display = 'none';
        uploadContainer.style.display = 'block';
        fileInput.value = '';
    });
}

// Function to show document preview
function showDocumentPreview(documentType, documentUrl) {
    const uploadContainer = document.getElementById(`${documentType}-upload`);
    const previewContainer = document.getElementById(`${documentType}-preview`);
    const previewImage = document.getElementById(`${documentType}-image`);
    const filenameElement = document.getElementById(`${documentType}-filename`);
    
    // Check if document is an image or PDF
    if (documentUrl.endsWith('.pdf')) {
        previewImage.src = '../img/pdf-icon.png';
    } else {
        previewImage.src = documentUrl;
    }
    
    // Extract filename from URL
    const filename = documentUrl.split('/').pop();
    filenameElement.textContent = filename;
    
    // Show preview
    previewContainer.style.display = 'block';
    uploadContainer.style.display = 'none';
}

// Function to handle documents submit
async function handleDocumentsSubmit() {
    // Check if any document is selected
    if (!documentFiles.identityProof && !documentFiles.addressProof && 
        !documentFiles.businessProof && !documentFiles.bankProof) {
        showAlert('warning', 'Please upload at least one document.');
        return;
    }
    
    try {
        // Show loading
        showLoading();
        
        // Create form data
        const formData = new FormData();
        
        if (documentFiles.identityProof) {
            formData.append('identityProof', documentFiles.identityProof);
        }
        
        if (documentFiles.addressProof) {
            formData.append('addressProof', documentFiles.addressProof);
        }
        
        if (documentFiles.businessProof) {
            formData.append('businessProof', documentFiles.businessProof);
        }
        
        if (documentFiles.bankProof) {
            formData.append('bankProof', documentFiles.bankProof);
        }
        
        // Upload documents
        const response = await fetch(`${API_BASE_URL}/sellers/profile/documents`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Failed to upload documents');
        }
        
        // Refresh profile data
        await fetchSellerProfileData();
        
        // Show success message
        showAlert('success', 'Documents uploaded successfully. Your verification is in progress.');
    } catch (error) {
        console.error('Documents submit error:', error);
        showAlert('danger', 'Failed to upload documents. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to handle password form submit
async function handlePasswordFormSubmit(e) {
    e.preventDefault();
    
    // Get form data
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    // Validate passwords
    if (newPassword !== confirmPassword) {
        showAlert('warning', 'New passwords do not match.');
        return;
    }
    
    try {
        // Show loading
        showLoading();
        
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
        showAlert('success', 'Password updated successfully');
    } catch (error) {
        console.error('Password update error:', error);
        showAlert('danger', error.message || 'Failed to update password. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to handle 2FA toggle
async function handle2FAToggle(e) {
    const enabled = e.target.checked;
    
    try {
        // Show loading
        showLoading();
        
        // Update 2FA status
        const response = await fetch(`${API_BASE_URL}/users/2fa`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({ enabled })
        });
        
        if (!response.ok) {
            throw new Error('Failed to update 2FA status');
        }
        
        // Show success message
        showAlert('success', `Two-factor authentication ${enabled ? 'enabled' : 'disabled'} successfully`);
    } catch (error) {
        console.error('2FA toggle error:', error);
        showAlert('danger', 'Failed to update 2FA status. Please try again.');
        
        // Reset toggle
        e.target.checked = !enabled;
    } finally {
        hideLoading();
    }
}

// Function to handle deactivate account
function handleDeactivateAccount() {
    if (confirm('Are you sure you want to deactivate your seller account? Your products will be hidden from customers.')) {
        deactivateAccount();
    }
}

// Function to deactivate account
async function deactivateAccount() {
    try {
        // Show loading
        showLoading();
        
        // Deactivate account
        const response = await fetch(`${API_BASE_URL}/sellers/profile/deactivate`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to deactivate account');
        }
        
        // Show success message
        showAlert('success', 'Your seller account has been deactivated. You can reactivate it at any time.');
        
        // Refresh profile data
        await fetchSellerProfileData();
    } catch (error) {
        console.error('Deactivate account error:', error);
        showAlert('danger', 'Failed to deactivate account. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to mask account number
function maskAccountNumber(accountNumber) {
    if (!accountNumber) return '';
    
    const length = accountNumber.length;
    if (length <= 4) return accountNumber;
    
    const lastFour = accountNumber.slice(-4);
    const masked = 'X'.repeat(length - 4);
    
    return masked + lastFour;
}

// Initialize profile page when DOM is loaded
document.addEventListener('DOMContentLoaded', initProfile);
