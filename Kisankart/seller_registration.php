<?php
session_start();

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("X-Content-Type-Options: nosniff");

// Initialize variables
$registration_success = false;
$registration_error = '';
$form_data = [
    'username' => '',
    'firstName' => '',
    'lastName' => '',
    'email' => '',
    'phone' => '',
    'business_name' => '',
    'business_description' => '',
    'business_address' => '',
    'gst_number' => '',
    'pan_number' => ''
];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_registration'])) {
    // Include database and seller registration model
    include_once __DIR__ . '/api/config/database.php';
    include_once __DIR__ . '/api/models/SellerRegistration.php';

    // Get database connection
    try {
        $database = new Database();
        $db = $database->getConnection();

        if (!$db) {
            throw new Exception("Failed to establish database connection");
        }
    } catch (Exception $e) {
        $registration_error = 'Unable to connect to the database. Please try again later.';
        error_log("Database connection error: " . $e->getMessage());
        // Set a flag to skip further processing
        $db_connection_error = true;
    }

    // Include file uploader utility
    include_once __DIR__ . '/api/utils/FileUploader.php';

    // Create file uploader instance
    $uploader = new FileUploader('uploads/seller', [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf'
    ]);

    // Process name field - split into first and last name
    $full_name = trim($_POST['full_name'] ?? '');
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[1] ?? '';

    // Process date of birth
    $date_of_birth = !empty($_POST['dob']) ? date('Y-m-d', strtotime($_POST['dob'])) : null;

    // Process product categories
    $product_categories = isset($_POST['product_categories']) ? $_POST['product_categories'] : [];
    $product_categories_json = json_encode($product_categories);

    // Handle file uploads
    $id_document_path = '';
    if (isset($_FILES['id_upload']) && !empty($_FILES['id_upload']['name'])) {
        $id_document_path = $uploader->upload($_FILES['id_upload'], 'id_documents');
        if (!$id_document_path) {
            $registration_error = 'ID document upload failed: ' . $uploader->getError();
        }
    }

    $tax_document_path = '';
    if (isset($_FILES['tax_document']) && !empty($_FILES['tax_document']['name'])) {
        $tax_document_path = $uploader->upload($_FILES['tax_document'], 'tax_documents');
        if (!$tax_document_path) {
            $registration_error = 'Tax document upload failed: ' . $uploader->getError();
        }
    }

    $bank_document_path = '';
    if (isset($_FILES['bank_document']) && !empty($_FILES['bank_document']['name'])) {
        $bank_document_path = $uploader->upload($_FILES['bank_document'], 'bank_documents');
        if (!$bank_document_path) {
            $registration_error = 'Bank document upload failed: ' . $uploader->getError();
        }
    }

    $store_logo_path = '';
    if (isset($_FILES['store_logo']) && !empty($_FILES['store_logo']['name'])) {
        $store_logo_path = $uploader->upload($_FILES['store_logo'], 'store_logos');
        if (!$store_logo_path) {
            $registration_error = 'Store logo upload failed: ' . $uploader->getError();
        }
    }

    // Prepare bank account details as JSON
    $bank_account_details = [
        'account_number' => trim($_POST['bank_account_number'] ?? ''),
        'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
        'ifsc_code' => trim($_POST['ifsc_code'] ?? ''),
        'bank_name' => '',
        'branch_name' => ''
    ];
    $bank_account_details_json = json_encode($bank_account_details);

    // Validate form data
    $form_data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'date_of_birth' => $date_of_birth,
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['mobile'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'business_name' => trim($_POST['business_name'] ?? ''),
        'business_description' => trim($_POST['business_description'] ?? ''),
        'business_logo' => '',
        'business_address' => trim($_POST['business_address'] ?? ''),
        'business_country' => trim($_POST['business_country'] ?? ''),
        'business_state' => trim($_POST['business_state'] ?? ''),
        'business_city' => trim($_POST['business_city'] ?? ''),
        'business_postal_code' => trim($_POST['business_postal_code'] ?? ''),
        'gst_number' => trim($_POST['gst'] ?? ''),
        'pan_number' => trim($_POST['pan'] ?? ''),
        'id_type' => trim($_POST['id_type'] ?? ''),
        'id_document_path' => $id_document_path,
        'tax_classification' => trim($_POST['tax_classification'] ?? ''),
        'tax_document_path' => $tax_document_path,
        'bank_account_details' => $bank_account_details_json,
        'bank_account_number' => trim($_POST['bank_account_number'] ?? ''),
        'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
        'ifsc_code' => trim($_POST['ifsc_code'] ?? ''),
        'bank_document_path' => $bank_document_path,
        'store_display_name' => trim($_POST['display_name'] ?? trim($_POST['store_name'] ?? '')),
        'product_categories' => $product_categories_json,
        'marketplace' => trim($_POST['marketplace'] ?? ''),
        'store_logo_path' => $store_logo_path
    ];

    // Validate required fields
    $required_fields = [
        'first_name' => 'Full Name',
        'email' => 'Email Address',
        'phone' => 'Mobile Number',
        'password' => 'Password',
        'business_name' => 'Business Name',
        'business_address' => 'Business Address'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty($form_data[$field])) {
            $missing_fields[] = $label;
        }
    }

    if (!empty($missing_fields)) {
        $registration_error = 'Please fill in the following required fields: ' . implode(', ', $missing_fields);
        error_log("Missing required fields: " . implode(', ', $missing_fields));
    }

    // Validate email format
    if (empty($registration_error) && !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $registration_error = 'Please enter a valid email address.';
    }

    // Validate password match
    if (empty($registration_error) && $form_data['password'] !== $form_data['confirm_password']) {
        $registration_error = 'Passwords do not match.';
    }

    // Validate password length
    if (empty($registration_error) && strlen($form_data['password']) < 6) {
        $registration_error = 'Password must be at least 6 characters long.';
    }

    // If validation passes and no database connection error, register the seller
    if (empty($registration_error) && !isset($db_connection_error)) {
        // Create seller registration object
        $seller = new SellerRegistration($db);

        // Validate table structure
        $structure_validation = $seller->validateTableStructure();
        if (!$structure_validation['valid']) {
            $registration_error = 'System error: ' . $structure_validation['message'] . '. Please contact support.';
            error_log("Table structure validation failed: " . $structure_validation['message']);
        }

        // Only proceed with email/phone checks if table structure is valid
        if (empty($registration_error)) {
            // Check if email already exists
            $seller->email = $form_data['email'];
            $email_exists = $seller->emailExists();
            error_log("Checking if email exists: " . $form_data['email'] . " - Result: " . ($email_exists ? "YES" : "NO"));

            if ($email_exists) {
                $registration_error = 'Email already exists. Please use a different email address.';
                error_log("Registration error: Email already exists: " . $form_data['email']);
            } else {
                // Check if phone already exists
                $seller->phone = $form_data['phone'];
                $phone_exists = $seller->phoneExists();
                error_log("Checking if phone exists: " . $form_data['phone'] . " - Result: " . ($phone_exists ? "YES" : "NO"));

                if ($phone_exists) {
                    $registration_error = 'Phone number already exists. Please use a different phone number.';
                    error_log("Registration error: Phone already exists: " . $form_data['phone']);
                } else {
                    // Set seller registration properties
                    $seller->first_name = $form_data['first_name'];
                    $seller->last_name = $form_data['last_name'];
                    $seller->date_of_birth = $form_data['date_of_birth'];
                    $seller->password = $form_data['password'];
                    $seller->business_name = $form_data['business_name'];
                    $seller->business_description = $form_data['business_description'];
                    $seller->business_logo = $form_data['business_logo'];
                    $seller->business_address = $form_data['business_address'];
                    $seller->business_country = $form_data['business_country'];
                    $seller->business_state = $form_data['business_state'];
                    $seller->business_city = $form_data['business_city'];
                    $seller->business_postal_code = $form_data['business_postal_code'];
                    $seller->gst_number = $form_data['gst_number'];
                    $seller->pan_number = $form_data['pan_number'];
                    $seller->id_type = $form_data['id_type'];
                    $seller->id_document_path = $form_data['id_document_path'];
                    $seller->tax_classification = $form_data['tax_classification'];
                    $seller->tax_document_path = $form_data['tax_document_path'];
                    $seller->bank_account_details = $form_data['bank_account_details'];
                    $seller->bank_account_number = $form_data['bank_account_number'];
                    $seller->account_holder_name = $form_data['account_holder_name'];
                    $seller->ifsc_code = $form_data['ifsc_code'];
                    $seller->bank_document_path = $form_data['bank_document_path'];
                    $seller->store_display_name = $form_data['store_display_name'];
                    $seller->product_categories = $form_data['product_categories'];
                    $seller->marketplace = $form_data['marketplace'];
                    $seller->store_logo_path = $form_data['store_logo_path'];
                    $seller->status = 'pending';

                    // Before attempting to create, do a direct database check for duplicates
                    try {
                        // Direct database check for email
                        $check_email_query = "SELECT id, email FROM " . $seller->getTableName() . " WHERE email = :email LIMIT 1";
                        $check_email_stmt = $db->prepare($check_email_query);
                        $check_email_stmt->bindParam(':email', $form_data['email']);
                        $check_email_stmt->execute();

                        if ($check_email_stmt->rowCount() > 0) {
                            $email_record = $check_email_stmt->fetch(PDO::FETCH_ASSOC);
                            $registration_error = 'Email address ' . $form_data['email'] . ' is already registered (ID: ' . $email_record['id'] . '). Please use a different email address.';
                            error_log("Direct DB check - Email already exists: " . $form_data['email'] . " with ID: " . $email_record['id']);
                        } else {
                            // Direct database check for phone
                            $check_phone_query = "SELECT id, phone FROM " . $seller->getTableName() . " WHERE phone = :phone LIMIT 1";
                            $check_phone_stmt = $db->prepare($check_phone_query);
                            $check_phone_stmt->bindParam(':phone', $form_data['phone']);
                            $check_phone_stmt->execute();

                            if ($check_phone_stmt->rowCount() > 0) {
                                $phone_record = $check_phone_stmt->fetch(PDO::FETCH_ASSOC);
                                $registration_error = 'Phone number ' . $form_data['phone'] . ' is already registered (ID: ' . $phone_record['id'] . '). Please use a different phone number.';
                                error_log("Direct DB check - Phone already exists: " . $form_data['phone'] . " with ID: " . $phone_record['id']);
                            } else {
                                // No duplicates found, proceed with registration
                                error_log("Attempting to create seller registration for email: " . $seller->email . " and phone: " . $seller->phone);

                                if ($seller->create()) {
                                    $registration_success = true;
                                    // Log success
                                    error_log("Seller registration successful for email: " . $seller->email);
                                } else {
                                    $registration_error = 'Failed to create seller registration. Please try again.';
                                    // Log error
                                    error_log("Seller registration failed for email: " . $seller->email . " and phone: " . $seller->phone);

                                    // Check for database error
                                    $db_error = $db->errorInfo();
                                    if (!empty($db_error[2])) {
                                        error_log("Database error: " . $db_error[2]);

                                        // Check for duplicate entry error
                                        if (strpos($db_error[2], 'Duplicate entry') !== false) {
                                            if (strpos($db_error[2], 'email') !== false) {
                                                $registration_error = 'Email address is already registered. Please use a different email address.';
                                            } elseif (strpos($db_error[2], 'phone') !== false) {
                                                $registration_error = 'Phone number is already registered. Please use a different phone number.';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        $registration_error = 'Database error occurred. Please try again later.';
                        error_log("PDO Exception during seller registration: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Registration | Kisan Kart</title>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet" type="text/css" media="all" />
    <link href="css/modern-style.css" rel="stylesheet" type="text/css" media="all" />
    <link href="css/seller-registration.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
    <!-- Navigation -->
    <div class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="frontend/index.php">
                <i class="fas fa-leaf text-success"></i> Kisan Kart
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/products.php">
                            <i class="fas fa-shopping-basket"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-user"></i> Customer Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="seller_login.php">
                            <i class="fas fa-store"></i> Seller Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="seller_registration.php">
                            <i class="fas fa-user-plus"></i> Seller Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="frontend/index.php#about">
                            <i class="fas fa-info-circle"></i> About Us
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="registration-container">
                <div class="registration-header">
                    <h1><i class="fas fa-store"></i> Seller Registration</h1>
                    <p>Join Kisan Kart as a seller and start selling your agricultural products to customers across the country.</p>
                </div>

                <?php if ($registration_success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h2>Registration Successful!</h2>
                        <p>Your seller account has been created successfully. You can now <a href="login.php">login</a> to your account.</p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($registration_error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $registration_error; ?>
                        </div>
                    <?php endif; ?>

                <!-- Progress Bar -->
                <div class="progress-container">
                    <ul class="progress-steps">
                        <li class="active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-label">Account</div>
                        </li>
                        <li data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-label">Business</div>
                        </li>
                        <li data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-label">Identity</div>
                        </li>
                        <li data-step="4">
                            <div class="step-number">4</div>
                            <div class="step-label">Tax</div>
                        </li>
                        <li data-step="5">
                            <div class="step-number">5</div>
                            <div class="step-label">Bank</div>
                        </li>
                        <li data-step="6">
                            <div class="step-number">6</div>
                            <div class="step-label">Store</div>
                        </li>
                        <li data-step="7">
                            <div class="step-number">7</div>
                            <div class="step-label">Billing</div>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form id="sellerRegistrationForm" action="seller_registration.php" method="POST" enctype="multipart/form-data" <?php if ($registration_success): ?>style="display: none;"<?php endif; ?>>
                    <!-- Step 1: Account Information -->
                    <div class="form-step active" id="step1">
                        <h2><i class="fas fa-user-circle"></i> Account Information</h2>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="password">Create a Password <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                            <div class="password-strength">
                                <div class="strength-meter"></div>
                                <div class="strength-text">Password strength: <span>Weak</span></div>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="store_name">Seller/Store Name <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-store"></i>
                                <input type="text" id="store_name" name="store_name" class="form-control" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="business_name">Legal Business Name (if registering as a business)</label>
                            <div class="input-with-icon">
                                <i class="fas fa-building"></i>
                                <input type="text" id="business_name" name="business_name" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="mobile">Mobile Number (with country code) <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-mobile-alt"></i>
                                <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="+91 XXXXXXXXXX" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-navigation">
                            <button type="button" class="next-btn" data-next="2">Next <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Step 2: Business Details -->
                    <div class="form-step" id="step2">
                        <h2><i class="fas fa-building"></i> Business Details</h2>

                        <div class="form-group">
                            <label for="business_type">Business Type <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-briefcase"></i>
                                <select id="business_type" name="business_type" class="form-control" required>
                                    <option value="">Select Business Type</option>
                                    <option value="individual">Individual</option>
                                    <option value="public">Publicly listed business</option>
                                    <option value="private">Privately owned business</option>
                                    <option value="charity">Charity</option>
                                </select>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="business_address">Business Address <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <textarea id="business_address" name="business_address" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_country">Country <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-globe"></i>
                                    <select id="business_country" name="business_country" class="form-control" required>
                                        <option value="">Select Country</option>
                                        <option value="IN">India</option>
                                        <option value="US">United States</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="CA">Canada</option>
                                        <option value="AU">Australia</option>
                                    </select>
                                </div>
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="business_state">State/Province <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-map"></i>
                                    <input type="text" id="business_state" name="business_state" class="form-control" required>
                                </div>
                                <div class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_city">City <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-city"></i>
                                    <input type="text" id="business_city" name="business_city" class="form-control" required>
                                </div>
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="business_postal_code">Postal Code <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-mail-bulk"></i>
                                    <input type="text" id="business_postal_code" name="business_postal_code" class="form-control" required>
                                </div>
                                <div class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-navigation">
                            <button type="button" class="prev-btn" data-prev="1"><i class="fas fa-arrow-left"></i> Previous</button>
                            <button type="button" class="next-btn" data-next="3">Next <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Step 3: Identity Verification -->
                    <div class="form-step" id="step3">
                        <h2><i class="fas fa-id-card"></i> Identity Verification</h2>

                        <div class="form-group">
                            <label for="full_name">Full Name (as per ID) <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="full_name" name="full_name" class="form-control" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="dob">Date of Birth <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" id="dob" name="dob" class="form-control" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="id_type">ID Type <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-id-badge"></i>
                                <select id="id_type" name="id_type" class="form-control" required>
                                    <option value="">Select ID Type</option>
                                    <option value="passport">Passport</option>
                                    <option value="national_id">National ID Card</option>
                                    <option value="business_reg">Business Registration Document</option>
                                </select>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="id_upload">Upload ID Document <span class="optional">(Optional)</span></label>
                            <div class="file-upload">
                                <input type="file" id="id_upload" name="id_upload" class="file-input" accept="image/*, application/pdf">
                                <div class="file-upload-box">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>(Optional) Drag & drop files here or <span>browse</span></p>
                                    <p class="file-info">Supported formats: JPG, PNG, PDF (Max 5MB)</p>
                                </div>
                                <div class="file-preview"></div>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-navigation">
                            <button type="button" class="prev-btn" data-prev="2"><i class="fas fa-arrow-left"></i> Previous</button>
                            <button type="button" class="next-btn" data-next="4">Next <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Step 4: Tax Information -->
                    <div class="form-step" id="step4">
                        <h2><i class="fas fa-file-invoice"></i> Tax Information</h2>

                        <div class="form-group">
                            <label for="pan">PAN (Permanent Account Number) <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-id-card-alt"></i>
                                <input type="text" id="pan" name="pan" class="form-control" placeholder="ABCDE1234F" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="gst">GST Number (if applicable)</label>
                            <div class="input-with-icon">
                                <i class="fas fa-receipt"></i>
                                <input type="text" id="gst" name="gst" class="form-control" placeholder="22AAAAA0000A1Z5">
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="tax_classification">Tax Classification <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-file-alt"></i>
                                <select id="tax_classification" name="tax_classification" class="form-control" required>
                                    <option value="">Select Tax Classification</option>
                                    <option value="individual">Individual</option>
                                    <option value="business">Business</option>
                                </select>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="tax_document">Upload Tax Document <span class="optional">(Optional)</span></label>
                            <div class="file-upload">
                                <input type="file" id="tax_document" name="tax_document" class="file-input" accept="image/*, application/pdf">
                                <div class="file-upload-box">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>(Optional) Drag & drop files here or <span>browse</span></p>
                                    <p class="file-info">Supported formats: JPG, PNG, PDF (Max 5MB)</p>
                                </div>
                                <div class="file-preview"></div>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-navigation">
                            <button type="button" class="prev-btn" data-prev="3"><i class="fas fa-arrow-left"></i> Previous</button>
                            <button type="button" class="next-btn" data-next="5">Next <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Step 5: Bank Account Details -->
                    <div class="form-step" id="step5">
                        <h2><i class="fas fa-university"></i> Bank Account Details</h2>

                        <div class="form-group">
                            <label for="bank_account_number">Bank Account Number <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-credit-card"></i>
                                <input type="text" id="bank_account_number" name="bank_account_number" class="form-control" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="account_holder_name">Account Holder's Name <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="account_holder_name" name="account_holder_name" class="form-control" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="ifsc_code">IFSC Code <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-university"></i>
                                <input type="text" id="ifsc_code" name="ifsc_code" class="form-control" placeholder="ABCD0123456" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="bank_document">Bank Statement or Canceled Cheque <span class="optional">(Optional)</span></label>
                            <div class="file-upload">
                                <input type="file" id="bank_document" name="bank_document" class="file-input" accept="image/*, application/pdf">
                                <div class="file-upload-box">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>(Optional) Drag & drop files here or <span>browse</span></p>
                                    <p class="file-info">Supported formats: JPG, PNG, PDF (Max 5MB)</p>
                                </div>
                                <div class="file-preview"></div>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-navigation">
                            <button type="button" class="prev-btn" data-prev="4"><i class="fas fa-arrow-left"></i> Previous</button>
                            <button type="button" class="next-btn" data-next="6">Next <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Step 6: Store Setup -->
                    <div class="form-step" id="step6">
                        <h2><i class="fas fa-store-alt"></i> Store Setup</h2>

                        <div class="form-group">
                            <label for="display_name">Store Name (Display Name) <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-store"></i>
                                <input type="text" id="display_name" name="display_name" class="form-control" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label>Product Categories You Plan to Sell In <span class="required">*</span></label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="category_fruits" name="product_categories[]" value="fruits">
                                    <label for="category_fruits">Fruits</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="category_vegetables" name="product_categories[]" value="vegetables">
                                    <label for="category_vegetables">Vegetables</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="category_grains" name="product_categories[]" value="grains">
                                    <label for="category_grains">Grains & Cereals</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="category_dairy" name="product_categories[]" value="dairy">
                                    <label for="category_dairy">Dairy Products</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="category_spices" name="product_categories[]" value="spices">
                                    <label for="category_spices">Spices & Herbs</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="category_organic" name="product_categories[]" value="organic">
                                    <label for="category_organic">Organic Products</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="category_other" name="product_categories[]" value="other">
                                    <label for="category_other">Other</label>
                                </div>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="marketplace">Marketplace (Country) <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-globe"></i>
                                <select id="marketplace" name="marketplace" class="form-control" required>
                                    <option value="">Select Marketplace</option>
                                    <option value="IN">India</option>
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="CA">Canada</option>
                                    <option value="AU">Australia</option>
                                </select>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="store_logo">Store Logo (Optional)</label>
                            <div class="file-upload">
                                <input type="file" id="store_logo" name="store_logo" class="file-input" accept="image/*">
                                <div class="file-upload-box">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>(Optional) Drag & drop files here or <span>browse</span></p>
                                    <p class="file-info">Supported formats: JPG, PNG (Max 2MB)</p>
                                </div>
                                <div class="file-preview"></div>
                            </div>
                        </div>

                        <div class="form-navigation">
                            <button type="button" class="prev-btn" data-prev="5"><i class="fas fa-arrow-left"></i> Previous</button>
                            <button type="button" class="next-btn" data-next="7">Next <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Step 7: Billing and Deposit Information -->
                    <div class="form-step" id="step7">
                        <h2><i class="fas fa-credit-card"></i> Billing and Deposit Information</h2>

                        <div class="form-group">
                            <label for="card_number">Credit Card Number <span class="required">*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-credit-card"></i>
                                <input type="text" id="card_number" name="card_number" class="form-control" placeholder="XXXX XXXX XXXX XXXX" required>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="card_holder_name">Card Holder's Name <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="card_holder_name" name="card_holder_name" class="form-control" required>
                                </div>
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="card_expiry">Expiry Date <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                    <input type="text" id="card_expiry" name="card_expiry" class="form-control" placeholder="MM/YY" required>
                                </div>
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="card_cvv">CVV <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="text" id="card_cvv" name="card_cvv" class="form-control" placeholder="XXX" required>
                                </div>
                                <div class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="billing_address">Billing Address (If different from business address)</label>
                            <div class="input-with-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <textarea id="billing_address" name="billing_address" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="terms_conditions" name="terms_conditions" required>
                                <label for="terms_conditions">I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a> <span class="required">*</span></label>
                            </div>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-navigation">
                            <button type="button" class="prev-btn" data-prev="6"><i class="fas fa-arrow-left"></i> Previous</button>
                            <button type="submit" name="submit_registration" class="submit-btn">Submit Registration <i class="fas fa-check-circle"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>ABOUT US</h4>
                    <p>Kisan Kart is an online marketplace connecting farmers directly with consumers, eliminating middlemen and ensuring fair prices for agricultural products.</p>
                </div>

                <div class="footer-column">
                    <h4>QUICK LINKS</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                        <li><a href="seller_registration.php">Become a Seller</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>CONTACT US</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Main Street, City, Country</li>
                        <li><i class="fas fa-phone"></i> +91 1234567890</li>
                        <li><i class="fas fa-envelope"></i> info@kisankart.com</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2023 Kisan Kart. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script src="js/seller-registration.js"></script>
</body>
</html>
