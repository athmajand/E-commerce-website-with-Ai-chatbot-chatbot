<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

// Define specific product image mappings
$specific_product_mappings = [
    'Fresh Milk' => [
        'url' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'fresh_milk.jpg'
    ],
    'Organic Cinnamon' => [
        'url' => 'https://images.unsplash.com/photo-1608613304899-ea8098577e38?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'organic_cinnamon.jpg'
    ],
    'Fresh Ginger' => [
        'url' => 'https://images.unsplash.com/photo-1615485500704-8e990f9900f1?q=80&w=1480&auto=format&fit=crop',
        'filename' => 'fresh_ginger.jpg'
    ],
    'Organic Turmeric' => [
        'url' => 'https://images.unsplash.com/photo-1615485500704-8e990f9900f1?q=80&w=1480&auto=format&fit=crop',
        'filename' => 'organic_turmeric.jpg'
    ],
    'Organic Quinoa' => [
        'url' => 'https://images.unsplash.com/photo-1586295166487-b265f7e83a7f?q=80&w=1480&auto=format&fit=crop',
        'filename' => 'organic_quinoa.jpg'
    ],
    'Organic Seeds Mix' => [
        'url' => 'https://images.unsplash.com/photo-1599599810769-bcde5a160d32?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'organic_seeds_mix.jpg'
    ],
    'Dried Fruits' => [
        'url' => 'https://images.unsplash.com/photo-1596591868231-05e908752cc9?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'dried_fruits.jpg'
    ],
    'Organic Nuts Mix' => [
        'url' => 'https://images.unsplash.com/photo-1604935067269-27c7e8b36618?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'organic_nuts_mix.jpg'
    ],
    'Fresh Eggs' => [
        'url' => 'https://images.unsplash.com/photo-1598965675045-45c5e72c7d05?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'fresh_eggs.jpg'
    ],
    'Organic Ghee' => [
        'url' => 'https://images.unsplash.com/photo-1631213717286-59a9f0a1c5d9?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'organic_ghee.jpg'
    ],
    'Organic Coconut Oil' => [
        'url' => 'https://images.unsplash.com/photo-1611071535774-8d5af2e2c5a1?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'organic_coconut_oil.jpg'
    ],
    'Organic Jaggery' => [
        'url' => 'https://images.unsplash.com/photo-1622484212850-eb596d769edc?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'organic_jaggery.jpg'
    ],
    'Organic Honey' => [
        'url' => 'https://images.unsplash.com/photo-1587049352851-8d4e89133924?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'organic_honey.jpg'
    ],
    'Black Pepper' => [
        'url' => 'https://images.unsplash.com/photo-1599789197514-47270cd526b4?q=80&w=1374&auto=format&fit=crop',
        'filename' => 'black_pepper.jpg'
    ]
];

// HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Specific Product Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .image-preview { max-width: 100px; max-height: 100px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Fix Specific Product Images</h1>";

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

    // Process each specific product
    foreach ($specific_product_mappings as $product_name => $image_data) {
        echo "<tr>";
        echo "<td>{$product_name}</td>";

        // Try fuzzy search to find products with similar names
        $query = "SELECT id, name, image_url FROM products WHERE name LIKE :name";
        $stmt = $conn->prepare($query);
        $fuzzy_name = '%' . $product_name . '%';
        $stmt->bindParam(':name', $fuzzy_name);
        $stmt->execute();
        $matching_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($matching_products) > 0) {
            // Download and save the new image
            $image_url = $image_data['url'];
            $filename = $image_data['filename'];
            $save_path = $upload_dir . $filename;

            // Get image content
            $image_content = @file_get_contents($image_url);

            if ($image_content !== false) {
                // Save image to file
                if (file_put_contents($save_path, $image_content)) {
                    $new_image_url = 'uploads/products/' . $filename;

                    // Update all matching products
                    $updated_count = 0;
                    $product_names = [];

                    foreach ($matching_products as $product) {
                        $old_image_url = $product['image_url'];

                        // Update the product image URL
                        $update_query = "UPDATE products SET image_url = :image_url WHERE id = :id";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bindParam(':image_url', $new_image_url);
                        $update_stmt->bindParam(':id', $product['id']);

                        if ($update_stmt->execute()) {
                            $updated_count++;
                            $product_names[] = $product['name'];
                        }
                    }

                    if ($updated_count > 0) {
                        echo "<td><span class='success'>Updated {$updated_count} products: " . implode(', ', $product_names) . "</span></td>";

                        // Show old and new images
                        echo "<td>";
                        if (!empty($matching_products[0]['image_url'])) {
                            echo "<img src='{$matching_products[0]['image_url']}' class='image-preview' alt='Old image'>";
                        } else {
                            echo "No image";
                        }
                        echo "</td>";
                        echo "<td><img src='{$new_image_url}' class='image-preview' alt='New image'></td>";
                    } else {
                        echo "<td><span class='error'>Failed to update any products</span></td>";
                        echo "<td colspan='2'>Error updating products</td>";
                    }
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
