<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include file uploader utility
include_once __DIR__ . '/api/utils/FileUploader.php';

// Create upload directories if they don't exist
$upload_dirs = [
    'uploads',
    'uploads/seller',
    'uploads/seller/id_documents',
    'uploads/seller/tax_documents',
    'uploads/seller/bank_documents',
    'uploads/seller/store_logos',
    'uploads/seller/test_uploads'
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

// Create a test file uploader
$uploader = new FileUploader('uploads/seller', [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'application/pdf'
]);

// Process file upload
$upload_result = null;
$upload_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file']) && !empty($_FILES['test_file']['name'])) {
    try {
        $upload_result = $uploader->upload($_FILES['test_file'], 'test_uploads');
        
        if (!$upload_result) {
            $upload_error = $uploader->getError();
        }
    } catch (Exception $e) {
        $upload_error = $e->getMessage();
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test File Upload</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            display: block;
            margin-bottom: 10px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
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
        <h1>Test File Upload</h1>
        
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
            <h2>Test File Upload</h2>
            
            <?php if ($upload_result): ?>
                <div class="success">
                    <h3>File Upload Success!</h3>
                    <p>File uploaded successfully to: <?php echo htmlspecialchars($upload_result); ?></p>
                    
                    <?php if (file_exists(__DIR__ . $upload_result)): ?>
                        <p>File exists on disk at: <?php echo htmlspecialchars(__DIR__ . $upload_result); ?></p>
                    <?php else: ?>
                        <p class="error">File does not exist on disk at: <?php echo htmlspecialchars(__DIR__ . $upload_result); ?></p>
                    <?php endif; ?>
                </div>
            <?php elseif ($upload_error): ?>
                <div class="error">
                    <h3>File Upload Error!</h3>
                    <p>Error: <?php echo htmlspecialchars($upload_error); ?></p>
                </div>
            <?php endif; ?>
            
            <form action="test_file_upload.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="test_file">Select a file to upload:</label>
                    <input type="file" id="test_file" name="test_file" required>
                    <p>Supported formats: JPG, PNG, GIF, PDF (Max 5MB)</p>
                </div>
                
                <button type="submit">Upload File</button>
            </form>
        </div>
        
        <div class="section">
            <h2>FileUploader Class Information</h2>
            <pre><?php
                $reflection = new ReflectionClass('FileUploader');
                $constructor = $reflection->getConstructor();
                $upload_method = $reflection->getMethod('upload');
                
                echo "Constructor:\n";
                echo "  Parameters:\n";
                foreach ($constructor->getParameters() as $param) {
                    echo "    - " . $param->getName();
                    if ($param->isOptional()) {
                        echo " (optional)";
                        if ($param->isDefaultValueAvailable()) {
                            echo " = " . var_export($param->getDefaultValue(), true);
                        }
                    }
                    echo "\n";
                }
                
                echo "\nupload() Method:\n";
                echo "  Parameters:\n";
                foreach ($upload_method->getParameters() as $param) {
                    echo "    - " . $param->getName();
                    if ($param->isOptional()) {
                        echo " (optional)";
                        if ($param->isDefaultValueAvailable()) {
                            echo " = " . var_export($param->getDefaultValue(), true);
                        }
                    }
                    echo "\n";
                }
            ?></pre>
        </div>
        
        <a href="seller_registration.php" class="back-btn">Go to Seller Registration</a>
    </div>
</body>
</html>
