<?php
// Include configuration
require_once 'config.php';

// PHPMailer includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

function sendPasswordResetEmail($email, $name, $newPassword, $userType) {
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
        $mail->setFrom(env('SMTP_USERNAME', 'noreply@docq.com'), env('APP_NAME', 'DocQ Healthcare'));
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - DocQ Healthcare';
        
        // Determine user type display
        $userTypeDisplay = ucfirst($userType);
        $dashboardUrl = '';
        switch($userType) {
            case 'patient':
                $dashboardUrl = 'patient_dashboard.php';
                break;
            case 'doctor':
                $dashboardUrl = 'doctor_dashboard.php';
                break;
            case 'admin':
                $dashboardUrl = 'admin_dashboard.php';
                break;
        }
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f7fafc; padding: 20px;'>
            <div style='background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1);'>
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>
                        <i class='fas fa-key'></i> Password Reset
                    </h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>DocQ Healthcare</p>
                </div>
                
                <!-- Content -->
                <div style='padding: 30px;'>
                    <h2 style='color: #2c5aa0; margin-top: 0;'>Hello {$name},</h2>
                    
                    <p style='font-size: 16px; line-height: 1.6; color: #4a5568;'>
                        Your password has been successfully reset as requested. This is a <strong>temporary password</strong> that you must change after logging in for security purposes.
                    </p>
                    
                    <!-- Credentials Box -->
                    <div style='background-color: #edf2f7; border-left: 4px solid #48bb78; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0;'>
                        <h3 style='margin-top: 0; color: #2c5aa0;'>Temporary Login Credentials</h3>
                        <p style='margin: 10px 0;'><strong>User Type:</strong> {$userTypeDisplay}</p>
                        <p style='margin: 10px 0;'><strong>Email:</strong> {$email}</p>
                        <p style='margin: 10px 0;'><strong>Temporary Password:</strong> 
                            <span style='background: #c53030; color: white; padding: 8px 12px; border-radius: 4px; font-family: monospace; font-size: 18px; letter-spacing: 2px;'>
                                {$newPassword}
                            </span>
                        </p>
                        <p style='margin: 10px 0; color: #c53030; font-weight: bold;'>⚠️ You will be required to change this password after logging in.</p>
                    </div>
                    
                    <!-- Login Button -->
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . env('APP_URL', 'http://localhost:8000') . "/login.php' 
                           style='background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); 
                                  color: white; 
                                  text-decoration: none; 
                                  padding: 15px 30px; 
                                  border-radius: 8px; 
                                  font-weight: bold; 
                                  font-size: 16px;
                                  display: inline-block;'>
                            Login to Your Account
                        </a>
                    </div>
                    
                    <!-- Security Notice -->
                    <div style='background-color: #fff5f5; border: 1px solid #feb2b2; border-radius: 8px; padding: 15px; margin: 20px 0;'>
                        <h4 style='color: #c53030; margin-top: 0;'>🔒 Security Notice</h4>
                        <ul style='margin: 10px 0; padding-left: 20px; color: #742a2a;'>
                            <li><strong>You MUST change this password after logging in</strong></li>
                            <li>This is a temporary password for security purposes</li>
                            <li>Do not share your password with anyone</li>
                            <li>If you did not request this reset, contact us immediately</li>
                            <li>This password was generated randomly for your security</li>
                        </ul>
                    </div>
                    
                    <p style='font-size: 14px; color: #718096; line-height: 1.6;'>
                        If you have any questions or need assistance, please don't hesitate to contact our support team.
                    </p>
                    
                    <p style='margin-top: 30px; color: #4a5568;'>
                        Best regards,<br>
                        <strong>DocQ Healthcare Team</strong>
                    </p>
                </div>
                
                <!-- Footer -->
                <div style='background-color: #f7fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;'>
                    <p style='margin: 0; font-size: 14px; color: #718096;'>
                        This is an automated message. Please do not reply to this email.
                    </p>
                    <p style='margin: 5px 0 0 0; font-size: 12px; color: #a0aec0;'>
                        © 2025 DocQ Healthcare. All rights reserved.
                    </p>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Password reset email failed: " . $e->getMessage());
        return false;
    }
}
?>
