// Seller Products JavaScript file for Kisan Kart

// Global variables
let currentPage = 1;
let totalPages = 1;
let searchQuery = '';
let categoryFilter = '';
let statusFilter = '';
let productToDelete = null;

// Function to initialize products page
async function initProducts() {
    try {
        // Fetch categories for filter
        await fetchCategories();
        
        // Fetch products
        await fetchProducts();
        
        // Add event listeners
        addEventListeners();
    } catch (error) {
        console.error('Products initialization error:', error);
        showAlert('danger', 'Failed to load products. Please try again.');
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
        
        const categories = await response.json();
        
        // Populate category filter dropdown
        const categoryFilter = document.getElementById('category-filter');
        
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            categoryFilter.appendChild(option);
        });
    } catch (error) {
        console.error('Fetch categories error:', error);
    }
}

// Function to fetch products
async function fetchProducts() {
    try {
        // Show loading spinner
        document.getElementById('products-container').innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Build query parameters
        let queryParams = new URLSearchParams();
        queryParams.append('page', currentPage);
        queryParams.append('limit', 10);
        
        if (searchQuery) {
            queryParams.append('search', searchQuery);
        }
        
        if (categoryFilter) {
            queryParams.append('categoryId', categoryFilter);
        }
        
        if (statusFilter) {
            queryParams.append('status', statusFilter);
        }
        
        const response = await fetch(`${API_BASE_URL}/sellers/products?${queryParams.toString()}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch products');
        }
        
        const data = await response.json();
        
        // Update global variables
        totalPages = data.totalPages || 1;
        
        // Display products
        displayProducts(data.products);
        
        // Update pagination
        updatePagination();
    } catch (error) {
        console.error('Fetch products error:', error);
        document.getElementById('products-container').innerHTML = `
            <div class="text-center py-3">
                <p class="text-danger">Failed to load products. Please try again.</p>
                <button class="btn btn-outline-success mt-2" onclick="fetchProducts()">
                    <i class="bi bi-arrow-clockwise"></i> Retry
                </button>
            </div>
        `;
        document.getElementById('pagination-container').innerHTML = '';
    }
}

// Function to display products
function displayProducts(products) {
    const container = document.getElementById('products-container');
    
    if (!products || products.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3">
                <p class="text-muted">No products found.</p>
                <a href="add-product.html" class="btn btn-success mt-2">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </a>
            </div>
        `;
        return;
    }
    
    let html = `
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    products.forEach(product => {
        // Get product image
        const productImage = product.images && product.images.length > 0 
            ? product.images[0] 
            : 'https://via.placeholder.com/50x50?text=No+Image';
        
        // Format price
        const price = formatCurrency(product.price);
        
        // Get stock status
        let stockStatus = '';
        let stockClass = '';
        
        if (product.stock <= 0) {
            stockStatus = 'Out of Stock';
            stockClass = 'bg-danger';
        } else if (product.stock <= 10) {
            stockStatus = 'Low Stock';
            stockClass = 'bg-warning text-dark';
        } else {
            stockStatus = 'In Stock';
            stockClass = 'bg-success';
        }
        
        // Get product status
        const isActive = product.isActive ? 'Active' : 'Inactive';
        const statusClass = product.isActive ? 'bg-success' : 'bg-secondary';
        
        html += `
            <tr data-id="${product.id}">
                <td data-label="Image">
                    <img src="${productImage}" alt="${product.name}" width="50" height="50" class="img-thumbnail"
                        onerror="this.src='https://via.placeholder.com/50x50?text=No+Image'">
                </td>
                <td data-label="Name">${product.name}</td>
                <td data-label="Category">${product.Category ? product.Category.name : 'Uncategorized'}</td>
                <td data-label="Price">${price}</td>
                <td data-label="Stock">${product.stock} ${product.unit}</td>
                <td data-label="Status">
                    <span class="badge ${statusClass}">${isActive}</span>
                    <span class="badge ${stockClass}">${stockStatus}</span>
                </td>
                <td data-label="Actions">
                    <div class="btn-group">
                        <a href="edit-product.html?id=${product.id}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-danger delete-product-btn" data-id="${product.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
    
    // Add event listeners to delete buttons
    document.querySelectorAll('.delete-product-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = e.currentTarget.dataset.id;
            showDeleteModal(productId);
        });
    });
}

// Function to update pagination
function updatePagination() {
    const paginationContainer = document.getElementById('pagination-container');
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // Previous button
    paginationHTML += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, startPage + 4);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Next button
    paginationHTML += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
    
    // Add event listeners to pagination links
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(e.target.closest('.page-link').dataset.page);
            if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                currentPage = page;
                fetchProducts();
                // Scroll to top of products section
                document.querySelector('.card.shadow').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

// Function to add event listeners
function addEventListeners() {
    // Search button
    document.getElementById('search-button').addEventListener('click', () => {
        searchQuery = document.getElementById('search-input').value.trim();
        currentPage = 1;
        fetchProducts();
    });
    
    // Search input (Enter key)
    document.getElementById('search-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchQuery = e.target.value.trim();
            currentPage = 1;
            fetchProducts();
        }
    });
    
    // Filter button
    document.getElementById('filter-button').addEventListener('click', () => {
        categoryFilter = document.getElementById('category-filter').value;
        statusFilter = document.getElementById('status-filter').value;
        currentPage = 1;
        fetchProducts();
    });
    
    // Delete product confirmation
    document.getElementById('confirm-delete-btn').addEventListener('click', deleteProduct);
}

// Function to show delete modal
function showDeleteModal(productId) {
    productToDelete = productId;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
    deleteModal.show();
}

// Function to delete product
async function deleteProduct() {
    if (!productToDelete) return;
    
    try {
        const response = await fetch(`${API_BASE_URL}/sellers/products/${productToDelete}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to delete product');
        }
        
        // Hide modal
        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteProductModal'));
        deleteModal.hide();
        
        // Show success message
        showAlert('success', 'Product deleted successfully');
        
        // Refresh products
        fetchProducts();
    } catch (error) {
        console.error('Delete product error:', error);
        showAlert('danger', 'Failed to delete product. Please try again.');
    } finally {
        productToDelete = null;
    }
}

// Initialize products page when DOM is loaded
document.addEventListener('DOMContentLoaded', initProducts);
