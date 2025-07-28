<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

// Define high-resolution product images
$product_images = [
    'Tomato' => [
        'url' => 'https://images.unsplash.com/photo-1607305387299-a3d9611cd469?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'tomato_hd.jpg'
    ],
    'Potato' => [
        'url' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'potato_hd.jpg'
    ],
    'Onion' => [
        'url' => 'https://images.unsplash.com/photo-1620574387735-3624d75b2dbc?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'onion_hd.jpg'
    ],
    'Carrot' => [
        'url' => 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'carrot_hd.jpg'
    ],
    'Apple' => [
        'url' => 'https://images.unsplash.com/photo-1570913149827-d2ac84ab3f9a?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'apple_hd.jpg'
    ],
    'Banana' => [
        'url' => 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'banana_hd.jpg'
    ],
    'Orange' => [
        'url' => 'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab12?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'orange_hd.jpg'
    ],
    'Grapes' => [
        'url' => 'https://images.unsplash.com/photo-1596363505729-4190a9506133?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'grapes_hd.jpg'
    ],
    'Rice' => [
        'url' => 'https://images.unsplash.com/photo-1586201375761-83865001e8ac?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'rice_hd.jpg'
    ],
    'Wheat' => [
        'url' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'wheat_hd.jpg'
    ],
    'Milk' => [
        'url' => 'https://images.unsplash.com/photo-1563636619-e9143da7973b?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'milk_hd.jpg'
    ],
    'Cheese' => [
        'url' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?q=80&w=1473&auto=format&fit=crop',
        'filename' => 'cheese_hd.jpg'
    ],
    'Spinach' => [
        'url' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?q=80&w=1480&auto=format&fit=crop',
        'filename' => 'spinach_hd.jpg'
    ],
    'Broccoli' => [
        'url' => 'https://images.unsplash.com/photo-1459411621453-7b03977f4bfc?q=80&w=1402&auto=format&fit=crop',
        'filename' => 'broccoli_hd.jpg'
    ],
    'Cucumber' => [
        'url' => 'https://images.unsplash.com/photo-1604977042946-1eecc30f269e?q=80&w=1470&auto=format&fit=crop',
        'filename' => 'cucumber_hd.jpg'
    ]
];

// HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Product Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .image-preview { max-width: 100px; max-height: 100px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Update Product Images to High Resolution</h1>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    echo "<table>
        <tr>
            <th>Product</th>
            <th>Status</th>
            <th>Old Image</th>
            <th>New Image</th>
        </tr>";
    
    // Process each product image
    foreach ($product_images as $product_name => $image_data) {
        echo "<tr>";
        echo "<td>{$product_name}</td>";
        
        // Find products with matching name
        $query = "SELECT id, name, image_url FROM products WHERE name LIKE :name";
        $stmt = $conn->prepare($query);
        $search_name = '%' . $product_name . '%';
        $stmt->bindParam(':name', $search_name);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            // Download the image
            $image_url = $image_data['url'];
            $filename = $image_data['filename'];
            $save_path = $upload_dir . $filename;
            
            // Get image content
            $image_content = @file_get_contents($image_url);
            
            if ($image_content !== false) {
                // Save image to file
                if (file_put_contents($save_path, $image_content)) {
                    $db_image_path = 'uploads/products/' . $filename;
                    
                    // Update all matching products
                    $update_query = "UPDATE products SET image_url = :image_url WHERE name LIKE :name";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':image_url', $db_image_path);
                    $update_stmt->bindParam(':name', $search_name);
                    
                    if ($update_stmt->execute()) {
                        $status = "<span class='success'>Updated " . $update_stmt->rowCount() . " products</span>";
                    } else {
                        $status = "<span class='error'>Failed to update database</span>";
                    }
                    
                    // Show old and new images
                    $old_image = !empty($products[0]['image_url']) ? $products[0]['image_url'] : 'No image';
                    echo "<td>{$status}</td>";
                    echo "<td>";
                    if (!empty($products[0]['image_url'])) {
                        echo "<img src='{$products[0]['image_url']}' class='image-preview' alt='Old image'>";
                    } else {
                        echo "No image";
                    }
                    echo "</td>";
                    echo "<td><img src='{$db_image_path}' class='image-preview' alt='New image'></td>";
                } else {
                    echo "<td><span class='error'>Failed to save image</span></td>";
                    echo "<td colspan='2'>Error saving to {$save_path}</td>";
                }
            } else {
                echo "<td><span class='error'>Failed to download image</span></td>";
                echo "<td colspan='2'>Error downloading from {$image_url}</td>";
            }
        } else {
            echo "<td><span class='error'>No matching products found</span></td>";
            echo "<td colspan='2'>No products with name like '{$product_name}'</td>";
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
