<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Buy Now Test</h1>";

// Display session information
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
echo "<p>Is logged in: " . ($is_logged_in ? 'Yes' : 'No') . "</p>";

if ($is_logged_in) {
    // Verify with database
    try {
        $query = "SELECT id, first_name, last_name, email FROM customer_registrations WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $_SESSION['user_id']);
        $stmt->execute();

        echo "<p>Database verification query: " . $query . "</p>";
        echo "<p>User ID being checked: " . $_SESSION['user_id'] . "</p>";
        echo "<p>Row count: " . $stmt->rowCount() . "</p>";

        if ($stmt->rowCount() > 0) {
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color:green;'>Customer verification successful!</p>";
            echo "<p>Customer details:</p>";
            echo "<pre>";
            print_r($customer);
            echo "</pre>";

            // Test the buy now functionality
            echo "<h2>Buy Now Test Form:</h2>";
            echo "<form method='post' action='frontend/check-login-redirect.php'>";
            echo "<input type='hidden' name='product_id' value='10'>";  // Using product ID 10 (carrot) which exists
            echo "<input type='hidden' name='quantity' value='1'>";
            echo "<input type='submit' value='Test Buy Now'>";
            echo "</form>";
        } else {
            echo "<p style='color:red;'>Customer verification failed - no matching record found for ID: " . $_SESSION['user_id'] . "</p>";

            // Show login form
            echo "<h2>Login First:</h2>";
            echo "<form method='post' action='test_login.php'>";
            echo "<label>Customer ID: <input type='number' name='customer_id' required></label><br>";
            echo "<input type='submit' value='Set Session'>";
            echo "</form>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error during customer verification: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>You need to login first.</p>";

    // Show login form
    echo "<h2>Login First:</h2>";
    echo "<form method='post' action='test_login.php'>";
    echo "<label>Customer ID: <input type='number' name='customer_id' required></label><br>";
    echo "<input type='submit' value='Set Session'>";
    echo "</form>";
}

// Show available customers
try {
    $query = "SELECT id, first_name, last_name, email FROM customer_registrations";
    $stmt = $db->query($query);

    if ($stmt->rowCount() > 0) {
        echo "<h2>Available Customers:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Action</th></tr>";

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td><form method='post' action='test_login.php'>";
            echo "<input type='hidden' name='customer_id' value='" . $row['id'] . "'>";
            echo "<input type='submit' value='Login as this customer'>";
            echo "</form></td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No customers found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error fetching customers: " . $e->getMessage() . "</p>";
}
?>

<p><a href="test_db_connection.php">Back to Test Page</a></p>
<p><a href="frontend/products.php">Go to Products Page</a></p>
<p><a href="login.php">Go to Login Page</a></p>
