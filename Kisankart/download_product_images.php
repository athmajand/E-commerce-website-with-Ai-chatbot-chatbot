<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set time limit to allow for longer execution time
set_time_limit(300);

// Define the target directory
$targetDir = __DIR__ . '/uploads/products/';

// Create the directory if it doesn't exist
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Define product images to download
// Format: [category => [[name, url, filename], ...]]
$productImages = [
    'Vegetables' => [
        ['Carrot', 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37', 'carrot.jpg'],
        ['Tomato', 'https://images.unsplash.com/photo-1607305387299-a3d9611cd469', 'tomato.jpg'],
        ['Potato', 'https://images.unsplash.com/photo-1518977676601-b53f82aba655', 'potato.jpg'],
        ['Onion', 'https://images.unsplash.com/photo-1620574387735-3624d75b2dbc', 'onion.jpg'],
        ['Cucumber', 'https://images.unsplash.com/photo-1604977042946-1eecc30f269e', 'cucumber.jpg'],
        ['Broccoli', 'https://images.unsplash.com/photo-1459411621453-7b03977f4bfc', 'broccoli.jpg'],
        ['Spinach', 'https://images.unsplash.com/photo-1576045057995-568f588f82fb', 'spinach.jpg'],
        ['Bell Pepper', 'https://images.unsplash.com/photo-1563565375-f3fdfdbefa83', 'bell_pepper.jpg'],
    ],
    'Fruits' => [
        ['Apple', 'https://images.unsplash.com/photo-1570913149827-d2ac84ab3f9a', 'apple.jpg'],
        ['Banana', 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e', 'banana.jpg'],
        ['Orange', 'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab12', 'orange.jpg'],
        ['Grapes', 'https://images.unsplash.com/photo-1596363505729-4190a9506133', 'grapes.jpg'],
        ['Mango', 'https://images.unsplash.com/photo-1553279768-865429fa0078', 'mango.jpg'],
        ['Strawberry', 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6', 'strawberry.jpg'],
        ['Watermelon', 'https://images.unsplash.com/photo-1563114773-84221bd62daa', 'watermelon.jpg'],
        ['Pineapple', 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba', 'pineapple.jpg'],
    ],
    'Dairy' => [
        ['Milk', 'https://images.unsplash.com/photo-1563636619-e9143da7973b', 'milk.jpg'],
        ['Cheese', 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d', 'cheese.jpg'],
        ['Butter', 'https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d', 'butter.jpg'],
        ['Yogurt', 'https://images.unsplash.com/photo-1584278433313-562a1bc0a5b5', 'yogurt.jpg'],
        ['Paneer', 'https://images.pexels.com/photos/4149260/pexels-photo-4149260.jpeg', 'paneer.jpg'],
    ],
    'Grains' => [
        ['Rice', 'https://images.unsplash.com/photo-1586201375761-83865001e8ac', 'rice.jpg'],
        ['Wheat', 'https://images.unsplash.com/photo-1574323347407-f5e1c5a1ec21', 'wheat.jpg'],
        ['Oats', 'https://images.unsplash.com/photo-1614961233913-a5113a4a34ed', 'oats.jpg'],
        ['Barley', 'https://images.pexels.com/photos/1537169/pexels-photo-1537169.jpeg', 'barley.jpg'],
        ['Corn', 'https://images.unsplash.com/photo-1551754655-cd27e38d2076', 'corn.jpg'],
    ],
    'Spices' => [
        ['Turmeric', 'https://images.unsplash.com/photo-1615485500704-8e990f9900f7', 'turmeric.jpg'],
        ['Chilli', 'https://images.unsplash.com/photo-1588252303782-cb80119abd6d', 'chilli.jpg'],
        ['Cumin', 'https://images.pexels.com/photos/4198714/pexels-photo-4198714.jpeg', 'cumin.jpg'],
        ['Coriander', 'https://images.unsplash.com/photo-1600277308318-6becdd0656b0', 'coriander.jpg'],
        ['Cardamom', 'https://images.pexels.com/photos/6157059/pexels-photo-6157059.jpeg', 'cardamom.jpg'],
    ],
    'Organic' => [
        ['Organic Tomato', 'https://images.unsplash.com/photo-1582284540020-8acbe03f4924', 'organic_tomato.jpg'],
        ['Organic Spinach', 'https://images.unsplash.com/photo-1540420773420-3366772f4999', 'organic_spinach.jpg'],
        ['Organic Apple', 'https://images.unsplash.com/photo-1619546813926-a78fa6372cd2', 'organic_apple.jpg'],
        ['Organic Carrot', 'https://images.unsplash.com/photo-1590868309235-ea34bed7bd7f', 'organic_carrot.jpg'],
        ['Organic Milk', 'https://images.unsplash.com/photo-1628088062854-d1870b4553da', 'organic_milk.jpg'],
    ],
    'Other' => [
        ['Honey', 'https://images.unsplash.com/photo-1587049352851-8d4e89133924', 'honey.jpg'],
        ['Eggs', 'https://images.unsplash.com/photo-1598965675045-45c5e72c7d05', 'eggs.jpg'],
        ['Mushroom', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836', 'mushroom.jpg'],
        ['Nuts', 'https://images.unsplash.com/photo-1599599810769-bcde5a160d32', 'nuts.jpg'],
    ],
];

echo "<h1>Downloading Product Images</h1>";
echo "<p>Target directory: " . $targetDir . "</p>";

// Function to download an image
function downloadImage($url, $targetPath) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        file_put_contents($targetPath, $data);
        return true;
    }
    
    return false;
}

// Download images
$downloadedImages = [];
$failedImages = [];

echo "<h2>Download Progress:</h2>";
echo "<ul>";

foreach ($productImages as $category => $images) {
    echo "<li><strong>Category: " . htmlspecialchars($category) . "</strong><ul>";
    
    foreach ($images as $image) {
        $name = $image[0];
        $url = $image[1];
        $filename = $image[2];
        $targetPath = $targetDir . $filename;
        
        echo "<li>Downloading " . htmlspecialchars($name) . "... ";
        
        if (downloadImage($url, $targetPath)) {
            echo "<span style='color: green;'>Success!</span> Saved to " . htmlspecialchars($filename) . "</li>";
            $downloadedImages[] = [
                'category' => $category,
                'name' => $name,
                'filename' => $filename,
                'path' => 'uploads/products/' . $filename
            ];
        } else {
            echo "<span style='color: red;'>Failed!</span></li>";
            $failedImages[] = [
                'category' => $category,
                'name' => $name,
                'url' => $url
            ];
        }
        
        // Add a small delay to avoid overwhelming the server
        usleep(500000); // 0.5 seconds
    }
    
    echo "</ul></li>";
}

echo "</ul>";

// Display summary
echo "<h2>Download Summary:</h2>";
echo "<p>Successfully downloaded: " . count($downloadedImages) . " images</p>";
echo "<p>Failed downloads: " . count($failedImages) . " images</p>";

// Generate SQL for updating the database
echo "<h2>SQL Statements for Database Update:</h2>";
echo "<pre>";

foreach ($downloadedImages as $image) {
    $category = $image['category'];
    $name = $image['name'];
    $path = $image['path'];
    
    echo "-- Update $name in $category category\n";
    echo "UPDATE products SET image_url = '$path' WHERE name LIKE '%$name%' AND category_id = (SELECT id FROM categories WHERE name LIKE '%$category%');\n\n";
}

echo "</pre>";

// Include database configuration
include_once __DIR__ . '/api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if database connection is successful
if (!$db) {
    die("Database connection failed. Please check your database settings.");
}

// Update database with new image paths
echo "<h2>Database Update Results:</h2>";
echo "<ul>";

foreach ($downloadedImages as $image) {
    $category = $image['category'];
    $name = $image['name'];
    $path = $image['path'];
    
    try {
        // Find the category ID
        $categoryQuery = "SELECT id FROM categories WHERE name LIKE :category";
        $categoryStmt = $db->prepare($categoryQuery);
        $categoryParam = "%$category%";
        $categoryStmt->bindParam(':category', $categoryParam);
        $categoryStmt->execute();
        
        if ($categoryRow = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
            $categoryId = $categoryRow['id'];
            
            // Update products with matching name and category
            $updateQuery = "UPDATE products SET image_url = :path WHERE name LIKE :name AND category_id = :category_id";
            $updateStmt = $db->prepare($updateQuery);
            $nameParam = "%$name%";
            $updateStmt->bindParam(':path', $path);
            $updateStmt->bindParam(':name', $nameParam);
            $updateStmt->bindParam(':category_id', $categoryId);
            $updateStmt->execute();
            
            $rowCount = $updateStmt->rowCount();
            
            if ($rowCount > 0) {
                echo "<li><span style='color: green;'>Updated $rowCount product(s)</span> matching '$name' in category '$category' with image path '$path'</li>";
            } else {
                echo "<li><span style='color: orange;'>No products found</span> matching '$name' in category '$category'</li>";
            }
        } else {
            echo "<li><span style='color: red;'>Category not found</span> for '$category'</li>";
        }
    } catch (PDOException $e) {
        echo "<li><span style='color: red;'>Error updating database</span> for '$name': " . $e->getMessage() . "</li>";
    }
}

echo "</ul>";
?>
