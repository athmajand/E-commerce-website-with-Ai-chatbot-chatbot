<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug information
error_log("Accessed direct_profile_link.php");
error_log("Session data: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Profile Link - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <!-- Sidebar -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Customer Menu</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="https://via.placeholder.com/100" class="rounded-circle" alt="Profile Image">
                            <h5 class="mt-2 mb-0">
                                <?php
                                // Display the user's full name from session
                                if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                                    echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                                } else {
                                    echo 'User Name';
                                }
                                ?>
                            </h5>
                            <p class="text-muted small">
                                <?php
                                // Display the user's email from session
                                echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'user@example.com';
                                ?>
                            </p>
                        </div>
                        <hr>
                        <div class="list-group list-group-flush">
                            <a href="customer_dashboard.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                            <a href="../standalone_profile.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person me-2"></i> My Profile (Standalone)
                            </a>

                            <a href="customer_profile.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person me-2"></i> My Profile (Old)
                            </a>
                            <a href="customer_orders.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-box me-2"></i> My Orders
                            </a>
                            <a href="customer_wishlist.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-heart me-2"></i> My Wishlist
                            </a>
                            <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Direct Profile Link Test</h5>
                    </div>
                    <div class="card-body">
                        <h4>Testing Profile Navigation</h4>
                        <p>This page is used to test navigation to the profile page.</p>

                        <h5 class="mt-4">Session Information</h5>
                        <pre><?php print_r($_SESSION); ?></pre>

                        <h5 class="mt-4">Direct Links</h5>
                        <div class="list-group mb-4">
                            <a href="../standalone_profile.php" class="list-group-item list-group-item-action">
                                Go to Standalone Profile Page
                            </a>

                            <a href="customer_profile.php" class="list-group-item list-group-item-action">
                                Go to Old Profile Page
                            </a>
                            <a href="../test_profile.php" class="list-group-item list-group-item-action">
                                Go to Test Profile Page
                            </a>
                        </div>

                        <h5>JavaScript Navigation Test</h5>
                        <div class="mb-3">
                            <button id="js-standalone-btn" class="btn btn-success me-2">Go to Standalone Profile</button>
                            <button id="js-new-btn" class="btn btn-primary me-2">Go to New Profile</button>
                            <button id="js-old-btn" class="btn btn-secondary">Go to Old Profile</button>
                        </div>

                        <h5 class="mt-4">Form Submission Test</h5>
                        <form action="../standalone_profile.php" method="GET" class="mb-2">
                            <input type="hidden" name="test" value="1">
                            <button type="submit" class="btn btn-outline-success">Submit Form to Standalone Profile</button>
                        </form>
                        <form action="new_profile.php" method="GET" class="mb-2">
                            <input type="hidden" name="test" value="1">
                            <button type="submit" class="btn btn-outline-primary">Submit Form to New Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript navigation tests
        document.getElementById('js-standalone-btn').addEventListener('click', function() {
            console.log('Navigating to standalone profile...');
            window.location.href = '../standalone_profile.php';
        });



        document.getElementById('js-old-btn').addEventListener('click', function() {
            console.log('Navigating to old profile...');
            window.location.href = 'customer_profile.php';
        });

        // Log page load
        console.log('Direct profile link page loaded at: ' + new Date().toString());
    </script>
</body>
</html>
