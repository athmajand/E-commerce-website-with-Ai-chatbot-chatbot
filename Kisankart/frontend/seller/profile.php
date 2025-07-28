<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    // Redirect to login page if not logged in as a seller
    header("Location: ../../seller_login.php?redirect=frontend/seller/profile.php");
    exit;
}

// Include database configuration
include_once __DIR__ . '/../../api/config/database.php';
include_once __DIR__ . '/../../api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$seller_data = null;
$error_message = '';
$success_message = '';

// Get seller data using the helper function
require_once '../../api/helpers/session_helper.php';
synchronizeSellerSessionIds();
$seller_id = getSellerIdFromSession();

// Debug information
$debug_info = "Profile page - Attempting to load seller with ID: " . $seller_id;
error_log($debug_info);

// Create seller object
$seller = new SellerRegistration($db);
$seller->id = $seller_id;

// Try to fetch seller data from database
if (!$seller->readOne()) {
    // If database fetch fails, create a fallback seller object with session data
    error_log("Profile page - Failed to load seller data for ID: " . $seller_id . ". Error: " . ($seller->error ?? 'Unknown error'));
    error_log("Profile page - Using session data instead. Session data: " . json_encode($_SESSION));

    // Set error message with more details
    $error_message = "Failed to load seller profile data.";

    // Set fallback data from session
    $seller->first_name = $_SESSION['first_name'] ?? '';
    $seller->last_name = $_SESSION['last_name'] ?? '';
    $seller->email = $_SESSION['email'] ?? '';

    // Add more detailed error information for debugging
    $error_details = "Error: " . ($seller->error ?? 'Unknown error');
    error_log($error_details);
} else {
    // Successfully loaded seller data from database
    error_log("Profile page - Successfully loaded seller data from database for ID: " . $seller_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Seller Dashboard - Kisan Kart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../../images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="../../images/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/seller.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom bg-success text-white">
                <img src="../../images/farmer-logo.png" alt="Kisan Kart Logo" style="height: 24px; width: 24px; margin-right: 8px; filter: brightness(0) invert(1);"> Seller Center
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="products.php">
                    <i class="bi bi-box me-2"></i> Products
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="orders.php">
                    <i class="bi bi-cart me-2"></i> Orders
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 active" href="profile.php">
                    <i class="bi bi-person me-2"></i> Profile
                </a>

                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="../index.html">
                    <i class="bi bi-house me-2"></i> Back to Store
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 text-danger" href="../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
        </div>

        <!-- Page content wrapper -->
        <div id="page-content-wrapper">
            <!-- Top navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-sm btn-success" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-label="Toggle Navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="notificationDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell" aria-hidden="true"></i>
                                    <span class="badge bg-danger rounded-pill notification-badge" style="display: none;">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" id="notification-dropdown">
                                    <div class="dropdown-header">Notifications</div>
                                    <div class="dropdown-divider"></div>
                                    <div id="notification-list">
                                        <a class="dropdown-item" href="#">No new notifications</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                                    <span class="seller-name">
                                        <?php
                                        $firstName = $seller->first_name ?? '';
                                        $lastName = $seller->last_name ?? '';
                                        echo htmlspecialchars(trim($firstName . (empty($lastName) ? '' : ' ' . $lastName)));
                                        ?>
                                    </span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="profile.php">Profile</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../../logout.php">Logout</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page content -->
            <div class="container-fluid p-4">
                <h1 class="mt-2 mb-4">Seller Profile</h1>

                <!-- Display error/success messages -->
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <div class="alert alert-info" role="alert">
                    <h5>Troubleshooting Tips:</h5>
                    <ul>
                        <li>Make sure your seller account is properly registered</li>
                        <li>Try logging out and logging back in</li>
                        <li>Contact support if the issue persists</li>
                    </ul>
                    <?php if (isset($error_details)): ?>
                    <hr>
                    <h6>Technical Details:</h6>
                    <p><?php echo htmlspecialchars($error_details); ?></p>
                    <p class="mb-0">Please provide these details when contacting support.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Profile Overview -->
                <div class="row mb-4">
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <div class="card shadow">
                            <div class="card-body text-center">
                                <div class="profile-image-container">
                                    <img src="<?php echo !empty($seller->business_logo) ? htmlspecialchars($seller->business_logo) : 'https://via.placeholder.com/150'; ?>" class="profile-image" alt="Profile Image" id="profile-image">
                                    <div class="profile-image-upload" id="profile-image-upload">
                                        <i class="bi bi-camera"></i>
                                    </div>
                                    <input type="file" id="profile-image-input" accept="image/*" style="display: none;">
                                </div>
                                <h5 class="mb-1" id="seller-name"><?php
                                    $firstName = $seller->first_name ?? '';
                                    $lastName = $seller->last_name ?? '';
                                    echo htmlspecialchars($firstName . (empty($lastName) ? '' : ' ' . $lastName));
                                ?></h5>
                                <p class="text-muted mb-3" id="seller-email"><?php echo htmlspecialchars($seller->email ?? ''); ?></p>
                                <div class="verification-status <?php echo $seller->is_verified ? 'verified' : 'pending'; ?> mb-3" id="verification-status">
                                    <?php echo $seller->is_verified ? 'Verified Seller' : 'Verification Pending'; ?>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <a href="../index.html" class="btn btn-outline-success btn-sm me-2">
                                        <i class="bi bi-shop"></i> View Store
                                    </a>
                                    <button class="btn btn-outline-primary btn-sm" id="edit-profile-btn">
                                        <i class="bi bi-pencil"></i> Edit Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Account Information</h5>
                                <div class="row mb-3">
                                    <div class="col-md-4 text-muted">Seller ID</div>
                                    <div class="col-md-8" id="seller-id"><?php echo htmlspecialchars($seller->id ?? ''); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 text-muted">Business Name</div>
                                    <div class="col-md-8" id="business-name"><?php echo htmlspecialchars($seller->business_name ?? ''); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 text-muted">Phone Number</div>
                                    <div class="col-md-8" id="phone-number"><?php echo htmlspecialchars($seller->phone ?? ''); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 text-muted">Address</div>
                                    <div class="col-md-8" id="address"><?php echo htmlspecialchars($seller->business_address ?? ''); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 text-muted">Joined Date</div>
                                    <div class="col-md-8" id="joined-date"><?php echo htmlspecialchars($seller->created_at ?? ''); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 text-muted">Account Status</div>
                                    <div class="col-md-8">
                                        <span class="badge <?php echo $seller->status == 'approved' ? 'bg-success' : ($seller->status == 'pending' ? 'bg-warning' : 'bg-danger'); ?>" id="account-status">
                                            <?php echo ucfirst(htmlspecialchars($seller->status ?? 'pending')); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Tabs -->
                <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab" aria-controls="business" aria-selected="true">
                            Business Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab" aria-controls="bank" aria-selected="false">
                            Bank Account
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                            Documents
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                            Security
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="profileTabContent">
                    <!-- Business Information Tab -->
                    <div class="tab-pane fade show active" id="business" role="tabpanel" aria-labelledby="business-tab">
                        <div class="card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Business Information</h5>
                                    <button class="btn btn-sm btn-outline-primary" id="edit-business-btn">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                </div>

                                <div id="business-info">
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Business Name</div>
                                        <div class="col-md-8" id="display-business-name"><?php echo htmlspecialchars($seller->business_name ?? ''); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Business Type</div>
                                        <div class="col-md-8" id="display-business-type"><?php echo isset($seller->tax_classification) && !empty($seller->tax_classification) ? htmlspecialchars(ucfirst($seller->tax_classification)) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">GST Number</div>
                                        <div class="col-md-8" id="display-gst-number"><?php echo !empty($seller->gst_number) ? htmlspecialchars($seller->gst_number) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">PAN Number</div>
                                        <div class="col-md-8" id="display-pan-number"><?php echo !empty($seller->pan_number) ? htmlspecialchars($seller->pan_number) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Business Description</div>
                                        <div class="col-md-8" id="display-business-description"><?php echo !empty($seller->business_description) ? htmlspecialchars($seller->business_description) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Business Address</div>
                                        <div class="col-md-8" id="display-business-address"><?php echo htmlspecialchars($seller->business_address ?? ''); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">City</div>
                                        <div class="col-md-8"><?php echo !empty($seller->business_city) ? htmlspecialchars($seller->business_city) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">State</div>
                                        <div class="col-md-8"><?php echo !empty($seller->business_state) ? htmlspecialchars($seller->business_state) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Postal Code</div>
                                        <div class="col-md-8"><?php echo !empty($seller->business_postal_code) ? htmlspecialchars($seller->business_postal_code) : '-'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Account Tab -->
                    <div class="tab-pane fade" id="bank" role="tabpanel" aria-labelledby="bank-tab">
                        <div class="card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Bank Account Information</h5>
                                    <button class="btn btn-sm btn-outline-primary" id="edit-bank-btn">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                </div>

                                <div id="bank-info">
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Account Holder Name</div>
                                        <div class="col-md-8" id="display-account-holder"><?php echo !empty($seller->account_holder_name) ? htmlspecialchars($seller->account_holder_name) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Account Number</div>
                                        <div class="col-md-8" id="display-account-number"><?php echo !empty($seller->bank_account_number) ? htmlspecialchars($seller->bank_account_number) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">IFSC Code</div>
                                        <div class="col-md-8" id="display-ifsc-code"><?php echo !empty($seller->ifsc_code) ? htmlspecialchars($seller->ifsc_code) : '-'; ?></div>
                                    </div>
                                    <?php if (!empty($seller->bank_account_details)):
                                        $bank_details = json_decode($seller->bank_account_details, true);
                                        if (is_array($bank_details)):
                                    ?>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Bank Name</div>
                                        <div class="col-md-8" id="display-bank-name"><?php echo isset($bank_details['bank_name']) ? htmlspecialchars($bank_details['bank_name']) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Account Type</div>
                                        <div class="col-md-8" id="display-account-type"><?php echo isset($bank_details['account_type']) ? htmlspecialchars(ucfirst($bank_details['account_type'])) : '-'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-muted">Branch</div>
                                        <div class="col-md-8" id="display-branch"><?php echo isset($bank_details['branch']) ? htmlspecialchars($bank_details['branch']) : '-'; ?></div>
                                    </div>
                                    <?php endif; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Tab -->
                    <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                        <div class="card shadow">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Verification Documents</h5>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Identity Proof</h6>
                                                <p class="card-text small text-muted">Upload Aadhaar Card, PAN Card, Voter ID, or Driving License</p>

                                                <?php if (!empty($seller->id_document_path)): ?>
                                                <div>
                                                    <p class="text-success"><i class="bi bi-check-circle"></i> Document uploaded</p>
                                                    <p class="small text-muted"><?php echo htmlspecialchars(basename($seller->id_document_path)); ?></p>
                                                </div>
                                                <?php else: ?>
                                                <div id="identity-proof-container">
                                                    <div class="document-upload-container" id="identity-proof-upload">
                                                        <i class="bi bi-upload fs-3 mb-2"></i>
                                                        <p class="mb-0">Click to upload Identity Proof</p>
                                                    </div>
                                                    <input type="file" id="identity-proof-input" accept="image/*,.pdf" style="display: none;">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Tax Document</h6>
                                                <p class="card-text small text-muted">Upload GST Certificate or Tax Registration Document</p>

                                                <?php if (!empty($seller->tax_document_path)): ?>
                                                <div>
                                                    <p class="text-success"><i class="bi bi-check-circle"></i> Document uploaded</p>
                                                    <p class="small text-muted"><?php echo htmlspecialchars(basename($seller->tax_document_path)); ?></p>
                                                </div>
                                                <?php else: ?>
                                                <div id="tax-document-container">
                                                    <div class="document-upload-container" id="tax-document-upload">
                                                        <i class="bi bi-upload fs-3 mb-2"></i>
                                                        <p class="mb-0">Click to upload Tax Document</p>
                                                    </div>
                                                    <input type="file" id="tax-document-input" accept="image/*,.pdf" style="display: none;">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Bank Document</h6>
                                                <p class="card-text small text-muted">Upload Cancelled Cheque or Bank Statement</p>

                                                <?php if (!empty($seller->bank_document_path)): ?>
                                                <div>
                                                    <p class="text-success"><i class="bi bi-check-circle"></i> Document uploaded</p>
                                                    <p class="small text-muted"><?php echo htmlspecialchars(basename($seller->bank_document_path)); ?></p>
                                                </div>
                                                <?php else: ?>
                                                <div id="bank-proof-container">
                                                    <div class="document-upload-container" id="bank-proof-upload">
                                                        <i class="bi bi-upload fs-3 mb-2"></i>
                                                        <p class="mb-0">Click to upload Bank Document</p>
                                                    </div>
                                                    <input type="file" id="bank-proof-input" accept="image/*,.pdf" style="display: none;">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-2">
                                    <button class="btn btn-success" id="submit-documents-btn">
                                        <i class="bi bi-check-circle"></i> Submit Documents for Verification
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <div class="card shadow">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Security Settings</h5>

                                <div class="mb-4">
                                    <h6>Change Password</h6>
                                    <form id="password-form" method="POST" action="change_password.php">
                                        <div class="mb-3">
                                            <label for="current-password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current-password" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new-password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new-password" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm-password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                                        </div>
                                        <button type="submit" class="btn btn-success" name="change_password">Update Password</button>
                                    </form>
                                </div>

                                <hr>

                                <div>
                                    <h6>Account Deactivation</h6>
                                    <p class="text-muted small">Deactivating your seller account will hide your products from customers. You can reactivate your account at any time.</p>
                                    <button class="btn btn-outline-danger" id="deactivate-account-btn">Deactivate Account</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/main.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    </script>
</body>
</html>