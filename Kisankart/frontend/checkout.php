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
    header("Location: ../login.php?redirect=frontend/checkout.php");
    exit;
}

// Initialize variables
$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$cart_items = [];
$total_amount = 0;
$error_message = '';
$success_message = '';

// Get customer data from customer_registrations table
$customer_data = [];
try {
    $customer_query = "SELECT address, city, state, postal_code FROM customer_registrations WHERE id = ?";
    $customer_stmt = $db->prepare($customer_query);
    $customer_stmt->bindParam(1, $userId);
    $customer_stmt->execute();

    if ($customer_stmt->rowCount() > 0) {
        $customer_data = $customer_stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Silently handle error - we'll just not pre-fill the form
    error_log("Error fetching customer data: " . $e->getMessage());
}

// Get cart items
try {
    // Check if cart table exists
    $check_table_query = "SHOW TABLES LIKE 'cart'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() > 0) {
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
    }
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Get form data
    $shipping_address = $_POST['shipping_address'];
    $shipping_city = $_POST['shipping_city'];
    $shipping_state = $_POST['shipping_state'];
    $shipping_postal_code = $_POST['shipping_postal_code'];
    $delivery_instructions = isset($_POST['delivery_instructions']) ? $_POST['delivery_instructions'] : '';
    $payment_method = $_POST['payment_method'];

    // Validate payment method
    $valid_payment_methods = ['cash_on_delivery', 'credit_card', 'debit_card', 'upi'];
    if (!in_array($payment_method, $valid_payment_methods)) {
        $error_message = "Invalid payment method selected.";
    } else {
        try {
            // Check if orders table exists
            $check_table_query = "SHOW TABLES LIKE 'orders'";
            $check_table_stmt = $db->prepare($check_table_query);
            $check_table_stmt->execute();

            if ($check_table_stmt->rowCount() === 0) {
                // Create orders table if it doesn't exist
                $create_table_query = "CREATE TABLE `orders` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `customer_id` int(11) NOT NULL,
                    `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
                    `total_amount` decimal(10,2) NOT NULL,
                    `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
                    `payment_method` varchar(50) DEFAULT NULL,
                    `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
                    `shipping_address` text,
                    `shipping_city` varchar(100) DEFAULT NULL,
                    `shipping_state` varchar(100) DEFAULT NULL,
                    `shipping_postal_code` varchar(20) DEFAULT NULL,
                    `delivery_instructions` text,
                    `payment_details` varchar(255) DEFAULT NULL,
                    `tracking_number` varchar(100) DEFAULT NULL,
                    `notes` text,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `customer_id` (`customer_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                $db->exec($create_table_query);
            }

            // Check if order_items table exists
            $check_table_query = "SHOW TABLES LIKE 'order_items'";
            $check_table_stmt = $db->prepare($check_table_query);
            $check_table_stmt->execute();

            if ($check_table_stmt->rowCount() === 0) {
                // Create order_items table if it doesn't exist
                $create_table_query = "CREATE TABLE `order_items` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `order_id` int(11) NOT NULL,
                    `product_id` int(11) NOT NULL,
                    `quantity` int(11) NOT NULL,
                    `price` decimal(10,2) NOT NULL,
                    `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `order_id` (`order_id`),
                    KEY `product_id` (`product_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                $db->exec($create_table_query);
            }

            // For Cash on Delivery, process the order directly
            if ($payment_method === 'cash_on_delivery') {
                // Begin transaction
                $db->beginTransaction();

                // Get payment details
                $payment_details = 'Cash on Delivery';

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
                                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, 'pending')";
                    $order_stmt = $db->prepare($order_query);
                    $order_stmt->bindParam(1, $userId);
                    $order_stmt->bindParam(2, $total_amount);
                    $order_stmt->bindParam(3, $payment_method);
                    $order_stmt->bindParam(4, $shipping_address);
                    $order_stmt->bindParam(5, $shipping_city);
                    $order_stmt->bindParam(6, $shipping_state);
                    $order_stmt->bindParam(7, $shipping_postal_code);
                    $order_stmt->bindParam(8, $delivery_instructions);
                    $order_stmt->bindParam(9, $payment_details);
                } else {
                    // Create order without payment_details column
                    $order_query = "INSERT INTO orders (customer_id, total_amount, payment_method, payment_status,
                                    shipping_address, shipping_city, shipping_state, shipping_postal_code,
                                    delivery_instructions, status)
                                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, 'pending')";
                    $order_stmt = $db->prepare($order_query);
                    $order_stmt->bindParam(1, $userId);
                    $order_stmt->bindParam(2, $total_amount);
                    $order_stmt->bindParam(3, $payment_method);
                    $order_stmt->bindParam(4, $shipping_address);
                    $order_stmt->bindParam(5, $shipping_city);
                    $order_stmt->bindParam(6, $shipping_state);
                    $order_stmt->bindParam(7, $shipping_postal_code);
                    $order_stmt->bindParam(8, $delivery_instructions);

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

                // Redirect to order confirmation page
                header("Location: order_confirmation.php?order_id=" . $order_id);
                exit;
            } else if ($payment_method === 'credit_card' || $payment_method === 'debit_card' || $payment_method === 'upi') {
                // For online payments, redirect to unified payment page
                header("Location: payment.php");
                exit;
            }
        } catch (PDOException $e) {
            // Rollback transaction on error if transaction was started
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Page title
$page_title = "Checkout - Kisan Kart";
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
        /* Payment method styles */
        .payment-method-section .form-check {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .payment-method-section .form-check:hover {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }

        .payment-method-section .form-check-input:checked + .form-check-label {
            font-weight: 500;
        }

        .payment-method-section .form-check-input:checked + .form-check-label .payment-icon {
            color: #1e8449;
        }

        .payment-method-section .form-check-input:checked ~ .form-check {
            border-color: #1e8449;
            box-shadow: 0 0 0 0.2rem rgba(30, 132, 73, 0.25);
        }

        .payment-logo {
            height: 24px;
            object-fit: contain;
            margin-right: 5px;
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
                        <a class="nav-link active" href="customer_cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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

    <!-- Checkout Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="mb-4">Checkout</h2>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="alert alert-info" role="alert">
                    Your cart is empty. <a href="products.php" class="alert-link">Continue shopping</a>.
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Order Summary -->
                    <div class="col-md-4 order-md-2 mb-4">
                        <h4 class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-success">Your Cart</span>
                            <span class="badge bg-success rounded-pill"><?php echo count($cart_items); ?></span>
                        </h4>
                        <ul class="list-group mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <?php
                                $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
                                $item_total = $price * $item['quantity'];
                                ?>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div>
                                        <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span class="text-muted">₹<?php echo number_format($item_total, 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total (INR)</span>
                                <strong>₹<?php echo number_format($total_amount, 2); ?></strong>
                            </li>
                        </ul>
                    </div>

                    <!-- Checkout Form -->
                    <div class="col-md-8 order-md-1">
                        <h4 class="mb-3">Shipping Address</h4>
                        <?php if (!empty($customer_data['address'])): ?>
                        <div class="alert alert-info mb-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i> Your address has been pre-filled from your profile. You can edit it if needed.
                        </div>
                        <?php endif; ?>
                        <form method="post" id="checkout-form" action="checkout.php">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="shipping_address" class="form-label">Address</label>
                                    <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($customer_data['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="col-md-4">
                                    <label for="shipping_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($customer_data['city'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="shipping_state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="shipping_state" name="shipping_state" value="<?php echo htmlspecialchars($customer_data['state'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="shipping_postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="shipping_postal_code" name="shipping_postal_code" value="<?php echo htmlspecialchars($customer_data['postal_code'] ?? ''); ?>" required>
                                    <div id="pincode-status" class="mt-2"></div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <label for="delivery_instructions" class="form-label">Delivery Instructions (Optional)</label>
                                    <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="2" placeholder="Special instructions for delivery (e.g., landmark, preferred delivery time, etc.)"></textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h4 class="mb-3">Payment Method</h4>
                            <div class="my-3 payment-method-section">
                                <div class="form-check">
                                    <input id="cash_on_delivery" name="payment_method" type="radio" class="form-check-input" value="cash_on_delivery" checked required>
                                    <label class="form-check-label" for="cash_on_delivery">
                                        <span class="d-flex align-items-center">
                                            <i class="fas fa-money-bill-wave me-2 text-success payment-icon"></i> Cash on Delivery
                                        </span>
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input id="credit_card" name="payment_method" type="radio" class="form-check-input" value="credit_card" required>
                                    <label class="form-check-label" for="credit_card">
                                        <span class="d-flex align-items-center">
                                            <i class="far fa-credit-card me-2 text-primary payment-icon"></i> Credit Card
                                            <span class="ms-2 d-flex align-items-center">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" class="payment-logo" style="width:40px;">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" class="payment-logo" style="width:32px;">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/American_Express_logo_%282018%29.svg/1200px-American_Express_logo_%282018%29.svg.png" alt="American Express" class="payment-logo" style="width:32px;">
                                            </span>
                                        </span>
                                    </label>
                                    <div class="credit-card-options mt-2 ms-4" style="display: none;">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="credit_card_type" id="visa_credit" value="visa">
                                            <label class="form-check-label" for="visa_credit">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" width="40" height="20">
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="credit_card_type" id="mastercard_credit" value="mastercard">
                                            <label class="form-check-label" for="mastercard_credit">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" width="32" height="20">
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="credit_card_type" id="amex_credit" value="amex">
                                            <label class="form-check-label" for="amex_credit">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/American_Express_logo_%282018%29.svg/1200px-American_Express_logo_%282018%29.svg.png" alt="American Express" width="32" height="20">
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check">
                                    <input id="debit_card" name="payment_method" type="radio" class="form-check-input" value="debit_card" required>
                                    <label class="form-check-label" for="debit_card">
                                        <span class="d-flex align-items-center">
                                            <i class="fas fa-credit-card me-2 text-info payment-icon"></i> Debit Card
                                            <span class="ms-2 d-flex align-items-center">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" class="payment-logo" style="width:40px;">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" class="payment-logo" style="width:32px;">
                                            </span>
                                        </span>
                                    </label>
                                    <div class="debit-card-options mt-2 ms-4" style="display: none;">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="debit_card_type" id="visa_debit" value="visa">
                                            <label class="form-check-label" for="visa_debit">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" width="40" height="20">
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="debit_card_type" id="mastercard_debit" value="mastercard">
                                            <label class="form-check-label" for="mastercard_debit">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" width="32" height="20">
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check">
                                    <input id="upi" name="payment_method" type="radio" class="form-check-input" value="upi" required>
                                    <label class="form-check-label" for="upi">
                                        <span class="d-flex align-items-center">
                                            <i class="fas fa-mobile-alt me-2 text-success payment-icon"></i> UPI
                                            <span class="ms-2 d-flex align-items-center">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1200px-UPI-Logo-vector.svg.png" alt="UPI" class="payment-logo" style="width:32px;">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Google_Pay_Logo.svg/512px-Google_Pay_Logo.svg.png" alt="Google Pay" class="payment-logo" style="width:32px;">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Paytm_Logo_%28standalone%29.svg/1200px-Paytm_Logo_%28standalone%29.svg.png" alt="Paytm" class="payment-logo" style="width:32px;">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/PhonePe_Logo.svg/1200px-PhonePe_Logo.svg.png" alt="PhonePe" class="payment-logo" style="width:20px;">
                                            </span>
                                        </span>
                                    </label>
                                    <div class="upi-options mt-2 ms-4" style="display: none;">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="upi_provider" id="google_pay" value="google_pay">
                                            <label class="form-check-label" for="google_pay">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Google_Pay_Logo.svg/512px-Google_Pay_Logo.svg.png" alt="Google Pay" width="40" height="20">
                                                <span class="ms-1">Google Pay</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="upi_provider" id="paytm" value="paytm">
                                            <label class="form-check-label" for="paytm">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Paytm_Logo_%28standalone%29.svg/1200px-Paytm_Logo_%28standalone%29.svg.png" alt="Paytm" width="40" height="20">
                                                <span class="ms-1">Paytm</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="upi_provider" id="phonepe" value="phonepe">
                                            <label class="form-check-label" for="phonepe">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/PhonePe_Logo.svg/1200px-PhonePe_Logo.svg.png" alt="PhonePe" width="25" height="20">
                                                <span class="ms-1">PhonePe</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="upi_provider" id="other_upi" value="other_upi">
                                            <label class="form-check-label" for="other_upi">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1200px-UPI-Logo-vector.svg.png" alt="UPI" width="32" height="20">
                                                <span class="ms-1">Other UPI</span>
                                            </label>
                                        </div>
                                        <div class="mt-2 other-upi-input" style="display: none;">
                                            <input type="text" class="form-control" id="upi_id" name="upi_id" placeholder="Enter your UPI ID (e.g., name@upi)">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <button class="w-100 btn btn-primary btn-lg" type="submit" name="place_order">Place Order</button>
                            <button id="proceed-to-payment-btn" class="w-100 btn btn-success btn-lg mt-2" type="button" style="display: none;">Proceed to Payment</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
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

    <!-- Pin Code Validation and Payment Method Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pin Code Validation
            const pincodeInput = document.getElementById('shipping_postal_code');
            const pincodeStatus = document.getElementById('pincode-status');
            const placeOrderBtn = document.querySelector('button[name="place_order"]');
            const checkoutForm = document.getElementById('checkout-form');

            // List of serviceable pin codes (you can replace this with your actual list or API call)
            const serviceablePincodes = [
                '110001', '110002', '110003', '110004', '110005', // Delhi
                '400001', '400002', '400003', '400004', '400005', // Mumbai
                '500001', '500002', '500003', '500004', '500005', // Hyderabad
                '600001', '600002', '600003', '600004', '600005', // Chennai
                '700001', '700002', '700003', '700004', '700005', // Kolkata
                '560001', '560002', '560003', '560004', '560005'  // Bangalore
            ];

            // Function to check if a pin code is serviceable
            function checkPincode(pincode) {
                if (!pincode || pincode.length !== 6 || !/^\d+$/.test(pincode)) {
                    return {
                        valid: false,
                        message: 'Please enter a valid 6-digit pin code'
                    };
                }

                if (serviceablePincodes.includes(pincode)) {
                    return {
                        valid: true,
                        message: 'Delivery available to this location!'
                    };
                } else {
                    return {
                        valid: false,
                        message: 'Sorry, we do not deliver to this pin code yet'
                    };
                }
            }

            // Function to update the UI based on pin code validation
            function updatePincodeStatus(result) {
                if (result.valid) {
                    pincodeStatus.innerHTML = '<div class="text-success"><i class="fas fa-check-circle"></i> ' + result.message + '</div>';
                    placeOrderBtn.disabled = false;
                } else {
                    pincodeStatus.innerHTML = '<div class="text-danger"><i class="fas fa-times-circle"></i> ' + result.message + '</div>';
                    placeOrderBtn.disabled = !result.valid;
                }
            }

            // Add event listener for pin code input
            if (pincodeInput) {
                pincodeInput.addEventListener('input', function() {
                    const pincode = this.value.trim();
                    if (pincode.length === 6) {
                        const result = checkPincode(pincode);
                        updatePincodeStatus(result);
                    } else {
                        pincodeStatus.innerHTML = '';
                        placeOrderBtn.disabled = false;
                    }
                });

                // Check pin code on page load if it's already filled
                if (pincodeInput.value.trim().length === 6) {
                    const result = checkPincode(pincodeInput.value.trim());
                    updatePincodeStatus(result);
                }
            }

            // Payment Method Selection
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const creditCardOptions = document.querySelector('.credit-card-options');
            const debitCardOptions = document.querySelector('.debit-card-options');
            const upiOptions = document.querySelector('.upi-options');
            const otherUpiOption = document.getElementById('other_upi');
            const otherUpiInput = document.querySelector('.other-upi-input');

            // Function to hide all payment subselections
            function hideAllPaymentOptions() {
                if (creditCardOptions) creditCardOptions.style.display = 'none';
                if (debitCardOptions) debitCardOptions.style.display = 'none';
                if (upiOptions) upiOptions.style.display = 'none';
                if (otherUpiInput) otherUpiInput.style.display = 'none';
            }

            // Get the proceed to payment button
            const proceedToPaymentBtn = document.getElementById('proceed-to-payment-btn');

            // Add event listeners to payment method radio buttons
            paymentMethods.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    hideAllPaymentOptions();

                    // Show the appropriate subselection based on the selected payment method
                    if (this.id === 'credit_card' && creditCardOptions) {
                        creditCardOptions.style.display = 'block';
                        // Select the first option by default
                        const firstCreditCardOption = creditCardOptions.querySelector('input[type="radio"]');
                        if (firstCreditCardOption) firstCreditCardOption.checked = true;

                        // Show the proceed to payment button for credit card
                        if (proceedToPaymentBtn) proceedToPaymentBtn.style.display = 'block';

                    } else if (this.id === 'debit_card' && debitCardOptions) {
                        debitCardOptions.style.display = 'block';
                        // Select the first option by default
                        const firstDebitCardOption = debitCardOptions.querySelector('input[type="radio"]');
                        if (firstDebitCardOption) firstDebitCardOption.checked = true;

                        // Show the proceed to payment button for debit card
                        if (proceedToPaymentBtn) proceedToPaymentBtn.style.display = 'block';

                    } else if (this.id === 'upi' && upiOptions) {
                        upiOptions.style.display = 'block';
                        // Select the first option by default
                        const firstUpiOption = upiOptions.querySelector('input[type="radio"]');
                        if (firstUpiOption) firstUpiOption.checked = true;

                        // Show the proceed to payment button for UPI
                        if (proceedToPaymentBtn) proceedToPaymentBtn.style.display = 'block';

                    } else if (this.id === 'cash_on_delivery') {
                        // Cash on Delivery is handled directly by checkout.php
                        // Hide the proceed to payment button for COD
                        if (proceedToPaymentBtn) proceedToPaymentBtn.style.display = 'none';
                    }
                });
            });

            // Add event listener for "Other UPI" option
            if (otherUpiOption) {
                otherUpiOption.addEventListener('change', function() {
                    if (this.checked && otherUpiInput) {
                        otherUpiInput.style.display = 'block';
                    } else if (otherUpiInput) {
                        otherUpiInput.style.display = 'none';
                    }
                });
            }

            // Add event listeners to UPI provider radio buttons
            const upiProviders = document.querySelectorAll('input[name="upi_provider"]');
            upiProviders.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.id === 'other_upi' && otherUpiInput) {
                        otherUpiInput.style.display = 'block';
                    } else if (otherUpiInput) {
                        otherUpiInput.style.display = 'none';
                    }
                });
            });

            // Add form validation
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(event) {
                    // Validate pin code
                    if (pincodeInput) {
                        const pincode = pincodeInput.value.trim();
                        const result = checkPincode(pincode);

                        if (!result.valid) {
                            event.preventDefault();
                            updatePincodeStatus(result);
                            pincodeInput.focus();
                            window.scrollTo({
                                top: pincodeInput.getBoundingClientRect().top + window.pageYOffset - 100,
                                behavior: 'smooth'
                            });
                            return;
                        }
                    }

                    // Validate payment method subselections
                    const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
                    if (selectedPaymentMethod) {
                        if (selectedPaymentMethod.id === 'credit_card') {
                            const selectedCreditCard = document.querySelector('input[name="credit_card_type"]:checked');
                            if (!selectedCreditCard) {
                                event.preventDefault();
                                alert('Please select a credit card type');
                                return;
                            }
                        } else if (selectedPaymentMethod.id === 'debit_card') {
                            const selectedDebitCard = document.querySelector('input[name="debit_card_type"]:checked');
                            if (!selectedDebitCard) {
                                event.preventDefault();
                                alert('Please select a debit card type');
                                return;
                            }
                        } else if (selectedPaymentMethod.id === 'upi') {
                            const selectedUpiProvider = document.querySelector('input[name="upi_provider"]:checked');
                            if (!selectedUpiProvider) {
                                event.preventDefault();
                                alert('Please select a UPI provider');
                                return;
                            }

                            if (selectedUpiProvider.id === 'other_upi') {
                                const upiId = document.getElementById('upi_id').value.trim();
                                if (!upiId) {
                                    event.preventDefault();
                                    alert('Please enter your UPI ID');
                                    document.getElementById('upi_id').focus();
                                    return;
                                }

                                // Validate UPI ID format (basic validation)
                                if (!upiId.includes('@')) {
                                    event.preventDefault();
                                    alert('Please enter a valid UPI ID (e.g., name@upi)');
                                    document.getElementById('upi_id').focus();
                                    return;
                                }
                            }
                        }
                    }
                });
            }

            // Add event listener for the proceed to payment button
            if (proceedToPaymentBtn) {
                proceedToPaymentBtn.addEventListener('click', function() {
                    // Get the selected payment method
                    const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');

                    if (selectedPaymentMethod) {
                        let isValid = true;
                        let formData = new FormData();

                        // Add the payment method to the form data
                        formData.append('payment_method', selectedPaymentMethod.value);

                        // Validate and add the appropriate payment details based on the selected method
                        if (selectedPaymentMethod.id === 'credit_card') {
                            const selectedCreditCard = document.querySelector('input[name="credit_card_type"]:checked');
                            if (!selectedCreditCard) {
                                alert('Please select a credit card type');
                                isValid = false;
                            } else {
                                formData.append('credit_card_type', selectedCreditCard.value);
                            }
                        } else if (selectedPaymentMethod.id === 'debit_card') {
                            const selectedDebitCard = document.querySelector('input[name="debit_card_type"]:checked');
                            if (!selectedDebitCard) {
                                alert('Please select a debit card type');
                                isValid = false;
                            } else {
                                formData.append('debit_card_type', selectedDebitCard.value);
                            }
                        } else if (selectedPaymentMethod.id === 'upi') {
                            const selectedUpiProvider = document.querySelector('input[name="upi_provider"]:checked');
                            if (!selectedUpiProvider) {
                                alert('Please select a UPI provider');
                                isValid = false;
                            } else {
                                formData.append('upi_provider', selectedUpiProvider.value);

                                if (selectedUpiProvider.id === 'other_upi') {
                                    const upiId = document.getElementById('upi_id').value.trim();
                                    if (!upiId) {
                                        alert('Please enter your UPI ID');
                                        document.getElementById('upi_id').focus();
                                        isValid = false;
                                    } else if (!upiId.includes('@')) {
                                        alert('Please enter a valid UPI ID (e.g., name@upi)');
                                        document.getElementById('upi_id').focus();
                                        isValid = false;
                                    } else {
                                        formData.append('upi_id', upiId);
                                    }
                                }
                            }
                        }

                        // If all validations pass, create a form and submit it to payment.php
                        if (isValid) {
                            // Create a temporary form
                            const tempForm = document.createElement('form');
                            tempForm.method = 'post';
                            tempForm.action = 'payment.php';
                            tempForm.style.display = 'none';

                            // Add form data to the temporary form
                            for (const [key, value] of formData.entries()) {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = key;
                                input.value = value;
                                tempForm.appendChild(input);
                            }

                            // Add the form to the document and submit it
                            document.body.appendChild(tempForm);
                            tempForm.submit();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
