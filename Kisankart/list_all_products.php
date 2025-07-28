<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

// HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>List All Products</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .image-preview { max-width: 100px; max-height: 100px; }
        .search-box { margin-bottom: 20px; padding: 10px; }
        .search-box input { padding: 5px; width: 300px; }
        .search-box button { padding: 5px 10px; }
    </style>
</head>
<body>
    <h1>List All Products</h1>
    
    <div class='search-box'>
        <form method='get'>
            <input type='text' name='search' placeholder='Search products...' value='" . (isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '') . "'>
            <button type='submit'>Search</button>
            <a href='list_all_products.php'><button type='button'>Clear</button></a>
        </form>
    </div>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build query based on search
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    if (!empty($search)) {
        $query = "SELECT id, name, description, price, discount_price, image_url, category_id 
                 FROM products 
                 WHERE name LIKE :search 
                 ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':search', '%' . $search . '%');
    } else {
        $query = "SELECT id, name, description, price, discount_price, image_url, category_id 
                 FROM products 
                 ORDER BY name";
        $stmt = $conn->prepare($query);
    }
    
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
    
    echo "<p>Found " . count($products) . " products</p>";
    
    echo "<table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Discount</th>
            <th>Image</th>
        </tr>";
    
    foreach ($products as $product) {
        $category_name = isset($categories[$product['category_id']]) ? $categories[$product['category_id']] : 'Unknown';
        
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$category_name}</td>";
        echo "<td>₹{$product['price']}</td>";
        echo "<td>" . (!empty($product['discount_price']) ? "₹{$product['discount_price']}" : "-") . "</td>";
        
        echo "<td>";
        if (!empty($product['image_url'])) {
            echo "<img src='{$product['image_url']}' class='image-preview' alt='{$product['name']}'>";
        } else {
            echo "No image";
        }
        echo "</td>";
        
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
