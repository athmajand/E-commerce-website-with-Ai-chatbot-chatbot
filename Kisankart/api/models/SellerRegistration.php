<?php
class SellerRegistration {
    // Database connection and table name
    private $conn;
    private $table_name = "seller_registrations";

    // Object properties
    public $id;
    public $first_name;
    public $last_name;
    public $date_of_birth; // Added field
    public $email;
    public $phone;
    public $password;
    public $business_name;
    public $business_description;
    public $business_logo;
    public $business_address;
    public $business_country;
    public $business_state;
    public $business_city;
    public $business_postal_code;
    public $gst_number;
    public $pan_number;
    public $id_type; // Added field
    public $id_document_path; // Added field
    public $tax_classification; // Added field
    public $tax_document_path; // Added field
    public $bank_account_details;
    public $bank_account_number; // Added field
    public $account_holder_name; // Added field
    public $ifsc_code; // Added field
    public $bank_document_path; // Added field
    public $store_display_name; // Added field
    public $product_categories; // Added field (JSON)
    public $marketplace; // Added field
    public $store_logo_path; // Added field
    public $verification_token;
    public $is_verified;
    public $status;
    public $last_login;
    public $notes;
    public $created_at;
    public $updated_at;
    public $role = 'seller'; // Always seller for this model

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Get table name
    public function getTableName() {
        return $this->table_name;
    }

