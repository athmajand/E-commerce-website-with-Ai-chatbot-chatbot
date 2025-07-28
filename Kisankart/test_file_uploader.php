<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include file uploader utility
include_once __DIR__ . '/api/utils/FileUploader.php';

// Check if upload directories exist and create them if they don't
$upload_dirs = [
    'uploads',
    'uploads/seller',
    'uploads/seller/id_documents',
    'uploads/seller/tax_documents',
    'uploads/seller/bank_documents',
    'uploads/seller/store_logos'
];

$upload_results = [];
foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (!file_exists($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            $upload_results[] = [
                'success' => true,
                'description' => "Created directory: $dir"
            ];
        } else {
            $upload_results[] = [
                'success' => false,
                'description' => "Failed to create directory: $dir",
                'error' => "Permission denied or other error"
            ];
        }
    } else {
        $upload_results[] = [
            'success' => true,
            'description' => "Directory already exists: $dir"
        ];
    }
}

// Check if the upload directories are writable
$writable_results = [];
foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (is_writable($full_path)) {
        $writable_results[] = [
            'success' => true,
            'description' => "Directory is writable: $dir"
        ];
    } else {
        $writable_results[] = [
            'success' => false,
            'description' => "Directory is not writable: $dir",
            'error' => "Permission denied or other error"
        ];
    }
}

// Create a test file uploader
$uploader = new FileUploader('uploads/seller', [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'application/pdf'
]);

// Get the document root
$document_root = $_SERVER['DOCUMENT_ROOT'];

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test File Uploader</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #4CAF50;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .back-btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test File Uploader</h1>
        
        <div class="section">
            <h2>Server Information</h2>
            <p><strong>Document Root:</strong> <?php echo $document_root; ?></p>
            <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
            <p><strong>Upload Directory:</strong> <?php echo __DIR__ . '/uploads/seller'; ?></p>
        </div>
        
        <div class="section">
            <h2>Upload Directories</h2>
            <?php foreach ($upload_results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <p><?php echo htmlspecialchars($result['description']); ?></p>
                    
                    <?php if (!$result['success']): ?>
                        <p>Error message: <?php echo htmlspecialchars($result['error']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h2>Directory Permissions</h2>
            <?php foreach ($writable_results as $result): ?>
                <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <p><?php echo htmlspecialchars($result['description']); ?></p>
                    
                    <?php if (!$result['success']): ?>
                        <p>Error message: <?php echo htmlspecialchars($result['error']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h2>Test File Upload</h2>
            <form action="test_file_uploader.php" method="POST" enctype="multipart/form-data">
                <div>
                    <label for="test_file">Select a file to upload:</label>
                    <input type="file" id="test_file" name="test_file">
                </div>
                <div>
                    <button type="submit" name="submit_upload">Upload</button>
                </div>
            </form>
            
            <?php
            // Process file upload
            if (isset($_POST['submit_upload']) && isset($_FILES['test_file'])) {
                $file = $_FILES['test_file'];
                
                if (!empty($file['name'])) {
                    $upload_result = $uploader->upload($file, 'test_uploads');
                    
                    if ($upload_result) {
                        echo '<div class="success">';
                        echo '<h3>File Upload Success!</h3>';
                        echo '<p>File uploaded successfully to: ' . $upload_result . '</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="error">';
                        echo '<h3>File Upload Error!</h3>';
                        echo '<p>Error: ' . $uploader->getError() . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="error">';
                    echo '<h3>File Upload Error!</h3>';
                    echo '<p>No file was selected.</p>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
