<?php
// Include database configuration
include_once __DIR__ . '/api/config/database.php';
include_once __DIR__ . '/api/models/SellerRegistration.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Function to check if email exists
function checkEmailExists($db, $email) {
    $seller = new SellerRegistration($db);
    $seller->email = $email;
    return $seller->emailExists();
}

// Function to check if phone exists
function checkPhoneExists($db, $phone) {
    $seller = new SellerRegistration($db);
    $seller->phone = $phone;
    return $seller->phoneExists();
}

// Test email and phone
$test_email = isset($_POST['email']) ? $_POST['email'] : '';
$test_phone = isset($_POST['phone']) ? $_POST['phone'] : '';

$email_exists = false;
$phone_exists = false;
$email_records = [];
$phone_records = [];

if (!empty($test_email)) {
    $email_exists = checkEmailExists($db, $test_email);
    
    // Get records with this email
    $query = "SELECT id, first_name, last_name, email, phone FROM seller_registrations WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $test_email);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $email_records[] = $row;
    }
}

if (!empty($test_phone)) {
    $phone_exists = checkPhoneExists($db, $test_phone);
    
    // Get records with this phone
    $query = "SELECT id, first_name, last_name, email, phone FROM seller_registrations WHERE phone = :phone";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':phone', $test_phone);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $phone_records[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Seller Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #4CAF50;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Seller Registration</h1>
        <p>Use this tool to check if an email or phone number already exists in the seller_registrations table.</p>
        
        <form method="POST" action="debug_seller_registration.php">
            <div class="form-group">
                <label for="email">Email to check:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($test_email); ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone to check:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($test_phone); ?>">
            </div>
            
            <button type="submit">Check</button>
        </form>
        
        <?php if (!empty($test_email) || !empty($test_phone)): ?>
            <div class="result">
                <h2>Results:</h2>
                
                <?php if (!empty($test_email)): ?>
                    <p><strong>Email (<?php echo htmlspecialchars($test_email); ?>):</strong> 
                        <?php echo $email_exists ? 'EXISTS in the database' : 'Does NOT exist in the database'; ?></p>
                    
                    <?php if (!empty($email_records)): ?>
                        <h3>Records with this email:</h3>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                            </tr>
                            <?php foreach ($email_records as $record): ?>
                                <tr>
                                    <td><?php echo $record['id']; ?></td>
                                    <td><?php echo $record['first_name']; ?></td>
                                    <td><?php echo $record['last_name']; ?></td>
                                    <td><?php echo $record['email']; ?></td>
                                    <td><?php echo $record['phone']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($test_phone)): ?>
                    <p><strong>Phone (<?php echo htmlspecialchars($test_phone); ?>):</strong> 
                        <?php echo $phone_exists ? 'EXISTS in the database' : 'Does NOT exist in the database'; ?></p>
                    
                    <?php if (!empty($phone_records)): ?>
                        <h3>Records with this phone:</h3>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                            </tr>
                            <?php foreach ($phone_records as $record): ?>
                                <tr>
                                    <td><?php echo $record['id']; ?></td>
                                    <td><?php echo $record['first_name']; ?></td>
                                    <td><?php echo $record['last_name']; ?></td>
                                    <td><?php echo $record['email']; ?></td>
                                    <td><?php echo $record['phone']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