    // Check if table structure is valid
    public function validateTableStructure() {
        try {
            // Check if table exists
            $table_check = $this->conn->query("SHOW TABLES LIKE '{$this->table_name}'");
            if ($table_check->rowCount() == 0) {
                error_log("SellerRegistration validateTableStructure() - Table {$this->table_name} does not exist");
                return [
                    'valid' => false,
                    'message' => "Table {$this->table_name} does not exist"
                ];
            }

            // Check required columns
            $required_columns = [
                'id', 'first_name', 'last_name', 'email', 'phone', 'password',
                'business_name', 'business_address', 'status'
            ];

            $columns_query = $this->conn->query("DESCRIBE {$this->table_name}");
            $existing_columns = [];

            while ($column = $columns_query->fetch(PDO::FETCH_ASSOC)) {
                $existing_columns[] = $column['Field'];
            }

            $missing_columns = array_diff($required_columns, $existing_columns);

            if (!empty($missing_columns)) {
                $missing_list = implode(', ', $missing_columns);
                error_log("SellerRegistration validateTableStructure() - Missing columns: {$missing_list}");
                return [
                    'valid' => false,
                    'message' => "Table {$this->table_name} is missing required columns: {$missing_list}"
                ];
            }

            // Check for unique constraints on email and phone
            $index_query = $this->conn->query("SHOW INDEX FROM {$this->table_name} WHERE Column_name IN ('email', 'phone')");
            $has_email_index = false;
            $has_phone_index = false;

            while ($index = $index_query->fetch(PDO::FETCH_ASSOC)) {
                if ($index['Column_name'] == 'email') {
                    $has_email_index = true;
                }
                if ($index['Column_name'] == 'phone') {
                    $has_phone_index = true;
                }
            }

            if (!$has_email_index || !$has_phone_index) {
                $missing_indexes = [];
                if (!$has_email_index) $missing_indexes[] = 'email';
                if (!$has_phone_index) $missing_indexes[] = 'phone';

                $missing_list = implode(', ', $missing_indexes);
                error_log("SellerRegistration validateTableStructure() - Missing unique indexes on: {$missing_list}");
                return [
                    'valid' => false,
                    'message' => "Table {$this->table_name} is missing unique indexes on: {$missing_list}"
                ];
            }

            return [
                'valid' => true,
                'message' => "Table structure is valid"
            ];

        } catch (PDOException $e) {
            error_log("SellerRegistration validateTableStructure() - Exception: " . $e->getMessage());
            return [
                'valid' => false,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }

    // Error message property
    public $error = '';

    // Create seller registration
    public function create() {
        try {
            error_log("SellerRegistration create() - Starting creation for email: " . $this->email . ", phone: " . $this->phone);

            // Validate table structure
            $structure_validation = $this->validateTableStructure();
            if (!$structure_validation['valid']) {
                error_log("SellerRegistration create() - " . $structure_validation['message']);
                $this->error = $structure_validation['message'];
                return false;
            }

            // Double check if email exists before creating
            if ($this->emailExists()) {
                error_log("SellerRegistration create() - Email already exists: " . $this->email);
                $this->error = "Email already exists";
                return false;
            }

            // Double check if phone exists before creating
            if ($this->phoneExists()) {
                error_log("SellerRegistration create() - Phone already exists: " . $this->phone);
                $this->error = "Phone number already exists";
                return false;
            }

            // Query to insert record
            $query = "INSERT INTO " . $this->table_name . "
                      SET first_name=:first_name, last_name=:last_name, date_of_birth=:date_of_birth,
                          email=:email, phone=:phone, password=:password,
                          business_name=:business_name, business_description=:business_description,
                          business_logo=:business_logo, business_address=:business_address,
                          business_country=:business_country, business_state=:business_state,
                          business_city=:business_city, business_postal_code=:business_postal_code,
                          gst_number=:gst_number, pan_number=:pan_number,
                          id_type=:id_type, id_document_path=:id_document_path,
                          tax_classification=:tax_classification, tax_document_path=:tax_document_path,
                          bank_account_details=:bank_account_details,
                          bank_account_number=:bank_account_number, account_holder_name=:account_holder_name,
                          ifsc_code=:ifsc_code, bank_document_path=:bank_document_path,
                          store_display_name=:store_display_name, product_categories=:product_categories,
                          marketplace=:marketplace, store_logo_path=:store_logo_path,
                          verification_token=:verification_token, status=:status";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->first_name = htmlspecialchars(strip_tags($this->first_name ?? ''));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name ?? ''));
            $this->date_of_birth = $this->date_of_birth ?? null; // Date doesn't need sanitization
            $this->email = htmlspecialchars(strip_tags($this->email ?? ''));
            $this->phone = htmlspecialchars(strip_tags($this->phone ?? ''));
            $this->business_name = htmlspecialchars(strip_tags($this->business_name ?? ''));
            $this->business_description = htmlspecialchars(strip_tags($this->business_description ?? ''));
            $this->business_logo = htmlspecialchars(strip_tags($this->business_logo ?? ''));
            $this->business_address = htmlspecialchars(strip_tags($this->business_address ?? ''));
            $this->business_country = htmlspecialchars(strip_tags($this->business_country ?? ''));
            $this->business_state = htmlspecialchars(strip_tags($this->business_state ?? ''));
            $this->business_city = htmlspecialchars(strip_tags($this->business_city ?? ''));
            $this->business_postal_code = htmlspecialchars(strip_tags($this->business_postal_code ?? ''));
            $this->gst_number = htmlspecialchars(strip_tags($this->gst_number ?? ''));
            $this->pan_number = htmlspecialchars(strip_tags($this->pan_number ?? ''));
            $this->id_type = htmlspecialchars(strip_tags($this->id_type ?? ''));
            $this->id_document_path = htmlspecialchars(strip_tags($this->id_document_path ?? ''));
            $this->tax_classification = htmlspecialchars(strip_tags($this->tax_classification ?? ''));
            $this->tax_document_path = htmlspecialchars(strip_tags($this->tax_document_path ?? ''));
            $this->bank_account_number = htmlspecialchars(strip_tags($this->bank_account_number ?? ''));
            $this->account_holder_name = htmlspecialchars(strip_tags($this->account_holder_name ?? ''));
            $this->ifsc_code = htmlspecialchars(strip_tags($this->ifsc_code ?? ''));
            $this->bank_document_path = htmlspecialchars(strip_tags($this->bank_document_path ?? ''));
            $this->store_display_name = htmlspecialchars(strip_tags($this->store_display_name ?? ''));

            // Handle JSON data for product categories
            if (is_array($this->product_categories)) {
                $this->product_categories = json_encode($this->product_categories);
            } else {
                $this->product_categories = htmlspecialchars(strip_tags($this->product_categories ?? '[]'));
            }

            $this->marketplace = htmlspecialchars(strip_tags($this->marketplace ?? ''));
            $this->store_logo_path = htmlspecialchars(strip_tags($this->store_logo_path ?? ''));
            $this->status = isset($this->status) ? $this->status : 'pending';

            // Generate verification token if not provided
            if (empty($this->verification_token)) {
                $this->verification_token = bin2hex(random_bytes(32));
            }

            // Hash the password
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

            // Bind values
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":date_of_birth", $this->date_of_birth);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":business_name", $this->business_name);
            $stmt->bindParam(":business_description", $this->business_description);
            $stmt->bindParam(":business_logo", $this->business_logo);
            $stmt->bindParam(":business_address", $this->business_address);
            $stmt->bindParam(":business_country", $this->business_country);
            $stmt->bindParam(":business_state", $this->business_state);
            $stmt->bindParam(":business_city", $this->business_city);
            $stmt->bindParam(":business_postal_code", $this->business_postal_code);
            $stmt->bindParam(":gst_number", $this->gst_number);
            $stmt->bindParam(":pan_number", $this->pan_number);
            $stmt->bindParam(":id_type", $this->id_type);
            $stmt->bindParam(":id_document_path", $this->id_document_path);
            $stmt->bindParam(":tax_classification", $this->tax_classification);
            $stmt->bindParam(":tax_document_path", $this->tax_document_path);
            $stmt->bindParam(":bank_account_details", $this->bank_account_details);
            $stmt->bindParam(":bank_account_number", $this->bank_account_number);
            $stmt->bindParam(":account_holder_name", $this->account_holder_name);
            $stmt->bindParam(":ifsc_code", $this->ifsc_code);
            $stmt->bindParam(":bank_document_path", $this->bank_document_path);
            $stmt->bindParam(":store_display_name", $this->store_display_name);
            $stmt->bindParam(":product_categories", $this->product_categories);
            $stmt->bindParam(":marketplace", $this->marketplace);
            $stmt->bindParam(":store_logo_path", $this->store_logo_path);
            $stmt->bindParam(":verification_token", $this->verification_token);
            $stmt->bindParam(":status", $this->status);

            // Execute query
            try {
                error_log("SellerRegistration create() - Executing query to insert new record");
                $result = $stmt->execute();

                if($result) {
                    $this->id = $this->conn->lastInsertId();
                    error_log("SellerRegistration create() - Success: Registration created for email={$this->email} with ID={$this->id}");

                    // Send verification email
                    try {
                        include_once __DIR__ . '/../utils/EmailSender.php';
                        $emailSender = new EmailSender();
                        $emailSender->sendSellerVerificationEmail(
                            $this->email,
                            $this->first_name . ' ' . $this->last_name,
                            $this->verification_token
                        );
                        error_log("SellerRegistration create() - Verification email sent to: " . $this->email);
                    } catch (Exception $e) {
                        error_log("SellerRegistration create() - Failed to send verification email: " . $e->getMessage());
                        // Continue even if email sending fails
                    }

                    return true;
                } else {
                    $error_info = $stmt->errorInfo();
                    error_log("SellerRegistration create() - Error: " . json_encode($error_info));

                    // Check for duplicate entry error (MySQL error code 1062)
                    if (isset($error_info[1]) && $error_info[1] == 1062) {
                        error_log("SellerRegistration create() - Duplicate entry error detected");

                        // Check which field is causing the duplicate entry
                        if (strpos($error_info[2], 'email') !== false) {
                            error_log("SellerRegistration create() - Duplicate email detected: " . $this->email);
                            $this->error = "Email already exists";
                        }
                        if (strpos($error_info[2], 'phone') !== false) {
                            error_log("SellerRegistration create() - Duplicate phone detected: " . $this->phone);
                            $this->error = "Phone number already exists";
                        }
                    } else {
                        $this->error = "Failed to create seller registration";
                    }

                    return false;
                }
            } catch (PDOException $e) {
                error_log("SellerRegistration create() - PDO Exception: " . $e->getMessage());

                // Check for duplicate entry error
                if ($e->getCode() == 23000) {
                    error_log("SellerRegistration create() - Duplicate entry error in exception: " . $e->getMessage());

                    if (strpos($e->getMessage(), 'email') !== false) {
                        $this->error = "Email already exists";
                    } else if (strpos($e->getMessage(), 'phone') !== false) {
                        $this->error = "Phone number already exists";
                    } else {
                        $this->error = "Duplicate entry error";
                    }
                } else {
                    $this->error = "Database error: " . $e->getMessage();
                }

                return false;
            }
        } catch (PDOException $e) {
            error_log("SellerRegistration create() - Exception: " . $e->getMessage());
            return false;
        }
    }

