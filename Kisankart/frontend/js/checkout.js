// Checkout JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Function to initialize checkout page
async function initCheckout() {
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            displayLoginMessage();
            return;
        }

        // Fetch cart and user addresses in parallel
        const [cartResponse, addressesResponse] = await Promise.all([
            fetch(`${API_BASE_URL}/cart`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            }),
            fetch(`${API_BASE_URL}/users/addresses`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            })
        ]);

        if (!cartResponse.ok || !addressesResponse.ok) {
            throw new Error('Failed to fetch checkout data');
        }

        const cartData = await cartResponse.json();
        const addressesData = await addressesResponse.json();

        // Check if cart is empty
        if (!cartData.items || cartData.items.length === 0) {
            displayEmptyCartMessage();
            return;
        }

        // Display checkout form
        displayCheckoutForm(cartData, addressesData);
    } catch (error) {
        console.error('Checkout initialization error:', error);
        displayErrorMessage();
    }
}

// Function to display checkout form
function displayCheckoutForm(cartData, addressesData) {
    const checkoutContainer = document.getElementById('checkout-container');

    // Format total price
    const formattedTotal = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(cartData.total);

    // Create HTML for order summary
    let orderSummaryHTML = '';
    cartData.items.forEach(item => {
        const product = item.Product;

        // Format price
        const formattedPrice = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 2
        }).format(item.price);

        // Format subtotal
        const formattedSubtotal = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 2
        }).format(item.price * item.quantity);

        orderSummaryHTML += `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="mb-0">${product.name}</h6>
                    <small class="text-muted">${formattedPrice} x ${item.quantity}</small>
                </div>
                <span>${formattedSubtotal}</span>
            </div>
        `;
    });

    // Create HTML for address options
    let addressOptionsHTML = '';
    if (addressesData && addressesData.length > 0) {
        addressesData.forEach(address => {
            addressOptionsHTML += `
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="address" id="address-${address.id}" value="${address.id}" ${addressesData[0].id === address.id ? 'checked' : ''}>
                    <label class="form-check-label" for="address-${address.id}">
                        <strong>${address.name}</strong><br>
                        ${address.street}, ${address.city}, ${address.state} ${address.postalCode}<br>
                        Phone: ${address.phone}
                    </label>
                </div>
            `;
        });
    }

    // Add new address form
    addressOptionsHTML += `
        <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="address" id="address-new" value="new" ${addressesData.length === 0 ? 'checked' : ''}>
            <label class="form-check-label" for="address-new">
                <strong>Add a new address</strong>
            </label>
        </div>

        <div id="new-address-form" class="card p-3 mt-3 mb-3" ${addressesData.length > 0 ? 'style="display: none;"' : ''}>
            <h6 class="mb-3">New Address</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="address-name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="address-name" required>
                </div>
                <div class="col-md-6">
                    <label for="address-phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="address-phone" required>
                </div>
                <div class="col-12">
                    <label for="address-street" class="form-label">Street Address</label>
                    <input type="text" class="form-control" id="address-street" required>
                </div>
                <div class="col-md-6">
                    <label for="address-city" class="form-label">City</label>
                    <input type="text" class="form-control" id="address-city" required>
                </div>
                <div class="col-md-4">
                    <label for="address-state" class="form-label">State</label>
                    <input type="text" class="form-control" id="address-state" required>
                </div>
                <div class="col-md-2">
                    <label for="address-postal-code" class="form-label">Postal Code</label>
                    <input type="text" class="form-control" id="address-postal-code" required>
                </div>
            </div>
        </div>
    `;

    // Create checkout form HTML
    const checkoutHTML = `
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Shipping Address</h5>
                        <form id="checkout-form">
                            ${addressOptionsHTML}
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Payment Method</h5>
                        <div id="payment-methods-container">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted small">Loading payment methods...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Order Summary</h5>
                        ${orderSummaryHTML}
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>${formattedTotal}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong class="text-success">${formattedTotal}</strong>
                        </div>
                        <button type="button" class="btn btn-success w-100 py-2" id="place-order-btn">
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    checkoutContainer.innerHTML = checkoutHTML;

    // Add event listeners
    addCheckoutEventListeners();
}

// Function to add event listeners to checkout elements
function addCheckoutEventListeners() {
    // Address radio buttons
    const addressRadios = document.querySelectorAll('input[name="address"]');
    addressRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            const newAddressForm = document.getElementById('new-address-form');
            if (radio.value === 'new') {
                newAddressForm.style.display = 'block';
            } else {
                newAddressForm.style.display = 'none';
            }
        });
    });

    // Load payment methods
    loadPaymentMethods();

    // Place order button
    const placeOrderBtn = document.getElementById('place-order-btn');
    placeOrderBtn.addEventListener('click', placeOrder);
}

// Function to load payment methods
async function loadPaymentMethods() {
    try {
        const paymentMethodsContainer = document.getElementById('payment-methods-container');

        // Get payment methods
        const paymentMethods = await window.KisanKartPayment.getPaymentMethods();

        let paymentMethodsHTML = '';

        paymentMethods.forEach((method, index) => {
            paymentMethodsHTML += `
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment-method"
                           id="payment-${method.id}" value="${method.id}" ${index === 0 ? 'checked' : ''}>
                    <label class="form-check-label" for="payment-${method.id}">
                        <i class="bi ${method.icon} me-2"></i> ${method.name}
                    </label>
                </div>
            `;
        });

        paymentMethodsContainer.innerHTML = paymentMethodsHTML;

        // Add event listeners to payment method radios
        const paymentRadios = document.querySelectorAll('input[name="payment-method"]');
        paymentRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                // You can add specific UI changes based on payment method if needed
            });
        });
    } catch (error) {
        console.error('Load payment methods error:', error);

        // Fallback to basic payment methods
        const paymentMethodsContainer = document.getElementById('payment-methods-container');

        paymentMethodsContainer.innerHTML = `
            <div class="form-check mb-3">
                <input class="form-check-input" type="radio" name="payment-method" id="payment-cod" value="cod" checked>
                <label class="form-check-label" for="payment-cod">
                    <i class="bi bi-cash me-2"></i> Cash on Delivery (COD)
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="radio" name="payment-method" id="payment-razorpay" value="razorpay">
                <label class="form-check-label" for="payment-razorpay">
                    <i class="bi bi-credit-card me-2"></i> Online Payment (Credit/Debit Card, UPI, etc.)
                </label>
            </div>
        `;
    }
}

// Function to place order
async function placeOrder() {
    try {
        // Show loading state
        const placeOrderBtn = document.getElementById('place-order-btn');
        placeOrderBtn.disabled = true;
        placeOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        // Get selected address
        const selectedAddressRadio = document.querySelector('input[name="address"]:checked');
        if (!selectedAddressRadio) {
            showAlert('danger', 'Please select or add a shipping address');
            placeOrderBtn.disabled = false;
            placeOrderBtn.innerHTML = 'Place Order';
            return;
        }

        let addressId;

        // If new address is selected, create it first
        if (selectedAddressRadio.value === 'new') {
            const addressData = {
                name: document.getElementById('address-name').value,
                phone: document.getElementById('address-phone').value,
                street: document.getElementById('address-street').value,
                city: document.getElementById('address-city').value,
                state: document.getElementById('address-state').value,
                postalCode: document.getElementById('address-postal-code').value
            };

            // Validate address fields
            for (const [key, value] of Object.entries(addressData)) {
                if (!value) {
                    showAlert('danger', `Please fill in all address fields`);
                    placeOrderBtn.disabled = false;
                    placeOrderBtn.innerHTML = 'Place Order';
                    return;
                }
            }

            // Create new address
            const addressResponse = await fetch(`${API_BASE_URL}/users/addresses`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                },
                body: JSON.stringify(addressData)
            });

            if (!addressResponse.ok) {
                throw new Error('Failed to create address');
            }

            const newAddress = await addressResponse.json();
            addressId = newAddress.id;
        } else {
            addressId = selectedAddressRadio.value;
        }

        // Get selected payment method
        const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;

        // Create order
        const orderResponse = await fetch(`${API_BASE_URL}/orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({
                addressId,
                paymentMethod
            })
        });

        if (!orderResponse.ok) {
            throw new Error('Failed to create order');
        }

        const orderData = await orderResponse.json();

        // Handle payment based on selected method
        if (paymentMethod === 'cod') {
            // Process Cash on Delivery
            window.KisanKartPayment.processCOD(orderData.id, (success, data) => {
                if (success) {
                    // Redirect to order confirmation page
                    window.location.href = `order-confirmation.html?id=${orderData.id}`;
                } else {
                    throw new Error(data || 'Failed to process Cash on Delivery');
                }
            });
        } else if (paymentMethod === 'razorpay' || paymentMethod === 'online') {
            // Process online payment
            window.KisanKartPayment.handlePaymentSelection(
                paymentMethod,
                orderData.id,
                orderData.total * 100, // Convert to paise for Razorpay
                (success, data) => {
                    if (success) {
                        // Redirect to order confirmation page
                        window.location.href = `order-confirmation.html?id=${orderData.id}`;
                    } else {
                        throw new Error(data || 'Failed to process online payment');
                    }
                }
            );
        } else {
            // Redirect to order confirmation page for other payment methods
            window.location.href = `order-confirmation.html?id=${orderData.id}`;
        }
    } catch (error) {
        console.error('Place order error:', error);
        showAlert('danger', 'Failed to place order. Please try again.');

        // Reset button state
        const placeOrderBtn = document.getElementById('place-order-btn');
        placeOrderBtn.disabled = false;
        placeOrderBtn.innerHTML = 'Place Order';
    }
}

