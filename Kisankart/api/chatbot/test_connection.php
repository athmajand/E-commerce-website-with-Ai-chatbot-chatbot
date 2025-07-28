<?php
echo "PHP Version: " . phpversion() . "\n";
echo "Available extensions:\n";

$extensions = get_loaded_extensions();
foreach ($extensions as $ext) {
    if (strpos(strtolower($ext), 'mysql') !== false || 
        strpos(strtolower($ext), 'pdo') !== false ||
        strpos(strtolower($ext), 'sql') !== false) {
        echo "- $ext\n";
    }
}

echo "\nTesting database connections...\n";

// Test if PDO is available
if (class_exists('PDO')) {
    echo "✅ PDO class is available\n";
    
    // Check available PDO drivers
    $drivers = PDO::getAvailableDrivers();
    echo "Available PDO drivers: " . implode(', ', $drivers) . "\n";
    
    if (in_array('mysql', $drivers)) {
        echo "✅ MySQL PDO driver is available\n";
        
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
            echo "✅ PDO MySQL connection successful\n";
            $pdo = null;
        } catch (PDOException $e) {
            echo "❌ PDO MySQL connection failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ MySQL PDO driver not available\n";
    }
} else {
    echo "❌ PDO class not available\n";
}

// Test if mysqli is available
if (class_exists('mysqli')) {
    echo "✅ mysqli class is available\n";
    
    try {
        $mysqli = new mysqli("localhost", "root", "", "kisan_kart");
        if ($mysqli->connect_error) {
            echo "❌ mysqli connection failed: " . $mysqli->connect_error . "\n";
        } else {
            echo "✅ mysqli connection successful\n";
            $mysqli->close();
        }
    } catch (Exception $e) {
        echo "❌ mysqli connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ mysqli class not available\n";
}

// Test if mysql functions are available
if (function_exists('mysql_connect')) {
    echo "✅ Old mysql functions are available (deprecated)\n";
} else {
    echo "❌ Old mysql functions not available\n";
}

echo "\nPHP Configuration:\n";
echo "extension_dir: " . ini_get('extension_dir') . "\n";
echo "include_path: " . ini_get('include_path') . "\n";
?>
