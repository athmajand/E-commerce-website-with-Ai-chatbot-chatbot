<?php

class ChatbotService {
    private $db;
    private $intents;
    private $quickReplies;

    public function __construct($database) {
        $this->db = $database;
        $this->initializeIntents();
        $this->initializeQuickReplies();
    }

    private function initializeIntents() {
        $this->intents = [
            'greeting' => [
                'patterns' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'namaste'],
                'responses' => [
                    "Hello! Welcome to Kisankart! 🌱 I'm here to help you with your queries.",
                    "Hi there! How can I assist you with Kisankart today?",
                    "Welcome to Kisankart! I'm your virtual assistant. How may I help you?"
                ],
                'confidence' => 0.9
            ],
            'order_status' => [
                'patterns' => ['order status', 'track order', 'where is my order', 'order tracking', 'delivery status'],
                'responses' => [
                    "I can help you track your order! Could you please provide your order ID?",
                    "To check your order status, please share your order number with me."
                ],
                'confidence' => 0.8,
                'requiresData' => true
            ],
            'product_info' => [
                'patterns' => ['product', 'item', 'what do you sell', 'products available', 'fresh produce'],
                'responses' => [
                    "We offer fresh fruits, vegetables, grains, dairy products, and organic produce directly from farmers!",
                    "Kisan Kart has a wide variety of fresh farm products including fruits, vegetables, dairy, and organic items."
                ],
                'confidence' => 0.7
            ],
            'delivery_info' => [
                'patterns' => ['delivery', 'shipping', 'when will i get', 'delivery time', 'delivery charges'],
                'responses' => [
                    "We offer same-day delivery for orders placed before 2 PM. Delivery charges start from ₹30.",
                    "Our delivery slots are: Morning (8-12 PM), Afternoon (12-6 PM), Evening (6-10 PM)."
                ],
                'confidence' => 0.8
            ],
            'payment_info' => [
                'patterns' => ['payment', 'pay', 'payment methods', 'upi', 'card', 'cash on delivery', 'cod'],
                'responses' => [
                    "We accept UPI, Credit/Debit Cards, Net Banking, and Cash on Delivery (COD).",
                    "You can pay using UPI, cards, online banking, or choose Cash on Delivery option."
                ],
                'confidence' => 0.8
            ],
            'account_help' => [
                'patterns' => ['login', 'register', 'account', 'forgot password', 'profile'],
                'responses' => [
                    "For account issues, you can reset your password using the 'Forgot Password' link on the login page.",
                    "Need help with your account? I can guide you through login, registration, or profile updates."
                ],
                'confidence' => 0.7
            ],
            'contact_info' => [
                'patterns' => ['contact', 'phone', 'email', 'support', 'help'],
                'responses' => [
                    "You can reach our support team at support@kisankart.com or call us at +91-XXXXXXXXXX.",
                    "For immediate assistance, contact us at support@kisankart.com or use our customer service page."
                ],
                'confidence' => 0.8
            ],
            'thanks' => [
                'patterns' => ['thank you', 'thanks', 'appreciate'],
                'responses' => [
                    "You're welcome! Happy to help! 😊",
                    "Glad I could assist you! Is there anything else you need help with?"
                ],
                'confidence' => 0.9
            ],
            'goodbye' => [
                'patterns' => ['bye', 'goodbye', 'see you', 'exit'],
                'responses' => [
                    "Goodbye! Thank you for choosing Kisankart. Have a great day! 🌱",
                    "See you later! Feel free to reach out anytime you need help."
                ],
                'confidence' => 0.9
            ],
            'human_agent' => [
                'patterns' => ['human', 'agent', 'speak to someone', 'customer service', 'representative'],
                'responses' => [
                    "I'll connect you with our customer service team right away!"
                ],
                'confidence' => 0.9,
                'escalate' => true
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
            $intent = $this->detectIntent($message);
            $response = '';

            if ($intent['name'] === 'order_status' && $intent['confidence'] > 0.7) {
                $response = $this->handleOrderStatus($message, $userId);
            } elseif (isset($intent['escalate']) && $intent['escalate']) {
                $response = $this->escalateToHuman($userId, $message);
            } elseif (isset($intent['requiresData']) && $intent['requiresData']) {
                $response = $this->handleDataRequiredIntent($intent, $message, $userId);
            } else {
                $response = $this->getRandomResponse($intent['responses']);
            }

            return [
                'message' => $response,
                'intent' => $intent['name'],
                'confidence' => $intent['confidence'],
                'quickReplies' => $intent['name'] === 'greeting' ? $this->quickReplies : null,
                'escalated' => isset($intent['escalate']) ? $intent['escalate'] : false
            ];
        } catch (Exception $e) {
            error_log('Chatbot processing error: ' . $e->getMessage());
            return [
                'message' => "I'm sorry, I'm having trouble understanding. Let me connect you with a human agent.",
                'intent' => 'error',
                'confidence' => 0,
                'escalated' => true
            ];
        }
    }

    private function detectIntent($message) {
        $lowerMessage = strtolower($message);
        $bestMatch = [
            'name' => 'unknown',
            'confidence' => 0,
            'responses' => ["I'm not sure I understand. Could you please rephrase your question?"]
        ];

        foreach ($this->intents as $intentName => $intentData) {
            $confidence = 0;

            foreach ($intentData['patterns'] as $pattern) {
                if (strpos($lowerMessage, strtolower($pattern)) !== false) {
                    $confidence = max($confidence, $intentData['confidence']);
                }
            }

            if ($confidence > $bestMatch['confidence']) {
                $bestMatch = array_merge($intentData, ['name' => $intentName, 'confidence' => $confidence]);
            }
        }

        return $bestMatch;
    }

    private function handleOrderStatus($message, $userId) {
        // Extract order ID from message
        preg_match('/\b(ORD\d+|\d{6,})\b/', $message, $matches);

        if (!empty($matches)) {
            $orderId = $matches[0];
            // Here you would typically query your orders table
            return "I found your order #$orderId. Let me check the status for you... Your order is currently being prepared and will be delivered within 2-3 hours.";
        }

        return "To track your order, please provide your order ID (it usually starts with 'ORD' followed by numbers).";
    }

    private function handleDataRequiredIntent($intent, $message, $userId) {
        return $this->getRandomResponse($intent['responses']);
    }

    private function escalateToHuman($userId, $message) {
        try {
            // Create a customer service request
            $sql = "INSERT INTO customer_service_requests (userId, subject, description, type, status, priority, createdAt)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                'Chatbot Escalation',
                "User message: $message\n\nUser requested human assistance.",
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

    private function getRandomResponse($responses) {
        return $responses[array_rand($responses)];
    }
}
?>
