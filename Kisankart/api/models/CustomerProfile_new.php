<?php
class CustomerProfile {
    // Database connection and table name
    private $conn;
    private $table_name = "customer_profiles";

    // Object properties
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $address;
    public $city;
    public $state;
    public $postal_code;
    public $profile_image;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create customer profile
    public function create() {
        try {
            // Query to insert record
            $query = "INSERT INTO " . $this->table_name . "
                      SET first_name=:first_name, last_name=:last_name, 
                          email=:email, phone=:phone, address=:address, 
                          city=:city, state=:state, postal_code=:postal_code";

            // Log the query for debugging
            error_log("CustomerProfile create() - Query: $query");
            error_log("CustomerProfile create() - Values: first_name={$this->first_name}, last_name={$this->last_name}, email={$this->email}, phone={$this->phone}");

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

            // Bind values
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":address", $this->address);
            $stmt->bindParam(":city", $this->city);
            $stmt->bindParam(":state", $this->state);
            $stmt->bindParam(":postal_code", $this->postal_code);

            // Execute query
            $result = $stmt->execute();

            if($result) {
                $this->id = $this->conn->lastInsertId();
                error_log("CustomerProfile create() - Success: Profile created for {$this->first_name} {$this->last_name}, ID: {$this->id}");
                return true;
            } else {
                error_log("CustomerProfile create() - Error: " . json_encode($stmt->errorInfo()));
                return false;
            }
        } catch (PDOException $e) {
            error_log("CustomerProfile create() - Exception: " . $e->getMessage());
            return false;
        }
    }

    // Read customer profile by ID
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
            $this->id = $row['id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->city = $row['city'];
            $this->state = $row['state'];
            $this->postal_code = $row['postal_code'];
            $this->profile_image = $row['profile_image'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Read customer profile by email
    public function readByEmail() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind email
        $stmt->bindParam(1, $this->email);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        if($row) {
            $this->id = $row['id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->city = $row['city'];
            $this->state = $row['state'];
            $this->postal_code = $row['postal_code'];
            $this->profile_image = $row['profile_image'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Update customer profile
    public function update() {
        // Query to update record
        $query = "UPDATE " . $this->table_name . "
                  SET first_name=:first_name, last_name=:last_name, 
                      email=:email, phone=:phone, address=:address, 
                      city=:city, state=:state, postal_code=:postal_code
                  WHERE id=:id";

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
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind values
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
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

        return false;
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
