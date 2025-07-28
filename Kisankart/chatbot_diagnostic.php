<!DOCTYPE html>
<html>
<head>
    <title>Chatbot Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; }
        .error { background-color: #f8d7da; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Kisan Kart Chatbot Diagnostic</h1>
    
    <div class="test">
        <h3>Current URL Information</h3>
        <p><strong>Current URL:</strong> <span id="currentUrl"></span></p>
        <p><strong>Origin:</strong> <span id="origin"></span></p>
        <p><strong>Expected API URL:</strong> <span id="apiUrl"></span></p>
    </div>
    
    <button onclick="testDirectAPI()">Test API Directly</button>
    <button onclick="testFileExists()">Check File Exists</button>
    <button onclick="testChatbotConfig()">Test Chatbot Config</button>
    
    <div id="results"></div>

    <script>
        // Display current URL info
        document.getElementById('currentUrl').textContent = window.location.href;
        document.getElementById('origin').textContent = window.location.origin;
        document.getElementById('apiUrl').textContent = window.location.origin + '/api/chatbot/message.php';
        
        async function testDirectAPI() {
            const resultsDiv = document.getElementById('results');
            const apiUrl = window.location.origin + '/api/chatbot/message.php';
            
            resultsDiv.innerHTML = '<div class="test">Testing API at: ' + apiUrl + '</div>';
            
            try {
                console.log('Testing API at:', apiUrl);
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello diagnostic test',
                        conversationId: null
                    })
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    resultsDiv.innerHTML = `
                        <div class="test success">
                            <h3>✅ API Test Successful!</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Bot Response:</strong> ${data.botMessage.message}</p>
                            <p><strong>Intent:</strong> ${data.botMessage.intent}</p>
                            <p><strong>Conversation ID:</strong> ${data.conversationId}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    console.error('API Error:', errorText);
                    
                    resultsDiv.innerHTML = `
                        <div class="test error">
                            <h3>❌ API Test Failed</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Error:</strong> ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                
                resultsDiv.innerHTML = `
                    <div class="test error">
                        <h3>❌ Network Error</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                        <p>This usually means the file doesn't exist or there's a server issue.</p>
                    </div>
                `;
            }
        }
        
        async function testFileExists() {
            const resultsDiv = document.getElementById('results');
            const apiUrl = window.location.origin + '/api/chatbot/message.php';
            
            try {
                const response = await fetch(apiUrl, {
                    method: 'HEAD'
                });
                
                if (response.status === 405) {
                    resultsDiv.innerHTML = `
                        <div class="test success">
                            <h3>✅ File Exists</h3>
                            <p>The API file exists (got 405 Method Not Allowed, which is expected for HEAD request)</p>
                        </div>
                    `;
                } else if (response.status === 200) {
                    resultsDiv.innerHTML = `
                        <div class="test success">
                            <h3>✅ File Exists</h3>
                            <p>The API file exists and is accessible</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="test error">
                            <h3>❌ File Issue</h3>
                            <p>Status: ${response.status}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="test error">
                        <h3>❌ File Not Found</h3>
                        <p>Cannot reach the API file: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        function testChatbotConfig() {
            const resultsDiv = document.getElementById('results');
            
            // Check if chatbot scripts are loaded
            let configInfo = '<div class="test"><h3>Chatbot Configuration</h3>';
            
            if (typeof window.kisanKartChatbotConfig !== 'undefined') {
                configInfo += '<p>✅ Chatbot config found</p>';
                configInfo += '<pre>' + JSON.stringify(window.kisanKartChatbotConfig, null, 2) + '</pre>';
            } else {
                configInfo += '<p>❌ Chatbot config not found</p>';
            }
            
            if (typeof window.KisanKartChatbot !== 'undefined') {
                configInfo += '<p>✅ Chatbot class loaded</p>';
            } else {
                configInfo += '<p>❌ Chatbot class not loaded</p>';
            }
            
            if (typeof window.kisanKartChatbot !== 'undefined') {
                configInfo += '<p>✅ Chatbot instance exists</p>';
                configInfo += '<p>API URL: ' + window.kisanKartChatbot.apiBaseUrl + '</p>';
            } else {
                configInfo += '<p>❌ Chatbot instance not found</p>';
            }
            
            configInfo += '</div>';
            resultsDiv.innerHTML = configInfo;
        }
        
        // Auto-run tests
        window.addEventListener('load', function() {
            setTimeout(testFileExists, 1000);
        });
    </script>
</body>
</html>
