<?php
// Product photo upload handler for admin
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

// Check if files were uploaded
if (!isset($_FILES['product_photos']) || empty($_FILES['product_photos']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No files were uploaded']);
    exit;
}

$product_id = $_POST['product_id'] ?? null;
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

$product = $product_result->fetch_assoc();

// Create upload directory if it doesn't exist
$upload_dir = "uploads/products/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded_files = [];
$errors = [];

// Process each uploaded file
$files = $_FILES['product_photos'];
$file_count = count($files['name']);

for ($i = 0; $i < $file_count; $i++) {
    if ($files['error'][$i] === UPLOAD_ERR_OK) {
        $file_name = $files['name'][$i];
        $file_tmp = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $file_type = $files['type'][$i];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "File '$file_name' has an invalid type. Allowed: JPG, PNG, GIF, WEBP";
            continue;
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file_size > $max_size) {
            $errors[] = "File '$file_name' is too large. Maximum size: 5MB";
            continue;
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = time() . '_' . $product_id . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $uploaded_files[] = $upload_path;
            
            // Check if this is the first photo (set as primary)
            $is_primary = 0;
            $check_primary = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ? AND is_primary = 1");
            $check_primary->bind_param("i", $product_id);
            $check_primary->execute();
            $primary_result = $check_primary->get_result();
            $primary_count = $primary_result->fetch_assoc()['count'];
            
            if ($primary_count == 0) {
                $is_primary = 1;
            }
            
            // Insert into database
            $insert_query = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)");
            $sort_order = $primary_count + $i + 1;
            $insert_query->bind_param("isii", $product_id, $upload_path, $is_primary, $sort_order);
            
            if (!$insert_query->execute()) {
                $errors[] = "Failed to save file '$file_name' to database";
                unlink($upload_path); // Delete uploaded file if database insert fails
            }
        } else {
            $errors[] = "Failed to upload file '$file_name'";
        }
    } else {
        $errors[] = "Error uploading file: " . $files['name'][$i];
    }
}

// Prepare response
if (empty($errors) && !empty($uploaded_files)) {
    echo json_encode([
        'success' => true,
        'message' => count($uploaded_files) . ' photo(s) uploaded successfully',
        'uploaded_files' => $uploaded_files
    ]);
} else {
    $response = [
        'success' => false,
        'message' => 'Upload completed with errors',
        'uploaded_files' => $uploaded_files,
        'errors' => $errors
    ];
    
    if (empty($uploaded_files)) {
        $response['success'] = false;
        $response['message'] = 'No files were uploaded successfully';
    }
    
    echo json_encode($response);
}

$conn->close();
?> 