<?php
// Initialize variables
$action_message = "";
$action_success = false;

// Include database and models
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/CustomerProfile.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $profile_id = $_GET['id'];
    
    // Delete the profile
    $query = "DELETE FROM customer_profiles WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $profile_id);
    
    if ($stmt->execute()) {
        $action_message = "Customer profile deleted successfully.";
        $action_success = true;
    } else {
        $action_message = "Failed to delete customer profile.";
        $action_success = false;
    }
}

// Get all customer profiles with user information
$profiles = [];
try {
    $query = "SELECT cp.*, u.firstName, u.lastName, u.email, u.phone 
              FROM customer_profiles cp
              JOIN users u ON cp.user_id = u.id
              ORDER BY cp.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $profiles[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching customer profiles: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customer Profiles - Kisan Kart</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
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
            max-width: 1200px;
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
        
        .table-responsive {
            margin-top: 20px;
        }
        
        .action-buttons .btn {
            margin-right: 5px;
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
                        <a class="nav-link" href="direct_customer_profile.php">Add Customer Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_registration.php">Customer Registration</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2><i class="fas fa-users"></i> Manage Customer Profiles</h2>
        
        <?php if (!empty($action_message)): ?>
        <div class="alert alert-<?php echo $action_success ? 'success' : 'danger'; ?>" role="alert">
            <?php echo $action_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-end mb-3">
            <a href="direct_customer_profile.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Profile
            </a>
        </div>
        
        <div class="table-responsive">
            <table id="profilesTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Postal Code</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td><?php echo $profile['id']; ?></td>
                        <td><?php echo $profile['user_id']; ?></td>
                        <td><?php echo $profile['firstName'] . ' ' . $profile['lastName']; ?></td>
                        <td><?php echo $profile['email']; ?></td>
                        <td><?php echo $profile['phone']; ?></td>
                        <td><?php echo $profile['address']; ?></td>
                        <td><?php echo $profile['city']; ?></td>
                        <td><?php echo $profile['state']; ?></td>
                        <td><?php echo $profile['postal_code']; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($profile['created_at'])); ?></td>
                        <td class="action-buttons">
                            <a href="edit_customer_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="manage_customer_profiles.php?action=delete&id=<?php echo $profile['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this profile?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#profilesTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                responsive: true
            });
        });
    </script>
</body>
</html>
