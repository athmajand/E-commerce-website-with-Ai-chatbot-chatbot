<?php
// Test the chatbot API directly
require_once 'ChatbotServiceSimple.php';

echo "Testing Kisankart Chatbot API...\n\n";

$chatbot = new ChatbotServiceSimple();

$testMessages = [
    "Hello",
    "What products do you have?",
    "Track my order ORD123456",
    "Delivery information",
    "Payment methods",
    "Thank you"
];

foreach ($testMessages as $message) {
    echo "User: $message\n";

    $response = $chatbot->processMessage($message, null, 'test_conv_123');

    echo "Bot: " . $response['message'] . "\n";
    echo "Intent: " . $response['intent'] . " (Confidence: " . $response['confidence'] . ")\n";

    if (!empty($response['quickReplies'])) {
        echo "Quick Replies: " . implode(', ', $response['quickReplies']) . "\n";
    }

    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "✅ Chatbot API test completed successfully!\n";
?>
