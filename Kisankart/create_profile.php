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

// Initialize variables
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
$address = isset($_POST['address']) ? $_POST['address'] : '';
$city = isset($_POST['city']) ? $_POST['city'] : '';
$state = isset($_POST['state']) ? $_POST['state'] : '';
$postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : '';
$profile_image = isset($_POST['profile_image']) ? $_POST['profile_image'] : '';
$success = false;
$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    try {
        // Check if user exists
        $check_query = "SELECT id FROM users WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            $message = "Error: User with ID $user_id does not exist.";
        } else {
            // Check if profile already exists
            $profile_check_query = "SELECT id FROM customer_profiles WHERE user_id = ?";
            $profile_check_stmt = $db->prepare($profile_check_query);
            $profile_check_stmt->bindParam(1, $user_id);
            $profile_check_stmt->execute();
            
            if ($profile_check_stmt->rowCount() > 0) {
                // Update existing profile
                $update_query = "UPDATE customer_profiles 
                                SET address = ?, city = ?, state = ?, postal_code = ?, profile_image = ? 
                                WHERE user_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $address);
                $update_stmt->bindParam(2, $city);
                $update_stmt->bindParam(3, $state);
                $update_stmt->bindParam(4, $postal_code);
                $update_stmt->bindParam(5, $profile_image);
                $update_stmt->bindParam(6, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = true;
                    $message = "Customer profile updated successfully!";
                } else {
                    $message = "Error: Failed to update customer profile.";
                }
            } else {
                // Create new profile
                $insert_query = "INSERT INTO customer_profiles (user_id, address, city, state, postal_code, profile_image) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(1, $user_id);
                $insert_stmt->bindParam(2, $address);
                $insert_stmt->bindParam(3, $city);
                $insert_stmt->bindParam(4, $state);
                $insert_stmt->bindParam(5, $postal_code);
                $insert_stmt->bindParam(6, $profile_image);
                
                if ($insert_stmt->execute()) {
                    $success = true;
                    $message = "Customer profile created successfully!";
                } else {
                    $message = "Error: Failed to create customer profile.";
                }
            }
        }
    } catch (Exception $e) {
        $message = "Exception: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Profile - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Create/Update Customer Profile</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="check_profile.php?id=<?php echo $user_id; ?>" class="btn btn-primary">Check Profile Data</a>
                            <a href="frontend/profile.html" class="btn btn-success ms-2">Go to Profile Page</a>
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
