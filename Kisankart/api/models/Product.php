<?php
class Product {
    // Database connection and table name
    private $conn;
    private $table_name = "products";

    // Object properties
    public $id;
    public $farmer_id;
    public $category_id;
    public $name;
    public $description;
    public $price;
    public $stock_quantity;
    public $unit;
    public $image;
    public $is_organic;
    public $is_available;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create product
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                  SET farmer_id=:farmer_id, category_id=:category_id, name=:name, 
                      description=:description, price=:price, stock_quantity=:stock_quantity,
                      unit=:unit, image=:image, is_organic=:is_organic, is_available=:is_available";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->farmer_id = htmlspecialchars(strip_tags($this->farmer_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->stock_quantity = htmlspecialchars(strip_tags($this->stock_quantity));
        $this->unit = htmlspecialchars(strip_tags($this->unit));
        $this->image = htmlspecialchars(strip_tags($this->image));
        $this->is_organic = htmlspecialchars(strip_tags($this->is_organic));
        $this->is_available = htmlspecialchars(strip_tags($this->is_available));

        // Bind values
        $stmt->bindParam(":farmer_id", $this->farmer_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":unit", $this->unit);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":is_organic", $this->is_organic);
        $stmt->bindParam(":is_available", $this->is_available);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read all products
    public function read() {
        // Query to select all products
        $query = "SELECT p.*, c.name as category_name, u.username as farmer_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN users u ON p.farmer_id = u.id
                  WHERE p.is_available = 1
                  ORDER BY p.created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read products by farmer
    public function readByFarmer() {
        // Query to select products by farmer
        $query = "SELECT p.*, c.name as category_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.farmer_id = ?
                  ORDER BY p.created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind farmer ID
        $stmt->bindParam(1, $this->farmer_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read products by category
    public function readByCategory() {
        // Query to select products by category
        $query = "SELECT p.*, c.name as category_name, u.username as farmer_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN users u ON p.farmer_id = u.id
                  WHERE p.category_id = ? AND p.is_available = 1
                  ORDER BY p.created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind category ID
        $stmt->bindParam(1, $this->category_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read single product
    public function readOne() {
        // Query to read single record
        $query = "SELECT p.*, c.name as category_name, u.username as farmer_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN users u ON p.farmer_id = u.id
                  WHERE p.id = ? LIMIT 0,1";

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
            $this->farmer_id = $row['farmer_id'];
            $this->category_id = $row['category_id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->stock_quantity = $row['stock_quantity'];
            $this->unit = $row['unit'];
            $this->image = $row['image'];
            $this->is_organic = $row['is_organic'];
            $this->is_available = $row['is_available'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    // Update product
    public function update() {
        // Query to update record
        $query = "UPDATE " . $this->table_name . "
                  SET category_id=:category_id, name=:name, description=:description,
                      price=:price, stock_quantity=:stock_quantity, unit=:unit,
                      image=:image, is_organic=:is_organic, is_available=:is_available
                  WHERE id=:id AND farmer_id=:farmer_id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->farmer_id = htmlspecialchars(strip_tags($this->farmer_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->stock_quantity = htmlspecialchars(strip_tags($this->stock_quantity));
        $this->unit = htmlspecialchars(strip_tags($this->unit));
        $this->image = htmlspecialchars(strip_tags($this->image));
        $this->is_organic = htmlspecialchars(strip_tags($this->is_organic));
        $this->is_available = htmlspecialchars(strip_tags($this->is_available));

        // Bind values
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":farmer_id", $this->farmer_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":unit", $this->unit);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":is_organic", $this->is_organic);
        $stmt->bindParam(":is_available", $this->is_available);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete product
    public function delete() {
        // Query to delete record
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND farmer_id = ?";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->farmer_id = htmlspecialchars(strip_tags($this->farmer_id));

        // Bind ids
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->farmer_id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Search products
    public function search($keywords) {
        // Query to search products
        $query = "SELECT p.*, c.name as category_name, u.username as farmer_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN users u ON p.farmer_id = u.id
                  WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?
                  AND p.is_available = 1
                  ORDER BY p.created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        // Bind
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);

        // Execute query
        $stmt->execute();

        return $stmt;
    }
}
?>
