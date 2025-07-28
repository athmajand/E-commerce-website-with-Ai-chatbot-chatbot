<?php
// Initialize variables
$profile_error = "";
$profile_success = "";
$profile = null;

// Include database and models
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_customer_profiles.php");
    exit;
}

$profile_id = $_GET['id'];

// Get profile data
try {
    $query = "SELECT cp.*, u.firstName, u.lastName, u.email, u.phone 
              FROM customer_profiles cp
              JOIN users u ON cp.user_id = u.id
              WHERE cp.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $profile_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        header("Location: manage_customer_profiles.php");
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching profile: " . $e->getMessage());
    header("Location: manage_customer_profiles.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Get form data
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    
    // Validate required fields
    if (empty($address) || empty($city) || empty($state) || empty($postal_code)) {
        $profile_error = "Please fill in all required fields.";
    } else {
        // Update the profile
        $query = "UPDATE customer_profiles 
                  SET address = ?, city = ?, state = ?, postal_code = ? 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $address);
        $stmt->bindParam(2, $city);
        $stmt->bindParam(3, $state);
        $stmt->bindParam(4, $postal_code);
        $stmt->bindParam(5, $profile_id);
        
        if ($stmt->execute()) {
            $profile_success = "Customer profile updated successfully!";
            
            // Update the profile data for display
            $profile['address'] = $address;
            $profile['city'] = $city;
            $profile['state'] = $state;
            $profile['postal_code'] = $postal_code;
        } else {
            $profile_error = "Unable to update customer profile. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer Profile - Kisan Kart</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1e8449;
            --accent-color: #ff9800;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #166938;
            border-color: #166938;
        }
        
        .alert {
            margin-bottom: 20px;
        }
        
        .user-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .user-info h5 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="frontend/index.html">Kisan Kart</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_customer_profiles.php">Manage Profiles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="direct_customer_profile.php">Add Profile</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2><i class="fas fa-user-edit"></i> Edit Customer Profile</h2>
        
        <?php if (!empty($profile_error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $profile_error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($profile_success)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $profile_success; ?>
        </div>
        <?php endif; ?>
        
        <div class="user-info">
            <h5>User Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?php echo $profile['firstName'] . ' ' . $profile['lastName']; ?></p>
                    <p><strong>Email:</strong> <?php echo $profile['email']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>User ID:</strong> <?php echo $profile['user_id']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $profile['phone']; ?></p>
                </div>
            </div>
        </div>
        
        <form method="POST" action="edit_customer_profile.php?id=<?php echo $profile_id; ?>">
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" class="form-control" required><?php echo $profile['address']; ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="city">City:</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?php echo $profile['city']; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="state">State:</label>
                        <input type="text" id="state" name="state" class="form-control" value="<?php echo $profile['state']; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="postal_code">Postal Code:</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?php echo $profile['postal_code']; ?>" pattern="[0-9]{6}" title="Please enter a valid 6-digit postal code" required>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="manage_customer_profiles.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </div>
        </form>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
