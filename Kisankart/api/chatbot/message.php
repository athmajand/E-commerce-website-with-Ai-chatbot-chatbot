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

require_once 'ChatbotService.php';

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

    // Check for JWT token
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $userId = validateJWT($token);
    }

    // Generate conversation ID if not provided
    if (!$conversationId) {
        $conversationId = generateConversationId($userId ?: 'anonymous');
    }

    // Initialize database connection using PDO (now working!)
    $db = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create messages table if it doesn't exist
    createMessagesTable($db);

    // Save user message
    $userMessageId = saveMessage($db, [
        'senderId' => $userId,
        'senderRole' => $userId ? 'customer' : 'anonymous',
        'receiverId' => null,
        'receiverRole' => 'bot',
        'conversationId' => $conversationId,
        'message' => $message,
        'messageType' => 'text',
        'isFromBot' => 0,
        'isRead' => 1
    ]);

    // Process message with chatbot service
    $chatbotService = new ChatbotService($db);
    $botResponse = $chatbotService->processMessage($message, $userId, $conversationId);

    // Save bot response
    $botMessageId = saveMessage($db, [
        'senderId' => null,
        'senderRole' => 'bot',
        'receiverId' => $userId,
        'receiverRole' => $userId ? 'customer' : 'anonymous',
        'conversationId' => $conversationId,
        'message' => $botResponse['message'],
        'messageType' => isset($botResponse['quickReplies']) ? 'quick_reply' : 'text',
        'metadata' => json_encode([
            'quickReplies' => $botResponse['quickReplies'] ?? null,
            'intent' => $botResponse['intent'],
            'confidence' => $botResponse['confidence'],
            'escalated' => $botResponse['escalated'] ?? false
        ]),
        'isFromBot' => 1,
        'intent' => $botResponse['intent'],
        'confidence' => $botResponse['confidence'],
        'isRead' => 0
    ]);

    // Return response
    echo json_encode([
        'conversationId' => $conversationId,
        'userMessage' => [
            'id' => $userMessageId,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'isFromBot' => false
        ],
        'botMessage' => [
            'id' => $botMessageId,
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

function validateJWT($token) {
    // Simple JWT validation - you can enhance this
    // For now, just return null if no valid token
    return null;
}

function generateConversationId($userId) {
    return 'conv_' . $userId . '_' . time() . '_' . rand(1000, 9999);
}

function createMessagesTable($db) {
    $sql = "CREATE TABLE IF NOT EXISTS chatbot_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        senderId INT NULL,
        senderRole ENUM('customer', 'seller', 'admin', 'bot', 'anonymous') NOT NULL,
        receiverId INT NULL,
        receiverRole ENUM('customer', 'seller', 'admin', 'bot', 'anonymous') NOT NULL,
        conversationId VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        messageType ENUM('text', 'image', 'file', 'quick_reply') DEFAULT 'text',
        metadata JSON NULL,
        isFromBot BOOLEAN DEFAULT FALSE,
        intent VARCHAR(100) NULL,
        confidence DECIMAL(3,2) NULL,
        isRead BOOLEAN DEFAULT FALSE,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_conversation (conversationId),
        INDEX idx_sender (senderId),
        INDEX idx_receiver (receiverId),
        INDEX idx_created (createdAt)
    )";

    $db->exec($sql);
}

function saveMessage($db, $data) {
    $sql = "INSERT INTO chatbot_messages (senderId, senderRole, receiverId, receiverRole, conversationId, message, messageType, metadata, isFromBot, intent, confidence, isRead)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $data['senderId'],
        $data['senderRole'],
        $data['receiverId'],
        $data['receiverRole'],
        $data['conversationId'],
        $data['message'],
        $data['messageType'],
        $data['metadata'] ?? null,
        $data['isFromBot'],
        $data['intent'] ?? null,
        $data['confidence'] ?? null,
        $data['isRead']
    ]);

    return $db->lastInsertId();
}
?>
