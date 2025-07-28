<?php
// Include database configuration
include_once 'api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $message = '';
    $success = false;
    
    // Validate input
    if (empty($username) || empty($password)) {
        $message = 'Please enter both username and password';
    } else {
        // Check if user exists
        $query = "SELECT id, username, password, email, role FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->bindParam(2, $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Generate a simple token
                $token = bin2hex(random_bytes(32));
                
                // Success message
                $message = 'Login successful! Redirecting...';
                $success = true;
                
                // User data to return
                $user_data = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'token' => $token
                ];
            } else {
                $message = 'Invalid password';
            }
        } else {
            $message = 'User not found';
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'user' => $success ? $user_data : null
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Login - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .login-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2 class="text-center mb-4">Direct Login</h2>
            <div id="message" class="alert" style="display: none;"></div>
            <form id="loginForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Login</button>
            </form>
            <div class="mt-3 text-center">
                <a href="frontend/index.html">Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const messageDiv = document.getElementById('message');
            
            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                
                const response = await fetch('direct_login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Success
                    messageDiv.className = 'alert alert-success';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                    
                    // Store user data in localStorage
                    localStorage.setItem('jwt_token', data.user.token);
                    localStorage.setItem('user_id', data.user.id);
                    localStorage.setItem('user_role', data.user.role);
                    localStorage.setItem('username', data.user.username);
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'frontend/dashboard.html';
                    }, 2000);
                } else {
                    // Error
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>
