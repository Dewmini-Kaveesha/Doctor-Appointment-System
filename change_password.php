<?php
require_once 'config.php';

// Check if user must change password
if (!isset($_SESSION['must_change_password']) || !$_SESSION['must_change_password']) {
    redirectTo('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_SESSION['user_type'];
    $user_id = $_SESSION[$user_type . '_id'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif ($current_password === $new_password) {
        $error = 'New password must be different from the temporary password.';
    } else {
        // Determine table
        $table = '';
        switch ($user_type) {
            case 'patient':
                $table = 'patients';
                break;
            case 'doctor':
                $table = 'doctors';
                break;
            case 'admin':
                $table = 'admin';
                break;
        }
        
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM $table WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($current_password, $user['password'])) {
                // Update password and remove temporary flag
                $hashedNewPassword = hashPassword($new_password);
                $updateStmt = $pdo->prepare("UPDATE $table SET password = ?, is_temp_password = 0 WHERE id = ?");
                
                if ($updateStmt->execute([$hashedNewPassword, $user_id])) {
                    // Clear session flags and logout user
                    session_destroy();
                    
                    $success = 'Password changed successfully! You will be redirected to the login page. Please log in with your new password.';
                    
                    // Redirect to login after 3 seconds
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 3000);
                    </script>";
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - DocQ</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .change-password-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .change-password-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
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
        
        .warning-box {
            background-color: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .warning-box h4 {
            color: #c53030;
            margin-top: 0;
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
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
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
        
        .logout-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .logout-link a {
            color: #e53e3e;
            text-decoration: none;
            font-weight: 600;
        }
        
        .logout-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="change-password-card">
            <div class="card-header">
                <h2><i class="fas fa-lock"></i> Change Password Required</h2>
                <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">You must change your temporary password</p>
            </div>
            
            <div class="card-body">
                <div class="warning-box">
                    <h4>🔒 Security Notice</h4>
                    <p style="margin: 0; color: #742a2a;">
                        You are currently using a temporary password. For security reasons, you must create a new password before accessing your account.
                    </p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($success)): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password (Temporary):</label>
                        <input type="password" name="current_password" id="current_password" 
                               placeholder="Enter the temporary password from email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" name="new_password" id="new_password" 
                               placeholder="Enter your new password (min 6 characters)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" name="confirm_password" id="confirm_password" 
                               placeholder="Re-enter your new password" required>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="logout-link">
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
