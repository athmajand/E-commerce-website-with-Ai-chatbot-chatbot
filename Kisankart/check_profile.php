<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Include database configuration
include_once 'api/config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Check if connection was successful
if (!$db) {
    die("Database connection failed");
}

// Get user ID from query parameter or use default
$user_id = isset($_GET['id']) ? $_GET['id'] : 10;

// Output array
$output = array(
    "user_id" => $user_id,
    "user_data" => null,
    "customer_profile_data" => null,
    "error" => null
);

try {
    // Check user data
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $output["user_data"] = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $output["error"] = "User not found";
    }
    
    // Check customer profile data
    $query = "SELECT * FROM customer_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $output["customer_profile_data"] = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $output["customer_profile_data"] = "No customer profile found for this user";
    }
} catch (Exception $e) {
    $output["error"] = "Exception: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Profile Data - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Check Profile Data</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p>This page checks your user data and customer profile data in the database.</p>
                        </div>
                        
                        <h5 class="mt-4">User ID</h5>
                        <form method="get" class="mb-4">
                            <div class="input-group">
                                <input type="number" class="form-control" name="id" value="<?php echo $user_id; ?>">
                                <button type="submit" class="btn btn-primary">Check</button>
                            </div>
                        </form>
                        
                        <h5 class="mt-4">User Data</h5>
                        <pre class="bg-light p-3 rounded"><?php echo json_encode($output["user_data"], JSON_PRETTY_PRINT); ?></pre>
                        
                        <h5 class="mt-4">Customer Profile Data</h5>
                        <pre class="bg-light p-3 rounded"><?php echo is_array($output["customer_profile_data"]) ? json_encode($output["customer_profile_data"], JSON_PRETTY_PRINT) : $output["customer_profile_data"]; ?></pre>
                        
                        <?php if ($output["customer_profile_data"] === "No customer profile found for this user"): ?>
                        <div class="alert alert-warning mt-4">
                            <h5>No Customer Profile Found!</h5>
                            <p>This is likely why your profile page isn't showing the correct data. Let's create a customer profile for you:</p>
                            <form method="post" action="create_profile.php">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="state" name="state" value="">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="postal_code" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Profile Image URL</label>
                                    <input type="text" class="form-control" id="profile_image" name="profile_image" value="">
                                </div>
                                <button type="submit" class="btn btn-success">Create Customer Profile</button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="frontend/profile.html" class="btn btn-primary">Go to Profile Page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
