<?php
// Set primary photo for product
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$photo_id = $input['photo_id'] ?? null;
$product_id = $input['product_id'] ?? null;

if (!$photo_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Photo ID and Product ID are required']);
    exit;
}

// Validate photo exists and belongs to the product
$photo_check = $conn->prepare("SELECT id, image_path FROM product_images WHERE id = ? AND product_id = ?");
$photo_check->bind_param("ii", $photo_id, $product_id);
$photo_check->execute();
$photo_result = $photo_check->get_result();

if ($photo_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Photo not found or does not belong to this product']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Remove primary flag from all photos of this product
    $remove_primary = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
    $remove_primary->bind_param("i", $product_id);
    $remove_primary->execute();
    
    // Set the selected photo as primary
    $set_primary = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
    $set_primary->bind_param("ii", $photo_id, $product_id);
    $set_primary->execute();
    
    if ($set_primary->affected_rows > 0) {
        // Update the main product image path
        $photo_data = $photo_result->fetch_assoc();
        $update_product = $conn->prepare("UPDATE products SET image_path = ? WHERE id = ?");
        $update_product->bind_param("si", $photo_data['image_path'], $product_id);
        $update_product->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Primary photo updated successfully']);
    } else {
        throw new Exception('Failed to update primary photo');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error updating primary photo: ' . $e->getMessage()]);
}

$conn->close();
?> 