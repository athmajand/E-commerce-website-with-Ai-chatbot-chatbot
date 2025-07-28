// Payment JavaScript file for Kisan Kart

// API base URL
const API_BASE_URL = 'http://localhost:8080/Kisankart/api';

// Razorpay key
const RAZORPAY_KEY = 'rzp_test_YOUR_KEY_HERE'; // Replace with your Razorpay test key

// Function to initialize payment
async function initPayment(orderId, amount, callback) {
    try {
        // Create Razorpay order
        const response = await fetch(`${API_BASE_URL}/payments/create-order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({
                orderId,
                amount
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to create payment order');
        }
        
        const data = await response.json();
        
        // Initialize Razorpay
        const options = {
            key: RAZORPAY_KEY,
            amount: data.amount,
            currency: data.currency,
            name: 'Kisan Kart',
            description: `Order #${orderId}`,
            order_id: data.id,
            handler: function(response) {
                // Verify payment
                verifyPayment(response, orderId, callback);
            },
            prefill: {
                name: localStorage.getItem('firstName') + ' ' + localStorage.getItem('lastName'),
                email: localStorage.getItem('username'),
                contact: localStorage.getItem('phone') || ''
            },
            theme: {
                color: '#198754'
            },
            modal: {
                ondismiss: function() {
                    console.log('Payment dismissed');
                    if (callback && typeof callback === 'function') {
                        callback(false, 'Payment cancelled');
                    }
                }
            }
        };
        
        const razorpay = new Razorpay(options);
        razorpay.open();
    } catch (error) {
        console.error('Payment initialization error:', error);
        if (callback && typeof callback === 'function') {
            callback(false, error.message || 'Failed to initialize payment');
        }
    }
}

// Function to verify payment
async function verifyPayment(paymentResponse, orderId, callback) {
    try {
        const response = await fetch(`${API_BASE_URL}/payments/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({
                orderId,
                paymentId: paymentResponse.razorpay_payment_id,
                signature: paymentResponse.razorpay_signature,
                razorpayOrderId: paymentResponse.razorpay_order_id
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to verify payment');
        }
        
        const data = await response.json();
        
        if (callback && typeof callback === 'function') {
            callback(true, data);
        }
    } catch (error) {
        console.error('Payment verification error:', error);
        if (callback && typeof callback === 'function') {
            callback(false, error.message || 'Failed to verify payment');
        }
    }
}

// Function to process Cash on Delivery
async function processCOD(orderId, callback) {
    try {
        const response = await fetch(`${API_BASE_URL}/payments/cod`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            },
            body: JSON.stringify({
                orderId
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to process Cash on Delivery');
        }
        
        const data = await response.json();
        
        if (callback && typeof callback === 'function') {
            callback(true, data);
        }
    } catch (error) {
        console.error('COD processing error:', error);
        if (callback && typeof callback === 'function') {
            callback(false, error.message || 'Failed to process Cash on Delivery');
        }
    }
}

// Function to get payment methods
async function getPaymentMethods() {
    try {
        const response = await fetch(`${API_BASE_URL}/payments/methods`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch payment methods');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Get payment methods error:', error);
        return [
            { id: 'razorpay', name: 'Credit/Debit Card', icon: 'bi-credit-card' },
            { id: 'upi', name: 'UPI', icon: 'bi-phone' },
            { id: 'netbanking', name: 'Net Banking', icon: 'bi-bank' },
            { id: 'wallet', name: 'Wallet', icon: 'bi-wallet2' },
            { id: 'cod', name: 'Cash on Delivery', icon: 'bi-cash' }
        ];
    }
}

// Function to get payment status
async function getPaymentStatus(orderId) {
    try {
        const response = await fetch(`${API_BASE_URL}/payments/status/${orderId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch payment status');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Get payment status error:', error);
        return { status: 'unknown' };
    }
}

// Function to handle payment selection
function handlePaymentSelection(paymentMethod, orderId, amount, callback) {
    if (paymentMethod === 'cod') {
        processCOD(orderId, callback);
    } else {
        initPayment(orderId, amount, callback);
    }
}

// Function to format payment amount
function formatPaymentAmount(amount) {
    return (amount / 100).toFixed(2);
}

// Function to load Razorpay script
function loadRazorpayScript(callback) {
    const script = document.createElement('script');
    script.src = 'https://checkout.razorpay.com/v1/checkout.js';
    script.async = true;
    script.onload = callback;
    document.body.appendChild(script);
}

// Export functions
window.KisanKartPayment = {
    initPayment,
    processCOD,
    getPaymentMethods,
    getPaymentStatus,
    handlePaymentSelection,
    loadRazorpayScript
};
