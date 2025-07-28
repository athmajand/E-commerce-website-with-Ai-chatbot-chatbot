<?php
// Include CustomerRegistration model
require_once __DIR__ . '/CustomerRegistration.php';

class User {
    // Database connection and table name
    private $conn;
    private $table_name = "customer_registrations"; // Changed from users to customer_registrations

    // Object properties
    public $id;
    public $username; // Will store first_name + last_name
    public $firstName; // Maps to first_name
    public $lastName;  // Maps to last_name
    public $password;
    public $email;
    public $phone;
    public $role = 'customer'; // Always customer for this model
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create user - DISABLED
    public function create() {
        // User creation functionality has been removed
        error_log("User creation attempt blocked - functionality disabled");
        return false;
    }

    // Login user - redirects to CustomerRegistration
    public function login() {
        // This method is deprecated - use CustomerRegistration instead
        error_log("User login attempt redirected to CustomerRegistration");

        // Create CustomerRegistration instance and try to login
        $customer_registration = new CustomerRegistration($this->conn);

        // Try email login
        if (!empty($this->email)) {
            $customer_registration->email = $this->email;
            $customer_registration->password = $this->password;
            if ($customer_registration->loginWithEmail()) {
                // Copy properties from CustomerRegistration
                $this->id = $customer_registration->id;
                $this->username = $customer_registration->first_name . ' ' . $customer_registration->last_name;
                $this->firstName = $customer_registration->first_name;
                $this->lastName = $customer_registration->last_name;
                $this->email = $customer_registration->email;
                $this->phone = $customer_registration->phone;
                return true;
            }
        }

        // Try username as email
        if (!empty($this->username)) {
            $customer_registration->email = $this->username;
            $customer_registration->password = $this->password;
            if ($customer_registration->loginWithEmail()) {
                // Copy properties from CustomerRegistration
                $this->id = $customer_registration->id;
                $this->username = $customer_registration->first_name . ' ' . $customer_registration->last_name;
                $this->firstName = $customer_registration->first_name;
                $this->lastName = $customer_registration->last_name;
                $this->email = $customer_registration->email;
                $this->phone = $customer_registration->phone;
                return true;
            }
        }

        // Try phone login
        if (!empty($this->phone)) {
            $customer_registration->phone = $this->phone;
            $customer_registration->password = $this->password;
            if ($customer_registration->loginWithPhone()) {
                // Copy properties from CustomerRegistration
                $this->id = $customer_registration->id;
                $this->username = $customer_registration->first_name . ' ' . $customer_registration->last_name;
                $this->firstName = $customer_registration->first_name;
                $this->lastName = $customer_registration->last_name;
                $this->email = $customer_registration->email;
                $this->phone = $customer_registration->phone;
                return true;
            }
        }

        return false;
    }

    // Login with phone number
    private function loginWithPhone() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

            // Query to check if phone exists
            $query = "SELECT id, first_name, last_name, password, email, phone, is_verified, status FROM " . $this->table_name . "
                      WHERE phone = ? LIMIT 0,1";

            // Prepare the query
            $stmt = $this->conn->prepare($query);

            // Bind value
            $stmt->bindParam(1, $this->phone);

            // Execute query
            $stmt->execute();

            // Get row count
            $num = $stmt->rowCount();

