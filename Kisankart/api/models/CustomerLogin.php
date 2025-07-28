<?php
class CustomerLogin {
    // Database connection and table name
    private $conn;
    private $table_name = "customer_logins";

    // Object properties
    public $id;
    public $email;
    public $phone;
    public $password;
    public $customer_profile_id;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create customer login
    public function create() {
        try {
            // Query to insert record
            $query = "INSERT INTO " . $this->table_name . "
                      SET email=:email, phone=:phone, password=:password, 
                          customer_profile_id=:customer_profile_id, is_active=:is_active";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->customer_profile_id = htmlspecialchars(strip_tags($this->customer_profile_id));
            $this->is_active = isset($this->is_active) ? $this->is_active : true;

            // Hash the password
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

            // Bind values
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":customer_profile_id", $this->customer_profile_id);
            $stmt->bindParam(":is_active", $this->is_active, PDO::PARAM_BOOL);

            // Execute query
            $result = $stmt->execute();

            if($result) {
                error_log("CustomerLogin create() - Success: Login created for email={$this->email}");
                return true;
            } else {
                error_log("CustomerLogin create() - Error: " . json_encode($stmt->errorInfo()));
                return false;
            }
        } catch (PDOException $e) {
            error_log("CustomerLogin create() - Exception: " . $e->getMessage());
            return false;
        }
    }

    // Login with email
    public function loginWithEmail() {
        // Query to check if email exists
        $query = "SELECT cl.id, cl.email, cl.phone, cl.password, cl.customer_profile_id, cl.is_active,
                         cp.user_id, u.firstName, u.lastName, u.role
                  FROM " . $this->table_name . " cl
                  JOIN customer_profiles cp ON cl.customer_profile_id = cp.id
                  JOIN users u ON cp.user_id = u.id
                  WHERE cl.email = ? AND cl.is_active = 1
                  LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

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
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->customer_profile_id = $row['customer_profile_id'];
                $this->is_active = $row['is_active'];
                
                // Set user properties
                $this->user_id = $row['user_id'];
                $this->firstName = $row['firstName'];
                $this->lastName = $row['lastName'];
                $this->role = $row['role'];

                // Update last login time
                $this->updateLastLogin();
                
                return true;
            }
        }

        return false;
    }

    // Login with phone
    public function loginWithPhone() {
        // Query to check if phone exists
        $query = "SELECT cl.id, cl.email, cl.phone, cl.password, cl.customer_profile_id, cl.is_active,
                         cp.user_id, u.firstName, u.lastName, u.role
                  FROM " . $this->table_name . " cl
                  JOIN customer_profiles cp ON cl.customer_profile_id = cp.id
                  JOIN users u ON cp.user_id = u.id
                  WHERE cl.phone = ? AND cl.is_active = 1
                  LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

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
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->customer_profile_id = $row['customer_profile_id'];
                $this->is_active = $row['is_active'];
                
                // Set user properties
                $this->user_id = $row['user_id'];
                $this->firstName = $row['firstName'];
                $this->lastName = $row['lastName'];
                $this->role = $row['role'];

                // Update last login time
                $this->updateLastLogin();
                
                return true;
            }
        }

        return false;
    }

    // Update last login time
    private function updateLastLogin() {
        // Query to update last login
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind id
        $stmt->bindParam(":id", $this->id);

        // Execute query
        $stmt->execute();
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
}
?>
