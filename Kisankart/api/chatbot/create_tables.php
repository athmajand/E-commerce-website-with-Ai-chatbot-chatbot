<?php
// Simple database connection for chatbot tables using mysqli
$host = "localhost";
$db_name = "kisan_kart";
$username = "root";
$password = "";

try {
    // Try to connect to MySQL using mysqli
    $db = new mysqli($host, $username, $password, $db_name);

    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    echo "✅ Connected to database successfully\n";
    echo "Creating chatbot tables...\n";

    // Create messages table
    $messagesTable = "CREATE TABLE IF NOT EXISTS messages (
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

    if ($db->query($messagesTable)) {
        echo "✅ Messages table created successfully\n";
    } else {
        echo "❌ Error creating messages table: " . $db->error . "\n";
    }

    // Create customer service requests table
    $customerServiceTable = "CREATE TABLE IF NOT EXISTS customer_service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        type ENUM('inquiry', 'complaint', 'suggestion', 'technical', 'other') DEFAULT 'inquiry',
        status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        assignedTo INT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (userId),
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_created (createdAt)
    )";

    if ($db->query($customerServiceTable)) {
        echo "✅ Customer service requests table created successfully\n";
    } else {
        echo "❌ Error creating customer service requests table: " . $db->error . "\n";
    }

    echo "\n🎉 All chatbot tables created successfully!\n";
    echo "You can now test the chatbot at: test_chatbot.php\n";

    $db->close();

} catch (Exception $e) {
    echo "❌ Error creating tables: " . $e->getMessage() . "\n";
}
?>
