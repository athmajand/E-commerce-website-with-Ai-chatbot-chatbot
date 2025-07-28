<?php
class Order {
    // Database connection and table name
    private $conn;
    private $table_name = "orders";
    private $items_table = "order_items";

    // Object properties
    public $id;
    public $customer_id;
    public $total_amount;
    public $status;
    public $payment_method;
    public $payment_status;
    public $shipping_address;
    public $shipping_city;
    public $shipping_state;
    public $shipping_postal_code;
    public $created_at;
    public $updated_at;
    public $items = array();

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create order
    public function create() {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Insert order
            $query = "INSERT INTO " . $this->table_name . "
                      SET customer_id=:customer_id, total_amount=:total_amount, 
                          status=:status, payment_method=:payment_method, 
                          payment_status=:payment_status, shipping_address=:shipping_address,
                          shipping_city=:shipping_city, shipping_state=:shipping_state,
                          shipping_postal_code=:shipping_postal_code";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->customer_id = htmlspecialchars(strip_tags($this->customer_id));
            $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
            $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));
            $this->shipping_address = htmlspecialchars(strip_tags($this->shipping_address));
            $this->shipping_city = htmlspecialchars(strip_tags($this->shipping_city));
            $this->shipping_state = htmlspecialchars(strip_tags($this->shipping_state));
            $this->shipping_postal_code = htmlspecialchars(strip_tags($this->shipping_postal_code));

            // Bind values
            $stmt->bindParam(":customer_id", $this->customer_id);
            $stmt->bindParam(":total_amount", $this->total_amount);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":payment_method", $this->payment_method);
            $stmt->bindParam(":payment_status", $this->payment_status);
            $stmt->bindParam(":shipping_address", $this->shipping_address);
            $stmt->bindParam(":shipping_city", $this->shipping_city);
            $stmt->bindParam(":shipping_state", $this->shipping_state);
            $stmt->bindParam(":shipping_postal_code", $this->shipping_postal_code);

            // Execute query
            $stmt->execute();

            // Get the order ID
            $this->id = $this->conn->lastInsertId();

            // Insert order items
            if(!empty($this->items)) {
                $items_query = "INSERT INTO " . $this->items_table . "
                               (order_id, product_id, farmer_id, quantity, price) 
                               VALUES (?, ?, ?, ?, ?)";
                
                $items_stmt = $this->conn->prepare($items_query);
                
                foreach($this->items as $item) {
                    $items_stmt->bindParam(1, $this->id);
                    $items_stmt->bindParam(2, $item['product_id']);
                    $items_stmt->bindParam(3, $item['farmer_id']);
                    $items_stmt->bindParam(4, $item['quantity']);
                    $items_stmt->bindParam(5, $item['price']);
                    
                    $items_stmt->execute();
                    
                    // Update product stock
                    $update_stock_query = "UPDATE products 
                                          SET stock_quantity = stock_quantity - ? 
                                          WHERE id = ?";
                    
                    $update_stock_stmt = $this->conn->prepare($update_stock_query);
                    $update_stock_stmt->bindParam(1, $item['quantity']);
                    $update_stock_stmt->bindParam(2, $item['product_id']);
                    $update_stock_stmt->execute();
                }
            }
            
            // Clear customer's cart
            $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
            $clear_cart_stmt = $this->conn->prepare($clear_cart_query);
            $clear_cart_stmt->bindParam(1, $this->customer_id);
            $clear_cart_stmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        }
    }

    // Read all orders for a customer
    public function readCustomerOrders() {
        // Query to select all orders for a customer
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE customer_id = ?
                  ORDER BY created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind customer ID
        $stmt->bindParam(1, $this->customer_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read all orders for a farmer
    public function readFarmerOrders($farmer_id) {
        // Query to select all orders containing products from a specific farmer
        $query = "SELECT o.*, oi.quantity, oi.price, p.name as product_name
                  FROM " . $this->table_name . " o
                  JOIN " . $this->items_table . " oi ON o.id = oi.order_id
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.farmer_id = ?
                  ORDER BY o.created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind farmer ID
        $stmt->bindParam(1, $farmer_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read single order
    public function readOne() {
        // Query to read single order
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE id = ? LIMIT 0,1";

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
            $this->customer_id = $row['customer_id'];
            $this->total_amount = $row['total_amount'];
            $this->status = $row['status'];
            $this->payment_method = $row['payment_method'];
            $this->payment_status = $row['payment_status'];
            $this->shipping_address = $row['shipping_address'];
            $this->shipping_city = $row['shipping_city'];
            $this->shipping_state = $row['shipping_state'];
            $this->shipping_postal_code = $row['shipping_postal_code'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            // Get order items
            $items_query = "SELECT oi.*, p.name as product_name, p.image as product_image
                           FROM " . $this->items_table . " oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?";
            
            $items_stmt = $this->conn->prepare($items_query);
            $items_stmt->bindParam(1, $this->id);
            $items_stmt->execute();
            
            $this->items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return true;
        }
        
        return false;
    }

    // Update order status
    public function updateStatus() {
        // Query to update order status
        $query = "UPDATE " . $this->table_name . "
                  SET status = :status, payment_status = :payment_status
                  WHERE id = :id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));

        // Bind values
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":payment_status", $this->payment_status);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Cancel order
    public function cancel() {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Update order status
            $query = "UPDATE " . $this->table_name . "
                      SET status = 'cancelled'
                      WHERE id = ? AND customer_id = ? AND status = 'pending'";

            // Prepare query
            $stmt = $this->conn->prepare($query);

            // Bind values
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $this->customer_id);

            // Execute query
            $stmt->execute();
            
            // If order was cancelled (rows affected > 0)
            if($stmt->rowCount() > 0) {
                // Get order items
                $items_query = "SELECT product_id, quantity FROM " . $this->items_table . "
                               WHERE order_id = ?";
                
                $items_stmt = $this->conn->prepare($items_query);
                $items_stmt->bindParam(1, $this->id);
                $items_stmt->execute();
                
                // Restore product stock
                while($item = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $update_stock_query = "UPDATE products 
                                          SET stock_quantity = stock_quantity + ? 
                                          WHERE id = ?";
                    
                    $update_stock_stmt = $this->conn->prepare($update_stock_query);
                    $update_stock_stmt->bindParam(1, $item['quantity']);
                    $update_stock_stmt->bindParam(2, $item['product_id']);
                    $update_stock_stmt->execute();
                }
                
                // Commit transaction
                $this->conn->commit();
                
                return true;
            } else {
                // Order not found or not in pending status
                $this->conn->rollBack();
                return false;
            }
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        }
    }
}
?>
