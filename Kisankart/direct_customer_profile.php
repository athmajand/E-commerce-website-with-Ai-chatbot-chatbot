<?php
// Initialize variables
$profile_error = "";
$profile_success = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_profile'])) {
    // Include database and models
    include_once __DIR__ . '/api/config/database.php';
    include_once __DIR__ . '/api/models/CustomerProfile.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get form data
    $user_id = $_POST['user_id'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';

    // Validate user_id
    if (empty($user_id) || !is_numeric($user_id)) {
        $profile_error = "Please enter a valid user ID.";
    }
    // Validate required fields
    else if (empty($address) || empty($city) || empty($state) || empty($postal_code)) {
        $profile_error = "Please fill in all required fields.";
    }
    else {
        // Check if user exists in the users table
        $query = "SELECT id FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $profile_error = "User ID does not exist. Please enter a valid user ID.";
        } else {
            // Check if profile already exists for this user
            $check_query = "SELECT id FROM customer_profiles WHERE user_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $profile_error = "A profile already exists for this user. Please use the update function instead.";
            } else {
                // Create customer profile
                $customer_profile = new CustomerProfile($db);
                $customer_profile->user_id = $user_id;
                $customer_profile->address = $address;
                $customer_profile->city = $city;
                $customer_profile->state = $state;
                $customer_profile->postal_code = $postal_code;

                if ($customer_profile->create()) {
                    $profile_success = "Customer profile created successfully!";
                } else {
                    $profile_error = "Unable to create customer profile. Please try again.";
                }
            }
        }
    }
}

// Get existing users without profiles for dropdown
$users_without_profiles = [];
try {
    // Include database
    include_once __DIR__ . '/api/config/database.php';
    
    // Get database connection if not already connected
    if (!isset($db) || !$db) {
        $database = new Database();
        $db = $database->getConnection();
    }
    
    // Query to get users without profiles
    $query = "SELECT u.id, u.firstName, u.lastName, u.email 
              FROM users u 
              LEFT JOIN customer_profiles cp ON u.id = cp.user_id 
              WHERE cp.id IS NULL AND u.role = 'customer'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users_without_profiles[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching users without profiles: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Customer Profile Creation - Kisan Kart</title>
    
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
                        <a class="nav-link" href="customer_registration.php">Customer Registration</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2><i class="fas fa-user-plus"></i> Direct Customer Profile Creation</h2>
        
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
        
        <form method="POST" action="direct_customer_profile.php">
            <div class="form-group">
                <label for="user_id">Select User:</label>
                <select id="user_id" name="user_id" class="form-control" required>
                    <option value="">-- Select a user --</option>
                    <?php foreach ($users_without_profiles as $user): ?>
                    <option value="<?php echo $user['id']; ?>">
                        ID: <?php echo $user['id']; ?> - 
                        <?php echo $user['firstName'] . ' ' . $user['lastName']; ?> 
                        (<?php echo $user['email']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Only users without existing profiles are shown.</small>
            </div>
            
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" class="form-control" required></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="city">City:</label>
                        <input type="text" id="city" name="city" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="state">State:</label>
                        <input type="text" id="state" name="state" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="postal_code">Postal Code:</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control" pattern="[0-9]{6}" title="Please enter a valid 6-digit postal code" required>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" name="submit_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Customer Profile
                </button>
            </div>
        </form>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
