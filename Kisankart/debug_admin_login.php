<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Admin Login</h2>";

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kisan_kart";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>1. Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Admin ID in session: " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'Not set') . "<br>";
echo "Admin username in session: " . (isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Not set') . "<br>";
echo "Admin name in session: " . (isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Not set') . "<br>";

echo "<h3>2. Admin Users Table Structure:</h3>";
$result = $conn->query("DESCRIBE admin_users");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h3>3. Admin Users Data:</h3>";
$result = $conn->query("SELECT * FROM admin_users");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Status</th><th>Created At</th><th>Last Login</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td>" . ($row['last_login'] ?? 'Never') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h3>4. Test Login Process:</h3>";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $test_username = $_POST['username'];
    $test_password = $_POST['password'];
    
    echo "Attempting login with username: " . htmlspecialchars($test_username) . "<br>";
    
    $stmt = $conn->prepare("SELECT id, username, password, name FROM admin_users WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $test_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "Query executed. Rows found: " . $result->num_rows . "<br>";
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        echo "User found: " . $row['username'] . "<br>";
        echo "Password verification: " . (password_verify($test_password, $row['password']) ? 'SUCCESS' : 'FAILED') . "<br>";
        
        if (password_verify($test_password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $row['username'];
            $_SESSION['admin_name'] = $row['name'];
            echo "Session variables set successfully<br>";
            echo "Redirecting to dashboard...<br>";
        }
    } else {
        echo "No active user found with that username<br>";
    }
    $stmt->close();
}

$conn->close();
?>

<form method="post">
    <h3>Test Login:</h3>
    Username: <input type="text" name="username" value="admin"><br><br>
    Password: <input type="password" name="password" value="admin123"><br><br>
    <input type="submit" value="Test Login">
</form>

<br>
<a href="admin_login.php">Go to Admin Login</a> | 
<a href="admin_dashboard.php">Go to Dashboard</a> |
<a href="?clear_session=1">Clear Session</a>

<?php
if (isset($_GET['clear_session'])) {
    session_destroy();
    echo "<br><strong>Session cleared!</strong>";
}
?>