    // Check if email exists
    public function emailExists() {
        try {
            // Query to check if email exists
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
            error_log("emailExists() - Checking email: " . $this->email);
            error_log("emailExists() - Query: " . $query);

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->email = htmlspecialchars(strip_tags($this->email));

            // Bind email
            $stmt->bindParam(1, $this->email);

            // Execute query
            $stmt->execute();

            // Get row count
            $num = $stmt->rowCount();
            error_log("emailExists() - Row count: " . $num);

            // If email exists
            if($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("emailExists() - Found existing email with ID: " . $row['id']);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("emailExists() - Exception: " . $e->getMessage());
            return false;
        }
    }

    // Check if phone exists
    public function phoneExists() {
        try {
            // Query to check if phone exists
            $query = "SELECT id FROM " . $this->table_name . " WHERE phone = ? LIMIT 0,1";
            error_log("phoneExists() - Checking phone: " . $this->phone);
            error_log("phoneExists() - Query: " . $query);

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->phone = htmlspecialchars(strip_tags($this->phone));

            // Bind phone
            $stmt->bindParam(1, $this->phone);

            // Execute query
            $stmt->execute();

            // Get row count
            $num = $stmt->rowCount();
            error_log("phoneExists() - Row count: " . $num);

            // If phone exists
            if($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("phoneExists() - Found existing phone with ID: " . $row['id']);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("phoneExists() - Exception: " . $e->getMessage());
            return false;
        }
    }

    // Read single registration by ID
    public function readOne() {
        try {
            // Log the attempt
            error_log("SellerRegistration readOne() - Attempting to read seller with ID: " . $this->id);

            // Check if ID is valid
            if (!isset($this->id) || empty($this->id)) {
                error_log("SellerRegistration readOne() - Invalid seller ID: " . ($this->id ?? 'null'));
                $this->error = "Invalid seller ID";
                return false;
            }

            // Check if connection is valid
            if (!$this->conn) {
                error_log("SellerRegistration readOne() - Database connection is null");
                $this->error = "Database connection failed";
                return false;
            }

            // Verify table exists
            try {
                $tableCheck = $this->conn->query("SHOW TABLES LIKE '{$this->table_name}'");
                if ($tableCheck->rowCount() == 0) {
                    error_log("SellerRegistration readOne() - Table {$this->table_name} does not exist");
                    $this->error = "Seller table does not exist";
                    return false;
                }
            } catch (PDOException $e) {
                error_log("SellerRegistration readOne() - Error checking table: " . $e->getMessage());
                $this->error = "Error checking seller table";
                return false;
            }

            // Query to read single record
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
            error_log("SellerRegistration readOne() - Query: " . $query . " with ID: " . $this->id);

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Bind ID
            $stmt->bindParam(1, $this->id);

            // Execute query
            $stmt->execute();

            // Check row count
            $rowCount = $stmt->rowCount();
            error_log("SellerRegistration readOne() - Row count: " . $rowCount);

            // Get retrieved row
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Set values to object properties
            if($row) {
                error_log("SellerRegistration readOne() - Found seller data for ID: " . $this->id);
                // Initialize all properties with default empty values or null
                $this->first_name = $row['first_name'] ?? '';
                $this->last_name = $row['last_name'] ?? '';
                $this->date_of_birth = $row['date_of_birth'] ?? null;
                $this->email = $row['email'] ?? '';
                $this->phone = $row['phone'] ?? '';
                $this->business_name = $row['business_name'] ?? '';
                $this->business_description = $row['business_description'] ?? '';
                $this->business_logo = $row['business_logo'] ?? '';
                $this->business_address = $row['business_address'] ?? '';
                $this->business_country = $row['business_country'] ?? '';
                $this->business_state = $row['business_state'] ?? '';
                $this->business_city = $row['business_city'] ?? '';
                $this->business_postal_code = $row['business_postal_code'] ?? '';
                $this->gst_number = $row['gst_number'] ?? '';
                $this->pan_number = $row['pan_number'] ?? '';
                $this->id_type = $row['id_type'] ?? '';
                $this->id_document_path = $row['id_document_path'] ?? '';
                $this->tax_classification = $row['tax_classification'] ?? '';
                $this->tax_document_path = $row['tax_document_path'] ?? '';
                $this->bank_account_details = $row['bank_account_details'] ?? '';
                $this->bank_account_number = $row['bank_account_number'] ?? '';
                $this->account_holder_name = $row['account_holder_name'] ?? '';
                $this->ifsc_code = $row['ifsc_code'] ?? '';
                $this->bank_document_path = $row['bank_document_path'] ?? '';
                $this->store_display_name = $row['store_display_name'] ?? '';
                $this->product_categories = $row['product_categories'] ?? '';
                $this->marketplace = $row['marketplace'] ?? '';
                $this->store_logo_path = $row['store_logo_path'] ?? '';
                $this->status = $row['status'] ?? 'pending';
                $this->verification_token = $row['verification_token'] ?? '';
                $this->is_verified = $row['is_verified'] ?? 0;
                $this->last_login = $row['last_login'] ?? null;
                $this->notes = $row['notes'] ?? '';
                $this->created_at = $row['created_at'] ?? '';
                $this->updated_at = $row['updated_at'] ?? '';

                // Log successful data retrieval
                error_log("SellerRegistration readOne() - Successfully loaded seller data: " .
                          "Name: {$this->first_name} {$this->last_name}, " .
                          "Email: {$this->email}, Business: {$this->business_name}");

                return true;
            } else {
                error_log("SellerRegistration readOne() - No seller found with ID: " . $this->id);
                $this->error = "Seller not found";
                return false;
            }
        } catch (PDOException $e) {
            error_log("SellerRegistration readOne() - PDO Exception: " . $e->getMessage());
            $this->error = "Database error: " . $e->getMessage();
            return false;
        } catch (Exception $e) {
            error_log("SellerRegistration readOne() - General Exception: " . $e->getMessage());
            $this->error = "Error retrieving seller data";
            return false;
        }
    }

    // Login with email
    public function loginWithEmail() {
        // Query to check if email exists
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE email = ?
                  LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind email
        $stmt->bindParam(1, $this->email);

        // Execute query
        $stmt->execute();

        // Get row count
        $num = $stmt->rowCount();

        // If email exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if(password_verify($this->password, $row['password'])) {
                // Temporarily disable verification check for testing
                // Check if email is verified
                /*if (!$row['is_verified']) {
                    $this->error = "Email not verified. Please check your email for verification link.";
                    return false;
                }

                // Check if account is approved
                if ($row['status'] !== 'approved' && $row['status'] !== 'pending') {
                    $this->error = "Your account has been " . $row['status'] . ". Please contact support.";
                    return false;
                }*/

                // Set values to object properties
                $this->id = $row['id'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->date_of_birth = $row['date_of_birth'] ?? null;
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->business_name = $row['business_name'];
                $this->business_description = $row['business_description'];
                $this->business_logo = $row['business_logo'];
                $this->business_address = $row['business_address'];
                $this->business_country = $row['business_country'];
                $this->business_state = $row['business_state'];
                $this->business_city = $row['business_city'];
                $this->business_postal_code = $row['business_postal_code'];
                $this->gst_number = $row['gst_number'];
                $this->pan_number = $row['pan_number'];
                $this->id_type = $row['id_type'] ?? null;
                $this->id_document_path = $row['id_document_path'] ?? null;
                $this->tax_classification = $row['tax_classification'] ?? null;
                $this->tax_document_path = $row['tax_document_path'] ?? null;
                $this->bank_account_number = $row['bank_account_number'] ?? null;
                $this->account_holder_name = $row['account_holder_name'] ?? null;
                $this->ifsc_code = $row['ifsc_code'] ?? null;
                $this->bank_document_path = $row['bank_document_path'] ?? null;
                $this->store_display_name = $row['store_display_name'] ?? null;
                $this->product_categories = $row['product_categories'] ?? null;
                $this->marketplace = $row['marketplace'] ?? null;
                $this->store_logo_path = $row['store_logo_path'] ?? null;
                $this->status = $row['status'];
                $this->is_verified = $row['is_verified'];
                $this->verification_token = $row['verification_token'];

                // Update last login time
                $this->updateLastLogin();

                return true;
            } else {
                $this->error = "Invalid password";
            }
        } else {
            $this->error = "Email not found";
        }

        return false;
    }

    // Login with phone
    public function loginWithPhone() {
        // Query to check if phone exists
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE phone = ?
                  LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->phone = htmlspecialchars(strip_tags($this->phone));

        // Bind phone
        $stmt->bindParam(1, $this->phone);

        // Execute query
        $stmt->execute();

        // Get row count
        $num = $stmt->rowCount();

        // If phone exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if(password_verify($this->password, $row['password'])) {
                // Temporarily disable verification check for testing
                // Check if email is verified
                /*if (!$row['is_verified']) {
                    $this->error = "Account not verified. Please check your email for verification link.";
                    return false;
                }

                // Check if account is approved
                if ($row['status'] !== 'approved' && $row['status'] !== 'pending') {
                    $this->error = "Your account has been " . $row['status'] . ". Please contact support.";
                    return false;
                }*/

                // Set values to object properties
                $this->id = $row['id'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->date_of_birth = $row['date_of_birth'] ?? null;
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->business_name = $row['business_name'];
                $this->business_description = $row['business_description'];
                $this->business_logo = $row['business_logo'];
                $this->business_address = $row['business_address'];
                $this->business_country = $row['business_country'];
                $this->business_state = $row['business_state'];
                $this->business_city = $row['business_city'];
                $this->business_postal_code = $row['business_postal_code'];
                $this->gst_number = $row['gst_number'];
                $this->pan_number = $row['pan_number'];
                $this->id_type = $row['id_type'] ?? null;
                $this->id_document_path = $row['id_document_path'] ?? null;
                $this->tax_classification = $row['tax_classification'] ?? null;
                $this->tax_document_path = $row['tax_document_path'] ?? null;
                $this->bank_account_number = $row['bank_account_number'] ?? null;
                $this->account_holder_name = $row['account_holder_name'] ?? null;
                $this->ifsc_code = $row['ifsc_code'] ?? null;
                $this->bank_document_path = $row['bank_document_path'] ?? null;
                $this->store_display_name = $row['store_display_name'] ?? null;
                $this->product_categories = $row['product_categories'] ?? null;
                $this->marketplace = $row['marketplace'] ?? null;
                $this->store_logo_path = $row['store_logo_path'] ?? null;
                $this->status = $row['status'];
                $this->is_verified = $row['is_verified'];
                $this->verification_token = $row['verification_token'];

                // Update last login time
                $this->updateLastLogin();

                return true;
            } else {
                $this->error = "Invalid password";
            }
        } else {
            $this->error = "Phone number not found";
        }

        return false;
    }

