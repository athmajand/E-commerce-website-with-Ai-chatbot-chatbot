<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Seller Session Information</h1>";

// Display session data
echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in as a seller
$is_seller = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller';
echo "<p>Is logged in as seller: " . ($is_seller ? "Yes" : "No") . "</p>";

if ($is_seller) {
    $seller_id = $_SESSION['user_id'];
    echo "<p>Seller ID from session: " . $seller_id . "</p>";
    
    // Get seller data
    $seller = new SellerRegistration($db);
    $seller->id = $seller_id;
    
    // Fetch seller data
    if ($seller->readOne()) {
        echo "<h2>Seller Registration Data</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . $seller->id . "</td></tr>";
        echo "<tr><td>First Name</td><td>" . htmlspecialchars($seller->first_name) . "</td></tr>";
        echo "<tr><td>Last Name</td><td>" . htmlspecialchars($seller->last_name) . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($seller->email) . "</td></tr>";
        echo "<tr><td>Phone</td><td>" . htmlspecialchars($seller->phone) . "</td></tr>";
        echo "<tr><td>Business Name</td><td>" . htmlspecialchars($seller->business_name) . "</td></tr>";
        echo "<tr><td>Is Verified</td><td>" . ($seller->is_verified ? "Yes" : "No") . "</td></tr>";
        echo "<tr><td>Status</td><td>" . htmlspecialchars($seller->status) . "</td></tr>";
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Failed to load seller data. Error: " . $seller->error . "</p>";
    }
    
    // Check if seller exists in seller_profiles
    try {
        $check_profile_query = "SELECT * FROM seller_profiles WHERE seller_id = ?";
        $check_profile_stmt = $db->prepare($check_profile_query);
        $check_profile_stmt->bindParam(1, $seller_id);
        $check_profile_stmt->execute();
        
        if ($check_profile_stmt->rowCount() > 0) {
            $seller_profile = $check_profile_stmt->fetch(PDO::FETCH_ASSOC);
            $seller_profile_id = $seller_profile['id'];
            
            echo "<h2>Seller Profile Data</h2>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($seller_profile as $key => $value) {
                echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
            
            echo "<p>Seller Profile ID: " . $seller_profile_id . "</p>";
            
            // Check products for this seller
            $products_query = "SELECT COUNT(*) as count FROM products WHERE seller_id = ?";
            $products_stmt = $db->prepare($products_query);
            $products_stmt->bindParam(1, $seller_profile_id);
            $products_stmt->execute();
            $products_count = $products_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<h2>Products Count</h2>";
            echo "<p>Total products for this seller: " . $products_count . "</p>";
            
            if ($products_count > 0) {
                // Show sample products
                $sample_query = "SELECT * FROM products WHERE seller_id = ? LIMIT 5";
                $sample_stmt = $db->prepare($sample_query);
                $sample_stmt->bindParam(1, $seller_profile_id);
                $sample_stmt->execute();
                
                echo "<h2>Sample Products</h2>";
                echo "<table border='1'>";
                echo "<tr>";
                
                // Get column names
                $column_count = $sample_stmt->columnCount();
                for ($i = 0; $i < $column_count; $i++) {
                    $col = $sample_stmt->getColumnMeta($i);
                    echo "<th>" . $col['name'] . "</th>";
                }
                echo "</tr>";
                
                // Get data
                while ($row = $sample_stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color: orange;'>No seller profile found for this seller ID.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
    }
}
?>
