// Product Ratings JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to initialize product ratings
async function initProductRatings(productId) {
    try {
        // Fetch product reviews
        const response = await fetch(`${API_BASE_URL}/reviews/product/${productId}?limit=5`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Display rating summary
        displayRatingSummary(data.ratingSummary, data.averageRating, data.totalReviews);
        
        // Display reviews
        displayReviews(data.reviews);
        
        // Initialize review pagination
        initReviewPagination(data.totalPages, data.currentPage, productId);
        
        // Initialize review filters
        initReviewFilters(productId);
        
        // Initialize review form if user is logged in
        if (isLoggedIn()) {
            initReviewForm(productId);
        } else {
            displayLoginToReview();
        }
    } catch (error) {
        console.error('Initialize product ratings error:', error);
        displayErrorMessage('Failed to load product ratings');
    }
}

// Function to display rating summary
function displayRatingSummary(ratingSummary, averageRating, totalReviews) {
    const container = document.getElementById('rating-summary');
    if (!container) return;
    
    // Calculate percentages for each rating
    const percentages = {};
    for (let i = 5; i >= 1; i--) {
        percentages[i] = totalReviews > 0 ? (ratingSummary[i] / totalReviews) * 100 : 0;
    }
    
    // Create HTML
    const html = `
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <div class="display-4 fw-bold text-success">${averageRating}</div>
                <div class="mb-2">
                    ${generateStarRating(averageRating)}
                </div>
                <p class="text-muted">${totalReviews} ratings</p>
            </div>
            <div class="col-md-8">
                ${Object.entries(percentages).map(([rating, percentage]) => `
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2">${rating} <i class="bi bi-star-fill text-warning"></i></div>
                        <div class="progress flex-grow-1" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ${percentage}%"></div>
                        </div>
                        <div class="ms-2 text-muted small">${ratingSummary[rating]}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

// Function to display reviews
function displayReviews(reviews) {
    const container = document.getElementById('product-reviews');
    if (!container) return;
    
    if (reviews.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <p class="text-muted">No reviews yet. Be the first to review this product!</p>
            </div>
        `;
        return;
    }
    
    const html = reviews.map(review => `
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <img src="${review.User.profileImage || 'https://via.placeholder.com/50x50'}" 
                            class="rounded-circle" width="50" height="50" alt="User">
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0">${review.User.firstName} ${review.User.lastName}</h6>
                        <div class="text-muted small">
                            ${formatDate(review.createdAt)}
                            ${review.isVerifiedPurchase ? ' · <span class="text-success"><i class="bi bi-patch-check-fill"></i> Verified Purchase</span>' : ''}
                        </div>
                    </div>
                </div>
                
                <div class="mb-2">
                    ${generateStarRating(review.rating)}
                    ${review.title ? `<h6 class="mt-2">${review.title}</h6>` : ''}
                </div>
                
                <p>${review.comment}</p>
                
                ${review.images && review.images.length > 0 ? `
                    <div class="review-images mb-3">
                        <div class="row g-2">
                            ${review.images.map(image => `
                                <div class="col-auto">
                                    <img src="${image}" class="img-thumbnail" width="80" height="80" alt="Review image">
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button class="btn btn-sm btn-outline-secondary helpful-btn" data-id="${review.id}">
                        <i class="bi bi-hand-thumbs-up"></i> Helpful (${review.helpfulCount})
                    </button>
                    <div class="text-muted small">
                        ${review.helpfulCount} ${review.helpfulCount === 1 ? 'person' : 'people'} found this helpful
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
    
    // Add event listeners to helpful buttons
    document.querySelectorAll('.helpful-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            if (!isLoggedIn()) {
                showLoginModal('Please login to mark reviews as helpful');
                return;
            }
            
            const reviewId = e.currentTarget.dataset.id;
            try {
                const response = await fetch(`${API_BASE_URL}/reviews/${reviewId}/helpful`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Update helpful count
                e.currentTarget.innerHTML = `<i class="bi bi-hand-thumbs-up"></i> Helpful (${data.helpfulCount})`;
                e.currentTarget.disabled = true;
                
                // Show success message
                showToast('Thank you for your feedback!', 'success');
            } catch (error) {
                console.error('Mark review as helpful error:', error);
                showToast('Failed to mark review as helpful', 'danger');
            }
        });
    });
}

// Function to initialize review pagination
function initReviewPagination(totalPages, currentPage, productId) {
    const container = document.getElementById('review-pagination');
    if (!container || totalPages <= 1) return;
    
    let html = '<nav aria-label="Review pagination"><ul class="pagination justify-content-center">';
    
    // Previous button
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Next button
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;
    
    html += '</ul></nav>';
    
    container.innerHTML = html;
    
    // Add event listeners to pagination links
    container.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const page = e.currentTarget.dataset.page;
            const filter = document.getElementById('review-filter').value;
            const sort = document.getElementById('review-sort').value;
            
            await loadReviews(productId, page, filter, sort);
        });
    });
}