            // If user exists
            if($num > 0) {
                // Get record details
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verification check permanently disabled
                // No verification or approval check needed

                // Verify password
                if(password_verify($this->password, $row['password'])) {
                    // Set values to object properties
                    $this->id = $row['id'];
                    $this->username = $row['first_name'] . ' ' . $row['last_name']; // Combine first and last name
                    $this->firstName = $row['first_name'];
                    $this->lastName = $row['last_name'];
                    $this->email = $row['email'];
                    $this->phone = $row['phone'];
                    // role is already set to 'customer' by default

                    // Update last login time
                    $this->updateLastLogin();

                    return true;
                }
            }
        } catch (PDOException $e) {
            error_log("Error in loginWithPhone: " . $e->getMessage());
        }

        return false;
    }

    // Helper method to check if table exists
    private function tableExists() {
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE '{$this->table_name}'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking if table exists: " . $e->getMessage());
            return false;
        }
    }

    // Update last login time
    private function updateLastLogin() {
        try {
            $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
    }

    // Login with username - now checks first_name or last_name
    private function loginWithUsername() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

            // Query to check if first_name or last_name matches
            $query = "SELECT id, first_name, last_name, password, email, phone, is_verified, status FROM " . $this->table_name . "
                      WHERE first_name LIKE ? OR last_name LIKE ? LIMIT 0,1";

            // Prepare the query
            $stmt = $this->conn->prepare($query);

            // Bind values with wildcards for partial matching
            $searchTerm = "%" . $this->username . "%";
            $stmt->bindParam(1, $searchTerm);
            $stmt->bindParam(2, $searchTerm);

            // Execute query
            $stmt->execute();

            // Get row count
            $num = $stmt->rowCount();

            // If user exists
            if($num > 0) {
                // Get record details
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verification check permanently disabled
                // No verification or approval check needed

                // Verify password
                if(password_verify($this->password, $row['password'])) {
                    // Set values to object properties
                    $this->id = $row['id'];
                    $this->username = $row['first_name'] . ' ' . $row['last_name']; // Combine first and last name
                    $this->firstName = $row['first_name'];
                    $this->lastName = $row['last_name'];
                    $this->email = $row['email'];
                    $this->phone = $row['phone'];
                    // role is already set to 'customer' by default

                    // Update last login time
                    $this->updateLastLogin();

                    return true;
                }
            }
        } catch (PDOException $e) {
            error_log("Error in loginWithUsername: " . $e->getMessage());
        }

        return false;
    }

    // Login with email
    private function loginWithEmail() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

            // Query to check if email exists
            $query = "SELECT id, first_name, last_name, password, email, phone, is_verified, status FROM " . $this->table_name . "
                      WHERE email = ? LIMIT 0,1";

            // Prepare the query
            $stmt = $this->conn->prepare($query);

            // Bind value - using username property to store email input
            $stmt->bindParam(1, $this->username);

            // Execute query
            $stmt->execute();

            // Get row count
            $num = $stmt->rowCount();

            // If user exists
            if($num > 0) {
                // Get record details
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verification check permanently disabled
                // No verification or approval check needed

                // Verify password
                if(password_verify($this->password, $row['password'])) {
                    // Set values to object properties
                    $this->id = $row['id'];
                    $this->username = $row['first_name'] . ' ' . $row['last_name']; // Combine first and last name
                    $this->firstName = $row['first_name'];
                    $this->lastName = $row['last_name'];
                    $this->email = $row['email'];
                    $this->phone = $row['phone'];
                    // role is already set to 'customer' by default

                    // Update last login time
                    $this->updateLastLogin();

                    return true;
                }
            }
        } catch (PDOException $e) {
            error_log("Error in loginWithEmail: " . $e->getMessage());
        }

        return false;
    }

    // Read single user
    public function readOne() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

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
                $this->username = $row['first_name'] . ' ' . $row['last_name']; // Combine first and last name
                $this->firstName = $row['first_name'];
                $this->lastName = $row['last_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                // role is already set to 'customer' by default
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error in readOne: " . $e->getMessage());
        }

        return false;
    }

    // Update user
    public function update() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

            // If password is not empty, update it
            $password_set = !empty($this->password) ? ", password = :password" : "";

            // Query to update record - map to customer_registrations fields
            $query = "UPDATE " . $this->table_name . "
                      SET first_name = :firstName, last_name = :lastName,
                          email = :email, phone = :phone" . $password_set . "
                      WHERE id = :id";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->firstName = htmlspecialchars(strip_tags($this->firstName));
            $this->lastName = htmlspecialchars(strip_tags($this->lastName));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind values
            $stmt->bindParam(":firstName", $this->firstName);
            $stmt->bindParam(":lastName", $this->lastName);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":id", $this->id);

            // If password is not empty, hash it and bind it
            if(!empty($this->password)) {
                $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
                $stmt->bindParam(":password", $password_hash);
            }

            // Execute query
            if($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error in update: " . $e->getMessage());
        }

        return false;
    }

    // Delete user
    public function delete() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

            // Query to delete record
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind id
            $stmt->bindParam(1, $this->id);

            // Execute query
            if($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error in delete: " . $e->getMessage());
        }

        return false;
    }

    // Check if username exists - now checks first_name or last_name
    public function usernameExists() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

            // Query to check if first_name or last_name matches
            $query = "SELECT id FROM " . $this->table_name . "
                      WHERE first_name LIKE ? OR last_name LIKE ? LIMIT 0,1";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->username = htmlspecialchars(strip_tags($this->username));

            // Bind values with wildcards for partial matching
            $searchTerm = "%" . $this->username . "%";
            $stmt->bindParam(1, $searchTerm);
            $stmt->bindParam(2, $searchTerm);

            // Execute query
            $stmt->execute();

            // Get row count
            $num = $stmt->rowCount();

            // If username exists
            if($num > 0) {
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error in usernameExists: " . $e->getMessage());
        }

        return false;
    }

    // Check if email exists
    public function emailExists() {
        try {
            // Check if the table exists first
            $tableExists = $this->tableExists();
            if (!$tableExists) {
                error_log("Table {$this->table_name} does not exist");
                return false;
            }

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
        } catch (PDOException $e) {
            error_log("Error in emailExists: " . $e->getMessage());
        }

        return false;
    }
}
?>
