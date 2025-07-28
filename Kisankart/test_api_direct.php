<!DOCTYPE html>
<html>
<head>
    <title>Direct API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Kisan Kart Chatbot API Test</h1>

    <button onclick="testAPI()">Test API</button>
    <button onclick="testSimpleAPI()">Test Simple API</button>

    <div id="results"></div>

    <script>
        async function testAPI() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<div class="test-result">Testing full API...</div>';

            try {
                const response = await fetch(window.location.origin + '/api/chatbot/message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello from direct test',
                        conversationId: null
                    })
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (response.ok) {
                    const data = await response.json();
                    resultsDiv.innerHTML = `
                        <div class="test-result success">
                            <h3>✅ Full API Success!</h3>
                            <p><strong>Bot Response:</strong> ${data.botMessage.message}</p>
                            <p><strong>Intent:</strong> ${data.botMessage.intent}</p>
                            <p><strong>Confidence:</strong> ${data.botMessage.confidence}</p>
                            <p><strong>Conversation ID:</strong> ${data.conversationId}</p>
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    resultsDiv.innerHTML = `
                        <div class="test-result error">
                            <h3>❌ Full API Failed</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Error:</strong> ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="test-result error">
                        <h3>❌ Full API Error</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }

        async function testSimpleAPI() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<div class="test-result">Testing simple API...</div>';

            try {
                const response = await fetch(window.location.origin + '/api/chatbot/message_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello from simple test',
                        conversationId: null
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    resultsDiv.innerHTML = `
                        <div class="test-result success">
                            <h3>✅ Simple API Success!</h3>
                            <p><strong>Bot Response:</strong> ${data.botMessage.message}</p>
                            <p><strong>Intent:</strong> ${data.botMessage.intent}</p>
                            <p><strong>Confidence:</strong> ${data.botMessage.confidence}</p>
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    resultsDiv.innerHTML = `
                        <div class="test-result error">
                            <h3>❌ Simple API Failed</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Error:</strong> ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="test-result error">
                        <h3>❌ Simple API Error</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }

        // Auto-test on page load
        window.addEventListener('load', function() {
            setTimeout(testAPI, 1000);
        });
    </script>
</body>
</html>
