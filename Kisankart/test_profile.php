<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug information
error_log("Accessed test_profile.php");
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));
error_log("POST data: " . print_r($_POST, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Profile Page</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Test Profile Page</h3>
                    </div>
                    <div class="card-body">
                        <h4>This is a test profile page</h4>
                        <p>If you can see this page, basic navigation is working.</p>
                        
                        <h5 class="mt-4">Session Information</h5>
                        <pre><?php print_r($_SESSION); ?></pre>
                        
                        <h5 class="mt-4">Navigation Tests</h5>
                        <div class="list-group mb-4">
                            <a href="frontend/customer_dashboard.php" class="list-group-item list-group-item-action">Go to Dashboard</a>
                            <a href="frontend/customer_profile.php" class="list-group-item list-group-item-action">Go to Old Profile Page</a>
                            <a href="frontend/new_profile.php" class="list-group-item list-group-item-action">Go to New Profile Page</a>
                            <a href="test_profile.php" class="list-group-item list-group-item-action">Refresh This Page</a>
                        </div>
                        
                        <h5>Direct HTML Link Test</h5>
                        <p>Click this direct HTML link: <a href="frontend/new_profile.php">Go to Profile</a></p>
                        
                        <h5>JavaScript Navigation Test</h5>
                        <button id="js-nav-btn" class="btn btn-success">Navigate via JavaScript</button>
                        
                        <h5 class="mt-4">Form Submission Test</h5>
                        <form action="frontend/new_profile.php" method="GET">
                            <input type="hidden" name="test" value="1">
                            <button type="submit" class="btn btn-primary">Submit Form to Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript navigation test
        document.getElementById('js-nav-btn').addEventListener('click', function() {
            console.log('Navigating via JavaScript...');
            window.location.href = 'frontend/new_profile.php';
        });
        
        // Log page load
        console.log('Test profile page loaded at: ' + new Date().toString());
    </script>
</body>
</html>
