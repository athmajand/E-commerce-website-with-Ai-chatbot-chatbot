<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/../api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=frontend/payment.php");
    exit;
}

// Initialize variables
$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$error_message = '';
$payment_method = '';
$payment_details = '';
$order_id = 0;
$total_amount = 0;

// Process payment form submission
if (isset($_POST['process_payment'])) {
    try {
        // Create order and redirect to confirmation page
        $order_id = createOrder($db, $userId, $_SESSION['checkout_data']);

        if ($order_id) {
            // Clear checkout data from session
            unset($_SESSION['checkout_data']);

            // Redirect to order confirmation page
            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit;
        } else {
            $error_message = "Failed to create order. Please try again.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
} elseif (isset($_POST['payment_method'])) {
    // Coming from checkout.php
    $payment_method = $_POST['payment_method'];

    // Store checkout form data in session for later use
    $_SESSION['checkout_data'] = $_POST;

    // Get payment details based on method
    if ($payment_method === 'credit_card' && isset($_POST['credit_card_type'])) {
        $payment_details = 'Credit Card: ' . $_POST['credit_card_type'];
    } elseif ($payment_method === 'debit_card' && isset($_POST['debit_card_type'])) {
        $payment_details = 'Debit Card: ' . $_POST['debit_card_type'];
    } elseif ($payment_method === 'upi' && isset($_POST['upi_provider'])) {
        $payment_details = 'UPI: ' . $_POST['upi_provider'];
        if ($_POST['upi_provider'] === 'other_upi' && isset($_POST['upi_id'])) {
            $payment_details .= ' (' . $_POST['upi_id'] . ')';
        }
    } else {
        // Invalid payment method
        header("Location: checkout.php");
        exit;
    }
} else {
    // Invalid access
    header("Location: checkout.php");
    exit;
}

// Function to create order in database
function createOrder($db, $userId, $formData) {
    try {
        // Get cart items to calculate total
        $cart_items = [];
        $total_amount = 0;

        $cart_query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.discount_price, p.image_url
                      FROM cart c
                      JOIN products p ON c.product_id = p.id
                      WHERE c.customer_id = ?";
        $cart_stmt = $db->prepare($cart_query);
        $cart_stmt->bindParam(1, $userId);
        $cart_stmt->execute();

        while ($row = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
            $cart_items[] = $row;

            // Calculate item total
            $price = !empty($row['discount_price']) ? $row['discount_price'] : $row['price'];
            $item_total = $price * $row['quantity'];
            $total_amount += $item_total;
        }

        if (empty($cart_items)) {
            return false;
        }

        // Extract form data with fallbacks for missing fields
        $shipping_address = isset($formData['shipping_address']) ? $formData['shipping_address'] : '';
        $shipping_city = isset($formData['shipping_city']) ? $formData['shipping_city'] : '';
        $shipping_state = isset($formData['shipping_state']) ? $formData['shipping_state'] : '';
        $shipping_postal_code = isset($formData['shipping_postal_code']) ? $formData['shipping_postal_code'] : '';
        $delivery_instructions = isset($formData['delivery_instructions']) ? $formData['delivery_instructions'] : '';
        $payment_method = $formData['payment_method'];

        // If shipping address information is missing, try to get it from customer_registrations table
        if (empty($shipping_address) || empty($shipping_city) || empty($shipping_state) || empty($shipping_postal_code)) {
            try {
                $customer_query = "SELECT address, city, state, postal_code FROM customer_registrations WHERE id = ?";
                $customer_stmt = $db->prepare($customer_query);
                $customer_stmt->bindParam(1, $userId);
                $customer_stmt->execute();

                if ($customer_stmt->rowCount() > 0) {
                    $customer_data = $customer_stmt->fetch(PDO::FETCH_ASSOC);

                    // Use customer data as fallback if form data is missing
                    if (empty($shipping_address)) $shipping_address = $customer_data['address'];
                    if (empty($shipping_city)) $shipping_city = $customer_data['city'];
                    if (empty($shipping_state)) $shipping_state = $customer_data['state'];
                    if (empty($shipping_postal_code)) $shipping_postal_code = $customer_data['postal_code'];
                }
            } catch (PDOException $e) {
                error_log("Error fetching customer data: " . $e->getMessage());
            }
        }

        // Get payment details
        $payment_details = '';
        if ($payment_method === 'credit_card' && isset($formData['credit_card_type'])) {
            $payment_details = 'Credit Card: ' . $formData['credit_card_type'];
        } elseif ($payment_method === 'debit_card' && isset($formData['debit_card_type'])) {
            $payment_details = 'Debit Card: ' . $formData['debit_card_type'];
        } elseif ($payment_method === 'upi' && isset($formData['upi_provider'])) {
            $payment_details = 'UPI: ' . $formData['upi_provider'];
            if ($formData['upi_provider'] === 'other_upi' && isset($formData['upi_id'])) {
                $payment_details .= ' (' . $formData['upi_id'] . ')';
            }
        }

        // Begin transaction
        $db->beginTransaction();

        // Check if payment_details column exists in orders table
        $check_column_query = "SHOW COLUMNS FROM orders LIKE 'payment_details'";
        $check_column_stmt = $db->prepare($check_column_query);
        $check_column_stmt->execute();
        $payment_details_exists = ($check_column_stmt->rowCount() > 0);

        // Create order with or without payment_details based on column existence
        if ($payment_details_exists) {
            $order_query = "INSERT INTO orders (customer_id, total_amount, payment_method, payment_status,
                            shipping_address, shipping_city, shipping_state, shipping_postal_code,
                            delivery_instructions, payment_details, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $order_stmt = $db->prepare($order_query);
            $order_stmt->bindParam(1, $userId);
            $order_stmt->bindParam(2, $total_amount);
            $order_stmt->bindParam(3, $payment_method);

            // Set payment status to completed for online payments
            $payment_status = ($payment_method === 'cash_on_delivery') ? 'pending' : 'completed';
            $order_stmt->bindParam(4, $payment_status);

            $order_stmt->bindParam(5, $shipping_address);
            $order_stmt->bindParam(6, $shipping_city);
            $order_stmt->bindParam(7, $shipping_state);
            $order_stmt->bindParam(8, $shipping_postal_code);
            $order_stmt->bindParam(9, $delivery_instructions);
            $order_stmt->bindParam(10, $payment_details);
        } else {
            // Create order without payment_details column
            $order_query = "INSERT INTO orders (customer_id, total_amount, payment_method, payment_status,
                            shipping_address, shipping_city, shipping_state, shipping_postal_code,
                            delivery_instructions, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $order_stmt = $db->prepare($order_query);
            $order_stmt->bindParam(1, $userId);
            $order_stmt->bindParam(2, $total_amount);
            $order_stmt->bindParam(3, $payment_method);

            // Set payment status to completed for online payments
            $payment_status = ($payment_method === 'cash_on_delivery') ? 'pending' : 'completed';
            $order_stmt->bindParam(4, $payment_status);

            $order_stmt->bindParam(5, $shipping_address);
            $order_stmt->bindParam(6, $shipping_city);
            $order_stmt->bindParam(7, $shipping_state);
            $order_stmt->bindParam(8, $shipping_postal_code);
            $order_stmt->bindParam(9, $delivery_instructions);

            // Log that payment_details column is missing
            error_log("Warning: payment_details column is missing in orders table. Payment details not saved.");
        }

        $order_stmt->execute();

        $order_id = $db->lastInsertId();

        // Add order items
        foreach ($cart_items as $item) {
            $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];

            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price)
                          VALUES (?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(1, $order_id);
            $item_stmt->bindParam(2, $item['product_id']);
            $item_stmt->bindParam(3, $item['quantity']);
            $item_stmt->bindParam(4, $price);
            $item_stmt->execute();
        }

        // Clear cart
        $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->bindParam(1, $userId);
        $clear_cart_stmt->execute();

        // Commit transaction
        $db->commit();

        return $order_id;
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Order creation error: " . $e->getMessage());
        return false;
    }
}

// Get total amount from cart for display
try {
    $cart_query = "SELECT c.quantity, p.price, p.discount_price
                  FROM cart c
                  JOIN products p ON c.product_id = p.id
                  WHERE c.customer_id = ?";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->bindParam(1, $userId);
    $cart_stmt->execute();

    while ($row = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
        $price = !empty($row['discount_price']) ? $row['discount_price'] : $row['price'];
        $item_total = $price * $row['quantity'];
        $total_amount += $item_total;
    }
} catch (PDOException $e) {
    error_log("Error calculating total: " . $e->getMessage());
}

// Page title
$page_title = "Payment - Kisan Kart";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .payment-card {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .payment-header {
            background-color: #f8f9fa;
            border-radius: 10px 10px 0 0;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .payment-body {
            padding: 30px;
        }
        .payment-footer {
            background-color: #f8f9fa;
            border-radius: 0 0 10px 10px;
            padding: 20px;
            border-top: 1px solid #dee2e6;
        }
        .timer-container {
            text-align: center;
            margin: 20px 0;
            display: none;
        }
        .timer {
            font-size: 2rem;
            font-weight: bold;
            color: #1e8449;
        }
        .payment-success {
            text-align: center;
            display: none;
        }
        .card-input {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            margin-bottom: 15px;
        }
        .card-row {
            display: flex;
            gap: 10px;
        }
        .upi-qr {
            max-width: 200px;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-leaf text-success"></i> Kisan Kart
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-shopping-basket"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo $userName; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="customer_dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="customer_profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="customer_orders.php">Orders</a></li>
                            <li><a class="dropdown-item" href="customer_wishlist.php">Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">
                            <i class="fas fa-info-circle"></i> About Us
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Payment Processing Section -->
    <section class="py-5">
        <div class="container">
            <div class="payment-container">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <div class="text-center">
                        <a href="checkout.php" class="btn btn-success">Return to Checkout</a>
                    </div>
                <?php else: ?>
                    <div class="card payment-card">
                        <div class="payment-header">
                            <h4 class="mb-0">
                                <?php if ($payment_method === 'credit_card'): ?>
                                    <i class="far fa-credit-card me-2"></i> Credit Card Payment
                                <?php elseif ($payment_method === 'debit_card'): ?>
                                    <i class="fas fa-credit-card me-2"></i> Debit Card Payment
                                <?php elseif ($payment_method === 'upi'): ?>
                                    <i class="fas fa-mobile-alt me-2"></i> UPI Payment
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="payment-body">
                            <!-- Credit/Debit Card Payment Form -->
                            <?php if ($payment_method === 'credit_card' || $payment_method === 'debit_card'): ?>
                                <div class="payment-processing" id="card-payment-section">
                                    <h5 class="mb-3">Amount: ₹<?php echo number_format($total_amount, 2); ?></h5>

                                    <!-- Card Payment Form -->
                                    <form id="card-payment-form" method="post" action="payment.php">
                                        <div class="mb-3">
                                            <label for="card_number" class="form-label">Card Number</label>
                                            <input type="text" class="card-input" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="card_name" class="form-label">Name on Card</label>
                                            <input type="text" class="card-input" id="card_name" name="card_name" placeholder="John Doe" required>
                                        </div>
                                        <div class="card-row">
                                            <div class="mb-3" style="flex: 1;">
                                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                                <input type="text" class="card-input" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" required>
                                            </div>
                                            <div class="mb-3" style="flex: 1;">
                                                <label for="cvv" class="form-label">CVV</label>
                                                <input type="text" class="card-input" id="cvv" name="cvv" placeholder="123" maxlength="3" required>
                                            </div>
                                        </div>
                                        <input type="hidden" name="process_payment" value="1">
                                        <!-- Preserve payment method information -->
                                        <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($payment_method); ?>">
                                        <?php if ($payment_method === 'credit_card' && isset($_POST['credit_card_type'])): ?>
                                            <input type="hidden" name="credit_card_type" value="<?php echo htmlspecialchars($_POST['credit_card_type']); ?>">
                                        <?php elseif ($payment_method === 'debit_card' && isset($_POST['debit_card_type'])): ?>
                                            <input type="hidden" name="debit_card_type" value="<?php echo htmlspecialchars($_POST['debit_card_type']); ?>">
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-success w-100" id="pay-button">Pay Now</button>
                                    </form>
                                </div>

                            <!-- UPI Payment Form -->
                            <?php elseif ($payment_method === 'upi'): ?>
                                <div class="payment-form text-center" id="upi-payment-section">
                                    <h5 class="mb-3">Amount: ₹<?php echo number_format($total_amount, 2); ?></h5>

                                    <p>Scan the QR code below to make payment</p>
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="UPI QR Code" class="upi-qr mb-3">
                                    <p class="small text-muted">or pay using UPI ID: kisankart@upi</p>
                                    <div class="d-grid gap-2">
                                        <form id="upi-payment-form" method="post" action="payment.php" style="display:none;">
                                            <input type="hidden" name="process_payment" value="1">
                                            <!-- Preserve payment method information -->
                                            <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($payment_method); ?>">
                                            <?php if ($payment_method === 'upi' && isset($_POST['upi_provider'])): ?>
                                                <input type="hidden" name="upi_provider" value="<?php echo htmlspecialchars($_POST['upi_provider']); ?>">
                                                <?php if ($_POST['upi_provider'] === 'other_upi' && isset($_POST['upi_id'])): ?>
                                                    <input type="hidden" name="upi_id" value="<?php echo htmlspecialchars($_POST['upi_id']); ?>">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </form>
                                        <button type="button" class="btn btn-success" id="upi-paid-button">I've Completed the Payment</button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Payment Processing Timer -->
                            <div class="timer-container" id="timer-container">
                                <p>Verifying your payment...</p>
                                <div class="timer" id="timer">15</div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" id="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            <!-- Payment Success Message -->
                            <div class="payment-success" id="payment-success">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-3">Payment Successful!</h4>
                                <p>Your order has been placed successfully.</p>
                                <form method="post" action="payment.php" id="success-form">
                                    <input type="hidden" name="process_payment" value="1">
                                    <!-- Preserve payment method information -->
                                    <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($payment_method); ?>">
                                    <?php if ($payment_method === 'credit_card' && isset($_POST['credit_card_type'])): ?>
                                        <input type="hidden" name="credit_card_type" value="<?php echo htmlspecialchars($_POST['credit_card_type']); ?>">
                                    <?php elseif ($payment_method === 'debit_card' && isset($_POST['debit_card_type'])): ?>
                                        <input type="hidden" name="debit_card_type" value="<?php echo htmlspecialchars($_POST['debit_card_type']); ?>">
                                    <?php elseif ($payment_method === 'upi' && isset($_POST['upi_provider'])): ?>
                                        <input type="hidden" name="upi_provider" value="<?php echo htmlspecialchars($_POST['upi_provider']); ?>">
                                        <?php if ($_POST['upi_provider'] === 'other_upi' && isset($_POST['upi_id'])): ?>
                                            <input type="hidden" name="upi_id" value="<?php echo htmlspecialchars($_POST['upi_id']); ?>">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-success">Continue to Order Confirmation</button>
                                </form>
                            </div>
                        </div>
                        <div class="payment-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-lock me-2"></i> Secure Payment</span>
                                <div>
                                    <?php if ($payment_method === 'credit_card' || $payment_method === 'debit_card'): ?>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" height="20" class="me-2">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" height="20" class="me-2">
                                        <?php if ($payment_method === 'credit_card'): ?>
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/American_Express_logo_%282018%29.svg/1200px-American_Express_logo_%282018%29.svg.png" alt="American Express" height="20" class="me-2">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/Discover_Card_logo.svg/1280px-Discover_Card_logo.svg.png" alt="Discover" height="20">
                                        <?php elseif ($payment_method === 'debit_card'): ?>
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9d/RuPay.svg/1200px-RuPay.svg.png" alt="RuPay" height="20">
                                        <?php endif; ?>
                                    <?php elseif ($payment_method === 'upi'): ?>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1200px-UPI-Logo-vector.svg.png" alt="UPI" height="20" class="me-2">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Paytm_Logo_%28standalone%29.svg/1200px-Paytm_Logo_%28standalone%29.svg.png" alt="Paytm" height="20" class="me-2">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Google_Pay_Logo.svg/1200px-Google_Pay_Logo.svg.png" alt="Google Pay" height="20" class="me-2">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/PhonePe_Logo.svg/1200px-PhonePe_Logo.svg.png" alt="PhonePe" height="20">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container px-5">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Kisan Kart</h5>
                    <p>Connecting farmers and customers for a better agricultural ecosystem.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="index.php#about" class="text-white">About Us</a></li>
                        <li><a href="../login.php" class="text-white">Login</a></li>
                        <li><a href="../customer_registration.php" class="text-white">Register</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5>Contact Us</h5>
                    <p>Email: info@kisankart.com<br>
                    Phone: +91 1234567890</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="small mb-0">© 2025 Kisan Kart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Payment Processing Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Card number formatting
            const cardNumberInput = document.getElementById('card_number');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    let formattedValue = '';

                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }

                    e.target.value = formattedValue;
                });
            }

            // Expiry date formatting
            const expiryDateInput = document.getElementById('expiry_date');
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');

                    if (value.length > 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }

                    e.target.value = value;
                });
            }

            // Payment button click handler for card payments
            const payButton = document.getElementById('pay-button');
            if (payButton) {
                payButton.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Validate form
                    const form = document.getElementById('card-payment-form');
                    if (form.checkValidity()) {
                        // Hide form and show timer
                        document.getElementById('card-payment-section').style.display = 'none';
                        document.getElementById('timer-container').style.display = 'block';

                        // Start countdown
                        startCountdown();
                    } else {
                        form.reportValidity();
                    }
                });
            }

            // UPI paid button click handler
            const upiPaidButton = document.getElementById('upi-paid-button');
            if (upiPaidButton) {
                upiPaidButton.addEventListener('click', function() {
                    // Hide form and show timer
                    document.getElementById('upi-payment-section').style.display = 'none';
                    document.getElementById('timer-container').style.display = 'block';

                    // Start countdown
                    startCountdown();
                });
            }

            // Countdown function
            function startCountdown() {
                const timerElement = document.getElementById('timer');
                const progressBar = document.getElementById('progress-bar');
                let timeLeft = 15;

                const countdownInterval = setInterval(function() {
                    timeLeft--;
                    timerElement.textContent = timeLeft;

                    // Update progress bar
                    const progressWidth = (timeLeft / 15) * 100;
                    progressBar.style.width = progressWidth + '%';

                    if (timeLeft <= 0) {
                        clearInterval(countdownInterval);

                        // Show success message
                        document.getElementById('timer-container').style.display = 'none';
                        document.getElementById('payment-success').style.display = 'block';

                        // Submit the appropriate form based on payment method
                        const paymentMethod = document.querySelector('input[name="payment_method"]').value;
                        if (paymentMethod === 'upi') {
                            // For UPI, submit the hidden UPI form
                            const upiForm = document.getElementById('upi-payment-form');
                            if (upiForm) {
                                // Wait a moment to show the success message before submitting
                                setTimeout(() => {
                                    upiForm.submit();
                                }, 2000);
                            }
                        } else if (paymentMethod === 'credit_card' || paymentMethod === 'debit_card') {
                            // For card payments, submit the card form
                            const cardForm = document.getElementById('card-payment-form');
                            if (cardForm) {
                                // Wait a moment to show the success message before submitting
                                setTimeout(() => {
                                    cardForm.submit();
                                }, 2000);
                            }
                        }
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>
