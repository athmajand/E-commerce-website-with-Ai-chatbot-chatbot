<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/product-cards.css">
    <style>
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4 debug-info">
        <h3>Debug Information</h3>
        <p>This page is for debugging the products section issue.</p>
    </div>

    <!-- Featured Products Section -->
    <section class="py-5" id="products">
        <div class="container px-5">
            <h2 class="text-center mb-5">Featured Products</h2>
            <div class="row" id="featured-products">
                <!-- Static product cards to test if the issue persists with static content -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="#" class="text-decoration-none">
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-13%</span>
                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top product-image" alt="Fresh Tomatoes">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Fresh Tomatoes</h5>
                                <p class="card-text text-muted small">Organically grown fresh tomatoes from local farms....</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-decoration-line-through text-muted small">₹40.00</span><br>
                                        <span class="fs-5 fw-bold text-success">₹35.00</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="3">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="3">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <a href="#" class="text-decoration-none">
                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top product-image" alt="Organic Potatoes">
                            <div class="card-body">
                                <h5 class="card-title text-dark">Organic Potatoes</h5>
                                <p class="card-text text-muted small">Premium quality potatoes grown without pesticides....</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fs-5 fw-bold text-success">₹30.00</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                            <button class="btn btn-success flex-grow-1 me-2 add-to-cart-btn" data-product-id="4">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-success add-to-wishlist-btn" data-product-id="4">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Debug Script -->
    <script>
    // Debug script to monitor for any changes to the featured-products section
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Debug script loaded');
        
        // Monitor the featured-products section for changes
        const productsContainer = document.getElementById('featured-products');
        
        if (productsContainer) {
            console.log('Products container found');
            
            // Create a MutationObserver to watch for changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    console.log('Mutation detected:', mutation.type);
                    if (mutation.type === 'childList') {
                        console.log('Child nodes changed');
                        console.log('Added nodes:', mutation.addedNodes.length);
                        console.log('Removed nodes:', mutation.removedNodes.length);
                    }
                });
            });
            
            // Start observing the target node for configured mutations
            observer.observe(productsContainer, { 
                childList: true,  // observe direct children
                subtree: true,    // and lower descendants too
                attributes: true  // observe attribute changes
            });
            
            console.log('Observer started');
        } else {
            console.log('Products container not found');
        }
    });
    </script>
</body>
</html>
