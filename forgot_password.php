<?php
require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $user_type = sanitize($_POST['user_type']);
    
    if (!empty($email) && !empty($user_type)) {
        // Check if email exists in the selected user type table
        $table = '';
        $name_field = '';
        
        switch ($user_type) {
            case 'patient':
                $table = 'patients';
                $name_field = 'name';
                break;
            case 'doctor':
                $table = 'doctors';
                $name_field = 'name';
                break;
            case 'admin':
                $table = 'admin';
                $name_field = 'username';
                break;
            default:
                $message = 'Invalid user type selected.';
                $message_type = 'error';
                break;
        }
        
        if (empty($message)) {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id, $name_field as name, email FROM $table WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate new password 
                    $newPassword = generateRandomPassword(10);
                    $hashedPassword = hashPassword($newPassword);
                    
                    // Update password and mark as temporary
                    $updateStmt = $pdo->prepare("UPDATE $table SET password = ?, is_temp_password = 1 WHERE email = ?");
                    
                    if ($updateStmt->execute([$hashedPassword, $email])) {
                        // Send email with new password
                        require_once 'send_password_reset_email.php';
                        
                        if (sendPasswordResetEmail($email, $user['name'], $newPassword, $user_type)) {
                            $message = 'A new password has been sent to your email address. Please check your inbox and change your password after logging in.';
                            $message_type = 'success';
                        } else {
                            $message = 'Password was reset but email could not be sent. Please contact administrator.';
                            $message_type = 'warning';
                        }
                    } else {
                        $message = 'Failed to reset password. Please try again.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'No account found with this email address for the selected user type.';
                    $message_type = 'error';
                }
            } catch (PDOException $e) {
                $message = 'An error occurred. Please try again later.';
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - DocQ</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .forgot-password-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .forgot-password-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #48bb78;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }
        
        .alert-warning {
            background: #fefcbf;
            color: #744210;
            border: 1px solid #f6e05e;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: #48bb78;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <div class="card-header">
                <h2><i class="fas fa-key"></i> Forgot Password</h2>
                <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Enter your email to reset your password</p>
            </div>
            
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="user_type">I am a:</label>
                        <select name="user_type" id="user_type" required>
                            <option value="">Select your role</option>
                            <option value="patient" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'patient') ? 'selected' : ''; ?>>Patient</option>
                            <option value="doctor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                            <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" name="email" id="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               placeholder="Enter your registered email address" required>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Send New Password
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
