<?php
// Simple API test
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🧪 API Connection Test</h1>
    
    <p><strong>Current URL:</strong> <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
    <p><strong>Expected API URL:</strong> <?php echo $_SERVER['HTTP_HOST']; ?>/api/chatbot/message.php</p>
    
    <button onclick="testAPI()">Test API Connection</button>
    <button onclick="testSimpleAPI()">Test Simple API</button>
    
    <div id="results"></div>

    <script>
        async function testAPI() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<div class="result">Testing API...</div>';
            
            try {
                console.log('Testing API...');
                
                const response = await fetch('/api/chatbot/message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello test',
                        conversationId: null
                    })
                });
                
                console.log('Response status:', response.status);
                console.log('Response URL:', response.url);
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Success:', data);
                    
                    resultsDiv.innerHTML = `
                        <div class="result success">
                            <h3>✅ API Working!</h3>
                            <p><strong>Response:</strong> ${data.botMessage.message}</p>
                            <p><strong>Intent:</strong> ${data.botMessage.intent}</p>
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    console.error('API Error:', response.status, errorText);
                    
                    resultsDiv.innerHTML = `
                        <div class="result error">
                            <h3>❌ API Error</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Error:</strong> ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Network Error:', error);
                
                resultsDiv.innerHTML = `
                    <div class="result error">
                        <h3>❌ Connection Error</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                        <p>This usually means the API file is not found or there's a server issue.</p>
                    </div>
                `;
            }
        }
        
        async function testSimpleAPI() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<div class="result">Testing Simple API...</div>';
            
            try {
                const response = await fetch('/api/chatbot/message_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello simple test',
                        conversationId: null
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    resultsDiv.innerHTML = `
                        <div class="result success">
                            <h3>✅ Simple API Working!</h3>
                            <p><strong>Response:</strong> ${data.botMessage.message}</p>
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    resultsDiv.innerHTML = `
                        <div class="result error">
                            <h3>❌ Simple API Error</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Error:</strong> ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="result error">
                        <h3>❌ Simple API Error</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>
