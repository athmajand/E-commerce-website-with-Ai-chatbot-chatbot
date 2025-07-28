<?php
// Headers
header("Content-Type: text/html; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Directory to check
$dir = __DIR__ . '/uploads/products';

echo "<h1>Testing Image Access</h1>";

// Check if directory exists
if (!is_dir($dir)) {
    echo "<p>Error: Directory '$dir' does not exist!</p>";
    exit;
}

// Get all files in the directory
$files = scandir($dir);

echo "<h2>Images in uploads/products directory:</h2>";
echo "<ul>";

foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        $file_path = "uploads/products/" . $file;
        $full_path = __DIR__ . '/' . $file_path;
        
        echo "<li>";
        echo "<strong>Filename:</strong> " . htmlspecialchars($file) . "<br>";
        echo "<strong>Full path:</strong> " . htmlspecialchars($full_path) . "<br>";
        echo "<strong>File exists:</strong> " . (file_exists($full_path) ? "Yes" : "No") . "<br>";
        echo "<strong>File size:</strong> " . (file_exists($full_path) ? filesize($full_path) . " bytes" : "N/A") . "<br>";
        echo "<strong>Image preview:</strong><br>";
        echo "<img src='" . htmlspecialchars($file_path) . "' style='max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px;' alt='Image preview'>";
        echo "</li>";
        echo "<hr>";
    }
}

echo "</ul>";

// Test direct access to a sample image
$sample_image = "uploads/products/sample_carrot.jpg";
$sample_path = __DIR__ . '/' . $sample_image;

echo "<h2>Testing direct access to a sample image:</h2>";
echo "<p><strong>Image path:</strong> " . htmlspecialchars($sample_path) . "</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($sample_path) ? "Yes" : "No") . "</p>";
echo "<p><strong>Image URL:</strong> <a href='" . htmlspecialchars($sample_image) . "' target='_blank'>" . htmlspecialchars($sample_image) . "</a></p>";
echo "<p><strong>Image preview:</strong></p>";
echo "<img src='" . htmlspecialchars($sample_image) . "' style='max-width: 300px; border: 1px solid #ddd; padding: 5px;' alt='Sample image'>";

// Test browser cache
echo "<h2>Testing browser cache:</h2>";
echo "<p>The same image with a random query parameter to bypass cache:</p>";
echo "<img src='" . htmlspecialchars($sample_image) . "?nocache=" . time() . "' style='max-width: 300px; border: 1px solid #ddd; padding: 5px;' alt='Sample image (no cache)'>";

?>
