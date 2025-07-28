<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check connection
if (!$db) {
    die("Database connection failed. Please check your database settings.");
}

// Function to standardize image paths
function standardizeImagePath($path) {
    if (empty($path)) {
        return null;
    }
    
    // Get just the filename
    $filename = basename($path);
    
    // Return standardized path
    return 'uploads/products/' . $filename;
}

// Function to check if file exists in uploads directory
function fileExistsInUploads($filename) {
    $path = __DIR__ . '/uploads/products/' . basename($filename);
    return file_exists($path);
}

echo "<h1>Running Image Path Standardization</h1>";

try {
    // Update main image paths
    $update_query = "UPDATE products SET image_url = CONCAT('uploads/products/', SUBSTRING_INDEX(image_url, '/', -1)) 
                    WHERE image_url IS NOT NULL AND image_url != ''";
    $result = $db->exec($update_query);
    
    echo "<p>Updated main image paths for " . $result . " products.</p>";
    
    // Get all products with additional images
    $query = "SELECT id, additional_images FROM products WHERE additional_images IS NOT NULL AND additional_images != ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $updated_count = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $additional_images = json_decode($row['additional_images'], true);
        
        if (is_array($additional_images)) {
            $updated_images = [];
            
            foreach ($additional_images as $img) {
                $updated_images[] = standardizeImagePath($img);
            }
            
            // Update the database with standardized paths
            $update_stmt = $db->prepare("UPDATE products SET additional_images = ? WHERE id = ?");
            $json_images = json_encode($updated_images);
            $update_stmt->bindParam(1, $json_images);
            $update_stmt->bindParam(2, $row['id']);
            $update_stmt->execute();
            
            $updated_count++;
        }
    }
    
    echo "<p>Updated additional images for " . $updated_count . " products.</p>";
    echo "<p style='color:green;'>Image paths have been standardized in the database.</p>";
    
    // List all products with their image paths
    $query = "SELECT id, name, image_url, additional_images FROM products ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<h2>Updated Product Images</h2>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Main Image Path</th>
            <th>Main Image Exists</th>
            <th>Additional Images</th>
          </tr>";
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        
        // Main image
        echo "<td>" . htmlspecialchars($product['image_url'] ?? 'None') . "</td>";
        
        // Check if main image exists
        $main_image_exists = !empty($product['image_url']) && fileExistsInUploads(basename($product['image_url']));
        echo "<td style='color:" . ($main_image_exists ? 'green' : 'red') . "'>" . ($main_image_exists ? 'Yes' : 'No') . "</td>";
        
        // Additional images
        echo "<td>";
        if (!empty($product['additional_images'])) {
            $additional_images = json_decode($product['additional_images'], true);
            if (is_array($additional_images)) {
                foreach ($additional_images as $index => $img) {
                    $exists = fileExistsInUploads(basename($img));
                    echo "<div style='color:" . ($exists ? 'green' : 'red') . "'>";
                    echo "Image " . ($index + 1) . ": " . htmlspecialchars($img);
                    echo " (Exists: " . ($exists ? 'Yes' : 'No') . ")";
                    echo "</div>";
                }
            } else {
                echo "<span style='color:red'>Invalid format</span>";
            }
        } else {
            echo "None";
        }
        echo "</td>";
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p><a href='index.php'>Back to Home</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
