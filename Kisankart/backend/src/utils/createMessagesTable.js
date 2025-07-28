const sequelize = require('../config/database');

async function createMessagesTable() {
  try {
    // Create Messages table
    await sequelize.query(`
      CREATE TABLE IF NOT EXISTS Messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        senderId INT NULL,
        senderRole ENUM('customer', 'seller', 'admin', 'bot') NOT NULL,
        receiverId INT NULL,
        receiverRole ENUM('customer', 'seller', 'admin', 'bot') NULL,
        conversationId VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        messageType ENUM('text', 'image', 'file', 'quick_reply', 'card') DEFAULT 'text',
        metadata TEXT NULL,
        isRead BOOLEAN DEFAULT FALSE,
        isFromBot BOOLEAN DEFAULT FALSE,
        intent VARCHAR(255) NULL,
        confidence FLOAT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_conversation_time (conversationId, createdAt),
        INDEX idx_sender_receiver (senderId, receiverId),
        INDEX idx_conversation_id (conversationId),
        INDEX idx_is_from_bot (isFromBot),
        INDEX idx_created_at (createdAt)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    console.log('Messages table created successfully');
    
    // Test the table by inserting a sample message
    await sequelize.query(`
      INSERT INTO Messages (
        senderId, senderRole, receiverId, receiverRole, 
        conversationId, message, messageType, isFromBot, 
        intent, confidence
      ) VALUES (
        NULL, 'bot', NULL, 'customer',
        'test_conversation_${Date.now()}', 
        'Hello! Welcome to Kisan Kart! 🌱 How can I help you today?',
        'text', TRUE, 'greeting', 0.95
      )
    `);

    console.log('Sample message inserted successfully');
    
    // Verify the table structure
    const [results] = await sequelize.query('DESCRIBE Messages');
    console.log('Messages table structure:');
    console.table(results);

  } catch (error) {
    console.error('Error creating Messages table:', error);
  } finally {
    await sequelize.close();
  }
}

// Run the migration
if (require.main === module) {
  createMessagesTable();
}

module.exports = createMessagesTable;