// Function to initialize review filters
function initReviewFilters(productId) {
    const filterSelect = document.getElementById('review-filter');
    const sortSelect = document.getElementById('review-sort');
    
    if (!filterSelect || !sortSelect) return;
    
    // Add event listeners to filter and sort selects
    filterSelect.addEventListener('change', async () => {
        const filter = filterSelect.value;
        const sort = sortSelect.value;
        await loadReviews(productId, 1, filter, sort);
    });
    
    sortSelect.addEventListener('change', async () => {
        const filter = filterSelect.value;
        const sort = sortSelect.value;
        await loadReviews(productId, 1, filter, sort);
    });
}

// Function to load reviews with filters and pagination
async function loadReviews(productId, page, filter, sort) {
    try {
        const url = `${API_BASE_URL}/reviews/product/${productId}?page=${page}&filter=${filter}&sort=${sort}`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Display reviews
        displayReviews(data.reviews);
        
        // Update pagination
        initReviewPagination(data.totalPages, data.currentPage, productId);
        
        // Scroll to reviews section
        document.getElementById('product-reviews-section').scrollIntoView({ behavior: 'smooth' });
    } catch (error) {
        console.error('Load reviews error:', error);
        showToast('Failed to load reviews', 'danger');
    }
}

// Function to initialize review form
function initReviewForm(productId) {
    const container = document.getElementById('review-form-container');
    if (!container) return;
    
    // Check if user has already reviewed this product
    checkUserReview(productId).then(hasReviewed => {
        if (hasReviewed) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> You have already reviewed this product.
                </div>
            `;
            return;
        }
        
        // Create review form
        container.innerHTML = `
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Write a Review</h5>
                    <form id="review-form">
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <i class="bi bi-star rating-star" data-rating="1"></i>
                                <i class="bi bi-star rating-star" data-rating="2"></i>
                                <i class="bi bi-star rating-star" data-rating="3"></i>
                                <i class="bi bi-star rating-star" data-rating="4"></i>
                                <i class="bi bi-star rating-star" data-rating="5"></i>
                                <input type="hidden" name="rating" id="rating-value" value="">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="review-title" class="form-label">Title (optional)</label>
                            <input type="text" class="form-control" id="review-title" name="title">
                        </div>
                        <div class="mb-3">
                            <label for="review-comment" class="form-label">Comment</label>
                            <textarea class="form-control" id="review-comment" name="comment" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Submit Review</button>
                    </form>
                </div>
            </div>
        `;
        
        // Initialize rating stars
        initRatingStars();
        
        // Add event listener to form submission
        document.getElementById('review-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const rating = document.getElementById('rating-value').value;
            const title = document.getElementById('review-title').value;
            const comment = document.getElementById('review-comment').value;
            
            if (!rating) {
                showToast('Please select a rating', 'warning');
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE_URL}/reviews`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                    },
                    body: JSON.stringify({
                        productId,
                        rating: parseInt(rating),
                        title,
                        comment
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                // Show success message
                showToast('Review submitted successfully', 'success');
                
                // Reload reviews
                await initProductRatings(productId);
            } catch (error) {
                console.error('Submit review error:', error);
                showToast('Failed to submit review', 'danger');
            }
        });
    }).catch(error => {
        console.error('Check user review error:', error);
        displayErrorMessage('Failed to check if you have already reviewed this product');
    });
}

