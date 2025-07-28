<?php
class Address {
    // Database connection and table name
    private $conn;
    private $table_name = "user_addresses";

    // Object properties
    public $id;
    public $user_id;
    public $name;
    public $phone;
    public $street;
    public $city;
    public $state;
    public $postal_code;
    public $is_default;
    public $address_type;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all addresses for a user
    public function readAll() {
        // Query
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind user ID
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read single address
    public function readOne() {
        // Query
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID and user ID
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->user_id);

        // Execute query
        $stmt->execute();

        // Get record
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set properties
        if($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->name = $row['name'];
            $this->phone = $row['phone'];
            $this->street = $row['street'];
            $this->city = $row['city'];
            $this->state = $row['state'];
            $this->postal_code = $row['postal_code'];
            $this->is_default = $row['is_default'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];

            return true;
        }

        return false;
    }

    // Create address
    public function create() {
        // Check if this is the first address (make it default)
        $check_query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->user_id);
        $check_stmt->execute();
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if($row['count'] == 0) {
            $this->is_default = 1;
        }

        // If this is set as default, unset all other defaults
        if($this->is_default == 1) {
            $update_query = "UPDATE " . $this->table_name . " SET is_default = 0 WHERE user_id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(1, $this->user_id);
            $update_stmt->execute();
        }

        // Query
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id = :user_id,
                      name = :name,
                      phone = :phone,
                      street = :street,
                      city = :city,
                      state = :state,
                      postal_code = :postal_code,
                      is_default = :is_default,
                      created_at = NOW(),
                      updated_at = NOW()";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->street = htmlspecialchars(strip_tags($this->street));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->state = htmlspecialchars(strip_tags($this->state));
        $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
        $this->is_default = htmlspecialchars(strip_tags($this->is_default));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":street", $this->street);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":state", $this->state);
        $stmt->bindParam(":postal_code", $this->postal_code);
        $stmt->bindParam(":is_default", $this->is_default);

        // Execute query
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Update address
    public function update() {
        // If this is set as default, unset all other defaults
        if($this->is_default == 1) {
            $update_query = "UPDATE " . $this->table_name . " SET is_default = 0 WHERE user_id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(1, $this->user_id);
            $update_stmt->execute();
        }

        // Query
        $query = "UPDATE " . $this->table_name . "
                  SET name = :name,
                      phone = :phone,
                      street = :street,
                      city = :city,
                      state = :state,
                      postal_code = :postal_code,
                      is_default = :is_default,
                      updated_at = NOW()
                  WHERE id = :id AND user_id = :user_id";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->street = htmlspecialchars(strip_tags($this->street));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->state = htmlspecialchars(strip_tags($this->state));
        $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
        $this->is_default = htmlspecialchars(strip_tags($this->is_default));

        // Bind values
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":street", $this->street);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":state", $this->state);
        $stmt->bindParam(":postal_code", $this->postal_code);
        $stmt->bindParam(":is_default", $this->is_default);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete address
    public function delete() {
        // Check if this is the default address
        if($this->is_default == 1) {
            // Find another address to make default
            $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? AND id != ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->user_id);
            $stmt->bindParam(2, $this->id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_default_id = $row['id'];

                // Make the other address default
                $update_query = "UPDATE " . $this->table_name . " SET is_default = 1 WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(1, $new_default_id);
                $update_stmt->execute();
            }
        }

        // Query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID and user ID
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->user_id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>
