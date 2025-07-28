<?php
// Initialize variables
$registrations = [];
$error = null;

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Fetch all customer registrations
try {
    $query = "SELECT * FROM customer_registrations ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $registrations[] = $row;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle status update if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if ($id > 0 && !empty($status)) {
        try {
            $query = "UPDATE customer_registrations SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                // Refresh the page to show updated data
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = "Failed to update status";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customer Registrations - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4CAF50;
            --accent-color: #FF9800;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .view-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .view-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .view-header h1 {
            color: var(--primary-color);
            font-weight: 600;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #3d8b40;
            border-color: #3d8b40;
        }
        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .status-rejected {
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
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
                        <a class="nav-link" href="frontend/index.html#products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.html#about">About Us</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container view-container">
        <div class="view-header">
            <h1><i class="fas fa-users"></i> Customer Registrations</h1>
            <p class="text-muted">View and manage all customer registrations</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="registrationsTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo $reg['id']; ?></td>
                            <td><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                            <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($reg['address']); ?><br>
                                <?php echo htmlspecialchars($reg['city'] . ', ' . $reg['state'] . ' - ' . $reg['postal_code']); ?>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($reg['registration_date'])); ?></td>
                            <td>
                                <span class="badge status-<?php echo $reg['status']; ?>">
                                    <?php echo ucfirst($reg['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $reg['id']; ?>">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $reg['id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Update Status Modal -->
                        <div class="modal fade" id="updateModal<?php echo $reg['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Registration Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?php echo $reg['id']; ?>">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="pending" <?php echo $reg['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $reg['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo $reg['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- View Details Modal -->
                        <div class="modal fade" id="viewModal<?php echo $reg['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Registration Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>ID:</strong> <?php echo $reg['id']; ?></p>
                                                <p><strong>First Name:</strong> <?php echo htmlspecialchars($reg['first_name']); ?></p>
                                                <p><strong>Last Name:</strong> <?php echo htmlspecialchars($reg['last_name']); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($reg['email']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($reg['phone']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Address:</strong> <?php echo htmlspecialchars($reg['address']); ?></p>
                                                <p><strong>City:</strong> <?php echo htmlspecialchars($reg['city']); ?></p>
                                                <p><strong>State:</strong> <?php echo htmlspecialchars($reg['state']); ?></p>
                                                <p><strong>Postal Code:</strong> <?php echo htmlspecialchars($reg['postal_code']); ?></p>
                                                <p><strong>Status:</strong> <?php echo ucfirst($reg['status']); ?></p>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <p><strong>Registration Date:</strong> <?php echo date('F d, Y H:i:s', strtotime($reg['registration_date'])); ?></p>
                                                <p><strong>Is Verified:</strong> <?php echo $reg['is_verified'] ? 'Yes' : 'No'; ?></p>
                                                <p><strong>Last Login:</strong> <?php echo $reg['last_login'] ? date('F d, Y H:i:s', strtotime($reg['last_login'])) : 'Never'; ?></p>
                                                <p><strong>Notes:</strong> <?php echo htmlspecialchars($reg['notes'] ?? 'No notes'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-4">
            <a href="setup_customer_registrations.php" class="btn btn-outline-primary">
                <i class="fas fa-database"></i> Setup Table
            </a>
            <a href="customer_registration.php" class="btn btn-outline-success">
                <i class="fas fa-user-plus"></i> New Registration
            </a>
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
            $('#registrationsTable').DataTable({
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>
