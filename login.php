<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $user_type = sanitize($_POST['user_type']);
    
    if (empty($email) || empty($password) || empty($user_type)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        try {
            $table = '';
            $redirect = '';
            
            switch ($user_type) {
                case 'patient':
                    $table = 'patients';
                    $redirect = 'patient_dashboard.php';
                    break;
                case 'doctor':
                    $table = 'doctors';
                    $redirect = 'doctor_dashboard.php';
                    break;
                case 'admin':
                    $table = 'admin';
                    $redirect = 'admin_dashboard.php';
                    break;
                default:
                    $error = 'Invalid user type.';
                    break;
            }
            
            if (empty($error)) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && verifyPassword($password, $user['password'])) {
                    $_SESSION[$user_type . '_id'] = $user['id'];
                    $_SESSION[$user_type . '_name'] = $user['name'] ?? $user['username'];
                    $_SESSION[$user_type . '_email'] = $user['email'];
                    
                    // Check if user has temporary password
                    if (isset($user['is_temp_password']) && $user['is_temp_password'] == 1) {
                        $_SESSION['must_change_password'] = true;
                        $_SESSION['user_type'] = $user_type;
                        redirectTo('change_password.php');
                    } else {
                        redirectTo($redirect);
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DocQ</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-heartbeat"></i>
                <span>DocQ</span>
            </div>
            <div class="nav-menu">
                <a href="indexx.php" class="nav-link">Home</a>
                <a href="patient_register.php" class="btn btn-outline">Register</a>
            </div>
        </div>
    </nav>

    <div style="padding-top: 100px; min-height: 100vh; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); display: flex; align-items: center;">
        <div class="container">
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas fa-sign-in-alt" style="color: #086e17; margin-right: 0.5rem;"></i>
                    Login to Your Account
                </h2>
                
               <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="user_type">Login As</label>
                        <select name="user_type" id="user_type" required>
                            <option value="">Select User Type</option>
                            <option value="patient" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'patient') ? 'selected' : ''; ?>>Patient</option>
                            <option value="doctor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                            <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="Enter your email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                    
                    <div class="form-group" style="text-align: center; margin-top: 1rem;">
                        <a href="forgot_password.php" style="color: #48bb78; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-key"></i> Forgot Password?
                        </a>
                    </div>
                </form>
                
                <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                    <p style="color: #055023; margin-bottom: 1rem;">Don't have an account?</p>
                    <a href="patient_register.php" class="btn btn-outline">Register as Patient</a>
                </div>
            </div>
            
           

    <script src="js/script.js"></script>
</body>
</html>