<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once 'ChatbotServiceSimple.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['message']) || empty(trim($input['message']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit();
    }
    
    $message = trim($input['message']);
    $conversationId = $input['conversationId'] ?? null;
    $userId = null;
    
    // Check for JWT token (simplified)
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        // For now, just extract user ID from token if it exists
        // In a real implementation, you'd validate the JWT properly
    }
    
    // Generate conversation ID if not provided
    if (!$conversationId) {
        $conversationId = generateConversationId($userId ?: 'anonymous');
    }
    
    // Process message with simple chatbot service
    $chatbotService = new ChatbotServiceSimple();
    $botResponse = $chatbotService->processMessage($message, $userId, $conversationId);
    
    // Log the conversation to a file (simple storage)
    logConversation($conversationId, $message, $botResponse, $userId);
    
    // Return response
    echo json_encode([
        'conversationId' => $conversationId,
        'userMessage' => [
            'id' => time() . '_user',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'isFromBot' => false
        ],
        'botMessage' => [
            'id' => time() . '_bot',
            'message' => $botResponse['message'],
            'timestamp' => date('Y-m-d H:i:s'),
            'isFromBot' => true,
            'intent' => $botResponse['intent'],
            'confidence' => $botResponse['confidence'],
            'quickReplies' => $botResponse['quickReplies'] ?? null,
            'escalated' => $botResponse['escalated'] ?? false
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Chatbot error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

function generateConversationId($userId) {
    return 'conv_' . $userId . '_' . time() . '_' . rand(1000, 9999);
}

function logConversation($conversationId, $userMessage, $botResponse, $userId) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/conversations.log';
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'conversationId' => $conversationId,
        'userId' => $userId,
        'userMessage' => $userMessage,
        'botResponse' => $botResponse
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}
?>
