<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Link - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Profile Link Test</h3>
                    </div>
                    <div class="card-body">
                        <h4>Click on the links below to test navigation:</h4>
                        
                        <div class="list-group mt-4">
                            <a href="standalone_profile.php" class="list-group-item list-group-item-action">
                                <strong>Standalone Profile</strong> - Direct Link
                            </a>
                            <a href="frontend/customer_dashboard.php" class="list-group-item list-group-item-action">
                                Dashboard - From there, click on "My Profile"
                            </a>
                            <a href="frontend/direct_profile_link.php" class="list-group-item list-group-item-action">
                                Direct Profile Link Test Page
                            </a>
                            <a href="test_profile.php" class="list-group-item list-group-item-action">
                                Test Profile Page
                            </a>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Session Information:</h5>
                            <pre><?php print_r($_SESSION); ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
