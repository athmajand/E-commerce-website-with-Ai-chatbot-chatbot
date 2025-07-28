<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h2>Add Sample Product with Image</h2>";

if ($db) {
    echo "<p style='color:green'>Database connection successful!</p>";

    // Check if uploads directory exists
    if (!is_dir("uploads")) {
        echo "<p>Creating uploads directory...</p>";
        mkdir("uploads", 0755);
    }

    // Check if uploads/products directory exists
    if (!is_dir("uploads/products")) {
        echo "<p>Creating uploads/products directory...</p>";
        mkdir("uploads/products", 0755);
    }

    // Create a sample image
    $sample_image_path = "uploads/products/sample_carrot.jpg";
    $additional_image_path = "uploads/products/sample_carrot_additional.jpg";

    // Sample image URLs (placeholder images)
    $placeholder_urls = [
        "https://images.unsplash.com/photo-1447175008436-054170c2e979?q=80&w=1000&auto=format&fit=crop",
        "https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?q=80&w=1000&auto=format&fit=crop"
    ];

    // Download sample images
    echo "<h3>Creating Sample Images:</h3>";

    // Download main image
    if (!file_exists($sample_image_path)) {
        echo "<p>Downloading main product image...</p>";
        $image_content = file_get_contents($placeholder_urls[0]);
        if ($image_content !== false) {
            file_put_contents($sample_image_path, $image_content);
            echo "<p style='color:green'>Main image downloaded successfully!</p>";
        } else {
            echo "<p style='color:red'>Failed to download main image.</p>";
        }
    } else {
        echo "<p>Main image already exists.</p>";
    }

    // Download additional image
    if (!file_exists($additional_image_path)) {
        echo "<p>Downloading additional product image...</p>";
        $image_content = file_get_contents($placeholder_urls[1]);
        if ($image_content !== false) {
            file_put_contents($additional_image_path, $image_content);
            echo "<p style='color:green'>Additional image downloaded successfully!</p>";
        } else {
            echo "<p style='color:red'>Failed to download additional image.</p>";
        }
    } else {
        echo "<p>Additional image already exists.</p>";
    }

    // Check if products table exists
    $check_products = $db->query("SHOW TABLES LIKE 'products'");
    if ($check_products->rowCount() > 0) {
        echo "<p style='color:green'>Products table exists.</p>";

        // Check if sample product already exists
        $check_query = "SELECT id FROM products WHERE name = 'Fresh Carrots'";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $product = $check_stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Sample product 'Fresh Carrots' already exists with ID: " . $product['id'] . "</p>";

            // Get vegetable category ID
            $category_query = "SELECT id FROM categories WHERE name = 'Vegetables' LIMIT 1";
            $category_stmt = $db->prepare($category_query);
            $category_stmt->execute();

            if ($category_stmt->rowCount() === 0) {
                echo "<p style='color:red'>Vegetables category not found. Please run setup_database.php first.</p>";
                echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
                exit;
            }

            $category = $category_stmt->fetch(PDO::FETCH_ASSOC);
            $category_id = $category['id'];

            // Get seller/farmer ID
            $seller_query = "SELECT id FROM seller_registrations LIMIT 1";
            $seller_stmt = $db->prepare($seller_query);
            $seller_stmt->execute();

            if ($seller_stmt->rowCount() === 0) {
                echo "<p style='color:red'>No sellers found. Please run setup_database.php first.</p>";
                echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
                exit;
            }

            $seller = $seller_stmt->fetch(PDO::FETCH_ASSOC);
            $farmer_id = $seller['id'];

            // Get seller profile ID
            $profile_query = "SELECT id FROM seller_profiles WHERE seller_registration_id = ? LIMIT 1";
            $profile_stmt = $db->prepare($profile_query);
            $profile_stmt->bindParam(1, $farmer_id);
            $profile_stmt->execute();

            $seller_id = null;
            if ($profile_stmt->rowCount() > 0) {
                $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
                $seller_id = $profile['id'];
            }

            // Update the product with image paths, category, and seller info
            $update_query = "UPDATE products SET
                            image_url = ?,
                            additional_images = ?,
                            category_id = ?,
                            farmer_id = ?,
                            seller_id = ?
                            WHERE name = 'Fresh Carrots'";
            $update_stmt = $db->prepare($update_query);

            // Create JSON for additional images
            $additional_images = json_encode([$additional_image_path]);

            $update_stmt->bindParam(1, $sample_image_path);
            $update_stmt->bindParam(2, $additional_images);
            $update_stmt->bindParam(3, $category_id);
            $update_stmt->bindParam(4, $farmer_id);
            $update_stmt->bindParam(5, $seller_id);

            if ($update_stmt->execute()) {
                echo "<p style='color:green'>Product images updated successfully!</p>";
            } else {
                echo "<p style='color:red'>Failed to update product images.</p>";
            }
        } else {
            // Create sample product with images
            echo "<h3>Creating Sample Product:</h3>";
            try {
                // Create JSON for additional images
                $additional_images = json_encode([$additional_image_path]);

                // Get vegetable category ID
                $category_query = "SELECT id FROM categories WHERE name = 'Vegetables' LIMIT 1";
                $category_stmt = $db->prepare($category_query);
                $category_stmt->execute();

                if ($category_stmt->rowCount() === 0) {
                    echo "<p style='color:red'>Vegetables category not found. Please run setup_database.php first.</p>";
                    echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
                    exit;
                }

                $category = $category_stmt->fetch(PDO::FETCH_ASSOC);
                $category_id = $category['id'];

                // Get seller/farmer ID
                $seller_query = "SELECT id FROM seller_registrations LIMIT 1";
                $seller_stmt = $db->prepare($seller_query);
                $seller_stmt->execute();

                if ($seller_stmt->rowCount() === 0) {
                    echo "<p style='color:red'>No sellers found. Please run setup_database.php first.</p>";
                    echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
                    exit;
                }

                $seller = $seller_stmt->fetch(PDO::FETCH_ASSOC);
                $farmer_id = $seller['id'];

                // Get seller profile ID
                $profile_query = "SELECT id FROM seller_profiles WHERE seller_registration_id = ? LIMIT 1";
                $profile_stmt = $db->prepare($profile_query);
                $profile_stmt->bindParam(1, $farmer_id);
                $profile_stmt->execute();

                $seller_id = null;
                if ($profile_stmt->rowCount() > 0) {
                    $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
                    $seller_id = $profile['id'];
                }

                $sample_product_sql = "INSERT INTO `products`
                    (`name`, `description`, `price`, `discount_price`, `stock_quantity`, `image_url`, `additional_images`, `category_id`, `farmer_id`, `seller_id`, `is_featured`, `status`)
                    VALUES
                    ('Fresh Carrots', 'Organic carrots freshly harvested from local farms. Rich in vitamins and minerals, these carrots are perfect for salads, cooking, or juicing.', 80.00, 65.00, 100, ?, ?, ?, ?, ?, 1, 'active')";

                $stmt = $db->prepare($sample_product_sql);
                $stmt->bindParam(1, $sample_image_path);
                $stmt->bindParam(2, $additional_images);
                $stmt->bindParam(3, $category_id);
                $stmt->bindParam(4, $farmer_id);
                $stmt->bindParam(5, $seller_id);

                if ($stmt->execute()) {
                    $product_id = $db->lastInsertId();
                    echo "<p style='color:green'>Sample product created successfully with ID: " . $product_id . "!</p>";
                } else {
                    echo "<p style='color:red'>Failed to create sample product.</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error creating sample product: " . $e->getMessage() . "</p>";
            }
        }

        // Display sample product link
        $product_query = "SELECT id FROM products WHERE name = 'Fresh Carrots'";
        $product_stmt = $db->prepare($product_query);
        $product_stmt->execute();

        if ($product_stmt->rowCount() > 0) {
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            echo "<h3>View Sample Product:</h3>";
            echo "<p><a href='frontend/product_details.php?id=" . $product['id'] . "' target='_blank'>View Fresh Carrots Product Page</a></p>";
        }

        // Display sample images
        echo "<h3>Sample Images:</h3>";
        echo "<div style='display: flex; gap: 20px;'>";
        echo "<div>";
        echo "<p>Main Image:</p>";
        echo "<img src='" . $sample_image_path . "' style='max-width: 300px; border: 1px solid #ddd;'>";
        echo "</div>";
        echo "<div>";
        echo "<p>Additional Image:</p>";
        echo "<img src='" . $additional_image_path . "' style='max-width: 300px; border: 1px solid #ddd;'>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<p style='color:red'>Products table does not exist. Please run check_images.php first to create the table.</p>";
    }
} else {
    echo "<p style='color:red'>Database connection failed!</p>";
}
?>