// Function to display empty cart message
function displayEmptyCartMessage() {
    const checkoutContainer = document.getElementById('checkout-container');

    checkoutContainer.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-cart-x fs-1 text-muted mb-3"></i>
            <h4>Your cart is empty</h4>
            <p class="text-muted">You need to add products to your cart before checkout.</p>
            <a href="products.html" class="btn btn-success mt-3">
                <i class="bi bi-arrow-left"></i> Browse Products
            </a>
        </div>
    `;
}

// Function to display login message
function displayLoginMessage() {
    const checkoutContainer = document.getElementById('checkout-container');

    checkoutContainer.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-person-lock fs-1 text-muted mb-3"></i>
            <h4>Please login to proceed with checkout</h4>
            <p class="text-muted">You need to be logged in to complete your purchase.</p>
            <a href="login.html?redirect=checkout.html" class="btn btn-success mt-3">
                Login Now
            </a>
        </div>
    `;
}

// Function to display error message
function displayErrorMessage() {
    const checkoutContainer = document.getElementById('checkout-container');

    checkoutContainer.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
            <h4>Oops! Something went wrong</h4>
            <p class="text-muted">We couldn't load the checkout page. Please try again later.</p>
            <button class="btn btn-outline-success mt-3" onclick="initCheckout()">
                <i class="bi bi-arrow-clockwise"></i> Try Again
            </button>
        </div>
    `;
}

// Function to show alert
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '1050';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);

    // Auto dismiss after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initCheckout();
    updateNavigation();
});
