<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating chatbot-specific tables...\n";
    
    // Create chatbot_messages table (separate from existing messages table)
    $chatbotMessagesTable = "CREATE TABLE IF NOT EXISTS chatbot_messages (
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
    
    $db->exec($chatbotMessagesTable);
    echo "✅ Chatbot messages table created successfully\n";
    
    // Update customer_service_requests table if needed
    $stmt = $db->query("SHOW COLUMNS FROM customer_service_requests LIKE 'createdAt'");
    if ($stmt->rowCount() == 0) {
        echo "Customer service requests table already exists with correct structure\n";
    } else {
        echo "✅ Customer service requests table already exists\n";
    }
    
    echo "\n🎉 Chatbot tables setup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
