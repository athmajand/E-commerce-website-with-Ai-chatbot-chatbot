<?php
// Test the database-backed chatbot service directly
require_once 'ChatbotService.php';

echo "Testing Database-Backed Chatbot Service...\n\n";

try {
    // Test database connection
    $db = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";

    // Initialize chatbot service
    $chatbot = new ChatbotService($db);
    echo "✅ Chatbot service initialized\n\n";

    // Test messages
    $testMessages = [
        "Hello there!",
        "What products do you sell?",
        "I want to track my order ORD123456",
        "What are your delivery options?",
        "I need to speak to a human agent",
        "Thank you for your help"
    ];

    $conversationId = 'test_conv_' . time();

    foreach ($testMessages as $index => $message) {
        echo "Test " . ($index + 1) . ":\n";
        echo "User: $message\n";

        $response = $chatbot->processMessage($message, null, $conversationId);

        echo "Bot: " . $response['message'] . "\n";
        echo "Intent: " . $response['intent'] . " (Confidence: " . $response['confidence'] . ")\n";

        if (!empty($response['quickReplies'])) {
            echo "Quick Replies: " . implode(', ', $response['quickReplies']) . "\n";
        }

        if ($response['escalated']) {
            echo "🚨 Escalated to human agent\n";
        }

        echo "\n" . str_repeat("-", 40) . "\n\n";
    }

    // Check database for stored messages
    echo "Checking database for messages...\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM chatbot_messages WHERE conversationId = ?");
    $stmt->execute([$conversationId]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Messages stored in database: $count\n";

    if ($count > 0) {
        echo "\n📋 Stored messages:\n";
        $stmt = $db->prepare("SELECT * FROM chatbot_messages WHERE conversationId = ? ORDER BY createdAt");
        $stmt->execute([$conversationId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sender = $row['isFromBot'] ? 'Bot' : 'User';
            echo "- $sender: " . substr($row['message'], 0, 60) . "...\n";
        }
    }

    // Check customer service requests
    $stmt = $db->query("SELECT COUNT(*) as count FROM customer_service_requests");
    $serviceCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\n📞 Customer service requests: $serviceCount\n";

    echo "\n🎉 Database-backed chatbot test completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
