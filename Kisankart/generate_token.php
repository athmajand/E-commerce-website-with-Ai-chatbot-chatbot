<?php
// Generate a valid JWT token for testing

// Function to generate JWT token
function generateJWT($user_id, $username, $role) {
    $secret_key = "kisan_kart_jwt_secret";
    $issuer_claim = "kisan_kart_api"; // this can be the servername
    $audience_claim = "kisan_kart_client";
    $issuedat_claim = time(); // issued at
    $notbefore_claim = $issuedat_claim; // not before
    $expire_claim = $issuedat_claim + 3600; // expire time (1 hour)

    $token = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "nbf" => $notbefore_claim,
        "exp" => $expire_claim,
        "data" => array(
            "id" => $user_id,
            "username" => $username,
            "role" => $role
        )
    );

    // Create JWT parts
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode($token));

    // Create signature
    $signature_data = $header . '.' . $payload;
    $signature = base64_encode(hash_hmac('sha256', $signature_data, $secret_key, true));

    // Combine all parts to form the JWT
    $jwt = $header . '.' . $payload . '.' . $signature;

    return $jwt;
}

// Get user data from request or use defaults
$user_id = isset($_GET['id']) ? $_GET['id'] : 10;
$username = isset($_GET['username']) ? $_GET['username'] : 'mohammedarsh';
$role = isset($_GET['role']) ? $_GET['role'] : 'customer';

// Generate token
$token = generateJWT($user_id, $username, $role);

// Output HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate JWT Token - Kisan Kart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Generate Valid JWT Token</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p>This page generates a valid JWT token for your user. Use this to fix your profile data issue.</p>
                        </div>
                        
                        <h5 class="mt-4">User Information</h5>
                        <form method="get" class="mb-4">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="id" class="form-label">User ID</label>
                                    <input type="number" class="form-control" id="id" name="id" value="<?php echo $user_id; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-control" id="role" name="role">
                                        <option value="customer" <?php echo ($role == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                        <option value="seller" <?php echo ($role == 'seller') ? 'selected' : ''; ?>>Seller</option>
                                        <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Generate New Token</button>
                        </form>
                        
                        <h5 class="mt-4">Generated JWT Token</h5>
                        <div class="alert alert-success">
                            <p>Copy this token and use it to update your localStorage:</p>
                        </div>
                        <pre id="token" class="bg-light p-3 rounded"><?php echo $token; ?></pre>
                        <button id="copy-btn" class="btn btn-success mb-3">Copy Token</button>
                        
                        <h5 class="mt-4">Update Your localStorage</h5>
                        <div class="alert alert-warning">
                            <p>Click the button below to automatically update your localStorage with this token:</p>
                        </div>
                        <button id="update-btn" class="btn btn-warning">Update localStorage</button>
                        
                        <div class="mt-4">
                            <a href="frontend/profile.html" class="btn btn-primary">Go to Profile Page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to copy token to clipboard
        document.getElementById('copy-btn').addEventListener('click', function() {
            const token = document.getElementById('token').textContent;
            navigator.clipboard.writeText(token).then(function() {
                alert('Token copied to clipboard!');
            }, function() {
                alert('Failed to copy token. Please select and copy manually.');
            });
        });
        
        // Function to update localStorage
        document.getElementById('update-btn').addEventListener('click', function() {
            const token = document.getElementById('token').textContent;
            const userId = document.getElementById('id').value;
            const username = document.getElementById('username').value;
            const role = document.getElementById('role').value;
            
            // Update localStorage
            localStorage.setItem('jwt_token', token);
            localStorage.setItem('user_id', userId);
            localStorage.setItem('username', username);
            localStorage.setItem('user_role', role);
            
            alert('localStorage updated with the new token! You can now go to your profile page.');
        });
    </script>
</body>
</html>
