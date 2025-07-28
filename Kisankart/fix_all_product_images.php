<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

// Define high-quality images for common product types
$product_image_mappings = [
    // Vegetables
    'tomato' => [
        'url' => 'https://images.unsplash.com/photo-1607305387299-a3d9611cd469?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['tomato', 'tomatoes']
    ],
    'potato' => [
        'url' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['potato', 'potatoes']
    ],
    'onion' => [
        'url' => 'https://images.unsplash.com/photo-1620574387735-3624d75b2dbc?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['onion', 'onions']
    ],
    'carrot' => [
        'url' => 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['carrot', 'carrots']
    ],
    'cucumber' => [
        'url' => 'https://images.unsplash.com/photo-1604977042946-1eecc30f269e?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['cucumber', 'cucumbers']
    ],
    'broccoli' => [
        'url' => 'https://images.unsplash.com/photo-1459411621453-7b03977f4bfc?q=80&w=1402&auto=format&fit=crop',
        'keywords' => ['broccoli']
    ],
    'spinach' => [
        'url' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?q=80&w=1480&auto=format&fit=crop',
        'keywords' => ['spinach', 'palak']
    ],
    'bell_pepper' => [
        'url' => 'https://images.unsplash.com/photo-1563565375-f3fdfdbefa83?q=80&w=1374&auto=format&fit=crop',
        'keywords' => ['bell pepper', 'capsicum']
    ],
    
    // Fruits
    'apple' => [
        'url' => 'https://images.unsplash.com/photo-1570913149827-d2ac84ab3f9a?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['apple', 'apples']
    ],
    'banana' => [
        'url' => 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?q=80&w=1374&auto=format&fit=crop',
        'keywords' => ['banana', 'bananas']
    ],
    'orange' => [
        'url' => 'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab12?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['orange', 'oranges']
    ],
    'grapes' => [
        'url' => 'https://images.unsplash.com/photo-1596363505729-4190a9506133?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['grape', 'grapes']
    ],
    'strawberry' => [
        'url' => 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['strawberry', 'strawberries']
    ],
    'mango' => [
        'url' => 'https://images.unsplash.com/photo-1553279768-865429fa0078?q=80&w=1374&auto=format&fit=crop',
        'keywords' => ['mango', 'mangoes']
    ],
    
    // Dairy
    'milk' => [
        'url' => 'https://images.unsplash.com/photo-1563636619-e9143da7973b?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['milk', 'dairy milk']
    ],
    'cheese' => [
        'url' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?q=80&w=1473&auto=format&fit=crop',
        'keywords' => ['cheese', 'paneer']
    ],
    'butter' => [
        'url' => 'https://images.unsplash.com/photo-1589985270958-bf087b2d8ed7?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['butter']
    ],
    'yogurt' => [
        'url' => 'https://images.unsplash.com/photo-1571212515416-fef01fc43637?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['yogurt', 'curd', 'yoghurt']
    ],
    
    // Grains
    'rice' => [
        'url' => 'https://images.unsplash.com/photo-1586201375761-83865001e8ac?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['rice', 'basmati']
    ],
    'wheat' => [
        'url' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['wheat', 'atta']
    ],
    'quinoa' => [
        'url' => 'https://images.unsplash.com/photo-1586295166487-b265f7e83a7f?q=80&w=1480&auto=format&fit=crop',
        'keywords' => ['quinoa']
    ],
    
    // Spices
    'turmeric' => [
        'url' => 'https://images.unsplash.com/photo-1615485500704-8e990f9900f1?q=80&w=1480&auto=format&fit=crop',
        'keywords' => ['turmeric', 'haldi']
    ],
    'cardamom' => [
        'url' => 'https://images.unsplash.com/photo-1638275963918-9044a2ba5b0a?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['cardamom', 'elaichi']
    ],
    'cumin' => [
        'url' => 'https://images.unsplash.com/photo-1599909366516-6c518cabc283?q=80&w=1470&auto=format&fit=crop',
        'keywords' => ['cumin', 'jeera']
    ]
];

// HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix All Product Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .image-preview { max-width: 100px; max-height: 100px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .controls { margin-bottom: 20px; }
        .controls button { padding: 10px; margin-right: 10px; }
    </style>
