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

// Check if user is logged in (using either customer_id or user_id)
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=frontend/delivery_address.php");
    exit;
}

// Initialize variables
$userId = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : $_SESSION['user_id'];
$userName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'Customer';
$cart_items = [];
$total_amount = 0;
$error_message = '';
$success_message = '';
$addresses = [];

// Get product ID from URL if it's a direct buy
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

// If product_id is provided, add it to cart
if ($product_id > 0) {
    try {
        // Check if cart table exists
        $check_table_query = "SHOW TABLES LIKE 'cart'";
        $check_table_stmt = $db->prepare($check_table_query);
        $check_table_stmt->execute();

        if ($check_table_stmt->rowCount() === 0) {
            // Create cart table if it doesn't exist
            $create_table_query = "CREATE TABLE `cart` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `product_id` int(11) NOT NULL,
                `quantity` int(11) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `customer_id` (`customer_id`),
                KEY `product_id` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $db->exec($create_table_query);
        }

        // Clear existing cart items for this user (for buy now we want just this item)
        $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->bindParam(1, $userId);
        $clear_cart_stmt->execute();

        // Add this item to cart
        $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(1, $userId);
        $insert_stmt->bindParam(2, $product_id);
        $insert_stmt->bindParam(3, $quantity);
        $insert_stmt->execute();
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
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

// Get user addresses
try {
    // Check if addresses table exists
    $check_table_query = "SHOW TABLES LIKE 'addresses'";
    $check_table_stmt = $db->prepare($check_table_query);
    $check_table_stmt->execute();

    if ($check_table_stmt->rowCount() === 0) {
        // Create addresses table if it doesn't exist
        $create_table_query = "CREATE TABLE `addresses` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `phone` varchar(20) NOT NULL,
            `street` text NOT NULL,
            `city` varchar(100) NOT NULL,
            `state` varchar(100) NOT NULL,
            `postal_code` varchar(20) NOT NULL,
            `is_default` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $db->exec($create_table_query);
    } else {
        // Get user addresses
        $address_query = "SELECT * FROM addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC";
        $address_stmt = $db->prepare($address_query);
        $address_stmt->bindParam(1, $userId);
        $address_stmt->execute();

        while ($row = $address_stmt->fetch(PDO::FETCH_ASSOC)) {
            $addresses[] = $row;
        }
    }
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        // Add new address
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $street = $_POST['street'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $postal_code = $_POST['postal_code'];
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        try {
            // If this is set as default, unset all other defaults
            if ($is_default) {
                $update_query = "UPDATE addresses SET is_default = 0 WHERE customer_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $userId);
                $update_stmt->execute();
            }

            // Insert new address
            $insert_query = "INSERT INTO addresses (customer_id, name, phone, street, city, state, postal_code, is_default)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $userId);
            $insert_stmt->bindParam(2, $name);
            $insert_stmt->bindParam(3, $phone);
            $insert_stmt->bindParam(4, $street);
            $insert_stmt->bindParam(5, $city);
            $insert_stmt->bindParam(6, $state);
            $insert_stmt->bindParam(7, $postal_code);
            $insert_stmt->bindParam(8, $is_default);
            $insert_stmt->execute();

            $success_message = "Address added successfully!";

            // Redirect to refresh the page
            header("Location: delivery_address.php");
            exit;
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['continue_to_payment'])) {
        // Process delivery address selection
        $address_id = $_POST['address_id'];
        $delivery_slot = $_POST['delivery_slot'];

        // Store in session for payment page
        $_SESSION['checkout_address_id'] = $address_id;
        $_SESSION['checkout_delivery_slot'] = $delivery_slot;

        // Redirect to payment page
        header("Location: payment_method.php");
        exit;
    }
}

// Generate delivery slots for the next 7 days
$delivery_slots = [];
$today = new DateTime();

for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $day_name = date('l', strtotime("+$i days"));

    $delivery_slots[] = [
        'date' => $date,
        'day_name' => $day_name,
        'slots' => [
            [
                'id' => "morning-$date",
                'time' => '9:00 AM - 12:00 PM'
            ],
            [
                'id' => "afternoon-$date",
                'time' => '1:00 PM - 4:00 PM'
            ],
            [
                'id' => "evening-$date",
                'time' => '5:00 PM - 8:00 PM'
            ]
        ]
    ];
}

// Page title
$page_title = "Delivery Address - Kisan Kart";
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

    <!-- Delivery Address Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h2 class="mb-4">Delivery Address</h2>

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

                    <?php if (empty($cart_items)): ?>
                        <div class="alert alert-info" role="alert">
                            Your cart is empty. <a href="products.php" class="alert-link">Continue shopping</a>.
                        </div>
                    <?php else: ?>
                        <form method="post" action="delivery_address.php">
                            <!-- Address Selection -->
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Choose Delivery Address</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($addresses)): ?>
                                        <p>You don't have any saved addresses. Please add a new address.</p>
                                    <?php else: ?>
                                        <?php foreach ($addresses as $address): ?>
                                            <div class="form-check mb-3 border p-3 rounded <?php echo $address['is_default'] ? 'border-success' : ''; ?>">
                                                <input class="form-check-input" type="radio" name="address_id" id="address-<?php echo $address['id']; ?>" value="<?php echo $address['id']; ?>" <?php echo $address['is_default'] ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="address-<?php echo $address['id']; ?>">
                                                    <strong><?php echo htmlspecialchars($address['name']); ?></strong><br>
                                                    <?php echo htmlspecialchars($address['street']); ?>,
                                                    <?php echo htmlspecialchars($address['city']); ?>,
                                                    <?php echo htmlspecialchars($address['state']); ?> -
                                                    <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                    Phone: <?php echo htmlspecialchars($address['phone']); ?>
                                                    <?php if ($address['is_default']): ?>
                                                        <span class="badge bg-success ms-2">Default</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                        <i class="fas fa-plus"></i> Add New Address
                                    </button>
                                </div>
                            </div>

                            <!-- Delivery Slot Selection -->
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Choose Delivery Slot</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select class="form-select mb-3" id="delivery-date" required>
                                                <?php foreach ($delivery_slots as $slot): ?>
                                                    <option value="<?php echo $slot['date']; ?>">
                                                        <?php echo $slot['day_name']; ?>, <?php echo date('d M', strtotime($slot['date'])); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <div id="time-slots">
                                                <?php foreach ($delivery_slots[0]['slots'] as $index => $time_slot): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio" name="delivery_slot" id="slot-<?php echo $time_slot['id']; ?>" value="<?php echo $time_slot['id']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?> required>
                                                        <label class="form-check-label" for="slot-<?php echo $time_slot['id']; ?>">
                                                            <?php echo $time_slot['time']; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="continue_to_payment" class="btn btn-success btn-lg" <?php echo empty($addresses) ? 'disabled' : ''; ?>>
                                Continue to Payment <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </form>
                    <?php endif; ?>
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

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="delivery_address.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-12">
                                <label for="street" class="form-label">Street Address</label>
                                <textarea class="form-control" id="street" name="street" rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                                    <label class="form-check-label" for="is_default">
                                        Set as default address
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_address" class="btn btn-success">Save Address</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

    <script>
        // Update time slots when date changes
        document.getElementById('delivery-date').addEventListener('change', function() {
            const selectedDate = this.value;
            const timeSlotsContainer = document.getElementById('time-slots');

            // Clear existing time slots
            timeSlotsContainer.innerHTML = '';

            // Find the selected date in delivery slots
            const selectedSlot = <?php echo json_encode($delivery_slots); ?>.find(slot => slot.date === selectedDate);

            if (selectedSlot) {
                // Add time slots for the selected date
                selectedSlot.slots.forEach((timeSlot, index) => {
                    const timeSlotHtml = `
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="delivery_slot" id="slot-${timeSlot.id}" value="${timeSlot.id}" ${index === 0 ? 'checked' : ''} required>
                            <label class="form-check-label" for="slot-${timeSlot.id}">
                                ${timeSlot.time}
                            </label>
                        </div>
                    `;
                    timeSlotsContainer.innerHTML += timeSlotHtml;
                });
            }
        });
    </script>
</body>
</html>
