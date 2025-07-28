<?php
class CustomerProfile {
    // Database connection and table name
    private $conn;
    private $table_name = "customer_profiles";

    // Object properties
    public $id;
    public $user_id;
    public $address;
    public $city;
    public $state;
    public $postal_code; // Changed from pincode to match database schema
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
                      SET user_id=:user_id, address=:address, city=:city,
                          state=:state, postal_code=:postal_code";

            // Log the query and values for debugging
            error_log("CustomerProfile create() - Query: $query");
            error_log("CustomerProfile create() - Values: user_id={$this->user_id}, address={$this->address}, city={$this->city}, state={$this->state}, postal_code={$this->postal_code}");

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->address = htmlspecialchars(strip_tags($this->address));
            $this->city = htmlspecialchars(strip_tags($this->city));
            $this->state = htmlspecialchars(strip_tags($this->state));
            $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));

            // Bind values
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":address", $this->address);
            $stmt->bindParam(":city", $this->city);
            $stmt->bindParam(":state", $this->state);
            $stmt->bindParam(":postal_code", $this->postal_code);

            // Execute query
            $result = $stmt->execute();

            if($result) {
                error_log("CustomerProfile create() - Success: Profile created for user_id={$this->user_id}");
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

    // Read customer profile by user_id
    public function readByUserId() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        if($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
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
                  SET address=:address, city=:city, state=:state,
                      postal_code=:postal_code, profile_image=:profile_image
                  WHERE user_id=:user_id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->state = htmlspecialchars(strip_tags($this->state));
        $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
        $this->profile_image = htmlspecialchars(strip_tags($this->profile_image));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":state", $this->state);
        $stmt->bindParam(":postal_code", $this->postal_code);
        $stmt->bindParam(":profile_image", $this->profile_image);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete customer profile
    public function delete() {
        // Query to delete record
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind id
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Check if profile exists for user
    public function profileExists() {
        // Query to check if profile exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind user_id
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        $stmt->execute();

        // Get row count
        $num = $stmt->rowCount();

        // If profile exists
        if($num > 0) {
            return true;
        }

        return false;
    }
}
?>