// Function to check if user has already reviewed the product
async function checkUserReview(productId) {
    try {
        const response = await fetch(`${API_BASE_URL}/reviews/user`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const reviews = await response.json();
        return reviews.some(review => review.productId === parseInt(productId));
    } catch (error) {
        console.error('Check user review error:', error);
        return false;
    }
}

// Function to initialize rating stars
function initRatingStars() {
    const stars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('rating-value');
    
    stars.forEach(star => {
        // Hover effect
        star.addEventListener('mouseenter', () => {
            const rating = parseInt(star.dataset.rating);
            updateStars(rating);
        });
        
        // Click event
        star.addEventListener('click', () => {
            const rating = parseInt(star.dataset.rating);
            ratingInput.value = rating;
            updateStars(rating);
        });
    });
    
    // Reset stars when mouse leaves the container
    document.querySelector('.rating-input').addEventListener('mouseleave', () => {
        const currentRating = ratingInput.value;
        updateStars(currentRating ? parseInt(currentRating) : 0);
    });
    
    // Function to update stars display
    function updateStars(rating) {
        stars.forEach(s => {
            const starRating = parseInt(s.dataset.rating);
            if (starRating <= rating) {
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill', 'text-warning');
            } else {
                s.classList.remove('bi-star-fill', 'text-warning');
                s.classList.add('bi-star');
            }
        });
    }
}

// Function to display login to review message
function displayLoginToReview() {
    const container = document.getElementById('review-form-container');
    if (!container) return;
    
    container.innerHTML = `
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <i class="bi bi-person-lock fs-1 text-muted mb-3"></i>
                <h5>Please login to write a review</h5>
                <p class="text-muted">Share your experience with this product by writing a review.</p>
                <a href="login.html?redirect=${encodeURIComponent(window.location.href)}" class="btn btn-success mt-2">
                    Login to Write a Review
                </a>
            </div>
        </div>
    `;
}

// Function to display error message
function displayErrorMessage(message) {
    const container = document.getElementById('product-reviews-section');
    if (!container) return;
    
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> ${message}
        </div>
    `;
}

// Helper function to generate star rating HTML
function generateStarRating(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
    
    let html = '';
    
    // Full stars
    for (let i = 0; i < fullStars; i++) {
        html += '<i class="bi bi-star-fill text-warning"></i>';
    }
    
    // Half star
    if (halfStar) {
        html += '<i class="bi bi-star-half text-warning"></i>';
    }
    
    // Empty stars
    for (let i = 0; i < emptyStars; i++) {
        html += '<i class="bi bi-star text-warning"></i>';
    }
    
    return html;
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Helper function to show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        // Create toast container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '5';
        document.body.appendChild(container);
    }
    
    const toastId = `toast-${Date.now()}`;
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.id = toastId;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    document.getElementById('toast-container').appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return !!localStorage.getItem('jwt_token');
}

// Helper function to show login modal
function showLoginModal(message) {
    // Check if modal already exists
    let modal = document.getElementById('login-modal');
    
    if (!modal) {
        // Create modal
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'login-modal';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'login-modal-label');
        modal.setAttribute('aria-hidden', 'true');
        
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="login-modal-label">Login Required</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="login-modal-message">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="login.html?redirect=${encodeURIComponent(window.location.href)}" class="btn btn-success">Login</a>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } else {
        // Update modal message
        document.getElementById('login-modal-message').textContent = message;
    }
    
    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}
