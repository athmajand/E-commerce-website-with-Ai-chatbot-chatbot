// Add Product JavaScript file for Kisan Kart

// Global variables
let selectedImages = [];
let categories = [];
let subcategories = [];

// Function to initialize add product page
async function initAddProduct() {
    try {
        // Fetch categories
        await fetchCategories();
        
        // Add event listeners
        addEventListeners();
    } catch (error) {
        console.error('Add product initialization error:', error);
        showAlert('danger', 'Failed to initialize page. Please try again.');
    }
}

// Function to fetch categories
async function fetchCategories() {
    try {
        const response = await fetch(`${API_BASE_URL}/products/categories`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch categories');
        }
        
        categories = await response.json();
        
        // Populate category dropdown
        const categorySelect = document.getElementById('product-category');
        
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            categorySelect.appendChild(option);
        });
    } catch (error) {
        console.error('Fetch categories error:', error);
        showAlert('danger', 'Failed to load categories. Please try again.');
    }
}

// Function to fetch subcategories based on selected category
async function fetchSubcategories(categoryId) {
    try {
        const response = await fetch(`${API_BASE_URL}/products/categories/${categoryId}/subcategories`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch subcategories');
        }
        
        subcategories = await response.json();
        
        // Populate subcategory dropdown
        const subcategorySelect = document.getElementById('product-subcategory');
        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
        
        subcategories.forEach(subcategory => {
            const option = document.createElement('option');
            option.value = subcategory.id;
            option.textContent = subcategory.name;
            subcategorySelect.appendChild(option);
        });
    } catch (error) {
        console.error('Fetch subcategories error:', error);
        showAlert('danger', 'Failed to load subcategories. Please try again.');
    }
}

// Function to add event listeners
function addEventListeners() {
    // Category change event
    document.getElementById('product-category').addEventListener('change', (e) => {
        const categoryId = e.target.value;
        if (categoryId) {
            fetchSubcategories(categoryId);
        } else {
            document.getElementById('product-subcategory').innerHTML = '<option value="">Select Subcategory</option>';
        }
    });
    
    // Image upload event
    document.getElementById('product-images').addEventListener('change', handleImageUpload);
    
    // Form submit event
    document.getElementById('product-form').addEventListener('submit', handleFormSubmit);
}

// Function to handle image upload
function handleImageUpload(e) {
    const files = e.target.files;
    
    if (files.length > 5) {
        showAlert('warning', 'You can upload a maximum of 5 images.');
        return;
    }
    
    // Clear previous images
    selectedImages = [];
    document.getElementById('image-preview-container').innerHTML = '';
    
    // Process each file
    Array.from(files).forEach(file => {
        // Check if file is an image
        if (!file.type.match('image.*')) {
            showAlert('warning', 'Please upload only image files.');
            return;
        }
        
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('warning', 'Image size should not exceed 5MB.');
            return;
        }
        
        // Add to selected images
        selectedImages.push(file);
        
        // Create preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewContainer = document.getElementById('image-preview-container');
            
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'product-image-preview';
            img.alt = file.name;
            
            const removeBtn = document.createElement('div');
            removeBtn.className = 'remove-image';
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';
            removeBtn.addEventListener('click', () => {
                // Remove from selected images
                const index = selectedImages.indexOf(file);
                if (index > -1) {
                    selectedImages.splice(index, 1);
                }
                
                // Remove preview
                previewItem.remove();
            });
            
            previewItem.appendChild(img);
            previewItem.appendChild(removeBtn);
            previewContainer.appendChild(previewItem);
        };
        
        reader.readAsDataURL(file);
    });
}

// Function to handle form submit
async function handleFormSubmit(e) {
    e.preventDefault();
    
    try {
        // Show loading
        showLoading();
        
        // Validate form
        if (!validateForm()) {
            hideLoading();
            return;
        }
        
        // Get form data
        const formData = new FormData();
        formData.append('name', document.getElementById('product-name').value);
        formData.append('categoryId', document.getElementById('product-category').value);
        
        const subcategoryId = document.getElementById('product-subcategory').value;
        if (subcategoryId) {
            formData.append('subcategoryId', subcategoryId);
        }
        
        formData.append('unit', document.getElementById('product-unit').value);
        formData.append('description', document.getElementById('product-description').value);
        formData.append('price', document.getElementById('product-price').value);
        
        const discountPrice = document.getElementById('product-discount-price').value;
        if (discountPrice) {
            formData.append('discountPrice', discountPrice);
        }
        
        formData.append('stock', document.getElementById('product-stock').value);
        formData.append('isActive', document.getElementById('product-active').checked);
        
        // Add images
        selectedImages.forEach(image => {
            formData.append('images', image);
        });
        
        // Send request
        const response = await fetch(`${API_BASE_URL}/sellers/products`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: formData
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to add product');
        }
        
        // Show success message
        showAlert('success', 'Product added successfully');
        
        // Redirect to products page
        setTimeout(() => {
            window.location.href = 'products.html';
        }, 1500);
    } catch (error) {
        console.error('Add product error:', error);
        showAlert('danger', error.message || 'Failed to add product. Please try again.');
    } finally {
        hideLoading();
    }
}

// Function to validate form
function validateForm() {
    // Check required fields
    const requiredFields = [
        'product-name',
        'product-category',
        'product-description',
        'product-price',
        'product-stock',
        'product-unit'
    ];
    
    for (const field of requiredFields) {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            showAlert('warning', `Please fill in all required fields`);
            input.focus();
            return false;
        }
    }
    
    // Check price
    const price = parseFloat(document.getElementById('product-price').value);
    if (isNaN(price) || price <= 0) {
        showAlert('warning', 'Please enter a valid price');
        document.getElementById('product-price').focus();
        return false;
    }
    
    // Check discount price
    const discountPrice = document.getElementById('product-discount-price').value;
    if (discountPrice) {
        const discountPriceValue = parseFloat(discountPrice);
        if (isNaN(discountPriceValue) || discountPriceValue <= 0 || discountPriceValue >= price) {
            showAlert('warning', 'Discount price must be less than the regular price');
            document.getElementById('product-discount-price').focus();
            return false;
        }
    }
    
    // Check stock
    const stock = parseInt(document.getElementById('product-stock').value);
    if (isNaN(stock) || stock < 0) {
        showAlert('warning', 'Please enter a valid stock quantity');
        document.getElementById('product-stock').focus();
        return false;
    }
    
    // Check images
    if (selectedImages.length === 0) {
        showAlert('warning', 'Please upload at least one product image');
        document.getElementById('product-images').focus();
        return false;
    }
    
    return true;
}

// Initialize add product page when DOM is loaded
document.addEventListener('DOMContentLoaded', initAddProduct);
