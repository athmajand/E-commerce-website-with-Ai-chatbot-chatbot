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

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Image Paths</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1, h2 {
            color: #4CAF50;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
        .actions {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Fix Product Image Paths</h1>
    
    <?php
    // Check if form was submitted to fix paths
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_paths'])) {
        try {
            // Update main image paths
            $update_query = "UPDATE products SET image_url = CONCAT('uploads/products/', SUBSTRING_INDEX(image_url, '/', -1)) 
                            WHERE image_url IS NOT NULL AND image_url != ''";
            $db->exec($update_query);
            
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
            
            echo "<div class='success'>";
            echo "<p>Image paths have been standardized in the database.</p>";
            echo "<p>Updated additional images for $updated_count products.</p>";
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>";
            echo "<p>Error: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // Display current product images
    try {
        $query = "SELECT id, name, image_url, additional_images FROM products ORDER BY id";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        echo "<h2>Current Product Images</h2>";
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Main Image Path</th>
                <th>Main Image Exists</th>
                <th>Preview</th>
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
            echo "<td class='" . ($main_image_exists ? 'success' : 'error') . "'>" . ($main_image_exists ? 'Yes' : 'No') . "</td>";
            
            // Preview
            echo "<td>";
            if (!empty($product['image_url'])) {
                echo "<img src='" . htmlspecialchars($product['image_url']) . "' class='image-preview' alt='Product Image' onerror=\"this.src='https://via.placeholder.com/100x100?text=No+Image'\">";
            } else {
                echo "No image";
            }
            echo "</td>";
            
            // Additional images
            echo "<td>";
            if (!empty($product['additional_images'])) {
                $additional_images = json_decode($product['additional_images'], true);
                if (is_array($additional_images)) {
                    foreach ($additional_images as $index => $img) {
                        $exists = fileExistsInUploads(basename($img));
                        echo "<div class='" . ($exists ? 'success' : 'error') . "'>";
                        echo "Image " . ($index + 1) . ": " . htmlspecialchars($img);
                        echo " (Exists: " . ($exists ? 'Yes' : 'No') . ")";
                        echo "</div>";
                    }
                } else {
                    echo "<span class='error'>Invalid format</span>";
                }
            } else {
                echo "None";
            }
            echo "</td>";
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Form to fix paths
        echo "<div class='actions'>";
        echo "<form method='post'>";
        echo "<button type='submit' name='fix_paths' class='btn'>Standardize All Image Paths</button>";
        echo "</form>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>
    
    <div class="actions">
        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>
