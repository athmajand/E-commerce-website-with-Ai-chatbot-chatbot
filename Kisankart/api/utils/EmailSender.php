<?php
/**
 * Email Sender Utility Class
 *
 * This class handles sending emails for various purposes like verification, password reset, etc.
 */
class EmailSender {
    // SMTP configuration
    private $host = 'smtp.gmail.com';
    private $username = 'athmajand2003@gmail.com'; // Sender email
    private $password = 'idpxklwuhirqtkfq'; // App password for Gmail
    private $port = 587;
    private $encryption = 'tls';

    // Sender information
    private $fromEmail = 'athmajand2003@gmail.com'; // Sender email
    private $fromName = 'Kisan Kart';

    // Base URL for links
    private $baseUrl = 'http://localhost:8080/Kisankart';

    /**
     * Constructor
     */
    public function __construct() {
        // You can override default settings here if needed
    }

    /**
     * Send verification email to a seller
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $token Verification token
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendSellerVerificationEmail($email, $name, $token) {
        $subject = 'Verify Your Seller Account - Kisan Kart';

        $verificationLink = $this->baseUrl . '/verify_seller.php?token=' . $token . '&email=' . urlencode($email);

        $message = "
        <html>
        <head>
            <title>Verify Your Seller Account</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                .header {
                    background-color: #4CAF50;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    padding: 20px;
                }
                .button {
                    display: inline-block;
                    background-color: #4CAF50;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 12px;
                    color: #777;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Kisan Kart - Seller Verification</h2>
                </div>
                <div class='content'>
                    <p>Dear $name,</p>
                    <p>Thank you for registering as a seller on Kisan Kart. Please click the button below to verify your email address and activate your seller account:</p>
                    <p style='text-align: center;'>
                        <a href='$verificationLink' class='button'>Verify Email Address</a>
                    </p>
                    <p>If the button doesn't work, you can also copy and paste the following link into your browser:</p>
                    <p>$verificationLink</p>
                    <p>This link will expire in 24 hours.</p>
                    <p>If you did not register for a seller account on Kisan Kart, please ignore this email.</p>
                    <p>Best regards,<br>The Kisan Kart Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>&copy; " . date('Y') . " Kisan Kart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->sendEmail($email, $name, $subject, $message);
    }

    /**
     * Send password reset email
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $token Reset token
     * @param string $role User role (seller or customer)
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendPasswordResetEmail($email, $name, $token, $role = 'seller') {
        $subject = 'Reset Your Password - Kisan Kart';

        $resetLink = $this->baseUrl . '/reset_password.php?token=' . $token . '&email=' . urlencode($email) . '&role=' . $role;

        $message = "
        <html>
        <head>
            <title>Reset Your Password</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                .header {
                    background-color: #4CAF50;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    padding: 20px;
                }
                .button {
                    display: inline-block;
                    background-color: #4CAF50;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 12px;
                    color: #777;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Kisan Kart - Password Reset</h2>
                </div>
                <div class='content'>
                    <p>Dear $name,</p>
                    <p>We received a request to reset your password. Please click the button below to reset your password:</p>
                    <p style='text-align: center;'>
                        <a href='$resetLink' class='button'>Reset Password</a>
                    </p>
                    <p>If the button doesn't work, you can also copy and paste the following link into your browser:</p>
                    <p>$resetLink</p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                    <p>Best regards,<br>The Kisan Kart Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>&copy; " . date('Y') . " Kisan Kart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->sendEmail($email, $name, $subject, $message);
    }

    /**
     * Send a generic email
     *
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $message Email message (HTML)
     * @return bool True if email sent successfully, false otherwise
     */
    private function sendEmail($toEmail, $toName, $subject, $message) {
        // For testing purposes, we'll just log the email
        error_log("Email would be sent to: $toName <$toEmail>");
        error_log("Subject: $subject");
        error_log("Message: " . substr($message, 0, 100) . "...");

        // In a real implementation, you would use PHPMailer or similar library
        // For now, we'll just return true to simulate successful sending
        return true;

        /*
        // Example implementation with PHPMailer
        require 'vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = $this->encryption;
            $mail->Port = $this->port;

            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
        */
    }
}
?>
