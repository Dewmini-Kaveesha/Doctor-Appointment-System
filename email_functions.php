<?php
// Email functions for sending credentials to new users
require_once 'config.php'; // This will load env_loader.php
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendDoctorWelcomeEmail($doctorEmail, $doctorName, $tempPassword) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings from environment variables
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME', 'your_email@gmail.com');
        $mail->Password   = env('SMTP_PASSWORD', 'your_app_password');
        
        // Handle encryption type
        $encryption = env('SMTP_ENCRYPTION', 'tls');
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = env('SMTP_PORT', 465);
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = env('SMTP_PORT', 587);
        }

        // Recipients
        $mail->setFrom(env('SMTP_USERNAME', 'noreply@docq.com'), env('APP_NAME', 'DocQ Hospital System'));
        $mail->addAddress($doctorEmail, $doctorName);
        $mail->addReplyTo(env('SMTP_USERNAME', 'noreply@docq.com'), env('APP_NAME', 'DocQ') . ' Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to DocQ - Your Account Details';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c5aa0; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; }
                .credentials { background-color: #e8f4f8; padding: 20px; border-left: 4px solid #2c5aa0; margin: 20px 0; }
                .footer { background-color: #333; color: white; padding: 15px; text-align: center; }
                .button { background-color: #2c5aa0; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 Welcome to DocQ Hospital System</h1>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($doctorName) . ",</h2>
                    
                    <p>Welcome to DocQ! Your doctor account has been successfully created by our administrator.</p>
                    
                    <div class='credentials'>
                        <h3>🔐 Your Login Credentials:</h3>
                        <p><strong>Email:</strong> " . htmlspecialchars($doctorEmail) . "</p>
                        <p><strong>Temporary Password:</strong> <code>" . htmlspecialchars($tempPassword) . "</code></p>
                        <p><strong>Login URL:</strong> <a href='" . env('APP_URL', 'http://localhost:8000') . "/login.php'>" . env('APP_URL', 'http://localhost:8000') . "/login.php</a></p>
                    </div>
                    
                    <p><strong>⚠️ Security Notice:</strong></p>
                    <ul>
                        <li>Please login and change your password immediately</li>
                        <li>Go to Account Settings → Change Password after login</li>
                        <li>Keep your credentials secure and confidential</li>
                        <li>Do not share your login details with anyone</li>
                    </ul>
                    
                    <p>As a doctor on our platform, you can:</p>
                    <ul>
                        <li>✅ View and manage your appointments</li>
                        <li>✅ Update appointment statuses</li>
                        <li>✅ Add notes to patient visits</li>
                        <li>✅ View patient information</li>
                    </ul>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost:8000/login.php' class='button'>Login to Your Account</a>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact our support team.</p>
                    
                    <p>Best regards,<br>
                    <strong>DocQ Hospital System</strong><br>
                    Administration Team</p>
                </div>
                
                <div class='footer'>
                    <p>&copy; 2025 DocQ Hospital System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "
        Welcome to DocQ Hospital System!
        
        Hello $doctorName,
        
        Your doctor account has been created successfully.
        
        Login Credentials:
        Email: $doctorEmail
        Password: $tempPassword
        Login URL: http://localhost:8000/login.php
        
        Please login and change your password immediately for security.
        
        Best regards,
        DocQ Hospital System
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

function sendAdminWelcomeEmail($adminEmail, $adminUsername, $tempPassword) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings from environment variables
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME', 'your_email@gmail.com');
        $mail->Password   = env('SMTP_PASSWORD', 'your_app_password');
        
        // Handle encryption type
        $encryption = env('SMTP_ENCRYPTION', 'tls');
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = env('SMTP_PORT', 465);
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = env('SMTP_PORT', 587);
        }

        $mail->setFrom(env('SMTP_USERNAME', 'noreply@docq.com'), env('APP_NAME', 'DocQ Hospital System'));
        $mail->addAddress($adminEmail, $adminUsername);

        $mail->isHTML(true);
        $mail->Subject = 'DocQ Admin Account Created';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #dc2626; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; }
                .credentials { background-color: #fef2f2; padding: 20px; border-left: 4px solid #dc2626; margin: 20px 0; }
                .footer { background-color: #333; color: white; padding: 15px; text-align: center; }
                .button { background-color: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ DocQ Admin Access</h1>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($adminUsername) . ",</h2>
                    
                    <p>Welcome to DocQ! Your administrator account has been successfully created.</p>
                    
                    <div class='credentials'>
                        <h3>🔐 Admin Login Credentials:</h3>
                        <p><strong>Username:</strong> " . htmlspecialchars($adminUsername) . "</p>
                        <p><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</p>
                        <p><strong>Temporary Password:</strong> <code>" . htmlspecialchars($tempPassword) . "</code></p>
                        <p><strong>Admin Login URL:</strong> <a href='http://localhost:8000/login.php'>http://localhost:8000/login.php</a></p>
                    </div>
                    
                    <p><strong>⚠️ High Security Notice:</strong></p>
                    <ul>
                        <li>As an administrator, you have full system access</li>
                        <li>Please login and change your password immediately</li>
                        <li>Go to Account Settings → Change Password after login</li>
                        <li>Keep your credentials extremely secure and confidential</li>
                        <li>Never share your admin login details with anyone</li>
                    </ul>
                    
                    <p>As an administrator, you can:</p>
                    <ul>
                        <li>✅ Manage all doctor accounts</li>
                        <li>✅ Create new admin and doctor accounts</li>
                        <li>✅ View system statistics and reports</li>
                        <li>✅ Oversee all appointments and users</li>
                        <li>✅ Configure system settings</li>
                    </ul>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost:8000/login.php' class='button'>Login to Admin Panel</a>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact the system administrator.</p>
                    
                    <p>Best regards,<br>
                    <strong>DocQ Hospital System</strong><br>
                    Administration Team</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
