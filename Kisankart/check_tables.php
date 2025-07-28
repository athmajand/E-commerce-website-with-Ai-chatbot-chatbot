<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Database Tables Check</h1>";

if ($db) {
    echo "<p style='color:green;'>Database connection successful!</p>";

    // Get all tables
    try {
        $query = "SHOW TABLES";
        $stmt = $db->query($query);

        echo "<h2>Tables in Database:</h2>";
        echo "<ul>";
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";

        // Check cart table
        $query = "SHOW TABLES LIKE 'cart'";
        $stmt = $db->query($query);

        if ($stmt->rowCount() > 0) {
            echo "<h2>Cart Table Structure:</h2>";
            $query = "DESCRIBE cart";
            $stmt = $db->query($query);

            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='color:red;'>Cart table does not exist!</p>";
        }

        // Check addresses table
        $query = "SHOW TABLES LIKE 'addresses'";
        $stmt = $db->query($query);

        if ($stmt->rowCount() > 0) {
            echo "<h2>Addresses Table Structure:</h2>";
            $query = "DESCRIBE addresses";
            $stmt = $db->query($query);

            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='color:red;'>Addresses table does not exist!</p>";
        }

        // Check orders table
        $query = "SHOW TABLES LIKE 'orders'";
        $stmt = $db->query($query);

        if ($stmt->rowCount() > 0) {
            echo "<h2>Orders Table Structure:</h2>";
            $query = "DESCRIBE orders";
            $stmt = $db->query($query);

            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='color:red;'>Orders table does not exist!</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>Error querying database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>Database connection failed!</p>";
}

echo "<h2>Actions</h2>";
echo "<p><a href='session_debug.php'>Check Session</a></p>";
echo "<p><a href='test_buy_now.php'>Test Buy Now</a></p>";
echo "<p><a href='frontend/products.php'>Go to Products Page</a></p>";
?>
