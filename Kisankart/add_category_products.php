<?php
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

// Define the farmer_id
$farmer_id = 6;

// Define product data for each category
$category_products = [
    // Category 1: Farm Fresh Vegetables
    1 => [
        [
            'name' => 'Fresh Spinach',
            'description' => 'Organic farm-fresh spinach, rich in iron and vitamins. Perfect for salads and cooking.',
            'price' => 35.00,
            'discount_price' => 30.00,
            'stock_quantity' => 100,
            'image_url' => 'uploads/products/spinach.jpg'
        ],
        [
            'name' => 'Organic Broccoli',
            'description' => 'Farm-fresh organic broccoli, packed with nutrients and antioxidants.',
            'price' => 60.00,
            'discount_price' => 55.00,
            'stock_quantity' => 80,
            'image_url' => 'uploads/products/broccoli.jpg'
        ],
        [
            'name' => 'Fresh Kale',
            'description' => 'Nutrient-dense kale, freshly harvested from organic farms.',
            'price' => 45.00,
            'discount_price' => 40.00,
            'stock_quantity' => 90,
            'image_url' => 'uploads/products/kale.jpg'
        ],
        [
            'name' => 'Organic Lettuce',
            'description' => 'Crisp and fresh organic lettuce, perfect for salads and sandwiches.',
            'price' => 30.00,
            'discount_price' => 25.00,
            'stock_quantity' => 120,
            'image_url' => 'uploads/products/lettuce.jpg'
        ]
    ],
    
    // Category 2: Vegetables
    2 => [
        [
            'name' => 'Fresh Tomatoes',
            'description' => 'Juicy and ripe tomatoes, perfect for salads and cooking.',
            'price' => 40.00,
            'discount_price' => 35.00,
            'stock_quantity' => 150,
            'image_url' => 'uploads/products/tomatoes.jpg'
        ],
        [
            'name' => 'Green Bell Peppers',
            'description' => 'Crisp and flavorful green bell peppers, great for stir-fries and salads.',
            'price' => 50.00,
            'discount_price' => 45.00,
            'stock_quantity' => 100,
            'image_url' => 'uploads/products/green_peppers.jpg'
        ],
        [
            'name' => 'Fresh Cucumbers',
            'description' => 'Cool and refreshing cucumbers, perfect for salads and snacking.',
            'price' => 30.00,
            'discount_price' => 25.00,
            'stock_quantity' => 120,
            'image_url' => 'uploads/products/cucumbers.jpg'
        ],
        [
            'name' => 'Organic Eggplant',
            'description' => 'Tender and flavorful organic eggplant, great for various cuisines.',
            'price' => 45.00,
            'discount_price' => 40.00,
            'stock_quantity' => 80,
            'image_url' => 'uploads/products/eggplant.jpg'
        ]
    ],
    
    // Category 3: Fruits
    3 => [
        [
            'name' => 'Fresh Apples',
            'description' => 'Crisp and sweet apples, perfect for snacking and baking.',
            'price' => 120.00,
            'discount_price' => 100.00,
            'stock_quantity' => 200,
            'image_url' => 'uploads/products/apples.jpg'
        ],
        [
            'name' => 'Organic Bananas',
            'description' => 'Sweet and nutritious organic bananas, rich in potassium.',
            'price' => 60.00,
            'discount_price' => 50.00,
            'stock_quantity' => 250,
            'image_url' => 'uploads/products/bananas.jpg'
        ],
        [
            'name' => 'Fresh Oranges',
            'description' => 'Juicy and tangy oranges, packed with vitamin C.',
            'price' => 80.00,
            'discount_price' => 70.00,
            'stock_quantity' => 180,
            'image_url' => 'uploads/products/oranges.jpg'
        ],
        [
            'name' => 'Sweet Strawberries',
            'description' => 'Plump and sweet strawberries, perfect for desserts and snacking.',
            'price' => 150.00,
            'discount_price' => 130.00,
            'stock_quantity' => 100,
            'image_url' => 'uploads/products/strawberries.jpg'
        ]
    ],
    
    // Category 4: Dairy
    4 => [
        [
            'name' => 'Fresh Milk',
            'description' => 'Farm-fresh milk, rich in calcium and nutrients.',
            'price' => 60.00,
            'discount_price' => 55.00,
            'stock_quantity' => 150,
            'image_url' => 'uploads/products/milk.jpg'
        ],
        [
            'name' => 'Organic Cheese',
            'description' => 'Artisanal organic cheese, made from high-quality milk.',
            'price' => 180.00,
            'discount_price' => 160.00,
            'stock_quantity' => 80,
            'image_url' => 'uploads/products/cheese.jpg'
        ],
        [
            'name' => 'Fresh Yogurt',
            'description' => 'Creamy and probiotic-rich yogurt, perfect for breakfast and snacks.',
            'price' => 45.00,
            'discount_price' => 40.00,
            'stock_quantity' => 120,
            'image_url' => 'uploads/products/yogurt.jpg'
        ],
        [
            'name' => 'Organic Butter',
            'description' => 'Smooth and rich organic butter, made from grass-fed cow milk.',
            'price' => 120.00,
            'discount_price' => 110.00,
            'stock_quantity' => 90,
            'image_url' => 'uploads/products/butter.jpg'
        ]
    ],
    
    // Category 5: Grains
    5 => [
        [
            'name' => 'Organic Rice',
            'description' => 'Premium organic rice, perfect for everyday meals.',
            'price' => 150.00,
            'discount_price' => 140.00,
            'stock_quantity' => 200,
            'image_url' => 'uploads/products/rice.jpg'
        ],
        [
            'name' => 'Whole Wheat',
            'description' => 'Nutritious whole wheat grains, rich in fiber and nutrients.',
            'price' => 80.00,
            'discount_price' => 75.00,
            'stock_quantity' => 180,
            'image_url' => 'uploads/products/wheat.jpg'
        ],
        [
            'name' => 'Organic Quinoa',
            'description' => 'Protein-rich organic quinoa, a superfood for healthy meals.',
            'price' => 220.00,
            'discount_price' => 200.00,
            'stock_quantity' => 100,
            'image_url' => 'uploads/products/quinoa.jpg'
        ],
        [
            'name' => 'Millet',
            'description' => 'Nutritious millet grains, perfect for various recipes.',
            'price' => 90.00,
            'discount_price' => 80.00,
            'stock_quantity' => 150,
            'image_url' => 'uploads/products/millet.jpg'
        ]
    ],
    
    // Category 6: Spices
    6 => [
        [
            'name' => 'Organic Turmeric',
            'description' => 'Premium organic turmeric powder, known for its anti-inflammatory properties.',
            'price' => 120.00,
            'discount_price' => 100.00,
            'stock_quantity' => 150,
            'image_url' => 'uploads/products/turmeric.jpg'
        ],
        [
            'name' => 'Fresh Ginger',
            'description' => 'Aromatic fresh ginger, perfect for cooking and herbal teas.',
            'price' => 60.00,
            'discount_price' => 50.00,
            'stock_quantity' => 120,
            'image_url' => 'uploads/products/ginger.jpg'
        ],
        [
            'name' => 'Organic Cinnamon',
            'description' => 'High-quality organic cinnamon, perfect for baking and cooking.',
            'price' => 150.00,
            'discount_price' => 130.00,
            'stock_quantity' => 100,
            'image_url' => 'uploads/products/cinnamon.jpg'
        ],
        [
            'name' => 'Black Pepper',
            'description' => 'Premium black pepper, freshly ground for maximum flavor.',
            'price' => 90.00,
            'discount_price' => 80.00,
            'stock_quantity' => 130,
            'image_url' => 'uploads/products/black_pepper.jpg'
        ]
    ],
    
    // Category 7: Organic
    7 => [
        [
            'name' => 'Organic Honey',
            'description' => 'Pure organic honey, harvested from pesticide-free farms.',
            'price' => 250.00,
            'discount_price' => 220.00,
            'stock_quantity' => 80,
            'image_url' => 'uploads/products/honey.jpg'
        ],
        [
            'name' => 'Organic Jaggery',
            'description' => 'Traditional organic jaggery, a healthier alternative to refined sugar.',
            'price' => 120.00,
            'discount_price' => 100.00,
            'stock_quantity' => 100,
            'image_url' => 'uploads/products/jaggery.jpg'
        ],
        [
            'name' => 'Organic Coconut Oil',
            'description' => 'Cold-pressed organic coconut oil, perfect for cooking and skincare.',
            'price' => 180.00,
            'discount_price' => 160.00,
            'stock_quantity' => 90,
            'image_url' => 'uploads/products/coconut_oil.jpg'
        ],
        [
            'name' => 'Organic Ghee',
            'description' => 'Pure organic ghee, made from grass-fed cow milk.',
            'price' => 350.00,
            'discount_price' => 320.00,
            'stock_quantity' => 70,
            'image_url' => 'uploads/products/ghee.jpg'
        ]
    ],
    
    // Category 8: Other
    8 => [
        [
            'name' => 'Fresh Eggs',
            'description' => 'Farm-fresh eggs from free-range chickens.',
            'price' => 90.00,
            'discount_price' => 80.00,
            'stock_quantity' => 200,
            'image_url' => 'uploads/products/eggs.jpg'
        ],
        [
            'name' => 'Organic Nuts Mix',
            'description' => 'Assorted organic nuts, rich in healthy fats and proteins.',
            'price' => 320.00,
            'discount_price' => 290.00,
            'stock_quantity' => 80,
            'image_url' => 'uploads/products/nuts.jpg'
        ],
        [
            'name' => 'Dried Fruits',
            'description' => 'Assorted dried fruits, perfect for snacking and baking.',
            'price' => 250.00,
            'discount_price' => 220.00,
            'stock_quantity' => 100,
            'image_url' => 'uploads/products/dried_fruits.jpg'
        ],
        [
            'name' => 'Organic Seeds Mix',
            'description' => 'Nutritious mix of organic seeds, rich in omega-3 fatty acids.',
            'price' => 180.00,
            'discount_price' => 160.00,
            'stock_quantity' => 90,
            'image_url' => 'uploads/products/seeds.jpg'
        ]
    ]
];

