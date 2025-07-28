<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisan Kart - Chatbot Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .test-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .status-card {
            background: #e8f5e8;
            border: 1px solid #4CAF50;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .test-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .test-button:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        .instructions {
            background: #f0f8ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="header">
            <h1><i class="fas fa-robot"></i> Kisan Kart Chatbot Test</h1>
            <p class="text-muted">Testing the PHP-based chatbot integration</p>
        </div>

        <div class="status-card">
            <h4><i class="fas fa-check-circle text-success"></i> Chatbot Status</h4>
            <p>✅ PHP API endpoints created<br>
               ✅ Frontend integration updated<br>
               ✅ Database configuration ready</p>
        </div>

        <div class="instructions">
            <h5><i class="fas fa-info-circle"></i> How to Test:</h5>
            <ol>
                <li>Look for the green chat bubble in the bottom-right corner</li>
                <li>Click on it to open the chatbot</li>
                <li>Try sending messages like:
                    <ul>
                        <li>"Hello" - for greeting</li>
                        <li>"Track my order" - for order status</li>
                        <li>"What products do you have?" - for product info</li>
                        <li>"Delivery information" - for delivery details</li>
                    </ul>
                </li>
                <li>The chatbot should respond immediately</li>
            </ol>
        </div>

        <div class="text-center">
            <button class="test-button" onclick="openChatbot()">
                <i class="fas fa-comments"></i> Open Chatbot
            </button>
        </div>

        <div class="mt-4">
            <h5>API Test:</h5>
            <button class="btn btn-outline-primary" onclick="testAPI()">Test API Directly</button>
            <div id="api-result" class="mt-3"></div>
        </div>
    </div>

    <!-- Include chatbot scripts -->
    <script src="frontend/js/chatbot-integration.js"></script>

    <script>
        function openChatbot() {
            if (window.KisanKartChatbotUtils) {
                window.KisanKartChatbotUtils.openChat();
            } else {
                alert('Chatbot is still loading. Please wait a moment and try again.');
            }
        }

        async function testAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Testing API...';

            try {
                const response = await fetch(window.location.origin + '/Kisankart/api/chatbot/message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello, this is a test message',
                        conversationId: null
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6>✅ API Test Successful!</h6>
                            <p><strong>Bot Response:</strong> ${data.botMessage.message}</p>
                            <p><strong>Intent:</strong> ${data.botMessage.intent}</p>
                            <p><strong>Confidence:</strong> ${data.botMessage.confidence}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>❌ API Test Failed</h6>
                            <p>${data.error || 'Unknown error'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ API Test Failed</h6>
                        <p>Error: ${error.message}</p>
                    </div>
                `;
            }
        }

        // Auto-test API on page load
        window.addEventListener('load', function() {
            setTimeout(testAPI, 2000);
        });
    </script>
</body>
</html>
