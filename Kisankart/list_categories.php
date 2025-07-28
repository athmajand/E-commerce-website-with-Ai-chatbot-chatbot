<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection is successful
if (!$db) {
    die("Database connection failed. Please check your database settings.");
}

echo "<h1>Categories in Database</h1>";

try {
    // Get all categories
    $query = "SELECT * FROM categories ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($categories) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Image URL</th>
              </tr>";
        
        foreach ($categories as $category) {
            echo "<tr>";
            echo "<td>" . $category['id'] . "</td>";
            echo "<td>" . htmlspecialchars($category['name']) . "</td>";
            echo "<td>" . htmlspecialchars($category['description'] ?? 'No description') . "</td>";
            echo "<td>" . htmlspecialchars($category['image_url'] ?? 'No image') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No categories found in the database.</p>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