// Function to check if a product already exists
function productExists($db, $name, $category_id) {
    $query = "SELECT COUNT(*) as count FROM products WHERE name = ? AND category_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $category_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

// Insert products for each category
echo "<h1>Adding Products for Each Category</h1>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Category ID</th><th>Product Name</th><th>Status</th></tr>";

foreach ($category_products as $category_id => $products) {
    foreach ($products as $product) {
        // Check if product already exists
        if (productExists($db, $product['name'], $category_id)) {
            echo "<tr>";
            echo "<td>" . $category_id . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>Already exists</td>";
            echo "</tr>";
            continue;
        }
        
        try {
            // Insert product
            $query = "INSERT INTO products (name, description, price, discount_price, category_id, farmer_id, stock_quantity, image_url, is_featured, status, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'active', NOW(), NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $product['name']);
            $stmt->bindParam(2, $product['description']);
            $stmt->bindParam(3, $product['price']);
            $stmt->bindParam(4, $product['discount_price']);
            $stmt->bindParam(5, $category_id);
            $stmt->bindParam(6, $farmer_id);
            $stmt->bindParam(7, $product['stock_quantity']);
            $stmt->bindParam(8, $product['image_url']);
            
            $result = $stmt->execute();
            
            echo "<tr>";
            echo "<td>" . $category_id . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . ($result ? "Added successfully" : "Failed to add") . "</td>";
            echo "</tr>";
            
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td>" . $category_id . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>Error: " . $e->getMessage() . "</td>";
            echo "</tr>";
        }
    }
}

echo "</table>";

// Create sample product images
echo "<h2>Creating Sample Product Images</h2>";

// Define the uploads directory
$uploads_dir = __DIR__ . '/uploads/products/';

// Create directory if it doesn't exist
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
    echo "<p>Created uploads/products directory</p>";
}

// Function to create a sample image
function createSampleImage($filename, $text, $uploads_dir) {
    $image_path = $uploads_dir . $filename;
    
    // Check if image already exists
    if (file_exists($image_path)) {
        return "Image already exists";
    }
    
    // Create a placeholder image using placeholder.com
    $placeholder_url = 'https://via.placeholder.com/300x200/4CAF50/FFFFFF?text=' . urlencode($text);
    
    // Try to get the image content
    $image_content = @file_get_contents($placeholder_url);
    
    if ($image_content === false) {
        return "Failed to download placeholder image";
    }
    
    // Save the image
    if (file_put_contents($image_path, $image_content)) {
        return "Created successfully";
    } else {
        return "Failed to save image";
    }
}

// Create sample images for all products
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Image Filename</th><th>Status</th></tr>";

foreach ($category_products as $category_id => $products) {
    foreach ($products as $product) {
        $filename = basename($product['image_url']);
        $status = createSampleImage($filename, str_replace(' ', '+', $product['name']), $uploads_dir);
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($filename) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<p><a href='index.php'>Back to Home</a></p>";
?>
