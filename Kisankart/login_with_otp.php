<?php
session_start();
include 'conn.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $_SESSION['email']=$email;

    try {
        // Use PDO for database queries
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data && password_verify($password, $data['password'])) {
            $otp = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+3 minute"));
            $subject= "Your OTP for Login";
            $message="Your OTP is: $otp";

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'athmajand2003@gmail.com'; //host email
            $mail->Password = 'idpxklwuhirqtkfq'; // app password of your host email
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';
            $mail->isHTML(true);
            $mail->setFrom('athmajand2003@gmail.com', 'Two Factor Authentication System');//Sender's Email & Name
            $mail->addAddress($email); //Receiver's Email
            $mail->Subject = ("$subject");
            $mail->Body = $message;
            $mail->send();

            // Update OTP in database
            $update_stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
            $update_stmt->execute([$otp, $otp_expiry, $data['id']]);

            $_SESSION['temp_user'] = ['id' => $data['id'], 'otp' => $otp];
            header("Location: otp_verification.php");
            exit();
        } else {
            ?>
            <script>
               alert("Invalid Email or Password. Please try again.");
                    function navigateToPage() {
                        window.location.href = 'login_with_otp.php';
                    }
                    window.onload = function() {
                        navigateToPage();
                    }
            </script>
            <?php
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login with OTP Verification</title>
    <style type="text/css">
        #container{
            margin-left: 400px;
            border: 1px solid black;
            width: 440px;
            padding: 20px;
            margin-top: 40px;
        }
        input[type=text],input[type=password]{
            width: 300px;
            height: 20px;
            padding: 10px;
        }
        label{
            font-size: 20px;
            font-weight: bold;
        }
        form{
            margin-left: 50px;
        }
        a{
            text-decoration: none;
            font-weight: bold;
            font-size: 21px;
            color: blue;
        }
        a:hover{
            cursor: pointer;
            color: purple;
        }
        input[type=submit]{
            width: 70px;
            background-color: blue;
            border: 1px solid blue;
            color: white;
            font-weight: bold;
            padding: 7px;
            margin-left: 130px;
        }
        input[type=submit]:hover{
            background-color: purple;
            cursor: pointer;
            border: 1px solid purple;
        }
    </style>
</head>
<body>
    <div id="container">
        <h2 style="text-align: center;">Login with OTP Verification</h2>
        <form method="post" action="login_with_otp.php">
            <label for="email">Email</label><br>
            <input type="text" name="email" placeholder="Enter Your Email" required><br><br>
            <label for="password">Password:</label><br>
            <input type="password" name="password" placeholder="Enter Your Password" required><br><br>
            <input type="submit" name="login" value="Login"><br><br>
            <label>Don't have an account? </label><a href="registration.php">Sign Up</a>
        </form>
    </div>

</body>
</html>
