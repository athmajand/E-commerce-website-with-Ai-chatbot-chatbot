<?php

class ChatbotServiceSimple {
    private $intents;
    private $quickReplies;

    public function __construct() {
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
                'patterns' => ['order status', 'track order', 'where is my order', 'order tracking', 'delivery status', 'track my order'],
                'responses' => [
                    "I can help you track your order! Could you please provide your order ID?",
                    "To check your order status, please share your order number with me.",
                    "For order tracking, please provide your order ID and I'll check the status for you."
                ],
                'confidence' => 0.8,
                'requiresData' => true
            ],
            'product_info' => [
                'patterns' => ['product', 'item', 'what do you sell', 'products available', 'fresh produce', 'product information'],
                'responses' => [
                    "We offer fresh fruits, vegetables, grains, dairy products, and organic produce directly from farmers! 🥕🍎🌾",
                    "Kisan Kart has a wide variety of fresh farm products including fruits, vegetables, dairy, and organic items.",
                    "Our marketplace features fresh produce from local farmers: vegetables, fruits, grains, dairy products, spices, and organic items."
                ],
                'confidence' => 0.7
            ],
            'delivery_info' => [
                'patterns' => ['delivery', 'shipping', 'when will i get', 'delivery time', 'delivery charges', 'delivery details'],
                'responses' => [
                    "We offer same-day delivery for orders placed before 2 PM. Delivery charges start from ₹30. 🚚",
                    "Our delivery slots are: Morning (8-12 PM), Afternoon (12-6 PM), Evening (6-10 PM). Free delivery on orders above ₹500!",
                    "Delivery is available within 24 hours. Choose your preferred time slot during checkout."
                ],
                'confidence' => 0.8
            ],
            'payment_info' => [
                'patterns' => ['payment', 'pay', 'payment methods', 'upi', 'card', 'cash on delivery', 'cod'],
                'responses' => [
                    "We accept UPI, Credit/Debit Cards, Net Banking, and Cash on Delivery (COD). 💳",
                    "You can pay using UPI, cards, online banking, or choose Cash on Delivery option.",
                    "Payment options: UPI (Google Pay, PhonePe, Paytm), Cards, Net Banking, and COD available."
                ],
                'confidence' => 0.8
            ],
            'account_help' => [
                'patterns' => ['login', 'register', 'account', 'forgot password', 'profile', 'login help'],
                'responses' => [
                    "For account issues, you can reset your password using the 'Forgot Password' link on the login page. 🔐",
                    "Need help with your account? I can guide you through login, registration, or profile updates.",
                    "Having trouble logging in? Try the password reset option or contact our support team."
                ],
                'confidence' => 0.7
            ],
            'contact_info' => [
                'patterns' => ['contact', 'phone', 'email', 'support', 'help', 'customer service'],
                'responses' => [
                    "You can reach our support team at support@kisankart.com or call us at +91-XXXXXXXXXX. 📞",
                    "For immediate assistance, contact us at support@kisankart.com or use our customer service page.",
                    "Need help? Email: support@kisankart.com | Phone: +91-XXXXXXXXXX | Available 24/7"
                ],
                'confidence' => 0.8
            ],
            'thanks' => [
                'patterns' => ['thank you', 'thanks', 'appreciate', 'great', 'awesome'],
                'responses' => [
                    "You're welcome! Happy to help! 😊",
                    "Glad I could assist you! Is there anything else you need help with?",
                    "My pleasure! Feel free to ask if you have any other questions."
                ],
                'confidence' => 0.9
            ],
            'goodbye' => [
                'patterns' => ['bye', 'goodbye', 'see you', 'exit', 'quit'],
                'responses' => [
                    "Goodbye! Thank you for choosing Kisan Kart. Have a great day! 🌱",
                    "See you later! Feel free to reach out anytime you need help.",
                    "Take care! Happy shopping with Kisan Kart! 🛒"
                ],
                'confidence' => 0.9
            ],
            'human_agent' => [
                'patterns' => ['human', 'agent', 'speak to someone', 'customer service', 'representative', 'talk to human'],
                'responses' => [
                    "I'll connect you with our customer service team right away! Please hold on."
                ],
                'confidence' => 0.9,
                'escalate' => true
            ],
            'pricing' => [
                'patterns' => ['price', 'cost', 'how much', 'expensive', 'cheap', 'rate'],
                'responses' => [
                    "Our prices are competitive and directly from farmers! You can browse products to see current prices. 💰",
                    "We offer farm-fresh products at great prices. Check our products page for current rates.",
                    "Prices vary by product and season. Visit our marketplace to see the latest pricing."
                ],
                'confidence' => 0.7
            ],
            'quality' => [
                'patterns' => ['quality', 'fresh', 'organic', 'good', 'best'],
                'responses' => [
                    "We guarantee farm-fresh quality! All products are sourced directly from verified farmers. ✅",
                    "Quality is our priority - fresh, organic, and pesticide-free options available.",
                    "Our farmers follow strict quality standards to ensure you get the freshest produce."
                ],
                'confidence' => 0.7
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
            'responses' => ["I'm not sure I understand. Could you please rephrase your question? You can also try asking about our products, delivery, or payment methods."]
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
            return "I found your order #$orderId. Your order is currently being prepared by our farmers and will be delivered within 2-3 hours. You'll receive a tracking notification soon! 📦";
        }

        return "To track your order, please provide your order ID (it usually starts with 'ORD' followed by numbers). You can find it in your email confirmation.";
    }

    private function handleDataRequiredIntent($intent, $message, $userId) {
        return $this->getRandomResponse($intent['responses']);
    }

    private function escalateToHuman($userId, $message) {
        // In a real implementation, this would create a support ticket
        return "I've notified our customer service team about your request. A human agent will contact you shortly via email or phone. You can also call us directly at +91-XXXXXXXXXX for immediate assistance. 👨‍💼";
    }

    private function getRandomResponse($responses) {
        return $responses[array_rand($responses)];
    }
}
?>
