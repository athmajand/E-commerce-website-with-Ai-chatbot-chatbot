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

// Process file upload
$upload_result = null;
$upload_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file']) && !empty($_FILES['test_file']['name'])) {
    try {
        $upload_type = $_POST['upload_type'] ?? 'test_uploads';
        $uploader = new FileUploader('uploads/seller/' . $upload_type, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf'
        ]);
        
        $upload_result = $uploader->upload($_FILES['test_file'], $upload_type);
        
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
    <title>Test Seller File Uploads</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --accent-color: #FF9800;
            --text-color: #333333;
            --text-light: #757575;
            --background-color: #f5fff5;
            --white: #ffffff;
            --error-color: #D32F2F;
            --success-color: #388E3C;
            --border-color: #E0E0E0;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            box-sizing: border-box;
            background-color: var(--background-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }

        .card-body {
            padding: 20px;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .success-text {
            color: var(--success-color);
        }

        .error-text {
            color: var(--error-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Test Seller File Uploads</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upload Test</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="upload_type" class="form-label">Upload Type</label>
                                <select class="form-select" id="upload_type" name="upload_type">
                                    <option value="id_documents">ID Document</option>
                                    <option value="tax_documents">Tax Document</option>
                                    <option value="bank_documents">Bank Document</option>
                                    <option value="store_logos">Store Logo</option>
                                    <option value="test_uploads">Test Upload</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="test_file" class="form-label">Select File</label>
                                <input type="file" class="form-control" id="test_file" name="test_file" required>
                                <div class="form-text">Supported formats: JPG, PNG, GIF, PDF (Max 5MB)</div>
                            </div>
                            <button type="submit" class="btn btn-success">Upload File</button>
                        </form>
                        
                        <?php if ($upload_result): ?>
                            <div class="mt-4 alert alert-success">
                                <h5>Upload Successful!</h5>
                                <p>File uploaded to: <?php echo htmlspecialchars($upload_result); ?></p>
                                
                                <?php if (file_exists(__DIR__ . $upload_result)): ?>
                                    <p>File exists on disk at: <?php echo htmlspecialchars(__DIR__ . $upload_result); ?></p>
                                    
                                    <?php if (in_array(pathinfo($upload_result, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <div class="mt-3">
                                            <h6>Preview:</h6>
                                            <img src="<?php echo htmlspecialchars($upload_result); ?>" alt="Uploaded Image" class="img-fluid" style="max-height: 200px;">
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="error-text">File does not exist on disk at: <?php echo htmlspecialchars(__DIR__ . $upload_result); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($upload_error): ?>
                            <div class="mt-4 alert alert-danger">
                                <h5>Upload Failed!</h5>
                                <p><?php echo htmlspecialchars($upload_error); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upload Directories</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($upload_results as $result): ?>
                                <div class="list-group-item <?php echo $result['success'] ? 'list-group-item-success' : 'list-group-item-danger'; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($result['description']); ?></h6>
                                        <small><?php echo $result['success'] ? 'Success' : 'Error'; ?></small>
                                    </div>
                                    <?php if (!$result['success']): ?>
                                        <p class="mb-1 error-text"><?php echo htmlspecialchars($result['error']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Server Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
                        <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
                        <p><strong>Upload Max Filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                        <p><strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?></p>
                        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="seller_registration.php" class="btn btn-primary">Go to Seller Registration Page</a>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
