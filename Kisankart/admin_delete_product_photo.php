<?php
// Delete product photo
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
$photo_check = $conn->prepare("SELECT id, image_path, is_primary FROM product_images WHERE id = ? AND product_id = ?");
$photo_check->bind_param("ii", $photo_id, $product_id);
$photo_check->execute();
$photo_result = $photo_check->get_result();

if ($photo_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Photo not found or does not belong to this product']);
    exit;
}

$photo_data = $photo_result->fetch_assoc();
$is_primary = $photo_data['is_primary'];
$image_path = $photo_data['image_path'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete the photo record from database
    $delete_query = $conn->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
    $delete_query->bind_param("ii", $photo_id, $product_id);
    $delete_query->execute();
    
    if ($delete_query->affected_rows > 0) {
        // Delete the physical file
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        // If this was the primary photo, set another photo as primary
        if ($is_primary) {
            $new_primary = $conn->prepare("SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
            $new_primary->bind_param("i", $product_id);
            $new_primary->execute();
            $new_primary_result = $new_primary->get_result();
            
            if ($new_primary_result->num_rows > 0) {
                $new_primary_data = $new_primary_result->fetch_assoc();
                
                // Set the new primary photo
                $set_new_primary = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?");
                $set_new_primary->bind_param("i", $new_primary_data['id']);
                $set_new_primary->execute();
                
                // Update product image path
                $update_product = $conn->prepare("UPDATE products SET image_path = ? WHERE id = ?");
                $update_product->bind_param("si", $new_primary_data['image_path'], $product_id);
                $update_product->execute();
            } else {
                // No more photos, clear product image path
                $clear_product = $conn->prepare("UPDATE products SET image_path = NULL WHERE id = ?");
                $clear_product->bind_param("i", $product_id);
                $clear_product->execute();
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Photo deleted successfully']);
    } else {
        throw new Exception('Failed to delete photo from database');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting photo: ' . $e->getMessage()]);
}

$conn->close();
?> 