<?php
class CustomerRegistration {
    // Database connection and table name
    private $conn;
    private $table_name = "customer_registrations";

    // Object properties
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $password; // Added password property
    public $address;
    public $city;
    public $state;
    public $postal_code;
    public $registration_date;
    public $status;
    public $verification_token;
    public $is_verified;
    public $last_login;
    public $notes;
    public $created_at;
    public $updated_at;
    public $role = 'customer'; // Always customer for this model

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create customer registration
    public function create() {
        try {
            // Query to insert record
            $query = "INSERT INTO " . $this->table_name . "
                      SET first_name=:first_name, last_name=:last_name, email=:email,
                          phone=:phone, password=:password, address=:address, city=:city, state=:state,
                          postal_code=:postal_code, status=:status, verification_token=:verification_token";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->address = htmlspecialchars(strip_tags($this->address));
            $this->city = htmlspecialchars(strip_tags($this->city));
            $this->state = htmlspecialchars(strip_tags($this->state));
            $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
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
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":address", $this->address);
            $stmt->bindParam(":city", $this->city);
            $stmt->bindParam(":state", $this->state);
            $stmt->bindParam(":postal_code", $this->postal_code);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":verification_token", $this->verification_token);

            // Execute query
            $result = $stmt->execute();

            if($result) {
                $this->id = $this->conn->lastInsertId();
                error_log("CustomerRegistration create() - Success: Registration created for email={$this->email}");
                return true;
            } else {
                error_log("CustomerRegistration create() - Error: " . json_encode($stmt->errorInfo()));
                return false;
            }
        } catch (PDOException $e) {
            error_log("CustomerRegistration create() - Exception: " . $e->getMessage());
            return false;
        }
    }

    // Check if email exists
    public function emailExists() {
        // Query to check if email exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

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
            return true;
        }

        return false;
    }

    // Check if phone exists
    public function phoneExists() {
        // Query to check if phone exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE phone = ? LIMIT 0,1";

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
            return true;
        }

        return false;
    }

    // Read single registration by ID
    public function readOne() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        if($row) {
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->city = $row['city'];
            $this->state = $row['state'];
            $this->postal_code = $row['postal_code'];
            $this->registration_date = $row['registration_date'];
            $this->status = $row['status'];
            $this->verification_token = $row['verification_token'];
            $this->is_verified = $row['is_verified'];
            $this->last_login = $row['last_login'];
            $this->notes = $row['notes'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Update registration status
    public function updateStatus() {
        // Query to update status
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind values
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Verify registration
    public function verify() {
        // Query to verify registration
        $query = "UPDATE " . $this->table_name . "
                  SET is_verified = 1, status = 'approved'
                  WHERE verification_token = :token AND is_verified = 0";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->verification_token = htmlspecialchars(strip_tags($this->verification_token));

        // Bind token
        $stmt->bindParam(":token", $this->verification_token);

        // Execute query
        if($stmt->execute() && $stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }

    // Login with email
    public function loginWithEmail() {
        // Query to check if email exists - verification check removed
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
                // Set values to object properties
                $this->id = $row['id'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->address = $row['address'];
                $this->city = $row['city'];
                $this->state = $row['state'];
                $this->postal_code = $row['postal_code'];
                $this->status = $row['status'];
                $this->is_verified = $row['is_verified'];

                // Update last login time
                $this->updateLastLogin();

                return true;
            }
        }

        return false;
    }

    // Login with phone
    public function loginWithPhone() {
        // Query to check if phone exists - verification check removed
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
                // Set values to object properties
                $this->id = $row['id'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->address = $row['address'];
                $this->city = $row['city'];
                $this->state = $row['state'];
                $this->postal_code = $row['postal_code'];
                $this->status = $row['status'];
                $this->is_verified = $row['is_verified'];

                // Update last login time
                $this->updateLastLogin();

                return true;
            }
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

    // Update customer profile
    public function update() {
        try {
            // Query to update record
            $query = "UPDATE " . $this->table_name . "
                      SET first_name = :first_name, last_name = :last_name,
                          phone = :phone, address = :address, city = :city,
                          state = :state, postal_code = :postal_code
                      WHERE id = :id";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->address = htmlspecialchars(strip_tags($this->address));
            $this->city = htmlspecialchars(strip_tags($this->city));
            $this->state = htmlspecialchars(strip_tags($this->state));
            $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind values
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":address", $this->address);
            $stmt->bindParam(":city", $this->city);
            $stmt->bindParam(":state", $this->state);
            $stmt->bindParam(":postal_code", $this->postal_code);
            $stmt->bindParam(":id", $this->id);

            // Execute query
            if($stmt->execute()) {
                return true;
            }

            error_log("CustomerRegistration update() - Error: " . json_encode($stmt->errorInfo()));
            return false;
        } catch (PDOException $e) {
            error_log("CustomerRegistration update() - Exception: " . $e->getMessage());
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
                        error_log("CustomerRegistration changePassword() - Error updating password: " . json_encode($update_stmt->errorInfo()));
                        return false;
                    }
                } else {
                    error_log("CustomerRegistration changePassword() - Current password is incorrect");
                    return false;
                }
            } else {
                error_log("CustomerRegistration changePassword() - User not found");
                return false;
            }
        } catch (PDOException $e) {
            error_log("CustomerRegistration changePassword() - Exception: " . $e->getMessage());
            return false;
        }
    }
}
?>
