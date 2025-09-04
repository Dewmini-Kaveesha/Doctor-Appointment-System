<?php
// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database configuration from environment variables
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
define('DB_NAME', env('DB_NAME', 'doctor_appointment_system'));

// Application configuration
define('APP_NAME', env('APP_NAME', 'Doctor Appointment System'));
define('APP_URL', env('APP_URL', 'http://localhost:8000'));
define('APP_DEBUG', env('APP_DEBUG', false));

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Helper functions
function isLoggedIn($userType) {
    return isset($_SESSION[$userType . '_id']);
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Check if email exists across all user tables (global uniqueness)
function isEmailTaken($email, $pdo) {
    $tables = [
        'admin' => 'username as name',
        'doctors' => 'name',
        'patients' => 'name'
    ];
    
    foreach ($tables as $table => $nameField) {
        $stmt = $pdo->prepare("SELECT id, $nameField FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                'exists' => true,
                'role' => ($table === 'admin') ? 'admin' : substr($table, 0, -1), // Remove 's' from 'doctors', 'patients'
                'name' => $result['name'],
                'table' => $table
            ];
        }
    }
    
    return ['exists' => false];
}

// Email Configuration for SMTP
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'your_email@gmail.com'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', 'your_app_password'));
define('SMTP_FROM_EMAIL', env('SMTP_USERNAME', 'your_email@gmail.com'));
define('SMTP_FROM_NAME', env('APP_NAME', 'DocQ Healthcare'));

// Password Generation Function (Method 2 - Character Pool)
function generateRandomPassword($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    $maxIndex = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $randomIndex = random_int(0, $maxIndex);
        $password .= $characters[$randomIndex];
    }
    
    return $password;
}
?>