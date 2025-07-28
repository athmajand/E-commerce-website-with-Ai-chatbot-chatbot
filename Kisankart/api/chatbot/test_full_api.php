<?php
// Test the full database-backed chatbot API
echo "Testing Full Database-Backed Chatbot API...\n\n";

// Simulate a POST request to the API
$_SERVER['REQUEST_METHOD'] = 'POST';

// Test message
$testMessage = json_encode([
    'message' => 'Hello, I need help with my order',
    'conversationId' => null
]);

// Simulate the input
$_POST = [];
file_put_contents('php://input', $testMessage);

// Capture output
ob_start();

try {
    // Include the API file
    include 'message.php';
    
    $output = ob_get_clean();
    
    echo "✅ API Response:\n";
    echo $output . "\n\n";
    
    // Parse and display the response nicely
    $response = json_decode($output, true);
    if ($response) {
        echo "📝 Parsed Response:\n";
        echo "Conversation ID: " . $response['conversationId'] . "\n";
        echo "User Message: " . $response['userMessage']['message'] . "\n";
        echo "Bot Response: " . $response['botMessage']['message'] . "\n";
        echo "Intent: " . $response['botMessage']['intent'] . "\n";
        echo "Confidence: " . $response['botMessage']['confidence'] . "\n";
        
        if (!empty($response['botMessage']['quickReplies'])) {
            echo "Quick Replies: " . implode(', ', $response['botMessage']['quickReplies']) . "\n";
        }
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Test database connection directly
echo "Testing Database Connection...\n";

try {
    $db = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful\n";
    
    // Check if tables exist
    $stmt = $db->query("SHOW TABLES LIKE 'messages'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Messages table exists\n";
        
        // Count messages
        $stmt = $db->query("SELECT COUNT(*) as count FROM messages");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 Total messages in database: $count\n";
        
        // Show recent messages
        if ($count > 0) {
            echo "\n📋 Recent messages:\n";
            $stmt = $db->query("SELECT * FROM messages ORDER BY createdAt DESC LIMIT 5");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- " . ($row['isFromBot'] ? 'Bot' : 'User') . ": " . substr($row['message'], 0, 50) . "...\n";
            }
        }
    } else {
        echo "❌ Messages table does not exist\n";
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'customer_service_requests'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Customer service requests table exists\n";
    } else {
        echo "❌ Customer service requests table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Full API test completed!\n";
?>
