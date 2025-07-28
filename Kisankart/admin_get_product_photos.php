<?php
// Get product photos for admin photo management
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

// Validate product exists
$product_check = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$product_check->bind_param("i", $product_id);
$product_check->execute();
$product_result = $product_check->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Get product photos
$photos_query = $conn->prepare("SELECT id, image_path, is_primary, sort_order, created_at FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$photos_query->bind_param("i", $product_id);
$photos_query->execute();
$photos_result = $photos_query->get_result();

$photos = [];
while ($photo = $photos_result->fetch_assoc()) {
    // Check if file exists
    if (file_exists($photo['image_path'])) {
        $photos[] = [
            'id' => $photo['id'],
            'image_path' => $photo['image_path'],
            'is_primary' => (bool)$photo['is_primary'],
            'sort_order' => $photo['sort_order'],
            'created_at' => $photo['created_at'],
            'file_size' => filesize($photo['image_path']),
            'file_name' => basename($photo['image_path'])
        ];
    } else {
        // File doesn't exist, mark for deletion
        $delete_query = $conn->prepare("DELETE FROM product_images WHERE id = ?");
        $delete_query->bind_param("i", $photo['id']);
        $delete_query->execute();
    }
}

echo json_encode([
    'success' => true,
    'photos' => $photos,
    'count' => count($photos)
]);

$conn->close();
?> 