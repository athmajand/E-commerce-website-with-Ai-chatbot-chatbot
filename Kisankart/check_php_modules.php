<?php
echo "PHP Version: " . phpversion() . "\n";
echo "PHP Configuration File: " . php_ini_loaded_file() . "\n";
echo "PHP Extension Directory: " . ini_get('extension_dir') . "\n\n";

echo "All loaded extensions:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "- $ext\n";
}

echo "\nMySQL-related extensions:\n";
foreach ($extensions as $ext) {
    if (stripos($ext, 'mysql') !== false || stripos($ext, 'pdo') !== false) {
        echo "✅ $ext\n";
    }
}

echo "\nPDO drivers:\n";
if (class_exists('PDO')) {
    $drivers = PDO::getAvailableDrivers();
    foreach ($drivers as $driver) {
        echo "✅ PDO $driver\n";
    }
    if (empty($drivers)) {
        echo "❌ No PDO drivers available\n";
    }
} else {
    echo "❌ PDO class not available\n";
}

echo "\nTesting database connection:\n";
try {
    if (in_array('mysql', PDO::getAvailableDrivers())) {
        $pdo = new PDO("mysql:host=localhost", "root", "");
        echo "✅ PDO MySQL connection successful\n";
        
        // Try to create/use database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS kisan_kart");
        $pdo->exec("USE kisan_kart");
        echo "✅ Database kisan_kart ready\n";
        
    } else {
        echo "❌ MySQL PDO driver not available\n";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?>
