<!DOCTYPE html>
<html>
<head>
    <title>Quick Replies Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { max-width: 600px; margin: 0 auto; }
        .test-result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .quick-reply-demo { 
            background: #f8f9fa; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            padding: 20px; 
            margin: 20px 0; 
        }
        .quick-reply-btn {
            display: inline-block;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 16px;
            margin: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        .quick-reply-btn:hover {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔧 Quick Replies Test</h1>
        
        <div class="quick-reply-demo">
            <h3>Demo Quick Replies (should look like this):</h3>
            <div id="demo-replies">
                <span class="quick-reply-btn">Track my order</span>
                <span class="quick-reply-btn">Product information</span>
                <span class="quick-reply-btn">Delivery details</span>
                <span class="quick-reply-btn">Payment methods</span>
                <span class="quick-reply-btn">Contact support</span>
            </div>
        </div>
        
        <button onclick="testAPI()">Test API Response</button>
        <button onclick="testQuickReplies()">Test Quick Replies Function</button>
        
        <div id="results"></div>
        
        <div class="quick-reply-demo">
            <h3>Live Test Area:</h3>
            <div id="test-quick-replies" style="display: none;"></div>
        </div>
    </div>

    <script>
        async function testAPI() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<div class="test-result">Testing API for quick replies...</div>';
            
            try {
                const response = await fetch('/Kisankart/api/chatbot/message_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: 'Hello',
                        conversationId: null
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('API Response:', data);
                    
                    if (data.botMessage.quickReplies) {
                        resultsDiv.innerHTML = `
                            <div class="test-result success">
                                <h3>✅ API Returns Quick Replies!</h3>
                                <p><strong>Bot Message:</strong> ${data.botMessage.message}</p>
                                <p><strong>Quick Replies:</strong> ${JSON.stringify(data.botMessage.quickReplies)}</p>
                                <p><strong>Type:</strong> ${typeof data.botMessage.quickReplies[0]}</p>
                            </div>
                        `;
                        
                        // Test the quick replies function
                        testQuickRepliesWithData(data.botMessage.quickReplies);
                    } else {
                        resultsDiv.innerHTML = `
                            <div class="test-result error">
                                <h3>❌ No Quick Replies in Response</h3>
                                <p>Response: ${JSON.stringify(data, null, 2)}</p>
                            </div>
                        `;
                    }
                } else {
                    const errorText = await response.text();
                    resultsDiv.innerHTML = `
                        <div class="test-result error">
                            <h3>❌ API Error</h3>
                            <p>Status: ${response.status}</p>
                            <p>Error: ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="test-result error">
                        <h3>❌ Network Error</h3>
                        <p>Error: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        function testQuickReplies() {
            const testReplies = [
                "Track my order",
                "Product information", 
                "Delivery details",
                "Payment methods",
                "Contact support"
            ];
            
            testQuickRepliesWithData(testReplies);
        }
        
        function testQuickRepliesWithData(quickReplies) {
            const container = document.getElementById('test-quick-replies');
            container.innerHTML = '';
            
            if (!quickReplies || quickReplies.length === 0) {
                container.innerHTML = '<p>No quick replies to display</p>';
                container.style.display = 'block';
                return;
            }
            
            quickReplies.forEach(reply => {
                const btn = document.createElement('span');
                btn.className = 'quick-reply-btn';
                
                // Handle both string and object formats (same logic as chatbot)
                if (typeof reply === 'string') {
                    btn.textContent = reply;
                    btn.addEventListener('click', () => {
                        alert('Quick reply clicked: ' + reply);
                    });
                } else {
                    btn.textContent = reply.text || reply.label || reply;
                    btn.addEventListener('click', () => {
                        alert('Quick reply clicked: ' + (reply.text || reply.label || reply));
                    });
                }
                
                container.appendChild(btn);
            });
            
            container.style.display = 'block';
            
            // Update results
            document.getElementById('results').innerHTML += `
                <div class="test-result success">
                    <h3>✅ Quick Replies Rendered!</h3>
                    <p>Successfully created ${quickReplies.length} quick reply buttons</p>
                    <p>Check the "Live Test Area" above to see them</p>
                </div>
            `;
        }
        
        // Auto-test on page load
        window.addEventListener('load', function() {
            setTimeout(testAPI, 1000);
        });
    </script>
</body>
</html>
