<?php
// Define the path to the PHP error log file
// This is the default location for XAMPP on Windows
$log_file = 'C:/xampp/php/logs/php_error_log';

// Check if the file exists
if (!file_exists($log_file)) {
    $log_file = 'C:/xampp/apache/logs/error.log';
    
    if (!file_exists($log_file)) {
        echo "<p>Error log file not found. Please check your PHP configuration.</p>";
        exit;
    }
}

// Function to get the last N lines of a file
function tail($filename, $lines = 100) {
    $file = file($filename);
    $total_lines = count($file);
    
    if ($total_lines <= $lines) {
        return $file;
    }
    
    return array_slice($file, $total_lines - $lines);
}

// Get the last 100 lines of the error log
$log_lines = tail($log_file, 100);

// Filter lines related to seller registration
$seller_reg_lines = array_filter($log_lines, function($line) {
    return (
        strpos($line, 'SellerRegistration') !== false || 
        strpos($line, 'seller_registration') !== false ||
        strpos($line, 'Seller registration') !== false ||
        strpos($line, 'email exists') !== false ||
        strpos($line, 'phone exists') !== false
    );
});

// Get the most recent lines first
$seller_reg_lines = array_reverse($seller_reg_lines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Error Log Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #4CAF50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .log-container {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .log-line {
            font-family: monospace;
            margin: 5px 0;
            padding: 5px;
            border-bottom: 1px solid #ddd;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-line:hover {
            background-color: #e0e0e0;
        }
        .error {
            color: #d32f2f;
        }
        .warning {
            color: #f57c00;
        }
        .info {
            color: #0288d1;
        }
        .success {
            color: #388e3c;
        }
        .refresh-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .refresh-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Seller Registration Error Log Viewer</h1>
        <p>Displaying the most recent log entries related to seller registration.</p>
        
        <a href="check_error_logs.php" class="refresh-btn">Refresh Logs</a>
        
        <div class="log-container">
            <?php if (empty($seller_reg_lines)): ?>
                <p>No seller registration related log entries found.</p>
            <?php else: ?>
                <?php foreach ($seller_reg_lines as $line): ?>
                    <?php
                    $class = 'info';
                    if (strpos($line, 'error') !== false || strpos($line, 'Error') !== false || strpos($line, 'Exception') !== false) {
                        $class = 'error';
                    } elseif (strpos($line, 'warning') !== false || strpos($line, 'Warning') !== false) {
                        $class = 'warning';
                    } elseif (strpos($line, 'success') !== false || strpos($line, 'Success') !== false) {
                        $class = 'success';
                    }
                    ?>
                    <div class="log-line <?php echo $class; ?>">
                        <?php echo htmlspecialchars($line); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
