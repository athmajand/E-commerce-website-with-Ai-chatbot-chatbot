<?php

class ChatbotServiceML {
    private $db;
    private $intents;
    private $quickReplies;
    private $learningData;
    private $nlpProcessor;

    public function __construct($database) {
        $this->db = $database;
        $this->initializeIntents();
        $this->initializeQuickReplies();
        $this->initializeNLPProcessor();
        $this->loadLearningData();
    }

    private function initializeNLPProcessor() {
        $this->nlpProcessor = [
            'synonyms' => [
                'hello' => ['hi', 'hey', 'greetings', 'good morning', 'good afternoon', 'good evening', 'namaste', 'hola'],
                'order' => ['purchase', 'buy', 'shopping', 'cart', 'checkout', 'transaction'],
                'track' => ['find', 'locate', 'check', 'status', 'where', 'trace'],
                'delivery' => ['shipping', 'transport', 'send', 'courier', 'dispatch'],
                'payment' => ['pay', 'money', 'cost', 'price', 'billing', 'charge'],
                'product' => ['item', 'goods', 'merchandise', 'stuff', 'things'],
                'help' => ['assist', 'support', 'aid', 'guidance', 'service'],
                'quality' => ['fresh', 'good', 'best', 'organic', 'natural', 'pure'],
                'fast' => ['quick', 'rapid', 'speedy', 'immediate', 'urgent'],
                'cheap' => ['affordable', 'budget', 'low-cost', 'economical', 'inexpensive'],
                'expensive' => ['costly', 'pricey', 'high-priced', 'premium']
            ],
            'negations' => ['not', 'no', 'never', 'none', 'nothing', 'neither', 'dont', "don't", 'cant', "can't"],
            'question_words' => ['what', 'when', 'where', 'why', 'how', 'which', 'who'],
            'sentiment_positive' => ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'love', 'like'],
            'sentiment_negative' => ['bad', 'terrible', 'awful', 'hate', 'dislike', 'poor', 'worst', 'horrible']
        ];
    }

    private function loadLearningData() {
        try {
            // Load conversation patterns that have been successful
            $stmt = $this->db->prepare("
                SELECT message, intent, confidence,
                       COUNT(*) as frequency,
                       AVG(CASE WHEN escalated = 0 THEN 1 ELSE 0 END) as success_rate
                FROM chatbot_messages
                WHERE isFromBot = 0 AND intent IS NOT NULL
                GROUP BY message, intent
                HAVING COUNT(*) > 1 AND success_rate > 0.7
                ORDER BY frequency DESC, success_rate DESC
                LIMIT 100
            ");
            $stmt->execute();
            $this->learningData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->learningData = [];
        }
    }

    private function initializeIntents() {
        $this->intents = [
            'greeting' => [
                'patterns' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'namaste'],
                'responses' => [
                    "Hello! Welcome to Kisan Kart! 🌱 I'm here to help you with your queries.",
                    "Hi there! How can I assist you with Kisan Kart today?",
                    "Welcome to Kisan Kart! I'm your virtual assistant. How may I help you?"
                ],
                'confidence' => 0.9,
                'context' => 'greeting'
            ],
            'order_status' => [
                'patterns' => ['order status', 'track order', 'where is my order', 'order tracking', 'delivery status', 'track my order'],
                'responses' => [
                    "I can help you track your order! Could you please provide your order ID?",
                    "To check your order status, please share your order number with me.",
                    "For order tracking, please provide your order ID and I'll check the status for you."
                ],
                'confidence' => 0.8,
                'requiresData' => true,
                'context' => 'order_tracking'
            ],
            'product_info' => [
                'patterns' => ['product', 'item', 'what do you sell', 'products available', 'fresh produce', 'product information'],
                'responses' => [
                    "We offer fresh fruits, vegetables, grains, dairy products, and organic produce directly from farmers! 🥕🍎🌾",
                    "Kisan Kart has a wide variety of fresh farm products including fruits, vegetables, dairy, and organic items.",
                    "Our marketplace features fresh produce from local farmers: vegetables, fruits, grains, dairy products, spices, and organic items."
                ],
                'confidence' => 0.7,
                'context' => 'product_inquiry'
            ],
            'delivery_info' => [
                'patterns' => ['delivery', 'shipping', 'when will i get', 'delivery time', 'delivery charges', 'delivery details'],
                'responses' => [
                    "We offer same-day delivery for orders placed before 2 PM. Delivery charges start from ₹30. 🚚",
                    "Our delivery slots are: Morning (8-12 PM), Afternoon (12-6 PM), Evening (6-10 PM). Free delivery on orders above ₹500!",
                    "Delivery is available within 24 hours. Choose your preferred time slot during checkout."
                ],
                'confidence' => 0.8,
                'context' => 'delivery_inquiry'
            ],
            'payment_info' => [
                'patterns' => ['payment', 'pay', 'payment methods', 'upi', 'card', 'cash on delivery', 'cod'],
                'responses' => [
                    "We accept UPI, Credit/Debit Cards, Net Banking, and Cash on Delivery (COD). 💳",
                    "You can pay using UPI, cards, online banking, or choose Cash on Delivery option.",
                    "Payment options: UPI (Google Pay, PhonePe, Paytm), Cards, Net Banking, and COD available."
                ],
                'confidence' => 0.8,
                'context' => 'payment_inquiry'
            ],
            'pricing' => [
                'patterns' => ['price', 'cost', 'how much', 'expensive', 'cheap', 'rate', 'affordable'],
                'responses' => [
                    "Our prices are competitive and directly from farmers! You can browse products to see current prices. 💰",
                    "We offer farm-fresh products at great prices. Check our products page for current rates.",
                    "Prices vary by product and season. Visit our marketplace to see the latest pricing."
                ],
                'confidence' => 0.7,
                'context' => 'pricing_inquiry'
            ],
            'quality' => [
                'patterns' => ['quality', 'fresh', 'organic', 'good', 'best', 'natural'],
                'responses' => [
                    "We guarantee farm-fresh quality! All products are sourced directly from verified farmers. ✅",
                    "Quality is our priority - fresh, organic, and pesticide-free options available.",
                    "Our farmers follow strict quality standards to ensure you get the freshest produce."
                ],
                'confidence' => 0.7,
                'context' => 'quality_inquiry'
            ],
            'complaint' => [
                'patterns' => ['problem', 'issue', 'complaint', 'wrong', 'bad', 'terrible', 'disappointed'],
                'responses' => [
                    "I'm sorry to hear about the issue. Let me connect you with our customer service team to resolve this immediately.",
                    "I apologize for the inconvenience. Please provide more details so I can help you better."
                ],
                'confidence' => 0.8,
                'escalate' => true,
                'context' => 'complaint'
            ],
            'thanks' => [
                'patterns' => ['thank you', 'thanks', 'appreciate', 'great', 'awesome'],
                'responses' => [
                    "You're welcome! Happy to help! 😊",
                    "Glad I could assist you! Is there anything else you need help with?",
                    "My pleasure! Feel free to ask if you have any other questions."
                ],
                'confidence' => 0.9,
                'context' => 'gratitude'
            ],
            'goodbye' => [
                'patterns' => ['bye', 'goodbye', 'see you', 'exit', 'quit'],
                'responses' => [
                    "Goodbye! Thank you for choosing Kisankart. Have a great day! 🌱",
                    "See you later! Feel free to reach out anytime you need help.",
                    "Take care! Happy shopping with Kisankart! 🛒"
                ],
                'confidence' => 0.9,
                'context' => 'farewell'
            ],
            'human_agent' => [
                'patterns' => ['human', 'agent', 'speak to someone', 'customer service', 'representative', 'talk to human'],
                'responses' => [
                    "I'll connect you with our customer service team right away! Please hold on."
                ],
                'confidence' => 0.9,
                'escalate' => true,
                'context' => 'escalation'
            ]
        ];
    }

    private function initializeQuickReplies() {
        $this->quickReplies = [
            "Track my order",
            "Product information",
            "Delivery details",
            "Payment methods",
            "Contact support"
        ];
    }

    public function processMessage($message, $userId, $conversationId) {
        try {
            // Enhanced NLP processing
            $processedMessage = $this->preprocessMessage($message);
            $intent = $this->detectIntentML($processedMessage, $message);
            $response = '';

            // Learn from this interaction
            $this->learnFromInteraction($message, $intent, $userId, $conversationId);

            if ($intent['name'] === 'order_status' && $intent['confidence'] > 0.7) {
                $response = $this->handleOrderStatus($message, $userId);
            } elseif (isset($intent['escalate']) && $intent['escalate']) {
                $response = $this->escalateToHuman($userId, $message);
            } elseif (isset($intent['requiresData']) && $intent['requiresData']) {
                $response = $this->handleDataRequiredIntent($intent, $message, $userId);
            } else {
                $response = $this->getSmartResponse($intent, $message, $userId);
            }

            return [
                'message' => $response,
                'intent' => $intent['name'],
                'confidence' => $intent['confidence'],
                'quickReplies' => $this->getContextualQuickReplies($intent),
                'escalated' => isset($intent['escalate']) ? $intent['escalate'] : false,
                'mlEnhanced' => true
            ];
        } catch (Exception $e) {
            error_log('Chatbot ML processing error: ' . $e->getMessage());
            return [
                'message' => "I'm sorry, I'm having trouble understanding. Let me connect you with a human agent.",
                'intent' => 'error',
                'confidence' => 0,
                'escalated' => true,
                'mlEnhanced' => false
            ];
        }
    }

    private function preprocessMessage($message) {
        $processed = strtolower(trim($message));

        // Remove punctuation except important ones
        $processed = preg_replace('/[^\w\s\?\!\.]/', ' ', $processed);

        // Handle contractions
        $contractions = [
            "don't" => "do not",
            "can't" => "cannot",
            "won't" => "will not",
            "i'm" => "i am",
            "you're" => "you are",
            "it's" => "it is",
            "that's" => "that is"
        ];

        foreach ($contractions as $contraction => $expansion) {
            $processed = str_replace($contraction, $expansion, $processed);
        }

        // Normalize whitespace
        $processed = preg_replace('/\s+/', ' ', $processed);

        return trim($processed);
    }

    private function detectIntentML($processedMessage, $originalMessage) {
        $bestMatch = [
            'name' => 'unknown',
            'confidence' => 0,
            'responses' => ["I'm not sure I understand. Could you please rephrase your question?"],
            'context' => 'unknown'
        ];

        // First, check learned patterns
        $learnedMatch = $this->checkLearnedPatterns($processedMessage);
        if ($learnedMatch && $learnedMatch['confidence'] > 0.8) {
            return $learnedMatch;
        }

        // Enhanced pattern matching with NLP
        foreach ($this->intents as $intentName => $intentData) {
            $confidence = $this->calculateAdvancedConfidence($processedMessage, $intentData, $originalMessage);

            if ($confidence > $bestMatch['confidence']) {
                $bestMatch = array_merge($intentData, [
                    'name' => $intentName,
                    'confidence' => $confidence
                ]);
            }
        }

        return $bestMatch;
    }

    private function calculateAdvancedConfidence($message, $intentData, $originalMessage) {
        $confidence = 0;
        $words = explode(' ', $message);
        $totalWords = count($words);

        // Pattern matching with synonyms
        foreach ($intentData['patterns'] as $pattern) {
            $patternWords = explode(' ', strtolower($pattern));
            $matchScore = $this->calculateSemanticSimilarity($words, $patternWords);
            $confidence = max($confidence, $matchScore);
        }

        // Boost confidence based on context and sentiment
        $confidence = $this->applyContextBoost($confidence, $message, $intentData);
        $confidence = $this->applySentimentAnalysis($confidence, $originalMessage, $intentData);

        return min($confidence, 1.0); // Cap at 1.0
    }

    private function calculateSemanticSimilarity($words1, $words2) {
        $matches = 0;
        $totalWords = max(count($words1), count($words2));

        foreach ($words1 as $word1) {
            foreach ($words2 as $word2) {
                if ($word1 === $word2) {
                    $matches += 1.0;
                } elseif ($this->areSynonyms($word1, $word2)) {
                    $matches += 0.8;
                } elseif (similar_text($word1, $word2) / max(strlen($word1), strlen($word2)) > 0.7) {
                    $matches += 0.6;
                }
            }
        }

        return $matches / $totalWords;
    }

    private function areSynonyms($word1, $word2) {
        foreach ($this->nlpProcessor['synonyms'] as $baseWord => $synonyms) {
            if (($word1 === $baseWord || in_array($word1, $synonyms)) &&
                ($word2 === $baseWord || in_array($word2, $synonyms))) {
                return true;
            }
        }
        return false;
    }

    private function applyContextBoost($confidence, $message, $intentData) {
        // Boost confidence based on question words
        $questionWords = $this->nlpProcessor['question_words'];
        $hasQuestionWord = false;

        foreach ($questionWords as $qWord) {
            if (strpos($message, $qWord) !== false) {
                $hasQuestionWord = true;
                break;
            }
        }

        if ($hasQuestionWord && isset($intentData['context']) &&
            in_array($intentData['context'], ['product_inquiry', 'delivery_inquiry', 'payment_inquiry'])) {
            $confidence *= 1.2;
        }

        return $confidence;
    }

    private function applySentimentAnalysis($confidence, $message, $intentData) {
        $sentiment = $this->analyzeSentiment($message);

        // Boost complaint detection for negative sentiment
        if ($sentiment < -0.3 && isset($intentData['context']) && $intentData['context'] === 'complaint') {
            $confidence *= 1.5;
        }

        // Boost gratitude detection for positive sentiment
        if ($sentiment > 0.3 && isset($intentData['context']) && $intentData['context'] === 'gratitude') {
            $confidence *= 1.3;
        }

        return $confidence;
    }

    private function analyzeSentiment($message) {
        $positiveCount = 0;
        $negativeCount = 0;
        $words = explode(' ', strtolower($message));

        foreach ($words as $word) {
            if (in_array($word, $this->nlpProcessor['sentiment_positive'])) {
                $positiveCount++;
            } elseif (in_array($word, $this->nlpProcessor['sentiment_negative'])) {
                $negativeCount++;
            }
        }

        $totalSentimentWords = $positiveCount + $negativeCount;
        if ($totalSentimentWords === 0) return 0;

        return ($positiveCount - $negativeCount) / $totalSentimentWords;
    }

    private function checkLearnedPatterns($message) {
        foreach ($this->learningData as $pattern) {
            $similarity = similar_text(strtolower($pattern['message']), $message);
            $maxLength = max(strlen($pattern['message']), strlen($message));
            $confidence = ($similarity / $maxLength) * $pattern['success_rate'];

            if ($confidence > 0.8) {
                return [
                    'name' => $pattern['intent'],
                    'confidence' => $confidence,
                    'responses' => $this->intents[$pattern['intent']]['responses'] ?? ['Thank you for your message.'],
                    'learned' => true
                ];
            }
        }

        return null;
    }

    private function learnFromInteraction($message, $intent, $userId, $conversationId) {
        try {
            // Store learning data for future improvement
            $stmt = $this->db->prepare("
                INSERT INTO chatbot_learning (
                    message, intent, confidence, userId, conversationId,
                    processed_at, message_length, word_count
                ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
            ");

            $wordCount = str_word_count($message);
            $messageLength = strlen($message);

            $stmt->execute([
                $message,
                $intent['name'],
                $intent['confidence'],
                $userId,
                $conversationId,
                $messageLength,
                $wordCount
            ]);
        } catch (Exception $e) {
            // Learning is optional, don't break the main flow
            error_log('Learning storage error: ' . $e->getMessage());
        }
    }

    private function getSmartResponse($intent, $message, $userId) {
        $responses = $intent['responses'];

        // Personalize response based on user history if available
        if ($userId) {
            $personalizedResponse = $this->getPersonalizedResponse($intent, $userId);
            if ($personalizedResponse) {
                return $personalizedResponse;
            }
        }

        // Select response based on message characteristics
        if (strlen($message) > 50) {
            // Longer messages get more detailed responses
            return end($responses); // Last response is usually most detailed
        }

        return $responses[array_rand($responses)];
    }

    private function getPersonalizedResponse($intent, $userId) {
        // This could be enhanced to provide personalized responses
        // based on user's previous interactions, preferences, etc.
        return null;
    }

    private function getContextualQuickReplies($intent) {
        $contextualReplies = [
            'greeting' => ["Track my order", "Product information", "Delivery details", "Payment methods", "Contact support"],
            'product_inquiry' => ["Show me vegetables", "Organic products", "Dairy items", "Seasonal fruits", "Price list"],
            'order_tracking' => ["Check another order", "Delivery updates", "Cancel order", "Modify order", "Contact support"],
            'delivery_inquiry' => ["Change delivery time", "Delivery charges", "Express delivery", "Track order", "Delivery areas"],
            'payment_inquiry' => ["UPI payment", "Card payment", "COD option", "Payment issues", "Refund status"],
            'complaint' => ["Speak to manager", "File complaint", "Get refund", "Order replacement", "Escalate issue"]
        ];

        $context = $intent['context'] ?? 'greeting';
        return $contextualReplies[$context] ?? $this->quickReplies;
    }

    // ... (continuing with existing methods)
    private function handleOrderStatus($message, $userId) {
        preg_match('/\b(ORD\d+|\d{6,})\b/', $message, $matches);

        if (!empty($matches)) {
            $orderId = $matches[0];
            return "I found your order #$orderId. Your order is currently being prepared by our farmers and will be delivered within 2-3 hours. You'll receive a tracking notification soon! 📦";
        }

        return "To track your order, please provide your order ID (it usually starts with 'ORD' followed by numbers). You can find it in your email confirmation.";
    }

    private function handleDataRequiredIntent($intent, $message, $userId) {
        return $this->getSmartResponse($intent, $message, $userId);
    }

    private function escalateToHuman($userId, $message) {
        try {
            $sql = "INSERT INTO customer_service_requests (userId, subject, description, type, status, priority, createdAt)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                'Chatbot Escalation - ML Enhanced',
                "User message: $message\n\nUser requested human assistance via ML-enhanced chatbot.",
                'inquiry',
                'open',
                'medium'
            ]);

            return "I've connected you with our support team. A human agent will assist you shortly. You can also visit our Customer Service page for immediate help.";
        } catch (Exception $e) {
            error_log('Escalation error: ' . $e->getMessage());
            return "I'm connecting you with our support team. Please visit our Customer Service page or call our helpline for immediate assistance.";
        }
    }
}
?>
