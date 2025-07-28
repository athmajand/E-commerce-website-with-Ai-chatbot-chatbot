<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check JavaScript - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #console-log {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            height: 200px;
            overflow-y: auto;
            font-family: monospace;
            margin-bottom: 20px;
        }
        .log-item {
            margin-bottom: 5px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .log-error {
            color: #dc3545;
        }
        .log-warn {
            color: #ffc107;
        }
        .log-info {
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">JavaScript Diagnostics</h1>
        
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Console Log</h5>
                    </div>
                    <div class="card-body">
                        <div id="console-log">
                            <div class="log-item">Console output will appear here...</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button id="clear-log" class="btn btn-secondary">Clear Log</button>
                            <button id="test-log" class="btn btn-primary">Test Console Log</button>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Navigation Tests</h5>
                    </div>
                    <div class="card-body">
                        <p>Click the buttons below to test navigation:</p>
                        
                        <div class="mb-3">
                            <button id="test-location" class="btn btn-outline-primary me-2">Test window.location</button>
                            <button id="test-href" class="btn btn-outline-success me-2">Test location.href</button>
                            <button id="test-assign" class="btn btn-outline-info me-2">Test location.assign()</button>
                            <button id="test-replace" class="btn btn-outline-warning">Test location.replace()</button>
                        </div>
                        
                        <div class="mb-3">
                            <button id="nav-profile" class="btn btn-success me-2">Go to Profile</button>
                            <button id="nav-dashboard" class="btn btn-primary me-2">Go to Dashboard</button>
                            <button id="nav-static" class="btn btn-secondary">Go to Static Profile</button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">HTML Links</h5>
                    </div>
                    <div class="card-body">
                        <p>Try these direct HTML links:</p>
                        
                        <div class="list-group">
                            <a href="profile_static.html" class="list-group-item list-group-item-action">Static Profile Page</a>
                            <a href="standalone_profile.php" class="list-group-item list-group-item-action">Standalone Profile Page</a>
                            <a href="frontend/customer_dashboard.php" class="list-group-item list-group-item-action">Dashboard Page</a>
                            <a href="profile_direct.html" class="list-group-item list-group-item-action">Direct Links Page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Override console.log to display in our custom console
        (function() {
            const consoleLog = document.getElementById('console-log');
            const oldLog = console.log;
            const oldError = console.error;
            const oldWarn = console.warn;
            const oldInfo = console.info;
            
            console.log = function(message) {
                // Call the original console.log
                oldLog.apply(console, arguments);
                
                // Add to our custom console
                const logItem = document.createElement('div');
                logItem.className = 'log-item';
                logItem.textContent = typeof message === 'object' ? JSON.stringify(message) : message;
                consoleLog.appendChild(logItem);
                consoleLog.scrollTop = consoleLog.scrollHeight;
            };
            
            console.error = function(message) {
                // Call the original console.error
                oldError.apply(console, arguments);
                
                // Add to our custom console
                const logItem = document.createElement('div');
                logItem.className = 'log-item log-error';
                logItem.textContent = 'ERROR: ' + (typeof message === 'object' ? JSON.stringify(message) : message);
                consoleLog.appendChild(logItem);
                consoleLog.scrollTop = consoleLog.scrollHeight;
            };
            
            console.warn = function(message) {
                // Call the original console.warn
                oldWarn.apply(console, arguments);
                
                // Add to our custom console
                const logItem = document.createElement('div');
                logItem.className = 'log-item log-warn';
                logItem.textContent = 'WARN: ' + (typeof message === 'object' ? JSON.stringify(message) : message);
                consoleLog.appendChild(logItem);
                consoleLog.scrollTop = consoleLog.scrollHeight;
            };
            
            console.info = function(message) {
                // Call the original console.info
                oldInfo.apply(console, arguments);
                
                // Add to our custom console
                const logItem = document.createElement('div');
                logItem.className = 'log-item log-info';
                logItem.textContent = 'INFO: ' + (typeof message === 'object' ? JSON.stringify(message) : message);
                consoleLog.appendChild(logItem);
                consoleLog.scrollTop = consoleLog.scrollHeight;
            };
            
            // Log any errors
            window.onerror = function(message, source, lineno, colno, error) {
                console.error(`Error: ${message} at ${source}:${lineno}:${colno}`);
                return false;
            };
            
            // Clear log button
            document.getElementById('clear-log').addEventListener('click', function() {
                consoleLog.innerHTML = '';
            });
            
            // Test log button
            document.getElementById('test-log').addEventListener('click', function() {
                console.log('This is a test log message');
                console.info('This is an info message');
                console.warn('This is a warning message');
                console.error('This is an error message');
            });
            
            // Navigation test buttons
            document.getElementById('test-location').addEventListener('click', function() {
                console.log('Current location: ' + window.location.href);
                try {
                    window.location = 'profile_static.html';
                    console.log('Navigation successful');
                } catch (e) {
                    console.error('Navigation failed: ' + e.message);
                }
            });
            
            document.getElementById('test-href').addEventListener('click', function() {
                console.log('Current location: ' + window.location.href);
                try {
                    window.location.href = 'profile_static.html';
                    console.log('Navigation successful');
                } catch (e) {
                    console.error('Navigation failed: ' + e.message);
                }
            });
            
            document.getElementById('test-assign').addEventListener('click', function() {
                console.log('Current location: ' + window.location.href);
                try {
                    window.location.assign('profile_static.html');
                    console.log('Navigation successful');
                } catch (e) {
                    console.error('Navigation failed: ' + e.message);
                }
            });
            
            document.getElementById('test-replace').addEventListener('click', function() {
                console.log('Current location: ' + window.location.href);
                try {
                    window.location.replace('profile_static.html');
                    console.log('Navigation successful');
                } catch (e) {
                    console.error('Navigation failed: ' + e.message);
                }
            });
            
            // Navigation buttons
            document.getElementById('nav-profile').addEventListener('click', function() {
                try {
                    window.location.href = 'standalone_profile.php';
                    console.log('Navigating to profile');
                } catch (e) {
                    console.error('Navigation failed: ' + e.message);
                }
            });
            
            document.getElementById('nav-dashboard').addEventListener('click', function() {
                try {
                    window.location.href = 'frontend/customer_dashboard.php';
                    console.log('Navigating to dashboard');
                } catch (e) {
                    console.error('Navigation failed: ' + e.message);
                }
            });
            
            document.getElementById('nav-static').addEventListener('click', function() {
                try {
                    window.location.href = 'profile_static.html';
                    console.log('Navigating to static profile');
                } catch (e) {
                    console.error('Navigation failed: ' + e.message);
                }
            });
            
            // Log page load
            console.log('Page loaded at: ' + new Date().toString());
            console.log('Current URL: ' + window.location.href);
        })();
    </script>
</body>
</html>
