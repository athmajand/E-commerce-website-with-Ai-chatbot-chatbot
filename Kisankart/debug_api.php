<?php
// Debug the API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>API Debug Test</h1>";

echo "<h2>1. Testing Database Connection</h2>";
try {
    $db = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>2. Testing ChatbotService</h2>";
try {
    require_once 'api/chatbot/ChatbotService.php';
    $chatbot = new ChatbotService($db);
    $response = $chatbot->processMessage("Hello", null, "debug_test");
    echo "✅ ChatbotService working<br>";
    echo "Response: " . htmlspecialchars($response['message']) . "<br>";
} catch (Exception $e) {
    echo "❌ ChatbotService error: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Testing API File Directly</h2>";
try {
    // Simulate POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [];
    
    // Capture any output
    ob_start();
    
    // Include the API file
    include 'api/chatbot/message.php';
    
    $output = ob_get_clean();
    echo "API Output: <pre>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ API file error: " . $e->getMessage() . "<br>";
}

echo "<h2>4. File Permissions Check</h2>";
$files = [
    'api/chatbot/message.php',
    'api/chatbot/ChatbotService.php',
    'api/chatbot/message_simple.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
        if (is_readable($file)) {
            echo "✅ $file is readable<br>";
        } else {
            echo "❌ $file is not readable<br>";
        }
    } else {
        echo "❌ $file does not exist<br>";
    }
}
?>
