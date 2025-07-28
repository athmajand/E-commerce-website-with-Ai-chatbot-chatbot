<?php
// Include SellerRegistration model
require_once __DIR__ . '/SellerRegistration.php';

class SellerLogin {
    // Database connection and table name
    private $conn;
    private $table_name = "seller_registrations";

    // Object properties
    public $id;
    public $username; // Will store first_name + last_name
    public $firstName; // Maps to first_name
    public $lastName;  // Maps to last_name
    public $password;
    public $email;
    public $phone;
    public $role = 'seller'; // Always seller for this model
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Login user - redirects to SellerRegistration
    public function login() {
        // Create SellerRegistration instance and try to login
        $seller_registration = new SellerRegistration($this->conn);

        // Try email login
        if (!empty($this->email)) {
            $seller_registration->email = $this->email;
            $seller_registration->password = $this->password;
            if ($seller_registration->loginWithEmail()) {
                // Copy properties from SellerRegistration
                $this->id = $seller_registration->id;
                $this->username = $seller_registration->first_name . ' ' . $seller_registration->last_name;
                $this->firstName = $seller_registration->first_name;
                $this->lastName = $seller_registration->last_name;
                $this->email = $seller_registration->email;
                $this->phone = $seller_registration->phone;
                return true;
            }
        }

        // Try username as email
        if (!empty($this->username)) {
            $seller_registration->email = $this->username;
            $seller_registration->password = $this->password;
            if ($seller_registration->loginWithEmail()) {
                // Copy properties from SellerRegistration
                $this->id = $seller_registration->id;
                $this->username = $seller_registration->first_name . ' ' . $seller_registration->last_name;
                $this->firstName = $seller_registration->first_name;
                $this->lastName = $seller_registration->last_name;
                $this->email = $seller_registration->email;
                $this->phone = $seller_registration->phone;
                return true;
            }
        }

        // Try phone login
        if (!empty($this->phone)) {
            $seller_registration->phone = $this->phone;
            $seller_registration->password = $this->password;
            if ($seller_registration->loginWithPhone()) {
                // Copy properties from SellerRegistration
                $this->id = $seller_registration->id;
                $this->username = $seller_registration->first_name . ' ' . $seller_registration->last_name;
                $this->firstName = $seller_registration->first_name;
                $this->lastName = $seller_registration->last_name;
                $this->email = $seller_registration->email;
                $this->phone = $seller_registration->phone;
                return true;
            }
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
                // role is already set to 'seller' by default
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error in readOne: " . $e->getMessage());
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
}
?>