    // Update last login time
    private function updateLastLogin() {
        // Query to update last login time
        $query = "UPDATE " . $this->table_name . "
                  SET last_login = NOW()
                  WHERE id = :id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(":id", $this->id);

        // Execute query
        $stmt->execute();
    }

    // Update seller profile
    public function update() {
        try {
            // Query to update record
            $query = "UPDATE " . $this->table_name . "
                      SET first_name = :first_name, last_name = :last_name, date_of_birth = :date_of_birth,
                          phone = :phone, business_name = :business_name,
                          business_description = :business_description, business_logo = :business_logo,
                          business_address = :business_address, business_country = :business_country,
                          business_state = :business_state, business_city = :business_city,
                          business_postal_code = :business_postal_code, gst_number = :gst_number,
                          pan_number = :pan_number, id_type = :id_type, id_document_path = :id_document_path,
                          tax_classification = :tax_classification, tax_document_path = :tax_document_path,
                          bank_account_details = :bank_account_details, bank_account_number = :bank_account_number,
                          account_holder_name = :account_holder_name, ifsc_code = :ifsc_code,
                          bank_document_path = :bank_document_path, store_display_name = :store_display_name,
                          product_categories = :product_categories, marketplace = :marketplace,
                          store_logo_path = :store_logo_path
                      WHERE id = :id";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->first_name = htmlspecialchars(strip_tags($this->first_name ?? ''));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name ?? ''));
            $this->date_of_birth = $this->date_of_birth ?? null; // Date doesn't need sanitization
            $this->phone = htmlspecialchars(strip_tags($this->phone ?? ''));
            $this->business_name = htmlspecialchars(strip_tags($this->business_name ?? ''));
            $this->business_description = htmlspecialchars(strip_tags($this->business_description ?? ''));
            $this->business_logo = htmlspecialchars(strip_tags($this->business_logo ?? ''));
            $this->business_address = htmlspecialchars(strip_tags($this->business_address ?? ''));
            $this->business_country = htmlspecialchars(strip_tags($this->business_country ?? ''));
            $this->business_state = htmlspecialchars(strip_tags($this->business_state ?? ''));
            $this->business_city = htmlspecialchars(strip_tags($this->business_city ?? ''));
            $this->business_postal_code = htmlspecialchars(strip_tags($this->business_postal_code ?? ''));
            $this->gst_number = htmlspecialchars(strip_tags($this->gst_number ?? ''));
            $this->pan_number = htmlspecialchars(strip_tags($this->pan_number ?? ''));
            $this->id_type = htmlspecialchars(strip_tags($this->id_type ?? ''));
            $this->id_document_path = htmlspecialchars(strip_tags($this->id_document_path ?? ''));
            $this->tax_classification = htmlspecialchars(strip_tags($this->tax_classification ?? ''));
            $this->tax_document_path = htmlspecialchars(strip_tags($this->tax_document_path ?? ''));
            $this->bank_account_number = htmlspecialchars(strip_tags($this->bank_account_number ?? ''));
            $this->account_holder_name = htmlspecialchars(strip_tags($this->account_holder_name ?? ''));
            $this->ifsc_code = htmlspecialchars(strip_tags($this->ifsc_code ?? ''));
            $this->bank_document_path = htmlspecialchars(strip_tags($this->bank_document_path ?? ''));
            $this->store_display_name = htmlspecialchars(strip_tags($this->store_display_name ?? ''));

            // Handle JSON data for product categories
            if (is_array($this->product_categories)) {
                $this->product_categories = json_encode($this->product_categories);
            } else {
                $this->product_categories = htmlspecialchars(strip_tags($this->product_categories ?? '[]'));
            }

            $this->marketplace = htmlspecialchars(strip_tags($this->marketplace ?? ''));
            $this->store_logo_path = htmlspecialchars(strip_tags($this->store_logo_path ?? ''));
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind values
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":date_of_birth", $this->date_of_birth);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":business_name", $this->business_name);
            $stmt->bindParam(":business_description", $this->business_description);
            $stmt->bindParam(":business_logo", $this->business_logo);
            $stmt->bindParam(":business_address", $this->business_address);
            $stmt->bindParam(":business_country", $this->business_country);
            $stmt->bindParam(":business_state", $this->business_state);
            $stmt->bindParam(":business_city", $this->business_city);
            $stmt->bindParam(":business_postal_code", $this->business_postal_code);
            $stmt->bindParam(":gst_number", $this->gst_number);
            $stmt->bindParam(":pan_number", $this->pan_number);
            $stmt->bindParam(":id_type", $this->id_type);
            $stmt->bindParam(":id_document_path", $this->id_document_path);
            $stmt->bindParam(":tax_classification", $this->tax_classification);
            $stmt->bindParam(":tax_document_path", $this->tax_document_path);
            $stmt->bindParam(":bank_account_details", $this->bank_account_details);
            $stmt->bindParam(":bank_account_number", $this->bank_account_number);
            $stmt->bindParam(":account_holder_name", $this->account_holder_name);
            $stmt->bindParam(":ifsc_code", $this->ifsc_code);
            $stmt->bindParam(":bank_document_path", $this->bank_document_path);
            $stmt->bindParam(":store_display_name", $this->store_display_name);
            $stmt->bindParam(":product_categories", $this->product_categories);
            $stmt->bindParam(":marketplace", $this->marketplace);
            $stmt->bindParam(":store_logo_path", $this->store_logo_path);
            $stmt->bindParam(":id", $this->id);

            // Execute query
            if($stmt->execute()) {
                return true;
            }

            error_log("SellerRegistration update() - Error: " . json_encode($stmt->errorInfo()));
            return false;
        } catch (PDOException $e) {
            error_log("SellerRegistration update() - Exception: " . $e->getMessage());
            return false;
        }
    }

    // Change password
    public function changePassword($current_password, $new_password) {
        try {
            // First verify current password
            $query = "SELECT password FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stored_password = $row['password'];

                if (password_verify($current_password, $stored_password)) {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                    // Update password
                    $update_query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(":password", $hashed_password);
                    $update_stmt->bindParam(":id", $this->id);

                    if ($update_stmt->execute()) {
                        return true;
                    } else {
                        error_log("SellerRegistration changePassword() - Error updating password: " . json_encode($update_stmt->errorInfo()));
                        return false;
                    }
                } else {
                    error_log("SellerRegistration changePassword() - Current password is incorrect");
                    return false;
                }
            } else {
                error_log("SellerRegistration changePassword() - User not found");
                return false;
            }
        } catch (PDOException $e) {
            error_log("SellerRegistration changePassword() - Exception: " . $e->getMessage());
            return false;
        }
    }
}
?>
