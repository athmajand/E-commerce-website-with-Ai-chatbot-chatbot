<?php
class SellerProfile {
    // Database connection and table name
    private $conn;
    private $table_name = "seller_profiles";

    // Object properties
    public $id;
    public $seller_id; // Changed from user_id to seller_id
    public $business_name;
    public $business_description;
    public $business_logo;
    public $business_address;
    public $gst_number;
    public $pan_number;
    public $bank_account_details;
    public $is_verified;
    public $verification_documents;
    public $rating;
    public $total_reviews;
    public $commission_rate;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create seller profile
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                  SET seller_id=:seller_id, business_name=:business_name,
                      business_description=:business_description, business_logo=:business_logo,
                      business_address=:business_address, gst_number=:gst_number,
                      pan_number=:pan_number, bank_account_details=:bank_account_details,
                      verification_documents=:verification_documents";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->seller_id = htmlspecialchars(strip_tags($this->seller_id));
        $this->business_name = htmlspecialchars(strip_tags($this->business_name));
        $this->business_description = htmlspecialchars(strip_tags($this->business_description));
        $this->business_logo = htmlspecialchars(strip_tags($this->business_logo));
        $this->business_address = htmlspecialchars(strip_tags($this->business_address));
        $this->gst_number = htmlspecialchars(strip_tags($this->gst_number));
        $this->pan_number = htmlspecialchars(strip_tags($this->pan_number));

        // Bind values
        $stmt->bindParam(":seller_id", $this->seller_id);
        $stmt->bindParam(":business_name", $this->business_name);
        $stmt->bindParam(":business_description", $this->business_description);
        $stmt->bindParam(":business_logo", $this->business_logo);
        $stmt->bindParam(":business_address", $this->business_address);
        $stmt->bindParam(":gst_number", $this->gst_number);
        $stmt->bindParam(":pan_number", $this->pan_number);
        $stmt->bindParam(":bank_account_details", $this->bank_account_details);
        $stmt->bindParam(":verification_documents", $this->verification_documents);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read single seller profile
    public function readOne() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE seller_id = ? LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(1, $this->seller_id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        if($row) {
            $this->id = $row['id'];
            $this->business_name = $row['business_name'];
            $this->business_description = $row['business_description'];
            $this->business_logo = $row['business_logo'];
            $this->business_address = $row['business_address'];
            $this->gst_number = $row['gst_number'];
            $this->pan_number = $row['pan_number'];
            $this->bank_account_details = $row['bank_account_details'];
            $this->is_verified = $row['is_verified'];
            $this->verification_documents = $row['verification_documents'];
            $this->rating = $row['rating'];
            $this->total_reviews = $row['total_reviews'];
            $this->commission_rate = $row['commission_rate'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Update seller profile
    public function update() {
        // Query to update record
        $query = "UPDATE " . $this->table_name . "
                  SET business_name=:business_name, business_description=:business_description,
                      business_logo=:business_logo, business_address=:business_address,
                      gst_number=:gst_number, pan_number=:pan_number,
                      bank_account_details=:bank_account_details, verification_documents=:verification_documents
                  WHERE seller_id=:seller_id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->seller_id = htmlspecialchars(strip_tags($this->seller_id));
        $this->business_name = htmlspecialchars(strip_tags($this->business_name));
        $this->business_description = htmlspecialchars(strip_tags($this->business_description));
        $this->business_logo = htmlspecialchars(strip_tags($this->business_logo));
        $this->business_address = htmlspecialchars(strip_tags($this->business_address));
        $this->gst_number = htmlspecialchars(strip_tags($this->gst_number));
        $this->pan_number = htmlspecialchars(strip_tags($this->pan_number));

        // Bind values
        $stmt->bindParam(":seller_id", $this->seller_id);
        $stmt->bindParam(":business_name", $this->business_name);
        $stmt->bindParam(":business_description", $this->business_description);
        $stmt->bindParam(":business_logo", $this->business_logo);
        $stmt->bindParam(":business_address", $this->business_address);
        $stmt->bindParam(":gst_number", $this->gst_number);
        $stmt->bindParam(":pan_number", $this->pan_number);
        $stmt->bindParam(":bank_account_details", $this->bank_account_details);
        $stmt->bindParam(":verification_documents", $this->verification_documents);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Check if seller profile exists
    public function exists() {
        // Query to check if seller profile exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE seller_id = ? LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind seller ID
        $stmt->bindParam(1, $this->seller_id);

        // Execute query
        $stmt->execute();

        // Get row count
        $num = $stmt->rowCount();

        // If seller profile exists
        if($num > 0) {
            return true;
        }

        return false;
    }
}
?>
