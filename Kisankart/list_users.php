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
$users = array();
$error = null;

try {
    // Get all users
    $query = "SELECT * FROM users ORDER BY id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = $row;
    }
} catch (Exception $e) {
    $error = "Exception: " . $e->getMessage();
}

// Process form submission for creating a new user
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    try {
        $username = $_POST['username'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        
        // Check if username or email already exists
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(1, $username);
        $check_stmt->bindParam(2, $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error_message = "Username or email already exists.";
        } else {
            // Insert new user
            $insert_query = "INSERT INTO users (username, firstName, lastName, password, email, phone, role) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $username);
            $insert_stmt->bindParam(2, $firstName);
            $insert_stmt->bindParam(3, $lastName);
            $insert_stmt->bindParam(4, $password);
            $insert_stmt->bindParam(5, $email);
            $insert_stmt->bindParam(6, $phone);
            $insert_stmt->bindParam(7, $role);
            
            if ($insert_stmt->execute()) {
                $success_message = "User created successfully!";
                
                // Refresh the page to show the new user
                header("Location: list_users.php");
                exit;
            } else {
                $error_message = "Failed to create user.";
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
    <title>List Users - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Users in Database</h4>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#createUserModal">Create New User</button>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (empty($users)): ?>
                            <div class="alert alert-warning">No users found in the database.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo $user['username']; ?></td>
                                                <td><?php echo $user['firstName']; ?></td>
                                                <td><?php echo $user['lastName']; ?></td>
                                                <td><?php echo $user['email']; ?></td>
                                                <td><?php echo $user['phone']; ?></td>
                                                <td><?php echo $user['role']; ?></td>
                                                <td>
                                                    <a href="check_profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">Check Profile</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="check_profile.php" class="btn btn-primary">Check Profile Data</a>
                            <a href="frontend/profile.html" class="btn btn-success ms-2">Go to Profile Page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="list_users.php">
                        <input type="hidden" name="action" value="create_user">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="customer">Customer</option>
                                <option value="seller">Seller</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Create User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
