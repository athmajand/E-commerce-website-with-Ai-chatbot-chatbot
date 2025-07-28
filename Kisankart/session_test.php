<?php
session_start();

echo "<h2>Session Test</h2>";

// Set a test session variable
if (isset($_GET['set'])) {
    $_SESSION['test_var'] = 'Session is working!';
    $_SESSION['timestamp'] = date('Y-m-d H:i:s');
    echo "<p>Session variables set!</p>";
}

// Display session info
echo "<h3>Session Information:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session save path: " . session_save_path() . "<br>";
echo "Session name: " . session_name() . "<br>";

echo "<h3>Session Variables:</h3>";
if (empty($_SESSION)) {
    echo "No session variables set.<br>";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "$key: $value<br>";
    }
}

echo "<h3>Actions:</h3>";
echo "<a href='?set=1'>Set Test Session</a> | ";
echo "<a href='?clear=1'>Clear Session</a> | ";
echo "<a href='session_test.php'>Refresh</a>";

if (isset($_GET['clear'])) {
    session_destroy();
    echo "<br><strong>Session cleared! <a href='session_test.php'>Refresh to see changes</a></strong>";
}
?>
