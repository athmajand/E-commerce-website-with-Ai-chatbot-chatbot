<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chatbot Status Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { margin: 10px 0; padding: 15px; border-radius: 5px; }
        .success { background-color: #d4edda; border-left: 4px solid #28a745; }
        .error { background-color: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background-color: #d1ecf1; border-left: 4px solid #17a2b8; }
        h1 { color: #28a745; text-align: center; }
        h2 { color: #333; border-bottom: 2px solid #28a745; padding-bottom: 5px; }
        .file-check { display: inline-block; margin: 5px; padding: 5px 10px; background: #e9ecef; border-radius: 3px; }
        .file-exists { background: #d4edda; }
        .file-missing { background: #f8d7da; }
        button { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #218838; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Kisan Kart Chatbot Status</h1>
        
        <h2>📁 File System Check</h2>
        <?php
        $files = [
            'api/chatbot/message.php' => 'Main API Endpoint',
            'api/chatbot/message_simple.php' => 'Fallback API Endpoint',
            'api/chatbot/ChatbotService.php' => 'Chatbot Service',
            'api/chatbot/ChatbotServiceSimple.php' => 'Simple Chatbot Service',
            'frontend/js/chatbot-widget.js' => 'Chatbot Widget',
            'frontend/js/chatbot-integration.js' => 'Chatbot Integration'
        ];
        
        foreach ($files as $file => $description) {
            $exists = file_exists($file);
            $class = $exists ? 'file-exists' : 'file-missing';
            $icon = $exists ? '✅' : '❌';
            echo "<div class='file-check $class'>$icon $description</div>";
        }
        ?>
        
        <h2>🗄️ Database Check</h2>
        <?php
        try {
            $db = new PDO("mysql:host=localhost;dbname=kisan_kart", "root", "");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='status success'>✅ Database connection successful</div>";
            
            // Check tables
            $tables = ['chatbot_messages', 'customer_service_requests'];
            foreach ($tables as $table) {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "<div class='status success'>✅ Table '$table' exists</div>";
                } else {
                    echo "<div class='status error'>❌ Table '$table' missing</div>";
                }
            }
            
            // Get message count
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM chatbot_messages");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<div class='status info'>📊 Total chatbot messages: $count</div>";
            } catch (Exception $e) {
                echo "<div class='status warning'>⚠️ Could not count messages: " . $e->getMessage() . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='status error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <h2>🔧 API Test</h2>
        <button onclick="testMainAPI()">Test Main API</button>
        <button onclick="testFallbackAPI()">Test Fallback API</button>
        <button onclick="testBothAPIs()">Test Both APIs</button>
        
        <div id="api-results"></div>
        
        <h2>🌐 URL Information</h2>
        <div class="status info">
            <strong>Current URL:</strong> <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?><br>
            <strong>Server Port:</strong> <?php echo $_SERVER['SERVER_PORT']; ?><br>
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
            <strong>Main API URL:</strong> <?php echo $_SERVER['HTTP_HOST']; ?>/api/chatbot/message.php<br>
            <strong>Fallback API URL:</strong> <?php echo $_SERVER['HTTP_HOST']; ?>/api/chatbot/message_simple.php
        </div>
        
        <h2>📋 Quick Actions</h2>
        <button onclick="window.open('/frontend/index.php', '_blank')">Open Main Site</button>
        <button onclick="window.open('/test_chatbot.php', '_blank')">Open Test Page</button>
        <button onclick="window.open('/api_test_simple.php', '_blank')">Open API Test</button>
    </div>

    <script>
        async function testMainAPI() {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = '<div class="status info">Testing main API...</div>';
            
            try {
                const response = await fetch('/api/chatbot/message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: 'Hello status test', conversationId: null })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    resultsDiv.innerHTML = `
                        <div class="status success">
                            <h3>✅ Main API Working!</h3>
                            <p><strong>Response:</strong> ${data.botMessage.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    resultsDiv.innerHTML = `
                        <div class="status error">
                            <h3>❌ Main API Failed</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Error:</strong> ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="status error">
                        <h3>❌ Main API Error</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function testFallbackAPI() {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = '<div class="status info">Testing fallback API...</div>';
            
            try {
                const response = await fetch('/api/chatbot/message_simple.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: 'Hello fallback test', conversationId: null })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    resultsDiv.innerHTML = `
                        <div class="status success">
                            <h3>✅ Fallback API Working!</h3>
                            <p><strong>Response:</strong> ${data.botMessage.message}</p>
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    resultsDiv.innerHTML = `
                        <div class="status error">
                            <h3>❌ Fallback API Failed</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Error:</strong> ${errorText}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="status error">
                        <h3>❌ Fallback API Error</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function testBothAPIs() {
            await testMainAPI();
            setTimeout(testFallbackAPI, 1000);
        }
        
        // Auto-test on load
        window.addEventListener('load', function() {
            setTimeout(testBothAPIs, 1000);
        });
    </script>
</body>
</html>
