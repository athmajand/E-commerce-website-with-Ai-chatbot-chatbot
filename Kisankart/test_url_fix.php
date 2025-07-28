<?php
// Test file to verify URL redirect fix
echo "<h1>URL Redirect Test</h1>";
echo "<p>This page tests the URL redirect functionality.</p>";
echo "<p>Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Base URL: " . $_SERVER['HTTP_HOST'] . "</p>";

// Test the main redirect
echo "<h2>Testing Main Redirect</h2>";
echo "<p><a href='/Kisankart/'>Test Main Index Redirect</a></p>";

// Test frontend access
echo "<h2>Testing Frontend Access</h2>";
echo "<p><a href='/Kisankart/frontend/index.php'>Test Frontend Index</a></p>";

// Test login redirects
echo "<h2>Testing Login Redirects</h2>";
echo "<p><a href='/Kisankart/login.php'>Test Customer Login</a></p>";
echo "<p><a href='/Kisankart/seller_login.php'>Test Seller Login</a></p>";

echo "<h2>URL Analysis</h2>";
echo "<p>If you see any extremely long URLs with repeated 'frontend/frontend/frontend...', the issue is not fixed.</p>";
echo "<p>All URLs should be clean and direct like: http://localhost:8080/Kisankart/frontend/index.php</p>";
?> 