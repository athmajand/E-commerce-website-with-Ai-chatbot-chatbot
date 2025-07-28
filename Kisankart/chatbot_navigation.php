<!DOCTYPE html>
<html>
<head>
    <title>Kisan Kart - Chatbot Navigation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: #333;
        }
        h1 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .nav-card {
            background: #f8f9fa;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        .nav-card:hover {
            background: #4CAF50;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        .nav-card h3 {
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }
        .nav-card p {
            margin: 0;
            font-size: 0.9em;
            opacity: 0.8;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        .status.working { background: #d4edda; color: #155724; }
        .status.testing { background: #fff3cd; color: #856404; }
        .current-info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .icon { font-size: 2em; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Kisan Kart Chatbot Navigation</h1>

        <div class="current-info">
            <h3>📍 Current Server Information</h3>
            <p><strong>Server:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
            <p><strong>Port:</strong> <?php echo $_SERVER['SERVER_PORT']; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
            <p><strong>Current Path:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
        </div>

        <div class="nav-grid">
            <a href="/Kisankart/frontend/index.php" class="nav-card">
                <div class="icon">🏠</div>
                <h3>Main Website <span class="status working">Live</span></h3>
                <p>Kisan Kart main site with chatbot integration</p>
            </a>

            <a href="/Kisankart/test_chatbot.php" class="nav-card">
                <div class="icon">🧪</div>
                <h3>Chatbot Test Page <span class="status testing">Test</span></h3>
                <p>Dedicated chatbot testing interface</p>
            </a>

            <a href="/Kisankart/chatbot_status.php" class="nav-card">
                <div class="icon">📊</div>
                <h3>System Status <span class="status testing">Debug</span></h3>
                <p>Complete chatbot system diagnostics</p>
            </a>

            <a href="/Kisankart/api_test_simple.php" class="nav-card">
                <div class="icon">🔧</div>
                <h3>API Test <span class="status testing">Debug</span></h3>
                <p>Direct API connection testing</p>
            </a>

            <a href="/Kisankart/debug_api.php" class="nav-card">
                <div class="icon">🐛</div>
                <h3>API Debug <span class="status testing">Debug</span></h3>
                <p>Detailed API debugging information</p>
            </a>

            <a href="/Kisankart/api/chatbot/message.php" class="nav-card" target="_blank">
                <div class="icon">⚡</div>
                <h3>Direct API <span class="status testing">Raw</span></h3>
                <p>Raw API endpoint (will show error - normal)</p>
            </a>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>🚀 Quick Start Guide:</h3>
            <ol>
                <li><strong>Test the main site:</strong> Click "Main Website" to see the chatbot in action</li>
                <li><strong>Debug issues:</strong> Use "System Status" to check all components</li>
                <li><strong>Test API:</strong> Use "API Test" to verify backend connectivity</li>
                <li><strong>Look for the chat bubble:</strong> Green chat icon in bottom-right corner</li>
            </ol>

            <h3>🔍 Troubleshooting:</h3>
            <ul>
                <li>If chatbot shows "connection error" → Check System Status</li>
                <li>If pages don't load → Verify XAMPP is running</li>
                <li>If API fails → Check MySQL service in XAMPP</li>
                <li>For detailed logs → Open browser console (F12)</li>
            </ul>
        </div>
    </div>
</body>
</html>
