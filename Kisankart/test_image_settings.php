<?php
// Test script to add sample image settings to products
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Testing Image Settings Feature</h2>";
    
    // Check if image_settings column exists
    $check_column = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_settings'");
    if ($check_column->rowCount() == 0) {
        echo "<p style='color:red'>Error: image_settings column does not exist. Please run add_image_settings_column.php first.</p>";
        exit;
    }
    
    echo "<p style='color:green'>✓ image_settings column exists</p>";
    
    // Get some products to test with
    $products_query = $pdo->query("SELECT id, name FROM products LIMIT 5");
    $products = $products_query->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p style='color:orange'>No products found to test with.</p>";
        exit;
    }
    
    echo "<h3>Adding sample image settings to products:</h3>";
    
    // Sample image settings configurations
    $sample_settings = [
        ['size' => 'small', 'padding' => 'large', 'fit' => 'contain', 'background' => 'white'],
        ['size' => 'medium', 'padding' => 'medium', 'fit' => 'cover', 'background' => 'light'],
        ['size' => 'large', 'padding' => 'small', 'fit' => 'contain', 'background' => 'dark'],
        ['size' => 'extra-large', 'padding' => 'none', 'fit' => 'fill', 'background' => 'transparent'],
        ['size' => 'default', 'padding' => 'medium', 'fit' => 'contain', 'background' => 'light']
    ];
    
    foreach ($products as $index => $product) {
        $settings = $sample_settings[$index % count($sample_settings)];
        $settings_json = json_encode($settings);
        
        $update_query = "UPDATE products SET image_settings = ? WHERE id = ?";
        $stmt = $pdo->prepare($update_query);
        $stmt->bindParam(1, $settings_json);
        $stmt->bindParam(2, $product['id']);
        
        if ($stmt->execute()) {
            echo "<p>✓ Updated product '{$product['name']}' (ID: {$product['id']}) with settings: " . 
                 "Size: {$settings['size']}, Padding: {$settings['padding']}, Fit: {$settings['fit']}, Background: {$settings['background']}</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to update product '{$product['name']}' (ID: {$product['id']})</p>";
        }
    }
    
    echo "<h3>Current products with image settings:</h3>";
    
    // Display current products with their image settings
    $display_query = $pdo->query("SELECT id, name, image_settings FROM products WHERE image_settings IS NOT NULL LIMIT 10");
    $products_with_settings = $display_query->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products_with_settings)) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>Product Name</th><th>Image Settings</th></tr>";
        
        foreach ($products_with_settings as $product) {
            $settings = json_decode($product['image_settings'], true);
            $settings_display = '';
            if ($settings) {
                $settings_display = "Size: " . ($settings['size'] ?? 'default') . 
                                  ", Padding: " . ($settings['padding'] ?? 'medium') . 
                                  ", Fit: " . ($settings['fit'] ?? 'contain') . 
                                  ", Background: " . ($settings['background'] ?? 'light');
            }
            
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>{$settings_display}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No products with image settings found.</p>";
    }
    
    echo "<h3>Test Instructions:</h3>";
    echo "<ol>";
    echo "<li>Go to the <a href='frontend/products.php' target='_blank'>Products Page</a> to see the image settings in action</li>";
    echo "<li>Go to the <a href='frontend/seller/products.php' target='_blank'>Seller Dashboard</a> to test editing image settings</li>";
    echo "<li>Try editing a product and changing the image display settings</li>";
    echo "<li>Check how the images appear differently based on the settings</li>";
    echo "</ol>";
    
    echo "<h3>CSS Classes Applied:</h3>";
    echo "<p>The dynamic image styles are applied using data attributes:</p>";
    echo "<ul>";
    echo "<li><code>data-size</code>: Controls image size (small, medium, large, extra-large, default)</li>";
    echo "<li><code>data-padding</code>: Controls padding around image (none, small, medium, large)</li>";
    echo "<li><code>data-fit</code>: Controls how image fits container (contain, cover, fill)</li>";
    echo "<li><code>data-background</code>: Controls background color (light, white, transparent, dark)</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
