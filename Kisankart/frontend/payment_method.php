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
    header("Location: ../login.php?redirect=frontend/payment_method.php");
    exit;
}

// Check if address and delivery slot are selected
if (!isset($_SESSION['checkout_address_id']) || !isset($_SESSION['checkout_delivery_slot'])) {
    header("Location: delivery_address.php");
    exit;
}

// Initialize variables
$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$addressId = $_SESSION['checkout_address_id'];
$deliverySlot = $_SESSION['checkout_delivery_slot'];
$cart_items = [];
$total_amount = 0;
$error_message = '';
$success_message = '';
$address = null;

// Get address details
try {
    $address_query = "SELECT * FROM addresses WHERE id = ? AND user_id = ?";
    $address_stmt = $db->prepare($address_query);
    $address_stmt->bindParam(1, $addressId);
    $address_stmt->bindParam(2, $userId);
    $address_stmt->execute();

    if ($address_stmt->rowCount() > 0) {
        $address = $address_stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Address not found or doesn't belong to user
        header("Location: delivery_address.php");
        exit;
    }
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Get cart items
try {
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
        // Cart is empty
        header("Location: products.php");
        exit;
    }
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = $_POST['payment_method'];

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
                `delivery_slot` varchar(50) DEFAULT NULL,
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

        // Begin transaction
        $db->beginTransaction();

        // Create order
        $order_query = "INSERT INTO orders (customer_id, total_amount, payment_method, payment_status,
                        shipping_address, shipping_city, shipping_state, shipping_postal_code, delivery_slot, status)
                        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, 'pending')";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->bindParam(1, $userId);
        $order_stmt->bindParam(2, $total_amount);
        $order_stmt->bindParam(3, $payment_method);
        $order_stmt->bindParam(4, $address['street']);
        $order_stmt->bindParam(5, $address['city']);
        $order_stmt->bindParam(6, $address['state']);
        $order_stmt->bindParam(7, $address['postal_code']);
        $order_stmt->bindParam(8, $deliverySlot);
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

        // Clear checkout session variables
        unset($_SESSION['checkout_address_id']);
        unset($_SESSION['checkout_delivery_slot']);

        // Commit transaction
        $db->commit();

        // Redirect to order confirmation page
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Page title
$page_title = "Payment Method - Kisan Kart";
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
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">Kisan Kart</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo $userName; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="customer_dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="customer_profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="customer_orders.php">Orders</a></li>
                            <li><a class="dropdown-item" href="customer_wishlist.php">Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Payment Method Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h2 class="mb-4">Payment Method</h2>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Checkout Progress -->
                    <div class="d-flex justify-content-between mb-4">
                        <div class="text-center">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>Address</div>
                        </div>
                        <div class="progress align-self-center" style="width: 20%; height: 2px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-center">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>Delivery</div>
                        </div>
                        <div class="progress align-self-center" style="width: 20%; height: 2px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-center">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                <span>3</span>
                            </div>
                            <div>Payment</div>
                        </div>
                        <div class="progress align-self-center" style="width: 20%; height: 2px;">
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-center">
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                <span>4</span>
                            </div>
                            <div>Confirmation</div>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Delivery Information</h5>
                                <a href="delivery_address.php" class="btn btn-sm btn-outline-success">Change</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Delivery Address</h6>
                                    <p>
                                        <strong><?php echo htmlspecialchars($address['name']); ?></strong><br>
                                        <?php echo htmlspecialchars($address['street']); ?><br>
                                        <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> - <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                        Phone: <?php echo htmlspecialchars($address['phone']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Delivery Slot</h6>
                                    <p>
                                        <?php
                                        $slot_parts = explode('-', $deliverySlot);
                                        $slot_time = $slot_parts[0];
                                        $slot_date = $slot_parts[1];

                                        $formatted_date = date('l, d M Y', strtotime($slot_date));
                                        $formatted_time = '';

                                        switch ($slot_time) {
                                            case 'morning':
                                                $formatted_time = '9:00 AM - 12:00 PM';
                                                break;
                                            case 'afternoon':
                                                $formatted_time = '1:00 PM - 4:00 PM';
                                                break;
                                            case 'evening':
                                                $formatted_time = '5:00 PM - 8:00 PM';
                                                break;
                                        }
                                        ?>
                                        <strong><?php echo $formatted_date; ?></strong><br>
                                        <?php echo $formatted_time; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Form -->
                    <form method="post" action="payment_method.php">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Choose Payment Method</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3 border p-3 rounded">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment-cod" value="cod" checked required>
                                    <label class="form-check-label" for="payment-cod">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                                            </div>
                                            <div>
                                                <strong>Cash on Delivery</strong>
                                                <p class="mb-0 text-muted small">Pay when your order is delivered</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="form-check mb-3 border p-3 rounded">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment-upi" value="upi" required>
                                    <label class="form-check-label" for="payment-upi">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <strong>UPI</strong>
                                                <p class="mb-0 text-muted small">Pay using UPI apps like Google Pay, PhonePe, Paytm, etc.</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="form-check mb-3 border p-3 rounded">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment-card" value="card" required>
                                    <label class="form-check-label" for="payment-card">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-credit-card fa-2x text-info"></i>
                                            </div>
                                            <div>
                                                <strong>Credit/Debit Card</strong>
                                                <p class="mb-0 text-muted small">Pay using Visa, MasterCard, RuPay, or other cards</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="form-check mb-3 border p-3 rounded">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment-netbanking" value="netbanking" required>
                                    <label class="form-check-label" for="payment-netbanking">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-university fa-2x text-warning"></i>
                                            </div>
                                            <div>
                                                <strong>Net Banking</strong>
                                                <p class="mb-0 text-muted small">Pay using your bank account</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="place_order" class="btn btn-success btn-lg">
                            Place Order <i class="fas fa-check ms-2"></i>
                        </button>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($cart_items as $item): ?>
                                    <?php
                                    $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
                                    $item_total = $price * $item['quantity'];
                                    ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                        </div>
                                        <span>₹<?php echo number_format($item_total, 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Subtotal</span>
                                    <strong>₹<?php echo number_format($total_amount, 2); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Shipping</span>
                                    <strong>Free</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total</span>
                                    <strong class="text-success">₹<?php echo number_format($total_amount, 2); ?></strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
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
</body>
</html>
