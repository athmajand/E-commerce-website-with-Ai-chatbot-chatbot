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
$success_message = '';
$error_message = '';
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 10;
$username = isset($_POST['username']) ? $_POST['username'] : 'mohammedarsh';
$firstName = isset($_POST['firstName']) ? $_POST['firstName'] : 'Hemanth';
$lastName = isset($_POST['lastName']) ? $_POST['lastName'] : 'Kumar';
$email = isset($_POST['email']) ? $_POST['email'] : 'hemanth@gmail.com';
$phone = isset($_POST['phone']) ? $_POST['phone'] : '9876543210';
$role = isset($_POST['role']) ? $_POST['role'] : 'customer';
$password = isset($_POST['password']) ? $_POST['password'] : 'password123';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user with this ID already exists
        $check_query = "SELECT id FROM users WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // User exists, update it
            $update_query = "UPDATE users SET 
                            username = ?, 
                            firstName = ?, 
                            lastName = ?, 
                            email = ?, 
                            phone = ?, 
                            role = ? 
                            WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $username);
            $update_stmt->bindParam(2, $firstName);
            $update_stmt->bindParam(3, $lastName);
            $update_stmt->bindParam(4, $email);
            $update_stmt->bindParam(5, $phone);
            $update_stmt->bindParam(6, $role);
            $update_stmt->bindParam(7, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "User with ID $user_id updated successfully!";
            } else {
                $error_message = "Failed to update user.";
            }
        } else {
            // User doesn't exist, create it with specific ID
            // First, check if auto-increment is higher than our desired ID
            $seq_query = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'kisan_kart' AND TABLE_NAME = 'users'";
            $seq_stmt = $db->prepare($seq_query);
            $seq_stmt->execute();
            $auto_increment = $seq_stmt->fetch(PDO::FETCH_ASSOC)['AUTO_INCREMENT'];
            
            // If auto_increment is lower than our desired ID, we can insert directly
            if ($auto_increment <= $user_id) {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $insert_query = "INSERT INTO users (id, username, firstName, lastName, password, email, phone, role) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(1, $user_id);
                $insert_stmt->bindParam(2, $username);
                $insert_stmt->bindParam(3, $firstName);
                $insert_stmt->bindParam(4, $lastName);
                $insert_stmt->bindParam(5, $password_hash);
                $insert_stmt->bindParam(6, $email);
                $insert_stmt->bindParam(7, $phone);
                $insert_stmt->bindParam(8, $role);
                
                if ($insert_stmt->execute()) {
                    $success_message = "User with ID $user_id created successfully!";
                } else {
                    $error_message = "Failed to create user.";
                }
            } else {
                // Auto_increment is higher, we need to alter the auto_increment value
                $alter_query = "ALTER TABLE users AUTO_INCREMENT = $user_id";
                $alter_stmt = $db->prepare($alter_query);
                
                if ($alter_stmt->execute()) {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $insert_query = "INSERT INTO users (id, username, firstName, lastName, password, email, phone, role) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $db->prepare($insert_query);
                    $insert_stmt->bindParam(1, $user_id);
                    $insert_stmt->bindParam(2, $username);
                    $insert_stmt->bindParam(3, $firstName);
                    $insert_stmt->bindParam(4, $lastName);
                    $insert_stmt->bindParam(5, $password_hash);
                    $insert_stmt->bindParam(6, $email);
                    $insert_stmt->bindParam(7, $phone);
                    $insert_stmt->bindParam(8, $role);
                    
                    if ($insert_stmt->execute()) {
                        $success_message = "User with ID $user_id created successfully!";
                    } else {
                        $error_message = "Failed to create user.";
                    }
                } else {
                    $error_message = "Failed to alter auto_increment value.";
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "Exception: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Specific User - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Create User with Specific ID</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <p>This page will create a user with ID 10 (or another ID you specify) to match your localStorage data.</p>
                        </div>
                        
                        <form method="post" action="create_specific_user.php">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">User ID</label>
                                <input type="number" class="form-control" id="user_id" name="user_id" value="<?php echo $user_id; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo $firstName; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo $lastName; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" value="<?php echo $password; ?>" required>
                                <small class="form-text text-muted">Default password: password123</small>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="customer" <?php echo ($role == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                    <option value="seller" <?php echo ($role == 'seller') ? 'selected' : ''; ?>>Seller</option>
                                    <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Create/Update User</button>
                        </form>
                        
                        <div class="mt-4">
                            <a href="check_profile.php?id=<?php echo $user_id; ?>" class="btn btn-primary">Check Profile</a>
                            <a href="list_users.php" class="btn btn-info ms-2">List All Users</a>
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