</head>
<body>
    <h1>Fix All Product Images</h1>
    
    <div class='controls'>
        <form method='post'>
            <button type='submit' name='action' value='check'>Check Products</button>
            <button type='submit' name='action' value='fix'>Fix Mismatched Images</button>
        </form>
    </div>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Get all products
    $query = "SELECT id, name, image_url, category_id FROM products ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories
    $cat_query = "SELECT id, name FROM categories";
    $cat_stmt = $conn->prepare($cat_query);
    $cat_stmt->execute();
    $categories = [];
    while ($row = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['id']] = $row['name'];
    }
    
    // Check if we're fixing images
    $action = isset($_POST['action']) ? $_POST['action'] : 'check';
    $fixing = ($action === 'fix');
    
    if ($fixing) {
        echo "<h2>Fixing Mismatched Images</h2>";
    } else {
        echo "<h2>Checking Product Images</h2>";
    }
    
    echo "<table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Status</th>
            <th>Old Image</th>
            <th>New Image</th>
        </tr>";
    
    foreach ($products as $product) {
        $product_id = $product['id'];
        $product_name = $product['name'];
        $product_name_lower = strtolower($product_name);
        $image_url = $product['image_url'];
        $category_name = isset($categories[$product['category_id']]) ? $categories[$product['category_id']] : 'Unknown';
        
        // Find matching image type
        $matched_type = null;
        foreach ($product_image_mappings as $type => $mapping) {
            foreach ($mapping['keywords'] as $keyword) {
                if (strpos($product_name_lower, $keyword) !== false) {
                    $matched_type = $type;
                    break 2;
                }
            }
        }
        
        // Check if current image matches the product type
        $image_matches = false;
        if ($matched_type && !empty($image_url)) {
            $image_filename = strtolower(basename($image_url));
            if (strpos($image_filename, $matched_type) !== false) {
                $image_matches = true;
            }
        }
        
        echo "<tr>
            <td>{$product_id}</td>
            <td>{$product_name}</td>
            <td>{$category_name}</td>";
        
        // If image doesn't match and we have a matched type, fix it
        if (!$image_matches && $matched_type && $fixing) {
            $image_source = $product_image_mappings[$matched_type]['url'];
            $filename = $matched_type . '.jpg';
            $save_path = $upload_dir . $filename;
            $new_image_url = 'uploads/products/' . $filename;
            
            // Check if the file already exists
            if (!file_exists($save_path)) {
                // Download the image
                $image_content = @file_get_contents($image_source);
                if ($image_content !== false) {
                    file_put_contents($save_path, $image_content);
                } else {
                    echo "<td><span class='error'>Failed to download image</span></td>";
                    echo "<td colspan='2'>Error downloading from {$image_source}</td>";
                    echo "</tr>";
                    continue;
                }
            }
            
            // Update the database
            $update_query = "UPDATE products SET image_url = :image_url WHERE id = :id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':image_url', $new_image_url);
            $update_stmt->bindParam(':id', $product_id);
            
            if ($update_stmt->execute()) {
                echo "<td><span class='success'>Updated successfully</span></td>";
                
                // Show old and new images
                echo "<td>";
                if (!empty($image_url)) {
                    echo "<img src='{$image_url}' class='image-preview' alt='Old image'>";
                } else {
                    echo "No image";
                }
                echo "</td>";
                echo "<td><img src='{$new_image_url}' class='image-preview' alt='New image'></td>";
            } else {
                echo "<td><span class='error'>Failed to update database</span></td>";
                echo "<td colspan='2'>Error updating product ID {$product_id}</td>";
            }
        } else {
            // Just display status
            if (!$matched_type) {
                echo "<td><span class='warning'>No matching product type found</span></td>";
            } elseif ($image_matches) {
                echo "<td><span class='success'>Image matches product type</span></td>";
            } else {
                echo "<td><span class='error'>Image doesn't match product type</span></td>";
            }
            
            // Show current image
            echo "<td>";
            if (!empty($image_url)) {
                echo "<img src='{$image_url}' class='image-preview' alt='Current image'>";
            } else {
                echo "No image";
            }
            echo "</td>";
            
            // Show expected image
            echo "<td>";
            if ($matched_type) {
                $expected_image = 'uploads/products/' . $matched_type . '.jpg';
                if (file_exists(__DIR__ . '/' . $expected_image)) {
                    echo "<img src='{$expected_image}' class='image-preview' alt='Expected image'>";
                } else {
                    echo "Expected: {$matched_type}.jpg (not downloaded yet)";
                }
            } else {
                echo "N/A";
            }
            echo "</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
