const chatbotService = require('../services/chatbot.service');

async function testChatbot() {
  console.log('🤖 Testing Kisan Kart Chatbot Service\n');

  const testQueries = [
    'Hello',
    'Hi there',
    'Track my order',
    'What are your delivery times?',
    'Payment methods',
    'Return policy',
    'Tell me about organic products',
    'How to contact farmer?',
    'Account help',
    'Technical issue',
    'Thank you',
    'Random query that should not match'
  ];

  for (const query of testQueries) {
    console.log(`\n📝 Query: "${query}"`);
    console.log('─'.repeat(50));
    
    try {
      const response = await chatbotService.processMessage(query, 1, 'test_conv_123');
      
      console.log(`🎯 Intent: ${response.intent}`);
      console.log(`📊 Confidence: ${(response.confidence * 100).toFixed(1)}%`);
      console.log(`🤖 Response: ${response.message}`);
      
      if (response.quickReplies) {
        console.log(`⚡ Quick Replies: ${response.quickReplies.map(r => r.text).join(', ')}`);
      }
      
      if (response.escalated) {
        console.log(`🚨 Escalated to human support`);
      }
      
    } catch (error) {
      console.error(`❌ Error processing query: ${error.message}`);
    }
  }

  console.log('\n✅ Chatbot testing completed!');
}

// Test intent detection specifically
function testIntentDetection() {
  console.log('\n🧠 Testing Intent Detection\n');

  const testCases = [
    { query: 'hello there', expectedIntent: 'greeting' },
    { query: 'track my order 123456', expectedIntent: 'order_status' },
    { query: 'how long does delivery take', expectedIntent: 'delivery_time' },
    { query: 'what payment options do you have', expectedIntent: 'payment_methods' },
    { query: 'can I return this product', expectedIntent: 'return_policy' },
    { query: 'tell me about your organic vegetables', expectedIntent: 'product_info' },
    { query: 'how can I contact the farmer', expectedIntent: 'contact_farmer' },
    { query: 'how much does this cost', expectedIntent: 'pricing' },
    { query: 'help with my account', expectedIntent: 'account_help' },
    { query: 'website is not working', expectedIntent: 'technical_support' },
    { query: 'goodbye', expectedIntent: 'goodbye' }
  ];

  let correctPredictions = 0;
  
  testCases.forEach(testCase => {
    const intent = chatbotService.detectIntent(testCase.query);
    const isCorrect = intent.name === testCase.expectedIntent;
    
    console.log(`Query: "${testCase.query}"`);
    console.log(`Expected: ${testCase.expectedIntent} | Detected: ${intent.name} | Confidence: ${(intent.confidence * 100).toFixed(1)}% | ${isCorrect ? '✅' : '❌'}`);
    console.log('');
    
    if (isCorrect) correctPredictions++;
  });

  const accuracy = (correctPredictions / testCases.length * 100).toFixed(1);
  console.log(`🎯 Intent Detection Accuracy: ${accuracy}% (${correctPredictions}/${testCases.length})`);
}

// Test conversation flow
async function testConversationFlow() {
  console.log('\n💬 Testing Conversation Flow\n');

  const conversationId = `test_conv_${Date.now()}`;
  const userId = 1;

  const conversation = [
    'Hello',
    'I want to track my order',
    'Order number 123456',
    'What are your delivery times?',
    'Thank you for your help'
  ];

  for (let i = 0; i < conversation.length; i++) {
    const message = conversation[i];
    console.log(`👤 User: ${message}`);
    
    try {
      const response = await chatbotService.processMessage(message, userId, conversationId);
      console.log(`🤖 Bot: ${response.message}`);
      
      if (response.quickReplies) {
        console.log(`⚡ Quick Replies: ${response.quickReplies.map(r => r.text).join(', ')}`);
      }
      
      console.log('');
    } catch (error) {
      console.error(`❌ Error: ${error.message}`);
    }
  }
}

// Performance test
async function performanceTest() {
  console.log('\n⚡ Performance Testing\n');

  const testQuery = 'Hello, I need help with my order';
  const iterations = 100;
  
  console.log(`Running ${iterations} iterations...`);
  
  const startTime = Date.now();
  
  for (let i = 0; i < iterations; i++) {
    await chatbotService.processMessage(testQuery, 1, `perf_test_${i}`);
  }
  
  const endTime = Date.now();
  const totalTime = endTime - startTime;
  const avgTime = totalTime / iterations;
  
  console.log(`📊 Performance Results:`);
  console.log(`   Total time: ${totalTime}ms`);
  console.log(`   Average time per query: ${avgTime.toFixed(2)}ms`);
  console.log(`   Queries per second: ${(1000 / avgTime).toFixed(2)}`);
}

// Main test runner
async function runAllTests() {
  try {
    await testChatbot();
    testIntentDetection();
    await testConversationFlow();
    await performanceTest();
    
    console.log('\n🎉 All tests completed successfully!');
  } catch (error) {
    console.error('❌ Test failed:', error);
  }
}

// Run tests if this file is executed directly
if (require.main === module) {
  runAllTests();
}

module.exports = {
  testChatbot,
  testIntentDetection,
  testConversationFlow,
  performanceTest,
  runAllTests
};
